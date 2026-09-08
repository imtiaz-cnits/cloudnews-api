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
        $this->assertMatchesRegularExpression('/^\d{3}-\d{3}-\d{3}$/', $meetingCode);

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

        // 4. Correct passcode
        $this->postJson('/api/v1/meetings/validate', [
            'meeting_code' => $meeting->meeting_code,
            'passcode' => 'CorrectPasscode',
        ])->assertStatus(200)
            ->assertJsonPath('data.valid', true);

        // 5. Inactive meeting
        $meeting->update(['is_active' => false]);
        $this->postJson('/api/v1/meetings/validate', [
            'meeting_code' => $meeting->meeting_code,
            'passcode' => 'CorrectPasscode',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'This meeting has already ended');

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
}
