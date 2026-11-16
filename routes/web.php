<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClimaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('clima', ClimaController::class)->middleware('throttle:15,1')->name('clima.index');
    Route::get('equipos', [EquipoController::class, 'index'])->name('equipos.index');
    Route::get('mantenimientos', [MantenimientoController::class, 'index'])->name('mantenimientos.index');
    Route::patch('mantenimientos/{mantenimiento}/estado', [MantenimientoController::class, 'updateEstado'])
        ->middleware('can:actualizar-estado')->name('mantenimientos.estado');
    Route::middleware('can:administrar')->group(function () {
        Route::get('usuarios', [UserController::class, 'index'])->name('usuarios.index');
        Route::post('usuarios', [UserController::class, 'store'])->name('usuarios.store');
        Route::resource('equipos', EquipoController::class)
            ->except(['show', 'index'])
            ->parameters(['equipos' => 'equipo']);
        Route::resource('mantenimientos', MantenimientoController::class)
            ->except(['show', 'index'])
            ->parameters(['mantenimientos' => 'mantenimiento']);
    });
});
