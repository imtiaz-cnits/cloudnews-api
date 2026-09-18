<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\MeetingMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeetingMessageController extends Controller
{
    /**
     * Get all chat messages and shared files for a meeting.
     */
    public function index(Request $request, string $meeting_code): JsonResponse
    {
        $meeting = $this->findMeetingByCode($meeting_code);

        if (! $meeting) {
            return $this->errorResponse('Meeting not found', 404);
        }

        $messages = $meeting->messages()
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->successResponse($messages, 'Meeting messages retrieved successfully');
    }

    /**
     * Save a new chat message or shared file record to the meeting history.
     */
    public function store(Request $request, string $meeting_code): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|string|in:text,image,video,audio,document',
            'text' => 'nullable|string',
            'sender_name' => 'nullable|string|max:100',
            'file_name' => 'nullable|string|max:255',
            'file_size' => 'nullable|string|max:50',
            'media_url' => 'nullable|string',
            'duration' => 'nullable|string|max:50',
        ]);

        $meeting = $this->findMeetingByCode($meeting_code);

        if (! $meeting) {
            return $this->errorResponse('Meeting not found', 404);
        }

        $user = $request->user();
        $senderName = $request->input('sender_name');

        if (! $senderName && $user) {
            $senderName = $user->name;
        }

        $message = MeetingMessage::create([
            'meeting_id' => $meeting->id,
            'meeting_code' => $meeting->meeting_code,
            'user_id' => $user?->id,
            'sender_name' => $senderName ?: 'Participant',
            'type' => $request->input('type', 'text'),
            'text' => $request->input('text'),
            'file_name' => $request->input('file_name'),
            'file_size' => $request->input('file_size'),
            'media_url' => $request->input('media_url'),
            'duration' => $request->input('duration'),
        ]);

        return $this->successResponse($message, 'Message stored successfully', 201);
    }

    /**
     * Find a meeting by flexible code, room name, ID, or shared link.
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
            ? substr($cleanDigits, 0, 3) . '-' . substr($cleanDigits, 3, 3)
            : ((strlen($cleanDigits) === 9)
                ? substr($cleanDigits, 0, 3) . '-' . substr($cleanDigits, 3, 3) . '-' . substr($cleanDigits, 6, 3)
                : $rawInput);

        return Meeting::where('meeting_code', $rawInput)
            ->orWhere('meeting_code', $formattedCode)
            ->orWhere('meeting_code', $cleanDigits)
            ->orWhere('room_name', $rawInput)
            ->orWhere('room_name', strtolower($rawInput))
            ->when(is_numeric($rawInput), function ($query) use ($rawInput) {
                $query->orWhere('id', (int) $rawInput);
            })
            ->first();
    }
}
