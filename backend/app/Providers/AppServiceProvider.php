<?php

namespace App\Providers;

use App\Models\Equipo;
use App\Policies\EquipoPolicy;
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
    }
}
