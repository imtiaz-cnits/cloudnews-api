<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MeetingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Prevent external network calls during tests
        Http::fake([
            '*/twirp/*' => Http::response(['success' => true], 200),
        ]);
    }

    public function test_authenticated_user_can_create_meeting(): void
    {
        $host = User::factory()->create(['name' => 'Host Engineer']);

        $response = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => 'Daily Architecture Sync',
            'passcode' => 'Pass@9921',
            'max_participants' => 15,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Meeting created successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'meeting' => [
                        'id',
                        'title',
                        'meeting_code',
                        'room_name',
                        'passcode',
                        'is_active',
                        'is_locked',
                        'max_participants',
                        'host' => ['id', 'name'],
                    ],
                    'token',
                    'livekit_url',
                ],
            ]);

        $meetingCode = $response->json('data.meeting.meeting_code');
        $this->assertMatchesRegularExpression('/^\d{3}-\d{3}$/', $meetingCode);

        // Verify encrypted at rest in database
        $rawDb = DB::table('meetings')->where('meeting_code', $meetingCode)->first();
        $this->assertNotEquals('Pass@9921', $rawDb->passcode);

        // Verify model decrypts it transparently
        $meeting = Meeting::where('meeting_code', $meetingCode)->first();
        $this->assertEquals('Pass@9921', $meeting->passcode);

        // Verify host is tracked in participants table
        $this->assertDatabaseHas('meeting_participants', [
            'meeting_id' => $meeting->id,
            'user_id' => $host->id,
            'role' => 'host',
        ]);
    }

    public function test_meeting_details_show_and_privacy(): void
    {
        $host = User::factory()->create();
        $participant = User::factory()->create();

        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'passcode' => 'SecretPin123',
        ]);

        // Host requests details -> should see passcode
        $hostResponse = $this->actingAs($host)->getJson("/api/v1/meetings/{$meeting->meeting_code}");
        $hostResponse->assertStatus(200)
            ->assertJsonPath('data.passcode', 'SecretPin123')
            ->assertJsonPath('data.requires_passcode', true);

        // Non-host requests details -> should NOT see plain passcode
        $guestResponse = $this->actingAs($participant)->getJson("/api/v1/meetings/{$meeting->meeting_code}");
        $guestResponse->assertStatus(200)
            ->assertJsonPath('data.requires_passcode', true)
            ->assertJsonMissing(['passcode' => 'SecretPin123']);
    }

    public function test_validate_meeting_endpoint_flow(): void
    {
        $host = User::factory()->create();

        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'passcode' => 'CorrectPasscode',
            'is_active' => true,
            'is_locked' => false,
        ]);

        // 1. Non-existent code
        $this->postJson('/api/v1/meetings/validate', [
            'meeting_code' => '000-000-000',
        ])->assertStatus(404);

        // 2. Missing passcode when required
        $this->postJson('/api/v1/meetings/validate', [
            'meeting_code' => $meeting->meeting_code,
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Passcode is required to join this meeting');

        // 3. Incorrect passcode
        $this->postJson('/api/v1/meetings/validate', [
            'meeting_code' => $meeting->meeting_code,
            'passcode' => 'WrongPin',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Invalid meeting passcode');

        // 4. Correct passcode (using 'code' parameter as mobile app sends)
        $this->postJson('/api/v1/meetings/validate', [
            'code' => $meeting->meeting_code,
            'passcode' => 'CorrectPasscode',
        ])->assertStatus(200)
            ->assertJsonPath('data.valid', true);

        // 5. Inactive meeting is reactivated and allowed (persistent meetings)
        $meeting->update(['is_active' => false]);
        $this->postJson('/api/meetings/validate', [
            'code' => str_replace('-', '', $meeting->meeting_code),
            'passcode' => 'CorrectPasscode',
        ])->assertStatus(200)
            ->assertJsonPath('data.valid', true);

        // 6. Locked meeting
        $meeting->update(['is_active' => true, 'is_locked' => true]);
        $this->postJson('/api/v1/meetings/validate', [
            'meeting_code' => $meeting->meeting_code,
            'passcode' => 'CorrectPasscode',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'This meeting is currently locked by the host');
    }

    public function test_participant_can_join_meeting_and_receive_token(): void
    {
        $host = User::factory()->create();
        $guest = User::factory()->guest()->create(['name' => 'Guest Joiner']);

        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'passcode' => 'JoinCode456',
        ]);

        $response = $this->actingAs($guest)->postJson("/api/v1/meetings/{$meeting->meeting_code}/join", [
            'passcode' => 'JoinCode456',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Joined meeting successfully',
                'data' => [
                    'meeting_code' => $meeting->meeting_code,
                    'identity' => "guest_{$guest->id}",
                    'role' => 'participant',
                ],
            ])
            ->assertJsonStructure(['data' => ['token', 'livekit_url']]);

        $this->assertDatabaseHas('meeting_participants', [
            'meeting_id' => $meeting->id,
            'user_id' => $guest->id,
            'role' => 'participant',
            'left_at' => null,
        ]);
    }

    public function test_join_fails_with_invalid_passcode(): void
    {
        $host = User::factory()->create();
        $user = User::factory()->create();

        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'passcode' => 'CorrectCode',
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/meetings/{$meeting->meeting_code}/join", [
            'passcode' => 'WrongCode',
        ]);

        $response->assertStatus(403);
    }

    public function test_join_fails_when_room_reaches_capacity(): void
    {
        $host = User::factory()->create();
        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'max_participants' => 2,
        ]);

        // Occupy 2 active slots
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        MeetingParticipant::create(['meeting_id' => $meeting->id, 'user_id' => $u1->id, 'role' => 'participant', 'joined_at' => now()]);
        MeetingParticipant::create(['meeting_id' => $meeting->id, 'user_id' => $u2->id, 'role' => 'participant', 'joined_at' => now()]);

        // 3rd user attempts to join
        $u3 = User::factory()->create();
        $response = $this->actingAs($u3)->postJson("/api/v1/meetings/{$meeting->meeting_code}/join");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Meeting has reached maximum participant limit');
    }

    public function test_host_can_end_meeting(): void
    {
        $host = User::factory()->create();
        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'is_active' => true,
        ]);

        $participant = User::factory()->create();
        MeetingParticipant::create([
            'meeting_id' => $meeting->id,
            'user_id' => $participant->id,
            'role' => 'participant',
            'joined_at' => now(),
            'left_at' => null,
        ]);

        $response = $this->actingAs($host)->postJson("/api/v1/meetings/{$meeting->meeting_code}/end");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Meeting ended successfully',
            ]);

        $fresh = $meeting->fresh();
        $this->assertFalse($fresh->is_active);
        $this->assertNotNull($fresh->ended_at);

        // Assert all participants marked left
        $this->assertDatabaseMissing('meeting_participants', [
            'meeting_id' => $meeting->id,
            'left_at' => null,
        ]);
    }

    public function test_non_host_cannot_end_meeting(): void
    {
        $host = User::factory()->create();
        $meeting = Meeting::factory()->create(['host_id' => $host->id]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->postJson("/api/v1/meetings/{$meeting->meeting_code}/end");

        $response->assertStatus(403);
    }

    public function test_participant_can_leave_meeting(): void
    {
        $host = User::factory()->create();
        $participant = User::factory()->create();

        $meeting = Meeting::factory()->create(['host_id' => $host->id]);

        MeetingParticipant::create([
            'meeting_id' => $meeting->id,
            'user_id' => $participant->id,
            'role' => 'participant',
            'joined_at' => now(),
            'left_at' => null,
        ]);

        $response = $this->actingAs($participant)->postJson("/api/v1/meetings/{$meeting->meeting_code}/leave");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Left meeting successfully',
            ]);

        $this->assertDatabaseMissing('meeting_participants', [
            'meeting_id' => $meeting->id,
            'user_id' => $participant->id,
            'left_at' => null,
        ]);
    }

    public function test_host_leaving_meeting_auto_ends_meeting_for_everyone(): void
    {
        $host = User::factory()->create();
        $participant = User::factory()->create();

        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'is_active' => true,
            'ended_at' => null,
        ]);

        MeetingParticipant::create([
            'meeting_id' => $meeting->id,
            'user_id' => $host->id,
            'role' => 'host',
            'joined_at' => now(),
            'left_at' => null,
        ]);

        MeetingParticipant::create([
            'meeting_id' => $meeting->id,
            'user_id' => $participant->id,
            'role' => 'participant',
            'joined_at' => now(),
            'left_at' => null,
        ]);

        $response = $this->actingAs($host)->postJson("/api/v1/meetings/{$meeting->meeting_code}/leave");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'meeting_ended' => true,
                ],
            ]);

        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseMissing('meeting_participants', [
            'meeting_id' => $meeting->id,
            'left_at' => null,
        ]);
    }

    public function test_user_can_schedule_meeting(): void
    {
        $host = User::factory()->create();

        $response = $this->actingAs($host)->postJson('/api/v1/meetings/schedule', [
            'title' => 'Design Sprint Kickoff',
            'scheduled_at' => '2026-10-01 10:00:00',
            'passcode' => '555666',
            'max_participants' => 20,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Meeting scheduled successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'meeting' => [
                        'id',
                        'title',
                        'meeting_code',
                        'room_name',
                        'scheduled_at',
                        'passcode',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('meetings', [
            'title' => 'Design Sprint Kickoff',
            'host_id' => $host->id,
        ]);
    }

    public function test_user_can_schedule_meeting_with_custom_options_and_verify_them(): void
    {
        $host = User::factory()->create();

        // 1. Schedule with custom passcode and waiting room enabled
        $response = $this->actingAs($host)->postJson('/api/v1/meetings/schedule', [
            'title' => 'Quarterly Product Review',
            'scheduled_at' => '2026-11-20 15:00:00',
            'passcode' => '849201',
            'waiting_room' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'meeting' => [
                        'title' => 'Quarterly Product Review',
                        'passcode' => '849201',
                        'requires_passcode' => true,
                        'waiting_room' => true,
                    ],
                ],
            ]);

        $code = $response->json('data.meeting.meeting_code');

        $dbMeeting = Meeting::where('meeting_code', $code)->first();
        $this->assertNotNull($dbMeeting);
        $this->assertEquals('849201', $dbMeeting->passcode);
        $this->assertTrue($dbMeeting->waiting_room);
        $this->assertDatabaseHas('meetings', [
            'meeting_code' => $code,
            'waiting_room' => true,
        ]);

        // 2. Validate endpoint should signal passcode is required
        $validateMissing = $this->postJson('/api/v1/meetings/validate', [
            'code' => $code,
        ]);
        $validateMissing->assertStatus(422)
            ->assertJson([
                'success' => false,
                'errors' => [
                    'requires_passcode' => true,
                ],
            ]);

        // 3. Validate with correct passcode should succeed
        $validateCorrect = $this->postJson('/api/v1/meetings/validate', [
            'code' => $code,
            'passcode' => '849201',
        ]);
        $validateCorrect->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'valid' => true,
                    'requires_passcode' => true,
                ],
            ]);

        // 4. Schedule another meeting with passcode OFF and waiting room OFF
        $responseOpen = $this->actingAs($host)->postJson('/api/v1/meetings/schedule', [
            'title' => 'Open Town Hall',
            'scheduled_at' => '2026-11-21 11:00:00',
            'passcode' => null,
            'waiting_room' => false,
        ]);

        $responseOpen->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'meeting' => [
                        'title' => 'Open Town Hall',
                        'requires_passcode' => false,
                        'waiting_room' => false,
                    ],
                ],
            ]);

        // 5. Host scheduled meetings list should accurately return both
        $scheduledList = $this->actingAs($host)->getJson('/api/v1/meetings/scheduled');
        $scheduledList->assertStatus(200)
            ->assertJsonFragment([
                'title' => 'Quarterly Product Review',
                'requires_passcode' => true,
                'passcode' => '849201',
                'waiting_room' => true,
            ])
            ->assertJsonFragment([
                'title' => 'Open Town Hall',
                'requires_passcode' => false,
                'waiting_room' => false,
            ]);
    }

    public function test_user_can_get_scheduled_meetings(): void
    {
        $host = User::factory()->create();

        // Create a scheduled meeting for this host
        $scheduledMeeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'title' => 'Sprint Planning',
            'scheduled_at' => now()->addDays(2),
            'is_active' => true,
            'ended_at' => null,
        ]);

        // Create an ended meeting (should not be returned)
        Meeting::factory()->create([
            'host_id' => $host->id,
            'title' => 'Past Meeting',
            'scheduled_at' => now()->subDays(2),
            'is_active' => false,
            'ended_at' => now()->subDays(2),
        ]);

        // Test with v1 prefix
        $responseV1 = $this->actingAs($host)->getJson('/api/v1/meetings/scheduled');
        $responseV1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Scheduled meetings retrieved successfully',
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Sprint Planning');

        // Test direct route (/api/meetings/scheduled)
        $responseDirect = $this->actingAs($host)->getJson('/api/meetings/scheduled');
        $responseDirect->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(1, 'data');
    }

    public function test_host_can_delete_meeting(): void
    {
        $host = User::factory()->create();

        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'title' => 'Meeting To Be Deleted',
            'meeting_code' => '999-888-777',
        ]);

        $response = $this->actingAs($host)->deleteJson("/api/v1/meetings/{$meeting->meeting_code}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Meeting deleted successfully',
            ]);

        $this->assertDatabaseMissing('meetings', [
            'id' => $meeting->id,
        ]);
    }

    public function test_non_host_cannot_delete_meeting(): void
    {
        $host = User::factory()->create();
        $otherUser = User::factory()->create();

        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'title' => 'Protected Meeting',
        ]);

        $response = $this->actingAs($otherUser)->deleteJson("/api/v1/meetings/{$meeting->meeting_code}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Only the host can delete this meeting',
            ]);

        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
        ]);
    }

    public function test_scheduled_meetings_contain_dynamic_status(): void
    {
        $host = User::factory()->create();

        // 1. Upcoming meeting (future)
        Meeting::factory()->create([
            'host_id' => $host->id,
            'title' => 'Future Meeting',
            'scheduled_at' => now()->addHours(5),
            'is_active' => true,
            'ended_at' => null,
            'started_at' => null,
        ]);

        // 2. Ongoing meeting (within -15 to +120 mins)
        Meeting::factory()->create([
            'host_id' => $host->id,
            'title' => 'Current Ongoing Meeting',
            'scheduled_at' => now()->subMinutes(10),
            'is_active' => true,
            'ended_at' => null,
            'started_at' => null,
        ]);

        $response = $this->actingAs($host)->getJson('/api/v1/meetings/scheduled');

        $response->assertStatus(200);
        $data = collect($response->json('data'));

        $future = $data->firstWhere('title', 'Future Meeting');
        $this->assertEquals('upcoming', $future['status']);
        $this->assertTrue($future['is_host']);
        $this->assertArrayHasKey('waiting_room', $future);

        $ongoing = $data->firstWhere('title', 'Current Ongoing Meeting');
        $this->assertEquals('ongoing', $ongoing['status']);
    }

    public function test_user_can_join_meeting_via_cleaned_code_or_url(): void
    {
        $host = User::factory()->create();
        $guest = User::factory()->guest()->create();

        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'meeting_code' => '801-981-285',
            'room_name' => 'cloudnews-testroom123',
            'is_active' => true,
        ]);

        // 1. Join using clean digits without dashes (801981285)
        $cleanCode = '801981285';
        $responseClean = $this->actingAs($guest)->postJson("/api/v1/meetings/{$cleanCode}/join");
        $responseClean->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'meeting_code' => '801-981-285',
                    'room_name' => 'cloudnews-testroom123',
                ],
            ])
            ->assertJsonStructure([
                'data' => ['token', 'livekit_token', 'room_name', 'meeting_code'],
            ]);

        // 2. Join via direct route using URL-like code path
        $responseDirect = $this->actingAs($guest)->postJson("/api/meetings/{$meeting->meeting_code}/join");
        $responseDirect->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // 3. Join by internal room name
        $responseRoomName = $this->actingAs($guest)->postJson("/api/v1/meetings/{$meeting->room_name}/join");
        $responseRoomName->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_host_can_start_and_restart_personal_meeting_room(): void
    {
        $host = User::factory()->create(['name' => 'Dr. Host']);
        $pmiCode = '882-149';

        // 1. First time starting personal room with 6-digit code
        $res1 = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => "Dr. Host's Personal Room",
            'meeting_code' => $pmiCode,
        ]);

        $res1->assertStatus(201)
            ->assertJsonPath('data.meeting_code', $pmiCode)
            ->assertJsonPath('data.is_host', true);

        // 2. Host ends the meeting
        $resEnd = $this->actingAs($host)->postJson("/api/v1/meetings/{$pmiCode}/end");
        $resEnd->assertStatus(200);

        $dbMeeting = Meeting::where('meeting_code', $pmiCode)->first();
        $this->assertFalse($dbMeeting->is_active);

        // 3. Host starts personal room again -> should reactivate same meeting and code
        $res2 = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => "Dr. Host's Personal Room",
            'meeting_code' => $pmiCode,
        ]);

        $res2->assertStatus(201)
            ->assertJsonPath('data.meeting_code', $pmiCode)
            ->assertJsonPath('data.meeting.id', $dbMeeting->id);

        $dbMeeting->refresh();
        $this->assertTrue($dbMeeting->is_active);
        $this->assertNull($dbMeeting->ended_at);
    }

    public function test_web_room_deep_link_gateway(): void
    {
        $meeting = Meeting::factory()->create([
            'meeting_code' => '801-285',
            'title' => 'Important Architecture Meet',
        ]);

        $response = $this->get('/room/801-285');
        $response->assertStatus(200);
        $response->assertSee('801-285');
        $response->assertSee('Important Architecture Meet');
        $response->assertSee('cloudnews://room/801-285');
        $response->assertSee('com.cloudnews.mobile');
    }

    public function test_host_cannot_see_other_hosts_scheduled_or_ongoing_meetings(): void
    {
        $hostA = User::factory()->create(['name' => 'Host Alpha', 'role' => 'host']);
        $hostB = User::factory()->create(['name' => 'Host Beta', 'role' => 'host']);

        // Host A creates an ongoing meeting
        $ongoingA = Meeting::factory()->create([
            'host_id' => $hostA->id,
            'title' => "Alpha's Ongoing Meeting",
            'is_active' => true,
            'ended_at' => null,
            'scheduled_at' => now()->subMinutes(10),
        ]);

        // Host A creates a scheduled future meeting
        $scheduledA = Meeting::factory()->create([
            'host_id' => $hostA->id,
            'title' => "Alpha's Future Meeting",
            'is_active' => true,
            'ended_at' => null,
            'scheduled_at' => now()->addHours(3),
        ]);

        // Host B joins Host A's meeting as a participant
        MeetingParticipant::create([
            'meeting_id' => $ongoingA->id,
            'user_id' => $hostB->id,
            'role' => 'participant',
            'joined_at' => now(),
        ]);

        // Host B checks scheduled/ongoing meetings -> MUST NOT see Host A's meetings
        $responseB = $this->actingAs($hostB)->getJson('/api/v1/meetings/scheduled');
        $responseB->assertStatus(200);
        $dataB = collect($responseB->json('data'));
        $this->assertEmpty($dataB, 'Host B should not see any of Host A meetings');

        // Host A checks -> MUST see both meetings
        $responseA = $this->actingAs($hostA)->getJson('/api/v1/meetings/scheduled');
        $responseA->assertStatus(200);
        $dataA = collect($responseA->json('data'));
        $this->assertCount(2, $dataA);
        $this->assertTrue($dataA->contains('title', "Alpha's Ongoing Meeting"));
        $this->assertTrue($dataA->contains('title', "Alpha's Future Meeting"));
    }

    public function test_host_cannot_manage_another_hosts_meeting(): void
    {
        $hostA = User::factory()->create(['name' => 'Host Alpha', 'role' => 'host']);
        $hostB = User::factory()->create(['name' => 'Host Beta', 'role' => 'host']);

        $meetingA = Meeting::factory()->create([
            'host_id' => $hostA->id,
            'title' => "Alpha's Secure Meeting",
            'meeting_code' => '999-111',
            'is_active' => true,
        ]);

        // Host B tries to end Host A's meeting -> 403 Forbidden
        $resEnd = $this->actingAs($hostB)->postJson("/api/v1/meetings/{$meetingA->meeting_code}/end");
        $resEnd->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Only the host can end this meeting',
            ]);

        // Host B tries to delete Host A's meeting -> 403 Forbidden
        $resDelete = $this->actingAs($hostB)->deleteJson("/api/v1/meetings/{$meetingA->meeting_code}");
        $resDelete->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Only the host can delete this meeting',
            ]);

        // Meeting is still active and intact
        $meetingA->refresh();
        $this->assertTrue($meetingA->is_active);
    }
}


