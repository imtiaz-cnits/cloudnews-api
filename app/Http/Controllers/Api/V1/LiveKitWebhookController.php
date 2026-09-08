<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\User;
use App\Services\LiveKitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class LiveKitWebhookController extends Controller
{
    public function __construct(
        protected LiveKitService $liveKitService
    ) {}

    /**
     * Handle incoming LiveKit server webhooks.
     */
    public function handle(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $authHeader = $request->header('Authorization');

        try {
            $eventData = $this->liveKitService->verifyWebhook($rawBody, $authHeader);
        } catch (InvalidArgumentException $e) {
            Log::warning('LiveKit Webhook Verification Failed: '.$e->getMessage());

            return $this->errorResponse($e->getMessage(), 401);
        } catch (Throwable $e) {
            Log::error('LiveKit Webhook Processing Exception: '.$e->getMessage());

            return $this->errorResponse('Webhook processing failed', 400);
        }

        $event = $eventData['event'] ?? null;
        $room = $eventData['room'] ?? [];
        $roomName = $room['name'] ?? null;
        $participant = $eventData['participant'] ?? [];
        $identity = $participant['identity'] ?? null;

        Log::info("LiveKit Webhook received: [{$event}] for room [{$roomName}]");

        if (! $roomName) {
            return $this->successResponse(null, 'Webhook received without room data');
        }

        $meeting = Meeting::where('room_name', $roomName)->first();

        if (! $meeting) {
            Log::warning("LiveKit Webhook: Meeting with room_name {$roomName} not found in database.");

            return $this->successResponse(null, 'Meeting not found');
        }

        match ($event) {
            'room_finished' => $this->handleRoomFinished($meeting),
            'participant_joined' => $this->handleParticipantJoined($meeting, $identity),
            'participant_left' => $this->handleParticipantLeft($meeting, $identity),
            default => null,
        };

        return $this->successResponse(null, 'Webhook processed successfully');
    }

    /**
     * Handle room_finished event.
     */
    protected function handleRoomFinished(Meeting $meeting): void
    {
        $meeting->update([
            'is_active' => false,
            'ended_at' => now(),
        ]);

        MeetingParticipant::where('meeting_id', $meeting->id)
            ->whereNull('left_at')
            ->update(['left_at' => now()]);
    }

    /**
     * Handle participant_joined event.
     */
    protected function handleParticipantJoined(Meeting $meeting, ?string $identity): void
    {
        $user = $this->resolveUserFromIdentity($identity);

        if (! $user) {
            return;
        }

        $alreadyActive = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->exists();

        if (! $alreadyActive) {
            $role = ($user->id === $meeting->host_id) ? 'host' : 'participant';

            MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'role' => $role,
                'joined_at' => now(),
            ]);
        }
    }

    /**
     * Handle participant_left event.
     */
    protected function handleParticipantLeft(Meeting $meeting, ?string $identity): void
    {
        $user = $this->resolveUserFromIdentity($identity);

        if (! $user) {
            return;
        }

        MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->update(['left_at' => now()]);
    }

    /**
     * Helper to resolve User instance from LiveKit identity string.
     */
    protected function resolveUserFromIdentity(?string $identity): ?User
    {
        if (! $identity) {
            return null;
        }

        // Format is user_{id} or guest_{id} or direct ID
        if (preg_match('/^(?:user_|guest_)?(\d+)$/', $identity, $matches)) {
            return User::find((int) $matches[1]);
        }

        return null;
    }
}
