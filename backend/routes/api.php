<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CompleteStepController;
use App\Http\Controllers\Api\V1\PauseSessionController;
use App\Http\Controllers\Api\V1\RecordDistractionController;
use App\Http\Controllers\Api\V1\ReportStuckController;
use App\Http\Controllers\Api\V1\ResumeSessionController;
use App\Http\Controllers\Api\V1\ShowCurrentSessionController;
use App\Http\Controllers\Api\V1\ShowNextActionController;
use App\Http\Controllers\Api\V1\SkipStepController;
use App\Http\Controllers\Api\V1\StopSessionController;
use App\Http\Controllers\Api\V1\StoreCaptureController;
use App\Http\Controllers\Api\V1\StoreSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('captures', StoreCaptureController::class)->name('captures.store');
    Route::get('next-action', ShowNextActionController::class)->name('next-action.show');

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
