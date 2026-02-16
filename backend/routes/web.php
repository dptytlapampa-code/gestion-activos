<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('login');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest')
    ->name('login.store');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::resource('institutions', InstitutionController::class)->except('show');
    Route::resource('services', ServiceController::class)->except('show');
    Route::resource('offices', OfficeController::class)->except('show');

    Route::resource('equipos', EquipoController::class);

    Route::prefix('api/search')->group(function (): void {
        Route::get('institutions', [SearchController::class, 'searchInstitutions']);
        Route::get('services', [SearchController::class, 'searchServices']);
        Route::get('offices', [SearchController::class, 'searchOffices']);
        Route::get('equipos', [SearchController::class, 'searchEquipos']);
    });
});
