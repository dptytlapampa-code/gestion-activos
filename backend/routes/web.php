<?php

use App\Http\Controllers\ActaController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\SystemSettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\EquipoPublicController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TipoEquipoController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthenticatedSessionController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware(['guest', 'throttle:5,1'])->name('login.store');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/equipos/public/{uuid}', [EquipoPublicController::class, 'show'])->middleware('web')->name('equipos.public.show');

Route::middleware('auth')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('institutions', InstitutionController::class)->except('show');
    Route::resource('services', ServiceController::class)->except('show');
    Route::resource('offices', OfficeController::class)->except('show');
    Route::resource('tipos-equipos', TipoEquipoController::class)->parameters(['tipos-equipos' => 'tipo_equipo']);

    Route::resource('equipos', EquipoController::class);
    Route::get('movimientos/create', [MovimientoController::class, 'create'])->name('movimientos.create');
    Route::post('equipos/{equipo}/movimientos', [MovimientoController::class, 'store'])->name('equipos.movimientos.store');

    Route::get('mantenimientos', [MantenimientoController::class, 'index'])->name('mantenimientos.index');
    Route::post('equipos/{equipo}/mantenimientos', [MantenimientoController::class, 'store'])->name('equipos.mantenimientos.store');
    Route::get('mantenimientos/{mantenimiento}/edit', [MantenimientoController::class, 'edit'])->name('mantenimientos.edit');
    Route::match(['put', 'patch'], 'mantenimientos/{mantenimiento}', [MantenimientoController::class, 'update'])->name('mantenimientos.update');
    Route::delete('mantenimientos/{mantenimiento}', [MantenimientoController::class, 'destroy'])->name('mantenimientos.destroy');

    Route::get('actas', [ActaController::class, 'index'])->name('actas.index');
    Route::get('actas/create', [ActaController::class, 'create'])->name('actas.create');
    Route::post('actas', [ActaController::class, 'store'])->name('actas.store');
    Route::post('actas/{acta}/anular', [ActaController::class, 'anular'])->name('actas.anular');
    Route::get('actas/{acta}', [ActaController::class, 'show'])->name('actas.show');
    Route::get('actas/{acta}/pdf', [ActaController::class, 'descargar'])->name('actas.download');

    Route::post('equipos/{equipo}/documents', [DocumentController::class, 'storeForEquipo'])->name('equipos.documents.store');
    Route::post('movimientos/{movimiento}/documents', [DocumentController::class, 'storeForMovimiento'])->name('movimientos.documents.store');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle_active');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset_password');
        Route::get('auditoria', [AuditLogController::class, 'index'])->name('audit.index');

        Route::get('configuracion/general', [SystemSettingsController::class, 'index'])->name('configuracion.general.edit');
        Route::match(['put', 'patch'], 'configuracion/general', [SystemSettingsController::class, 'update'])->name('configuracion.general.update');
    });

    Route::prefix('api/search')->group(function (): void {
        Route::get('institutions', [SearchController::class, 'searchInstitutions'])->name('api.search.institutions');
        Route::get('services', [SearchController::class, 'searchServices'])->name('api.search.services');
        Route::get('offices', [SearchController::class, 'searchOffices'])->name('api.search.offices');
        Route::get('equipos', [SearchController::class, 'searchEquipos'])->name('api.search.equipos');
        Route::get('acta-equipos', [SearchController::class, 'searchActaEquipos'])->name('api.search.acta-equipos');
    });
});

Route::get('/api/search/tipos-equipos', [SearchController::class, 'tiposEquipos'])->middleware('auth')->name('api.search.tipos-equipos');


