<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\MeetingHostSession;
use App\Models\MeetingParticipant;
use App\Models\User;
use App\Services\HostSessionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuestWaitingRoomTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake([
            '*/twirp/*' => Http::response(['success' => true], 200),
        ]);
    }

    /**
     * 1. Guest joins while host is absent -> WAITING_FOR_HOST.
     */
    public function test_guest_joins_while_host_is_absent_returns_waiting_for_host(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);
        $guest = User::factory()->guest()->create(['name' => 'Guest User']);

        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'is_active' => true,
            'is_host_online' => false,
            'started_at' => null,
        ]);

        $response = $this->actingAs($guest)->postJson("/api/v1/meetings/{$meeting->meeting_code}/join");

        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'status' => 'waiting_for_host',
                'code' => 'WAITING_FOR_HOST',
                'data' => [
                    'meeting_code' => $meeting->meeting_code,
                    'waiting_for_host' => true,
                ],
            ]);
    }

    /**
     * 2. No LiveKit token is issued in waiting state.
     */
    public function test_no_livekit_token_issued_in_waiting_state(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);
        $guest = User::factory()->guest()->create(['name' => 'Guest User']);

        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'is_active' => true,
            'is_host_online' => false,
        ]);

        $response = $this->actingAs($guest)->postJson("/api/v1/meetings/{$meeting->meeting_code}/join");

        $this->assertNull($response->json('data.token'));
        $this->assertNull($response->json('data.livekit_token'));

        $this->assertDatabaseMissing('meeting_participants', [
            'meeting_id' => $meeting->id,
            'user_id' => $guest->id,
        ]);
    }

    /**
     * 3. Guest joins after host becomes active -> allowed (receives LiveKit token).
     */
    public function test_guest_joins_after_host_becomes_active_is_allowed(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);
        $guest = User::factory()->guest()->create(['name' => 'Guest User']);

        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'is_active' => true,
            'is_host_online' => false,
        ]);

        // Host joins and acquires active host session lock
        $hostResponse = $this->actingAs($host)->postJson("/api/v1/meetings/{$meeting->meeting_code}/join");
        $hostResponse->assertStatus(200);
        $this->assertNotEmpty($hostResponse->json('data.livekit_token'));

        // Host is now active in meeting_host_sessions
        $this->assertDatabaseHas('meeting_host_sessions', [
            'meeting_id' => $meeting->id,
            'user_id' => $host->id,
        ]);

        // Guest joins -> allowed!
        $guestResponse = $this->actingAs($guest)->postJson("/api/v1/meetings/{$meeting->meeting_code}/join");
        $guestResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_host' => false,
                    'role' => 'participant',
                    'meeting_code' => $meeting->meeting_code,
                ],
            ]);

        $this->assertNotNull($guestResponse->json('data.livekit_token'));

        $this->assertDatabaseHas('meeting_participants', [
            'meeting_id' => $meeting->id,
            'user_id' => $guest->id,
            'role' => 'participant',
        ]);
    }

    /**
     * 4. Host leaves -> new guest is blocked/waiting.
     */
    public function test_host_leaves_then_new_guest_is_blocked(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);
        $guest1 = User::factory()->guest()->create(['name' => 'Participant 1']);
        $guest2 = User::factory()->guest()->create(['name' => 'Late Guest']);

        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'is_active' => true,
        ]);

        // Host joins
        $hostJoinRes = $this->actingAs($host)->postJson("/api/v1/meetings/{$meeting->meeting_code}/join");
        $hostSessionToken = $hostJoinRes->json('data.host_session_token');

        // Guest 1 joins while host is active
        $this->actingAs($guest1)->postJson("/api/v1/meetings/{$meeting->meeting_code}/join")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        // Host leaves the meeting cleanly
        $this->actingAs($host)->postJson("/api/v1/meetings/{$meeting->meeting_code}/leave", [
            'host_session_token' => $hostSessionToken,
        ])->assertStatus(200);

        // Host session lock is released
        $this->assertDatabaseMissing('meeting_host_sessions', [
            'user_id' => $host->id,
            'meeting_id' => $meeting->id,
        ]);

        // Guest 2 attempts to join -> Blocked in waiting room
        $response = $this->actingAs($guest2)->postJson("/api/v1/meetings/{$meeting->meeting_code}/join");
        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'code' => 'WAITING_FOR_HOST',
                'status' => 'waiting_for_host',
            ]);

        $this->assertNull($response->json('data.livekit_token'));
    }

    /**
     * 5. Expired host heartbeat -> guest is blocked/waiting.
     */
    public function test_expired_host_heartbeat_blocks_guest(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);
        $guest = User::factory()->guest()->create(['name' => 'Guest User']);

        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'is_active' => true,
        ]);

        // Create an expired host session (heartbeat stopped / phone died)
        MeetingHostSession::create([
            'user_id' => $host->id,
            'meeting_id' => $meeting->id,
            'meeting_code' => $meeting->meeting_code,
            'room_name' => $meeting->room_name,
            'session_token' => Str::random(64),
            'last_seen_at' => now()->subMinutes(5),
            'expires_at' => now()->subMinutes(3), // expired!
        ]);

        // Guest attempts entry -> Must be blocked because host heartbeat expired
        $response = $this->actingAs($guest)->postJson("/api/v1/meetings/{$meeting->meeting_code}/join");

        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'code' => 'WAITING_FOR_HOST',
                'status' => 'waiting_for_host',
            ]);

        $this->assertNull($response->json('data.livekit_token'));
    }

    /**
     * 6. Wrong/stale host session cannot make host active.
     */
    public function test_wrong_or_stale_host_session_cannot_make_host_active(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);
        $otherHost = User::factory()->create(['role' => 'host', 'is_guest' => false]);
        $guest = User::factory()->guest()->create(['name' => 'Guest User']);

        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'is_active' => true,
        ]);

        // Create a host session belonging to a DIFFERENT user (not the meeting's host)
        MeetingHostSession::create([
            'user_id' => $otherHost->id,
            'meeting_id' => $meeting->id,
            'meeting_code' => $meeting->meeting_code,
            'room_name' => $meeting->room_name,
            'session_token' => Str::random(64),
            'last_seen_at' => now(),
            'expires_at' => now()->addMinutes(1),
        ]);

        // Guest attempts entry -> Host of THIS meeting ($host) is not active
        $response = $this->actingAs($guest)->postJson("/api/v1/meetings/{$meeting->meeting_code}/join");

        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'code' => 'WAITING_FOR_HOST',
                'status' => 'waiting_for_host',
            ]);
    }

    /**
     * 7. Host joining another meeting remains protected by existing host lock.
     */
    public function test_host_joining_another_meeting_remains_protected_by_existing_lock(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $meeting1 = Meeting::factory()->create([
            'host_id' => $host->id,
            'is_active' => true,
        ]);

        $meeting2 = Meeting::factory()->create([
            'host_id' => $host->id,
            'is_active' => true,
        ]);

        // Host joins meeting 1
        $this->actingAs($host)->postJson("/api/v1/meetings/{$meeting1->meeting_code}/join")
            ->assertStatus(200);

        // Host attempts to join meeting 2 while session 1 is active -> 409 Conflict
        $response = $this->actingAs($host)->postJson("/api/v1/meetings/{$meeting2->meeting_code}/join");
        $response->assertStatus(409)
            ->assertJson([
                'code' => 'HOST_ALREADY_IN_MEETING',
            ]);
    }

    /**
     * 8. Existing participant behavior is preserved once host is active.
     */
    public function test_existing_participant_behavior_is_preserved(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);
        $guest = User::factory()->guest()->create(['name' => 'Regular Participant']);

        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'is_active' => true,
        ]);

        // Host joins
        $this->actingAs($host)->postJson("/api/v1/meetings/{$meeting->meeting_code}/join");

        // Guest joins
        $guestRes = $this->actingAs($guest)->postJson("/api/v1/meetings/{$meeting->meeting_code}/join");
        $guestRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_host' => false,
                    'role' => 'participant',
                ],
            ]);

        $this->assertNotEmpty($guestRes->json('data.livekit_token'));
    }
}
