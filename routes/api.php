<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\LiveKitWebhookController;
use App\Http\Controllers\Api\V1\MeetingController;
use App\Http\Controllers\Api\V1\MeetingFileController;
use App\Http\Controllers\Api\V1\UserController;
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

    // Users Directory / Contact List for Direct Messages
    Route::get('/users', [UserController::class, 'index']);

    // Meeting Pre-validation (accessible before joining)
    Route::post('/meetings/validate', [MeetingController::class, 'validateMeeting']);

    // Authenticated User Profile Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::put('/users/profile', [UserController::class, 'updateProfile']);
    });

    // Authenticated Meeting Routes
    Route::middleware('auth:sanctum')->prefix('meetings')->group(function () {
        Route::get('/', [MeetingController::class, 'index']);
        Route::post('/', [MeetingController::class, 'store']);
        Route::post('/schedule', [MeetingController::class, 'schedule']);
        Route::get('/scheduled', [MeetingController::class, 'getScheduled']);
        Route::get('/{meeting_code}', [MeetingController::class, 'show']);
        Route::delete('/{meeting_code}', [MeetingController::class, 'destroy']);
        Route::post('/{meeting_code}/join', [MeetingController::class, 'join']);
        Route::post('/{meeting_code}/end', [MeetingController::class, 'end']);
        Route::post('/{meeting_code}/leave', [MeetingController::class, 'leave']);
        Route::post('/{meeting_code}/files', [MeetingFileController::class, 'upload']);
        Route::get('/files/download/{meeting_code}/{filename}', [MeetingFileController::class, 'download']);
    });

    // LiveKit SFU Webhooks
    Route::post('/webhooks/livekit', [LiveKitWebhookController::class, 'handle']);
});

// Support direct routes without v1 prefix (e.g. /api/users, /api/meetings/scheduled, /api/meetings/validate, /api/meetings/{code})
Route::get('/users', [UserController::class, 'index']);
Route::post('/meetings/validate', [MeetingController::class, 'validateMeeting']);
Route::get('/files/download/{meeting_code}/{filename}', [MeetingFileController::class, 'download']);

Route::middleware('auth:sanctum')->group(function () {
    Route::put('/users/profile', [UserController::class, 'updateProfile']);
    Route::post('/meetings/schedule', [MeetingController::class, 'schedule']);
    Route::get('/meetings/scheduled', [MeetingController::class, 'getScheduled']);
    Route::get('/meetings', [MeetingController::class, 'index']);
    Route::post('/meetings', [MeetingController::class, 'store']);
    Route::get('/meetings/{meeting_code}', [MeetingController::class, 'show']);
    Route::delete('/meetings/{meeting_code}', [MeetingController::class, 'destroy']);
    Route::post('/meetings/{meeting_code}/join', [MeetingController::class, 'join']);
    Route::post('/meetings/{meeting_code}/end', [MeetingController::class, 'end']);
    Route::post('/meetings/{meeting_code}/leave', [MeetingController::class, 'leave']);
    Route::post('/meetings/{meeting_code}/files', [MeetingFileController::class, 'upload']);
});
