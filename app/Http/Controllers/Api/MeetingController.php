<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LiveKitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MeetingController extends Controller
{
    //
    protected LiveKitService $liveKitService;

    public function __construct(LiveKitService $liveKitService)
    {
        $this->liveKitService = $liveKitService;
    }

    /**
     * Create a new meeting room.
     */
    public function createRoom(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_name' => 'nullable|string|max:128',
            'empty_timeout' => 'nullable|integer|min:60|max:86400',
            'max_participants' => 'nullable|integer|min:2|max:500',
        ]);

        $roomName = $validated['room_name'] ?? 'room_' . Str::random(10);

        try {
            if (config('livekit.api_key') && config('livekit.api_secret')) {
                try {
                    $this->liveKitService->createRoom($roomName, [
                        'empty_timeout' => $validated['empty_timeout'] ?? 300,
                        'max_participants' => $validated['max_participants'] ?? 50,
                    ]);
                } catch (\Exception $e) {
                    // Fallback to LiveKit implicit room creation on join
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Room ready',
                'data' => [
                    'room_name' => $roomName,
                    'empty_timeout' => $validated['empty_timeout'] ?? 300,
                    'max_participants' => $validated['max_participants'] ?? 50,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create room: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate an access token to join a room.
     */
    public function joinToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_name' => 'required|string|max:128',
            'identity' => 'nullable|string|max:128',
            'name' => 'nullable|string|max:128',
            'metadata' => 'nullable|array',
        ]);

        $identity = $validated['identity'] ?? 'user_' . Str::random(8);
        $name = $validated['name'] ?? $identity;

        try {
            $token = $this->liveKitService->generateJoinToken(
                $validated['room_name'],
                $identity,
                $name,
                $validated['metadata'] ?? []
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'room_name' => $validated['room_name'],
                    'identity' => $identity,
                    'name' => $name,
                    'livekit_url' => config('livekit.url', env('LIVEKIT_URL', 'http://127.0.0.1:7880')),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate token: ' . $e->getMessage(),
            ], 500);
        }
    }
}
