<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Meeting\CreateMeetingRequest;
use App\Http\Requests\Meeting\JoinMeetingRequest;
use App\Http\Requests\Meeting\ValidateMeetingRequest;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Services\LiveKitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function __construct(
        protected LiveKitService $liveKitService
    ) {}

    /**
     * Display a listing of meetings for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $meetings = Meeting::with('host:id,name,avatar_url')
            ->withCount('activeParticipants')
            ->where(function ($query) use ($user) {
                $query->where('host_id', $user->id)
                    ->orWhereHas('participants', function ($pQuery) use ($user) {
                        $pQuery->where('user_id', $user->id);
                    });
            })
            ->latest()
            ->paginate(15);

        return $this->successResponse($meetings, 'Meetings retrieved successfully');
    }

    /**
     * Create a new video meeting room.
     */
    public function store(CreateMeetingRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $roomName = Meeting::generateRoomName();
        $meetingCode = Meeting::generateMeetingCode();

        $meeting = Meeting::create([
            'host_id' => $user->id,
            'room_name' => $roomName,
            'meeting_code' => $meetingCode,
            'title' => $validated['title'],
            'passcode' => $validated['passcode'] ?? null,
            'is_active' => true,
            'is_locked' => false,
            'max_participants' => $validated['max_participants'] ?? 12,
            'started_at' => now(),
        ]);

        // Explicitly pre-create room on LiveKit SFU
        $this->liveKitService->createRoom($roomName, [
            'empty_timeout' => 300,
            'max_participants' => $meeting->max_participants,
        ]);

        // Record host as first participant
        MeetingParticipant::create([
            'meeting_id' => $meeting->id,
            'user_id' => $user->id,
            'role' => 'host',
            'joined_at' => now(),
        ]);

        // Generate LiveKit Host JWT Token
        $identity = $user->is_guest ? "guest_{$user->id}" : "user_{$user->id}";
        $token = $this->liveKitService->generateJoinToken(
            roomName: $meeting->room_name,
            identity: $identity,
            name: $user->name,
            isHost: true,
            role: 'host'
        );

        $meetingData = $meeting->toArray();
        // Since caller is host, include plain passcode
        $meetingData['passcode'] = $meeting->passcode;
        $meetingData['host'] = [
            'id' => $user->id,
            'name' => $user->name,
            'avatar_url' => $user->avatar_url,
        ];

        return $this->successResponse([
            'meeting' => $meetingData,
            'token' => $token,
            'livekit_url' => $this->liveKitService->getHost(),
        ], 'Meeting created successfully', 201);
    }

    /**
     * Show meeting summary and public/participant status.
     */
    public function show(Request $request, string $meetingCode): JsonResponse
    {
        $meeting = Meeting::with('host:id,name,avatar_url')
            ->withCount('activeParticipants')
            ->where('meeting_code', $meetingCode)
            ->orWhere('room_name', $meetingCode)
            ->first();

        if (!$meeting) {
            return $this->errorResponse('Meeting not found', 404);
        }

        $user = $request->user();
        $isHost = $user && ($user->id === $meeting->host_id);

        $data = [
            'id' => $meeting->id,
            'title' => $meeting->title,
            'meeting_code' => $meeting->meeting_code,
            'room_name' => $meeting->room_name,
            'is_active' => $meeting->is_active,
            'is_locked' => $meeting->is_locked,
            'max_participants' => $meeting->max_participants,
            'active_participants_count' => $meeting->active_participants_count,
            'started_at' => $meeting->started_at,
            'ended_at' => $meeting->ended_at,
            'requires_passcode' => $meeting->hasPasscode(),
            'host' => $meeting->host,
        ];

        // Reveal passcode only if requester is host
        if ($isHost) {
            $data['passcode'] = $meeting->passcode;
        }

        return $this->successResponse($data, 'Meeting details retrieved successfully');
    }

    /**
     * Validate meeting existence, status, and passcode before joining.
     */
    public function validateMeeting(ValidateMeetingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $meeting = Meeting::where('meeting_code', $validated['meeting_code'])
            ->orWhere('room_name', $validated['meeting_code'])
            ->first();

        if (!$meeting) {
            return $this->errorResponse('Meeting not found', 404);
        }

        if (!$meeting->is_active) {
            return $this->errorResponse('This meeting has already ended', 422, [
                'is_active' => false,
            ]);
        }

        if ($meeting->is_locked) {
            return $this->errorResponse('This meeting is currently locked by the host', 422, [
                'is_locked' => true,
            ]);
        }

        if ($meeting->hasPasscode()) {
            if (empty($validated['passcode'])) {
                return $this->errorResponse('Passcode is required to join this meeting', 422, [
                    'requires_passcode' => true,
                ]);
            }

            if (!$meeting->verifyPasscode($validated['passcode'])) {
                return $this->errorResponse('Invalid meeting passcode', 422, [
                    'invalid_passcode' => true,
                ]);
            }
        }

        if ($meeting->activeParticipants()->count() >= $meeting->max_participants) {
            return $this->errorResponse('Meeting has reached maximum capacity', 422, [
                'room_full' => true,
            ]);
        }

        return $this->successResponse([
            'valid' => true,
            'meeting_code' => $meeting->meeting_code,
            'room_name' => $meeting->room_name,
            'title' => $meeting->title,
            'is_active' => $meeting->is_active,
            'is_locked' => $meeting->is_locked,
            'requires_passcode' => $meeting->hasPasscode(),
        ], 'Meeting is valid and ready to join');
    }

    /**
     * Join an existing meeting room and receive a LiveKit token.
     */
    public function join(JoinMeetingRequest $request, string $meetingCode): JsonResponse
    {
        $meeting = Meeting::where('meeting_code', $meetingCode)
            ->orWhere('room_name', $meetingCode)
            ->first();

        if (!$meeting) {
            return $this->errorResponse('Meeting not found', 404);
        }

        if (!$meeting->is_active) {
            return $this->errorResponse('This meeting has ended', 422);
        }

        $user = $request->user();
        $isHost = ($user->id === $meeting->host_id);

        if (!$isHost && $meeting->is_locked) {
            return $this->errorResponse('This meeting has been locked by the host', 403);
        }

        // Passcode verification for non-hosts
        if (!$isHost && $meeting->hasPasscode()) {
            $passcode = $request->validated('passcode');
            if (empty($passcode) || !$meeting->verifyPasscode($passcode)) {
                return $this->errorResponse('Invalid or missing meeting passcode', 403);
            }
        }

        // Capacity check (allow existing active participant to re-join/reconnect)
        $existingActive = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        if (!$existingActive && $meeting->activeParticipants()->count() >= $meeting->max_participants) {
            return $this->errorResponse('Meeting has reached maximum participant limit', 422);
        }

        $role = $isHost ? 'host' : 'participant';

        // Record participant entry if not already logged
        if (!$existingActive) {
            MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'role' => $role,
                'joined_at' => now(),
            ]);
        }

        // Generate LiveKit JWT
        $identity = $user->is_guest ? "guest_{$user->id}" : "user_{$user->id}";
        $token = $this->liveKitService->generateJoinToken(
            roomName: $meeting->room_name,
            identity: $identity,
            name: $user->name,
            isHost: $isHost,
            role: $role
        );

        return $this->successResponse([
            'token' => $token,
            'room_name' => $meeting->room_name,
            'meeting_code' => $meeting->meeting_code,
            'title' => $meeting->title,
            'identity' => $identity,
            'name' => $user->name,
            'role' => $role,
            'livekit_url' => $this->liveKitService->getHost(),
        ], 'Joined meeting successfully');
    }

    /**
     * Terminate meeting (Host only).
     */
    public function end(Request $request, string $meetingCode): JsonResponse
    {
        $meeting = Meeting::where('meeting_code', $meetingCode)
            ->orWhere('room_name', $meetingCode)
            ->first();

        if (!$meeting) {
            return $this->errorResponse('Meeting not found', 404);
        }

        $user = $request->user();

        if ($meeting->host_id !== $user->id) {
            return $this->errorResponse('Only the host can end this meeting', 403);
        }

        $meeting->update([
            'is_active' => false,
            'ended_at' => now(),
        ]);

        // Mark all active participants as departed
        MeetingParticipant::where('meeting_id', $meeting->id)
            ->whereNull('left_at')
            ->update(['left_at' => now()]);

        // Terminate room on LiveKit SFU
        $this->liveKitService->deleteRoom($meeting->room_name);

        return $this->successResponse(null, 'Meeting ended successfully');
    }

    /**
     * Leave meeting (Participant).
     */
    public function leave(Request $request, string $meetingCode): JsonResponse
    {
        $meeting = Meeting::where('meeting_code', $meetingCode)
            ->orWhere('room_name', $meetingCode)
            ->first();

        if (!$meeting) {
            return $this->errorResponse('Meeting not found', 404);
        }

        $user = $request->user();

        // Mark active participant record as left
        MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->update(['left_at' => now()]);

        return $this->successResponse(null, 'Left meeting successfully');
    }
}
