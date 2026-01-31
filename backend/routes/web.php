<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\ProfileController;
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

Route::get('/', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::get('/profile', [ProfileController::class, 'show'])
    ->middleware('auth')
    ->name('profile.show');

Route::post('/profile/theme', [ProfileController::class, 'updateTheme'])
    ->middleware('auth')
    ->name('profile.theme');

Route::middleware(['auth'])->group(function () {
    Route::resource('institutions', InstitutionController::class)
        ->parameters(['institutions' => 'institution_id']);

    Route::get('institutions/{institution_id}/services', [ServiceController::class, 'byInstitution'])
        ->name('institutions.services');
    Route::get('services/{service_id}/offices', [OfficeController::class, 'byService'])
        ->name('services.offices');
});
