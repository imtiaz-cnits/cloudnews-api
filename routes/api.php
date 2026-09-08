<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\LiveKitWebhookController;
use App\Http\Controllers\Api\V1\MeetingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Authentication Routes
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/guest', [AuthController::class, 'guest']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // Meeting Pre-validation (accessible before joining)
    Route::post('/meetings/validate', [MeetingController::class, 'validateMeeting']);

    // Authenticated Meeting Routes
    Route::middleware('auth:sanctum')->prefix('meetings')->group(function () {
        Route::get('/', [MeetingController::class, 'index']);
        Route::post('/', [MeetingController::class, 'store']);
        Route::get('/{meeting_code}', [MeetingController::class, 'show']);
        Route::post('/{meeting_code}/join', [MeetingController::class, 'join']);
        Route::post('/{meeting_code}/end', [MeetingController::class, 'end']);
        Route::post('/{meeting_code}/leave', [MeetingController::class, 'leave']);
    });

    // LiveKit SFU Webhooks
    Route::post('/webhooks/livekit', [LiveKitWebhookController::class, 'handle']);
});
