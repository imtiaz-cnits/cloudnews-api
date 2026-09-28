<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HostController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MeetingAdminController;
use App\Http\Controllers\MeetingJoinWebController;
use Illuminate\Support\Facades\Route;

// Public Landing Page
Route::get('/', [LandingController::class, 'index'])->name('home');

// Deep Link Meeting Gateway & Android App Links (WhatsApp, Facebook, Messenger integration)
Route::get('/room/{meetingCode}', [MeetingJoinWebController::class, 'join'])->name('meeting.web.join');
Route::get('/join/{meetingCode?}', [MeetingJoinWebController::class, 'join'])->name('meeting.web.join_alias');
Route::get('/.well-known/assetlinks.json', [MeetingJoinWebController::class, 'assetLinks'])->name('meeting.web.assetlinks');

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
});

// Protected Admin Dashboard Routes
Route::middleware(['auth', 'admin'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    // Host Management CRUD & Bulk Actions
    Route::resource('hosts', HostController::class)->except(['show']);
    Route::post('hosts/{host}/reset-password', [HostController::class, 'resetPassword'])->name('hosts.reset-password');
    Route::post('hosts/{host}/revoke-session', [HostController::class, 'revokeSession'])->name('hosts.revoke-session');
    Route::post('hosts/bulk-action', [HostController::class, 'bulkAction'])->name('hosts.bulk-action');

    // Meeting & Room Management
    Route::get('meetings', [MeetingAdminController::class, 'index'])->name('meetings.index');
    Route::post('meetings/{meeting}/end', [MeetingAdminController::class, 'endMeeting'])->name('meetings.end');
    Route::delete('meetings/{meeting}', [MeetingAdminController::class, 'destroy'])->name('meetings.destroy');
    Route::post('meetings/bulk-action', [MeetingAdminController::class, 'bulkAction'])->name('meetings.bulk-action');
});
