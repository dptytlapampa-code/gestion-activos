<?php

namespace App\Providers;

use App\Models\Acta;
use App\Models\Document;
use App\Models\Equipo;
use App\Models\EquipoStatus;
use App\Models\Institution;
use App\Models\Mantenimiento;
use App\Models\Movimiento;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Policies\ActaPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\EquipoPolicy;
use App\Policies\EquipoStatusPolicy;
use App\Policies\InstitutionPolicy;
use App\Policies\MantenimientoPolicy;
use App\Policies\MovimientoPolicy;
use App\Policies\OfficePolicy;
use App\Policies\ServicePolicy;
use App\Policies\TipoEquipoPolicy;
use App\Policies\UserPolicy;
use App\Services\ActiveInstitutionContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! function_exists('system_config')) {
            require_once app_path('Helpers/SystemConfig.php');
        }
    }

    public function boot(): void
    {
        $this->ensureRuntimeDirectories();

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
        Gate::define('manage-system-settings', fn (User $user): bool => $user->hasRole(User::ROLE_SUPERADMIN));

        View::composer('*', function ($view): void {
            $view->with('settings', system_config());

            $user = auth()->user();

            if (! $user instanceof User) {
                return;
            }

            $user->loadMissing(['institution:id,nombre', 'permittedInstitutions:id,nombre']);

            /** @var ActiveInstitutionContext $activeInstitutionContext */
            $activeInstitutionContext = app(ActiveInstitutionContext::class);
            $accessibleInstitutions = $activeInstitutionContext->accessibleInstitutions($user);

            $view->with('authInstitutionContext', [
                'activeInstitutionId' => $activeInstitutionContext->currentId($user),
                'activeInstitution' => $activeInstitutionContext->activeInstitution($user),
                'primaryInstitution' => $user->institution,
                'accessibleInstitutions' => $accessibleInstitutions,
                'canSwitchInstitution' => $accessibleInstitutions->count() > 1,
            ]);
        });
    }

    private function ensureRuntimeDirectories(): void
    {
        foreach ([
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ] as $path) {
            File::ensureDirectoryExists($path);
        }
    }
}
