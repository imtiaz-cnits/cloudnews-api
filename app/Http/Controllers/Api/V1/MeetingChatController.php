<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\MeetingMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeetingChatController extends Controller
{
    /**
     * Get all chat messages and shared files for a meeting ordered by created_at ASC.
     */
    public function index(Request $request, string $meetingId): JsonResponse
    {
        $meeting = $this->findMeetingByCode($meetingId);

        if (! $meeting) {
            return $this->errorResponse('Meeting not found', 404);
        }

        $messages = $meeting->messages()
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->successResponse($messages, 'Meeting messages retrieved successfully');
    }

    /**
     * Store a new chat message or shared file record to the meeting history.
     */
    public function store(Request $request, string $meetingId): JsonResponse
    {
        $request->validate([
            'message' => 'nullable|string',
            'text' => 'nullable|string',
            'sender_name' => 'nullable|string|max:100',
            'sender_id' => 'nullable',
            'user_id' => 'nullable',
            'file_url' => 'nullable|string',
            'media_url' => 'nullable|string',
            'file_type' => 'nullable|string|in:text,image,video,audio,document',
            'type' => 'nullable|string|in:text,image,video,audio,document',
            'file_name' => 'nullable|string|max:255',
            'file_size' => 'nullable|string|max:50',
            'duration' => 'nullable|string|max:50',
            'client_msg_id' => 'nullable|string|max:100',
            'timestamp' => 'nullable',
        ]);

        $meeting = $this->findMeetingByCode($meetingId);

        if (! $meeting) {
            return $this->errorResponse('Meeting not found', 404);
        }

        $user = $request->user() ?: auth('sanctum')->user();
        $senderName = $request->input('sender_name');

        if (! $senderName && $user) {
            $senderName = $user->name;
        }

        $senderId = $request->input('sender_id') ?? $request->input('user_id') ?? $user?->id;
        $messageText = $request->input('message') ?? $request->input('text');
        $mediaUrl = $request->input('file_url') ?? $request->input('media_url');
        $msgType = $request->input('file_type') ?? $request->input('type', 'text');
        $clientMsgId = $request->input('client_msg_id');

        $message = MeetingMessage::create([
            'meeting_id' => $meeting->id,
            'meeting_code' => $meeting->meeting_code,
            'user_id' => $senderId,
            'client_msg_id' => $clientMsgId,
            'sender_name' => $senderName ?: 'Participant',
            'type' => $msgType,
            'text' => $messageText,
            'file_name' => $request->input('file_name'),
            'file_size' => $request->input('file_size'),
            'media_url' => $mediaUrl,
            'duration' => $request->input('duration'),
        ]);

        return $this->successResponse($message, 'Message stored successfully', 201);
    }

    /**
     * Find a meeting by flexible code, room name, numeric ID, or shared link.
     */
    protected function findMeetingByCode(string $rawInput): ?Meeting
    {
        $rawInput = trim($rawInput);

        if (str_contains($rawInput, '/')) {
            $path = parse_url($rawInput, PHP_URL_PATH) ?? $rawInput;
            $segments = array_values(array_filter(explode('/', $path)));
            $rawInput = ! empty($segments) ? end($segments) : $rawInput;
        }

        $rawInput = trim(explode('?', explode('#', $rawInput)[0])[0]);
        $cleanDigits = preg_replace('/\D/', '', $rawInput);

        $formattedCode = (strlen($cleanDigits) === 6)
            ? substr($cleanDigits, 0, 3).'-'.substr($cleanDigits, 3, 3)
            : ((strlen($cleanDigits) === 9)
                ? substr($cleanDigits, 0, 3).'-'.substr($cleanDigits, 3, 3).'-'.substr($cleanDigits, 6, 3)
                : $rawInput);

        return Meeting::where('meeting_code', $rawInput)
            ->orWhere('meeting_code', $formattedCode)
            ->orWhere('meeting_code', $cleanDigits)
            ->orWhere('room_name', $rawInput)
            ->orWhere('room_name', strtolower($rawInput))
            ->orWhereRaw("REPLACE(room_name, '-', '') = ?", [str_replace('-', '', strtolower($rawInput))])
            ->orWhereRaw("REPLACE(meeting_code, '-', '') = ?", [$cleanDigits ?: $rawInput])
            ->when(is_numeric($rawInput), function ($query) use ($rawInput) {
                $query->orWhere('id', (int) $rawInput);
            })
            ->first();
    }
}
