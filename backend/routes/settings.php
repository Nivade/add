<?php

declare(strict_types=1);

use App\Http\Controllers\Settings\ConnectCalendarController;
use App\Http\Controllers\Settings\DisconnectCalendarController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\ShowAiController;
use App\Http\Controllers\Settings\ShowCalendarController;
use App\Http\Controllers\Settings\UpdateAiConsentController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

    Route::get('settings/calendar', ShowCalendarController::class)->name('calendar.edit');
    Route::put('settings/calendar', ConnectCalendarController::class)
        ->middleware('throttle:6,1')
        ->name('calendar.update');
    Route::delete('settings/calendar', DisconnectCalendarController::class)->name('calendar.destroy');

    Route::get('settings/ai', ShowAiController::class)->name('ai.edit');
    Route::put('settings/ai', UpdateAiConsentController::class)->name('ai.update');
});

Route::get('.well-known/passkey-endpoints', fn () => response()->json([
    'enroll' => route('security.edit'),
    'manage' => route('security.edit'),
]))->name('well-known.passkeys');
