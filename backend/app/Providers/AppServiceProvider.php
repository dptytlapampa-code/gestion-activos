<?php

namespace App\Providers;

use App\Models\Acta;
use App\Models\Document;
use App\Models\Equipo;
use App\Models\EquipoStatus;
use App\Models\Mantenimiento;
use App\Models\Institution;
use App\Models\Movimiento;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Policies\ActaPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\EquipoPolicy;
use App\Policies\EquipoStatusPolicy;
use App\Policies\MantenimientoPolicy;
use App\Policies\InstitutionPolicy;
use App\Policies\MovimientoPolicy;
use App\Policies\OfficePolicy;
use App\Policies\ServicePolicy;
use App\Policies\TipoEquipoPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Equipo::class, EquipoPolicy::class);
        Gate::policy(EquipoStatus::class, EquipoStatusPolicy::class);
        Gate::policy(Mantenimiento::class, MantenimientoPolicy::class);
        Gate::policy(Institution::class, InstitutionPolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(Office::class, OfficePolicy::class);
        Gate::policy(TipoEquipo::class, TipoEquipoPolicy::class);
        Gate::policy(Movimiento::class, MovimientoPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(Acta::class, ActaPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::define('manage-users', [UserPolicy::class, 'manageUsers']);
    }
}
