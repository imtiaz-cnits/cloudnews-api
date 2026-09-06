<?php

use App\Http\Controllers\Api\MeetingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1/rooms')->group(function () {
    Route::post('/create', [MeetingController::class, 'createRoom']);
    Route::post('/join-token', [MeetingController::class, 'joinToken']);
});
