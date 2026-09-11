<?php

use App\Http\Controllers\Api\HardwareRecognitionController;
use Illuminate\Support\Facades\Route;

Route::prefix('hardware')->group(function () {
    Route::post('/recognition', [HardwareRecognitionController::class, 'store'])
        ->middleware('device.auth');
});
