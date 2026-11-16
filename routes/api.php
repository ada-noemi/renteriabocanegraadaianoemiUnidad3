<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login');

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::get('user', [AuthController::class, 'show'])->name('user');
        Route::post('logout', [AuthController::class, 'destroy'])->name('logout');
    });
});
