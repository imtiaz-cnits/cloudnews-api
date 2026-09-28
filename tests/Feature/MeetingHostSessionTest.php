<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\MeetingHostSession;
use App\Models\MeetingParticipant;
use App\Models\User;
use App\Services\HostSessionService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class MeetingHostSessionTest extends TestCase
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

    public function test_first_host_create_succeeds_and_returns_session_token(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $response = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => 'Architecture Strategy',
            'max_participants' => 10,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_host' => true,
                ],
            ]);

        $sessionToken = $response->json('data.host_session_token');
        $this->assertNotEmpty($sessionToken);
        $this->assertEquals(64, strlen($sessionToken));

        $this->assertDatabaseHas('meeting_host_sessions', [
            'user_id' => $host->id,
            'session_token' => $sessionToken,
        ]);
    }

    public function test_same_user_attempting_host_join_to_another_meeting_is_rejected(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        // Host creates meeting 1
        $create1 = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => 'Meeting 1',
        ]);
        $create1->assertStatus(201);
        $code1 = $create1->json('data.meeting_code');

        // Another user creates meeting 2 (with $host as host)
        $meeting2 = Meeting::create([
            'host_id' => $host->id,
            'room_name' => 'room-meeting-2',
            'meeting_code' => '888-999',
            'title' => 'Meeting 2',
            'is_active' => true,
            'is_locked' => false,
            'max_participants' => 10,
        ]);

        // Host attempts to join meeting 2 as host while meeting 1 is active
        $response = $this->actingAs($host)->postJson("/api/v1/meetings/{$meeting2->meeting_code}/join");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'code' => 'HOST_ALREADY_IN_MEETING',
            ]);

        // Ensure NO LiveKit token was issued in the rejected response
        $this->assertNull($response->json('data.token'));
        $this->assertNull($response->json('data.livekit_token'));

        // Ensure only meeting 1 host session exists in DB
        $this->assertEquals(1, MeetingHostSession::where('user_id', $host->id)->count());
        $this->assertEquals($code1, MeetingHostSession::where('user_id', $host->id)->value('meeting_code'));
    }

    public function test_same_user_retrying_host_join_with_valid_session_token_is_idempotent(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $createResponse = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => 'Daily Standup',
        ]);
        $createResponse->assertStatus(201);
        $meetingCode = $createResponse->json('data.meeting_code');
        $sessionToken = $createResponse->json('data.host_session_token');

        // Same host reconnects to same meeting presenting the valid host_session_token
        $joinResponse = $this->actingAs($host)->postJson("/api/v1/meetings/{$meetingCode}/join", [
            'host_session_token' => $sessionToken,
        ]);

        $joinResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_host' => true,
                ],
            ]);

        $this->assertEquals($sessionToken, $joinResponse->json('data.host_session_token'));
        $this->assertEquals(1, MeetingHostSession::where('user_id', $host->id)->count());
    }

    public function test_same_user_attempting_host_join_to_same_meeting_without_or_with_wrong_token_is_rejected(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $createResponse = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => 'Daily Standup',
        ]);
        $createResponse->assertStatus(201);
        $meetingCode = $createResponse->json('data.meeting_code');
        $validToken = $createResponse->json('data.host_session_token');

        // Device B (same account) tries to join same meeting with NO token
        $noTokenResponse = $this->actingAs($host)->postJson("/api/v1/meetings/{$meetingCode}/join");
        $noTokenResponse->assertStatus(409)
            ->assertJson([
                'success' => false,
                'code' => 'HOST_ALREADY_IN_MEETING',
            ]);
        // Ensure valid token is NEVER leaked
        $this->assertNotEquals($validToken, $noTokenResponse->json('data.host_session_token'));

        // Device B tries with wrong token
        $wrongTokenResponse = $this->actingAs($host)->postJson("/api/v1/meetings/{$meetingCode}/join", [
            'host_session_token' => Str::random(64),
        ]);
        $wrongTokenResponse->assertStatus(409)
            ->assertJson([
                'success' => false,
                'code' => 'HOST_ALREADY_IN_MEETING',
            ]);
    }

    public function test_true_database_level_concurrency_and_unique_constraint(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);
        $meeting1 = Meeting::create([
            'host_id' => $host->id,
            'room_name' => 'room-c1',
            'meeting_code' => '111-222',
            'title' => 'Concurrent 1',
            'is_active' => true,
            'max_participants' => 10,
        ]);
        $meeting2 = Meeting::create([
            'host_id' => $host->id,
            'room_name' => 'room-c2',
            'meeting_code' => '333-444',
            'title' => 'Concurrent 2',
            'is_active' => true,
            'max_participants' => 10,
        ]);

        // 1. Verify UNIQUE(user_id) constraint in database
        MeetingHostSession::create([
            'user_id' => $host->id,
            'meeting_id' => $meeting1->id,
            'meeting_code' => $meeting1->meeting_code,
            'room_name' => $meeting1->room_name,
            'session_token' => Str::random(64),
            'last_seen_at' => now(),
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->expectException(QueryException::class);

        // Attempting a second raw insert for the same user must be blocked by the UNIQUE constraint
        MeetingHostSession::create([
            'user_id' => $host->id,
            'meeting_id' => $meeting2->id,
            'meeting_code' => $meeting2->meeting_code,
            'room_name' => $meeting2->room_name,
            'session_token' => Str::random(64),
            'last_seen_at' => now(),
            'expires_at' => now()->addMinutes(5),
        ]);
    }

    public function test_normal_host_leave_with_valid_token_releases_the_lock(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $createResponse = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => 'Quick Sync',
        ]);
        $createResponse->assertStatus(201);
        $meetingCode = $createResponse->json('data.meeting_code');
        $sessionToken = $createResponse->json('data.host_session_token');

        $this->assertDatabaseHas('meeting_host_sessions', ['user_id' => $host->id]);

        // Host leaves with valid session token
        $leaveResponse = $this->actingAs($host)->postJson("/api/v1/meetings/{$meetingCode}/leave", [
            'host_session_token' => $sessionToken,
        ]);

        $leaveResponse->assertStatus(200);
        $this->assertDatabaseMissing('meeting_host_sessions', ['user_id' => $host->id]);

        // Now host can freely create or join another meeting as host!
        $create2 = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => 'Subsequent Meeting',
        ]);
        $create2->assertStatus(201);
    }

    public function test_host_leave_without_token_or_with_wrong_token_fails_and_preserves_session(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $createResponse = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => 'Active Host Session',
        ]);
        $createResponse->assertStatus(201);
        $meetingCode = $createResponse->json('data.meeting_code');
        $sessionToken = $createResponse->json('data.host_session_token');

        // Leave without token
        $noTokenResponse = $this->actingAs($host)->postJson("/api/v1/meetings/{$meetingCode}/leave");
        $noTokenResponse->assertStatus(422)
            ->assertJson([
                'success' => false,
                'errors' => [
                    'code' => 'HOST_SESSION_TOKEN_REQUIRED',
                ],
            ]);
        $this->assertDatabaseHas('meeting_host_sessions', ['user_id' => $host->id]);

        // Leave with wrong token
        $wrongTokenResponse = $this->actingAs($host)->postJson("/api/v1/meetings/{$meetingCode}/leave", [
            'host_session_token' => 'invalid_random_token_string_1234567890123456789012345678901234567890',
        ]);
        $wrongTokenResponse->assertStatus(403)
            ->assertJson([
                'success' => false,
                'errors' => [
                    'code' => 'HOST_SESSION_INVALID',
                ],
            ]);
        $this->assertDatabaseHas('meeting_host_sessions', ['user_id' => $host->id]);
    }

    public function test_valid_heartbeat_refreshes_lease(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $createResponse = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => 'Heartbeat Test',
        ]);
        $createResponse->assertStatus(201);
        $meetingCode = $createResponse->json('data.meeting_code');
        $sessionToken = $createResponse->json('data.host_session_token');

        $initialSession = MeetingHostSession::where('user_id', $host->id)->first();
        $this->assertNotNull($initialSession);

        // Fast-forward or send heartbeat
        $heartbeatResponse = $this->actingAs($host)->postJson("/api/v1/meetings/{$meetingCode}/host/heartbeat", [
            'host_session_token' => $sessionToken,
        ]);

        $heartbeatResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Host heartbeat acknowledged',
            ]);

        $refreshedSession = MeetingHostSession::where('user_id', $host->id)->first();
        $this->assertTrue($refreshedSession->expires_at->isFuture());
    }

    public function test_heartbeat_with_wrong_token_or_by_another_device_fails(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $createResponse = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => 'Heartbeat Security',
        ]);
        $createResponse->assertStatus(201);
        $meetingCode = $createResponse->json('data.meeting_code');

        // Another device using same account sends wrong token
        $heartbeatResponse = $this->actingAs($host)->postJson("/api/v1/meetings/{$meetingCode}/host/heartbeat", [
            'host_session_token' => Str::random(64),
        ]);

        $heartbeatResponse->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'HOST_SESSION_INVALID',
            ]);
    }

    public function test_expired_stale_session_can_be_reclaimed(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $create1 = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => 'Meeting 1',
        ]);
        $create1->assertStatus(201);
        $oldToken = $create1->json('data.host_session_token');

        // Manually expire session to simulate host crash / network loss
        MeetingHostSession::where('user_id', $host->id)->update([
            'expires_at' => now()->subMinutes(5),
        ]);

        // Host attempts to create or join meeting 2
        $create2 = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => 'Meeting 2 Reclaimed',
        ]);

        $create2->assertStatus(201);
        $newToken = $create2->json('data.host_session_token');
        $this->assertNotEquals($oldToken, $newToken);

        // Record was reclaimed (still only 1 record for this user!)
        $this->assertEquals(1, MeetingHostSession::where('user_id', $host->id)->count());
        $this->assertEquals($create2->json('data.meeting_code'), MeetingHostSession::where('user_id', $host->id)->value('meeting_code'));
    }

    public function test_participant_join_is_completely_unblocked_by_host_session_state(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);
        $participant = User::factory()->create(['role' => 'user', 'is_guest' => false]);

        // Host is actively hosting Meeting 1
        $create1 = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => 'Meeting 1 Hosted',
        ]);
        $create1->assertStatus(201);

        // Meeting 2 is started by another host
        $otherHost = User::factory()->create(['role' => 'host', 'is_guest' => false]);
        $create2 = $this->actingAs($otherHost)->postJson('/api/v1/meetings', [
            'title' => 'Meeting 2 Other Host',
        ]);
        $create2->assertStatus(201);
        $meeting2Code = $create2->json('data.meeting_code');

        // 1. Normal participant joins Meeting 2 -> succeeds
        $pJoin = $this->actingAs($participant)->postJson("/api/v1/meetings/{$meeting2Code}/join");
        $pJoin->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'role' => 'participant',
                    'is_host' => false,
                ],
            ]);

        // 2. $host user joins Meeting 2 as a regular PARTICIPANT -> succeeds!
        // Host lock does NOT prevent a user from being a participant in other meetings!
        $hostAsParticipantJoin = $this->actingAs($host)->postJson("/api/v1/meetings/{$meeting2Code}/join");
        $hostAsParticipantJoin->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'role' => 'participant',
                    'is_host' => false,
                ],
            ]);
    }

    public function test_rejected_store_rolls_back_transaction_and_leaves_no_orphan_meeting(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        // Host starts meeting 1
        $create1 = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => 'Existing Active Meeting',
        ]);
        $create1->assertStatus(201);

        $initialMeetingCount = Meeting::count();

        // Host attempts to create meeting 2 while meeting 1 is active
        $create2 = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => 'Should Rollback Meeting',
        ]);

        $create2->assertStatus(409)
            ->assertJson([
                'success' => false,
                'code' => 'HOST_ALREADY_IN_MEETING',
            ]);

        // Crucial: Meeting count must NOT increase! No orphan meeting created!
        $this->assertEquals($initialMeetingCount, Meeting::count());
        $this->assertDatabaseMissing('meetings', ['title' => 'Should Rollback Meeting']);
    }

    public function test_reacquire_host_session_after_lease_expired_succeeds(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $createResponse = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => 'Background Recovery Test',
        ]);
        $createResponse->assertStatus(201);
        $meetingCode = $createResponse->json('data.meeting_code');
        $oldToken = $createResponse->json('data.host_session_token');

        // Simulate app backgrounded for > 90s: lease expired in DB
        $session = MeetingHostSession::where('user_id', $host->id)->first();
        $session->update([
            'expires_at' => now()->subSeconds(30),
            'last_seen_at' => now()->subMinutes(2),
        ]);

        // Heartbeat should fail because lease expired
        $heartbeatRes = $this->actingAs($host)->postJson("/api/v1/meetings/{$meetingCode}/host/heartbeat", [
            'host_session_token' => $oldToken,
        ]);
        $heartbeatRes->assertStatus(403)
            ->assertJson(['code' => 'HOST_SESSION_INVALID']);

        // Controlled reacquire endpoint recovers the host session
        $reacquireRes = $this->actingAs($host)->postJson("/api/v1/meetings/{$meetingCode}/host/reacquire", [
            'host_session_token' => $oldToken,
        ]);

        $reacquireRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Host session re-acquired successfully',
            ]);

        $newToken = $reacquireRes->json('data.host_session_token');
        $this->assertNotEmpty($newToken);

        // New token can now send heartbeats successfully
        $freshHeartbeat = $this->actingAs($host)->postJson("/api/v1/meetings/{$meetingCode}/host/heartbeat", [
            'host_session_token' => $newToken,
        ]);
        $freshHeartbeat->assertStatus(200);
    }

    public function test_reacquire_by_non_host_is_rejected(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);
        $otherUser = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $createResponse = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => 'Security Test',
        ]);
        $meetingCode = $createResponse->json('data.meeting_code');

        $reacquireRes = $this->actingAs($otherUser)->postJson("/api/v1/meetings/{$meetingCode}/host/reacquire");
        $reacquireRes->assertStatus(403);
    }

    public function test_reacquire_on_ended_meeting_is_rejected(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $createResponse = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => 'Ended Meeting Test',
        ]);
        $meetingCode = $createResponse->json('data.meeting_code');
        $token = $createResponse->json('data.host_session_token');

        // End meeting
        $this->actingAs($host)->postJson("/api/v1/meetings/{$meetingCode}/end", [
            'host_session_token' => $token,
        ])->assertStatus(200);

        // Attempt to reacquire on ended meeting -> rejected with 410
        $reacquireRes = $this->actingAs($host)->postJson("/api/v1/meetings/{$meetingCode}/host/reacquire", [
            'host_session_token' => $token,
        ]);
        $reacquireRes->assertStatus(410)
            ->assertJson(['code' => 'MEETING_ENDED']);
    }

    public function test_reacquire_when_host_is_active_in_another_meeting_is_rejected(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        // Host creates meeting 1
        $create1 = $this->actingAs($host)->postJson('/api/v1/meetings', [
            'title' => 'Meeting 1',
        ]);
        $create1->assertStatus(201);

        // Admin/system creates meeting 2 for the same host
        $meeting2 = Meeting::create([
            'host_id' => $host->id,
            'room_name' => 'room-meeting-2',
            'meeting_code' => '999-888',
            'title' => 'Meeting 2',
            'is_active' => true,
            'is_locked' => false,
            'max_participants' => 10,
        ]);

        // Attempting to reacquire host on meeting 2 while meeting 1 is active -> 409
        $reacquireRes = $this->actingAs($host)->postJson("/api/v1/meetings/{$meeting2->meeting_code}/host/reacquire");
        $reacquireRes->assertStatus(409)
            ->assertJson(['code' => 'HOST_ALREADY_IN_MEETING']);
    }
}

