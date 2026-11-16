<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EquipoController;
use App\Http\Controllers\Api\MantenimientoController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login');

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::get('user', [AuthController::class, 'show'])->name('user');
        Route::post('logout', [AuthController::class, 'destroy'])->name('logout');

        Route::middleware(CheckAbilities::class.':read')->group(function () {
            Route::apiResource('equipos', EquipoController::class)->only(['index', 'show'])->parameters(['equipos' => 'equipo']);
            Route::apiResource('mantenimientos', MantenimientoController::class)->only(['index', 'show'])->parameters(['mantenimientos' => 'mantenimiento']);
        });
    });
});
