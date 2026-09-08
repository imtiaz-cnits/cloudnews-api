<?php

use App\Http\Controllers\Api\MeetingController;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\LiveKitWebhookController;
use App\Http\Controllers\Api\V1\MeetingController;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::prefix('v1')->group(function () {
    // Authentication Routes
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/guest', [AuthController::class, 'guest']);

Route::prefix('v1/rooms')->group(function () {
    Route::post('/create', [MeetingController::class, 'createRoom']);
    Route::post('/join-token', [MeetingController::class, 'joinToken']);
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
