<?php

declare(strict_types=1);

use App\Enums\AppointmentKind;
use App\Http\Controllers\Web\AdjustPlanController;
use App\Http\Controllers\Web\ClarifyIntentionController;
use App\Http\Controllers\Web\CompleteStepController;
use App\Http\Controllers\Web\ConfirmDeadlineController;
use App\Http\Controllers\Web\DismissReminderController;
use App\Http\Controllers\Web\PauseFocusController;
use App\Http\Controllers\Web\PromoteIntentionToCommitmentController;
use App\Http\Controllers\Web\RecordDistractionController;
use App\Http\Controllers\Web\ReportStuckController;
use App\Http\Controllers\Web\RespondToWaitingForController;
use App\Http\Controllers\Web\ResumeFocusController;
use App\Http\Controllers\Web\SetIntentionRecurrenceController;
use App\Http\Controllers\Web\ShowFocusController;
use App\Http\Controllers\Web\ShowHomeController;
use App\Http\Controllers\Web\ShowOverwhelmedController;
use App\Http\Controllers\Web\ShowWelcomeController;
use App\Http\Controllers\Web\SkipStepController;
use App\Http\Controllers\Web\StartFocusController;
use App\Http\Controllers\Web\StopFocusController;
use App\Http\Controllers\Web\StoreCaptureController;
use App\Http\Controllers\Web\StoreCommitmentController;
use App\Http\Controllers\Web\StoreFutureReminderController;
use App\Http\Controllers\Web\StoreRelativeFutureReminderController;
use App\Http\Controllers\Web\StoreWaitingForController;
use Illuminate\Support\Facades\Route;

Route::get('/', ShowWelcomeController::class)->name('welcome');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('home', ShowHomeController::class)->name('home');
    Route::post('captures', StoreCaptureController::class)->name('captures.store');
    Route::get('overwhelmed', ShowOverwhelmedController::class)->name('overwhelmed');
    Route::post('reminders/{notification}/dismiss', DismissReminderController::class)->name('reminders.dismiss');
    Route::post('intentions/{appointment}/plan', AdjustPlanController::class)
        ->defaults('appointment_kind', AppointmentKind::Intention)
        ->name('intentions.plan');
    Route::post('calendar-events/{appointment}/plan', AdjustPlanController::class)
        ->defaults('appointment_kind', AppointmentKind::CalendarEvent)
        ->name('calendar-events.plan');
    Route::post('intentions/{intention}/deadline', ConfirmDeadlineController::class)->name('intentions.deadline');
    Route::post('intentions/{intention}/clarification', ClarifyIntentionController::class)->name('intentions.clarification');
    Route::post('waiting-fors', StoreWaitingForController::class)->name('waiting-fors.store');
    Route::post('waiting-fors/{waitingFor}/respond', RespondToWaitingForController::class)->name('waiting-fors.respond');
    Route::post('commitments', StoreCommitmentController::class)->name('commitments.store');
    Route::post('intentions/{intention}/commitment', PromoteIntentionToCommitmentController::class)->name('intentions.commitment');
    Route::post('intentions/{intention}/recurrence', SetIntentionRecurrenceController::class)->name('intentions.recurrence');
    Route::post('future-reminders', StoreFutureReminderController::class)->name('future-reminders.store');
    Route::post('calendar-events/{calendarEvent}/future-reminder', StoreRelativeFutureReminderController::class)->name('calendar-events.future-reminder');

    Route::get('focus', ShowFocusController::class)->name('focus');
    Route::post('focus', StartFocusController::class)->name('focus.start');

    Route::prefix('focus/{session}')->name('focus.')->group(function (): void {
        Route::post('complete-step', CompleteStepController::class)->name('complete-step');
        Route::post('skip-step', SkipStepController::class)->name('skip-step');
        Route::post('pause', PauseFocusController::class)->name('pause');
        Route::post('resume', ResumeFocusController::class)->name('resume');
        Route::post('stuck', ReportStuckController::class)->name('stuck');
        Route::post('distracted', RecordDistractionController::class)->name('distracted');
        Route::post('stop', StopFocusController::class)->name('stop');
    });
});

require __DIR__.'/settings.php';
