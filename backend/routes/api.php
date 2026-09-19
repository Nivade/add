<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ShowNextActionController;
use App\Http\Controllers\Api\V1\StoreCaptureController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('captures', StoreCaptureController::class)->name('captures.store');
    Route::get('next-action', ShowNextActionController::class)->name('next-action.show');
});
