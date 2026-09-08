<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveKitWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function generateWebhookAuthHeader(string $rawBody, ?string $overrideSecret = null, ?string $overrideSha256 = null): string
    {
        $apiKey = config('livekit.api_key', 'devkey');
        $apiSecret = $overrideSecret ?? config('livekit.api_secret', 'secret_token_for_cloudnews_2026_32chars');
        $hash = $overrideSha256 ?? base64_encode(hash('sha256', $rawBody, true));

        $payload = [
            'iss' => $apiKey,
            'exp' => time() + 300,
            'nbf' => time() - 10,
            'sha256' => $hash,
        ];

        return JWT::encode($payload, $apiSecret, 'HS256');
    }

    public function test_webhook_rejects_missing_authorization(): void
    {
        $rawPayload = json_encode(['event' => 'room_finished']);

        $response = $this->call('POST', '/api/v1/webhooks/livekit', [], [], [], ['CONTENT_TYPE' => 'application/json'], $rawPayload);

        $response->assertStatus(401);
    }

    public function test_webhook_rejects_tampered_body_hash(): void
    {
        $rawPayload = json_encode(['event' => 'room_finished']);
        $badAuth = $this->generateWebhookAuthHeader($rawPayload, overrideSha256: 'tampered_hash');

        $response = $this->call('POST', '/api/v1/webhooks/livekit', [], [], [], [
            'HTTP_AUTHORIZATION' => $badAuth,
            'CONTENT_TYPE' => 'application/json',
        ], $rawPayload);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Webhook body checksum does not match sha256 claim.');
    }

    public function test_webhook_processes_room_finished(): void
    {
        $host = User::factory()->create();
        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'is_active' => true,
            'ended_at' => null,
        ]);

        $participant = User::factory()->create();
        MeetingParticipant::create([
            'meeting_id' => $meeting->id,
            'user_id' => $participant->id,
            'role' => 'participant',
            'joined_at' => now(),
            'left_at' => null,
        ]);

        $rawPayload = json_encode([
            'event' => 'room_finished',
            'room' => [
                'name' => $meeting->room_name,
            ],
        ]);

        $authHeader = $this->generateWebhookAuthHeader($rawPayload);

        $response = $this->call('POST', '/api/v1/webhooks/livekit', [], [], [], [
            'HTTP_AUTHORIZATION' => $authHeader,
            'CONTENT_TYPE' => 'application/json',
        ], $rawPayload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $fresh = $meeting->fresh();
        $this->assertFalse($fresh->is_active);
        $this->assertNotNull($fresh->ended_at);

        $this->assertDatabaseMissing('meeting_participants', [
            'meeting_id' => $meeting->id,
            'left_at' => null,
        ]);
    }

    public function test_webhook_syncs_participant_joined_and_left(): void
    {
        $host = User::factory()->create();
        $meeting = Meeting::factory()->create(['host_id' => $host->id]);
        $attendee = User::factory()->create();

        // 1. Participant joined
        $joinPayload = json_encode([
            'event' => 'participant_joined',
            'room' => [
                'name' => $meeting->room_name,
            ],
            'participant' => [
                'identity' => "user_{$attendee->id}",
                'name' => $attendee->name,
            ],
        ]);

        $response = $this->call('POST', '/api/v1/webhooks/livekit', [], [], [], [
            'HTTP_AUTHORIZATION' => $this->generateWebhookAuthHeader($joinPayload),
            'CONTENT_TYPE' => 'application/json',
        ], $joinPayload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('meeting_participants', [
            'meeting_id' => $meeting->id,
            'user_id' => $attendee->id,
            'left_at' => null,
        ]);

        // 2. Participant left
        $leavePayload = json_encode([
            'event' => 'participant_left',
            'room' => [
                'name' => $meeting->room_name,
            ],
            'participant' => [
                'identity' => "user_{$attendee->id}",
            ],
        ]);

        $leaveResponse = $this->call('POST', '/api/v1/webhooks/livekit', [], [], [], [
            'HTTP_AUTHORIZATION' => $this->generateWebhookAuthHeader($leavePayload),
            'CONTENT_TYPE' => 'application/json',
        ], $leavePayload);

        $leaveResponse->assertStatus(200);

        $this->assertDatabaseMissing('meeting_participants', [
            'meeting_id' => $meeting->id,
            'user_id' => $attendee->id,
            'left_at' => null,
        ]);
    }
}
