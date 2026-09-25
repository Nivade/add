<?php

declare(strict_types=1);

use App\Enums\AppointmentKind;
use App\Http\Controllers\Api\V1\AdjustPlanController;
use App\Http\Controllers\Api\V1\ClarifyIntentionController;
use App\Http\Controllers\Api\V1\CompleteStepController;
use App\Http\Controllers\Api\V1\ConfirmDeadlineController;
use App\Http\Controllers\Api\V1\DestroyTokenController;
use App\Http\Controllers\Api\V1\DismissReminderController;
use App\Http\Controllers\Api\V1\PauseSessionController;
use App\Http\Controllers\Api\V1\RecordDistractionController;
use App\Http\Controllers\Api\V1\ReportStuckController;
use App\Http\Controllers\Api\V1\RespondToWaitingForController;
use App\Http\Controllers\Api\V1\ResumeSessionController;
use App\Http\Controllers\Api\V1\ShowAiConsentController;
use App\Http\Controllers\Api\V1\ShowAppointmentController;
use App\Http\Controllers\Api\V1\ShowCurrentSessionController;
use App\Http\Controllers\Api\V1\ShowHomeController;
use App\Http\Controllers\Api\V1\ShowNextActionController;
use App\Http\Controllers\Api\V1\ShowOverwhelmedController;
use App\Http\Controllers\Api\V1\SkipStepController;
use App\Http\Controllers\Api\V1\StopSessionController;
use App\Http\Controllers\Api\V1\StoreCaptureController;
use App\Http\Controllers\Api\V1\StoreDeviceController;
use App\Http\Controllers\Api\V1\StoreSessionController;
use App\Http\Controllers\Api\V1\StoreTokenController;
use App\Http\Controllers\Api\V1\StoreWaitingForController;
use App\Http\Controllers\Api\V1\UpdateAiConsentController;
use Illuminate\Support\Facades\Route;

// The device has no session to authenticate with yet, so this is the one route outside the guard.
Route::post('v1/tokens', StoreTokenController::class)
    ->middleware('throttle:login')
    ->name('api.v1.tokens.store');

Route::middleware('auth:sanctum')->prefix('v1')->name('api.v1.')->group(function (): void {
    Route::delete('tokens/current', DestroyTokenController::class)->name('tokens.destroy');
    Route::post('devices', StoreDeviceController::class)->name('devices.store');

    Route::get('ai-consent', ShowAiConsentController::class)->name('ai-consent.show');
    Route::patch('ai-consent', UpdateAiConsentController::class)->name('ai-consent.update');

    Route::get('home', ShowHomeController::class)->name('home.show');
    Route::get('appointments/{kind}/{id}', ShowAppointmentController::class)->name('appointments.show');
    Route::post('captures', StoreCaptureController::class)->name('captures.store');
    Route::post('reminders/{notification}/dismiss', DismissReminderController::class)->name('reminders.dismiss');
    Route::get('next-action', ShowNextActionController::class)->name('next-action.show');
    Route::get('overwhelmed', ShowOverwhelmedController::class)->name('overwhelmed.show');
    Route::patch('intentions/{appointment}/plan', AdjustPlanController::class)
        ->defaults('appointment_kind', AppointmentKind::Intention)
        ->name('intentions.plan');
    Route::patch('calendar-events/{appointment}/plan', AdjustPlanController::class)
        ->defaults('appointment_kind', AppointmentKind::CalendarEvent)
        ->name('calendar-events.plan');
    Route::patch('intentions/{intention}/deadline', ConfirmDeadlineController::class)->name('intentions.deadline');
    Route::patch('intentions/{intention}/clarification', ClarifyIntentionController::class)->name('intentions.clarification');
    Route::post('waiting-fors', StoreWaitingForController::class)->name('waiting-fors.store');
    Route::post('waiting-fors/{waitingFor}/respond', RespondToWaitingForController::class)->name('waiting-fors.respond');

    Route::post('sessions', StoreSessionController::class)->name('sessions.store');
    Route::get('sessions/current', ShowCurrentSessionController::class)->name('sessions.current');

    Route::prefix('sessions/{session}')->name('sessions.')->group(function (): void {
        Route::post('complete-step', CompleteStepController::class)->name('complete-step');
        Route::post('skip-step', SkipStepController::class)->name('skip-step');
        Route::post('pause', PauseSessionController::class)->name('pause');
        Route::post('resume', ResumeSessionController::class)->name('resume');
        Route::post('stuck', ReportStuckController::class)->name('stuck');
        Route::post('distracted', RecordDistractionController::class)->name('distracted');
        Route::post('stop', StopSessionController::class)->name('stop');
    });
});
