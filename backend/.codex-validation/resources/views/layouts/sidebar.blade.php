@php
    use App\Models\Equipo;
    use App\Models\User;

    $systemConfig = system_config();
    $siteName = $siteName ?? $systemConfig->nombre_sistema;
    $logoInstitucionalUrl = $logoInstitucionalUrl ?? $systemConfig->logo_url;
    $systemLogoUrl = $systemLogoUrl ?? $systemConfig->system_logo_url;
    $user = auth()->user();

    $canInstitutions = $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN);
    $canServices = $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO);
    $canOffices = $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO);
    $canTiposEquipos = $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER);
    $canEquipos = $user->can('viewAny', Equipo::class);
    $canActas = $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER);
    $canAdministracion = $user->hasRole(User::ROLE_SUPERADMIN);

    $institucionesGroupVisible = $canInstitutions || $canServices || $canOffices;
    $equiposGroupVisible = $canTiposEquipos || $canEquipos;
    $administracionGroupVisible = $canAdministracion;

    $institucionesGroupActive = request()->routeIs('institutions.*') || request()->routeIs('services.*') || request()->routeIs('offices.*');
    $equiposGroupActive = request()->routeIs('tipos-equipos.*') || request()->routeIs('equipos.*');
    $administracionGroupActive = request()->routeIs('admin.users.*') || request()->routeIs('admin.audit.*') || request()->routeIs('admin.configuracion.general.*');
@endphp

<aside class="app-sidebar relative overflow-hidden" style="background-color: {{ system_config()->color_sidebar }}">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-36 bg-gradient-to-b from-white/12 to-transparent"></div>

    <div class="relative space-y-3 px-4 py-3">
        @if ($logoInstitucionalUrl)
            <img src="{{ $logoInstitucionalUrl }}" alt="Logo institucional" class="h-12 w-auto rounded-lg bg-white/95 p-1.5 shadow-sm">
        @else
            <img src="{{ $systemLogoUrl }}" alt="Logo del sistema" class="h-12 w-auto rounded-lg bg-white/95 p-1.5 shadow-sm">
        @endif

        <div>
            <h1 class="text-base font-semibold tracking-tight text-white">{{ $siteName }}</h1>
            <p class="mt-1 text-xs font-medium uppercase tracking-[0.12em] text-white/70">Panel administrativo</p>
        </div>
    </div>

    <nav
        class="relative mt-4 flex-1 space-y-2.5"
        x-data="{
            openGroups: {
                instituciones: @js($institucionesGroupActive),
                equipos: @js($equiposGroupActive),
                administracion: @js($administracionGroupActive),
            },
            toggle(group) {
                this.openGroups[group] = !this.openGroups[group];
            },
            isOpen(group) {
                return this.openGroups[group] === true;
            },
            submenuStyle(group, panelRef) {
                if (!this.isOpen(group)) {
                    return 'max-height: 0px; opacity: 0;';
                }

                const panel = this.$refs[panelRef];

                if (!panel) {
                    return 'max-height: 0px; opacity: 0;';
                }

                return `max-height: ${panel.scrollHeight}px; opacity: 1;`;
            },
        }"
    >
        <a href="{{ route('dashboard') }}" class="app-sidebar-link {{ request()->routeIs('dashboard') ? 'app-sidebar-link-active' : '' }}">
            <x-sidebar.icon name="panel" class="h-5 w-5 shrink-0" />
            <span>Panel</span>
        </a>

        @if ($institucionesGroupVisible)
            <section class="space-y-1">
                <button
                    type="button"
                    class="app-sidebar-group-button {{ $institucionesGroupActive ? 'app-sidebar-group-button-active' : '' }}"
                    @click="toggle('instituciones')"
                    :aria-expanded="isOpen('instituciones').toString()"
                    aria-controls="sidebar-group-instituciones"
                >
                    <x-sidebar.icon name="instituciones" class="h-5 w-5 shrink-0" />
                    <span class="truncate">Instituciones</span>
                    <x-icon name="chevron-down" class="app-sidebar-group-chevron" x-bind:class="{ 'rotate-180': isOpen('instituciones') }" />
                </button>

                <div
                    id="sidebar-group-instituciones"
                    class="app-sidebar-submenu {{ $institucionesGroupActive ? 'mt-1.5' : 'mt-0' }}"
                    :class="isOpen('instituciones') ? 'mt-1.5' : 'mt-0'"
                    style="max-height: {{ $institucionesGroupActive ? '560px' : '0px' }}; opacity: {{ $institucionesGroupActive ? '1' : '0' }};"
                    :style="submenuStyle('instituciones', 'institucionesPanel')"
                    :aria-hidden="(!isOpen('instituciones')).toString()"
                >
                    <div x-ref="institucionesPanel" class="app-sidebar-submenu-inner">
                        @if ($canInstitutions)
                            <a href="{{ route('institutions.index') }}" class="app-sidebar-sublink {{ request()->routeIs('institutions.*') ? 'app-sidebar-sublink-active' : '' }}" :tabindex="isOpen('instituciones') ? 0 : -1">
                                <x-sidebar.icon name="institucion-item" class="h-4 w-4 shrink-0" />
                                <span>Instituciones</span>
                            </a>
                        @endif

                        @if ($canServices)
                            <a href="{{ route('services.index') }}" class="app-sidebar-sublink {{ request()->routeIs('services.*') ? 'app-sidebar-sublink-active' : '' }}" :tabindex="isOpen('instituciones') ? 0 : -1">
                                <x-sidebar.icon name="servicios" class="h-4 w-4 shrink-0" />
                                <span>Servicios</span>
                            </a>
                        @endif

                        @if ($canOffices)
                            <a href="{{ route('offices.index') }}" class="app-sidebar-sublink {{ request()->routeIs('offices.*') ? 'app-sidebar-sublink-active' : '' }}" :tabindex="isOpen('instituciones') ? 0 : -1">
                                <x-sidebar.icon name="oficinas" class="h-4 w-4 shrink-0" />
                                <span>Oficinas</span>
                            </a>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if ($equiposGroupVisible)
            <section class="space-y-1">
                <button
                    type="button"
                    class="app-sidebar-group-button {{ $equiposGroupActive ? 'app-sidebar-group-button-active' : '' }}"
                    @click="toggle('equipos')"
                    :aria-expanded="isOpen('equipos').toString()"
                    aria-controls="sidebar-group-equipos"
                >
                    <x-sidebar.icon name="equipos-group" class="h-5 w-5 shrink-0" />
                    <span class="truncate">Equipos</span>
                    <x-icon name="chevron-down" class="app-sidebar-group-chevron" x-bind:class="{ 'rotate-180': isOpen('equipos') }" />
                </button>

                <div
                    id="sidebar-group-equipos"
                    class="app-sidebar-submenu {{ $equiposGroupActive ? 'mt-1.5' : 'mt-0' }}"
                    :class="isOpen('equipos') ? 'mt-1.5' : 'mt-0'"
                    style="max-height: {{ $equiposGroupActive ? '460px' : '0px' }}; opacity: {{ $equiposGroupActive ? '1' : '0' }};"
                    :style="submenuStyle('equipos', 'equiposPanel')"
                    :aria-hidden="(!isOpen('equipos')).toString()"
                >
                    <div x-ref="equiposPanel" class="app-sidebar-submenu-inner">
                        @if ($canTiposEquipos)
                            <a href="{{ route('tipos-equipos.index') }}" class="app-sidebar-sublink {{ request()->routeIs('tipos-equipos.*') ? 'app-sidebar-sublink-active' : '' }}" :tabindex="isOpen('equipos') ? 0 : -1">
                                <x-sidebar.icon name="tipos-equipo" class="h-4 w-4 shrink-0" />
                                <span>Tipos de equipo</span>
                            </a>
                        @endif

                        @if ($canEquipos)
                            <a href="{{ route('equipos.index') }}" class="app-sidebar-sublink {{ request()->routeIs('equipos.*') ? 'app-sidebar-sublink-active' : '' }}" :tabindex="isOpen('equipos') ? 0 : -1">
                                <x-sidebar.icon name="equipos" class="h-4 w-4 shrink-0" />
                                <span>Equipos</span>
                            </a>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if ($canActas)
            <a href="{{ route('actas.index') }}" class="app-sidebar-link {{ request()->routeIs('actas.*') ? 'app-sidebar-link-active' : '' }}">
                <x-sidebar.icon name="actas" class="h-5 w-5 shrink-0" />
                <span>Actas</span>
            </a>
        @endif

        @if ($administracionGroupVisible)
            <div class="app-sidebar-divider"></div>

            <section class="space-y-1">
                <button
                    type="button"
                    class="app-sidebar-group-button {{ $administracionGroupActive ? 'app-sidebar-group-button-active' : '' }}"
                    @click="toggle('administracion')"
                    :aria-expanded="isOpen('administracion').toString()"
                    aria-controls="sidebar-group-administracion"
                >
                    <x-sidebar.icon name="administracion" class="h-5 w-5 shrink-0" />
                    <span class="truncate">Administracion</span>
                    <x-icon name="chevron-down" class="app-sidebar-group-chevron" x-bind:class="{ 'rotate-180': isOpen('administracion') }" />
                </button>

                <div
                    id="sidebar-group-administracion"
                    class="app-sidebar-submenu {{ $administracionGroupActive ? 'mt-1.5' : 'mt-0' }}"
                    :class="isOpen('administracion') ? 'mt-1.5' : 'mt-0'"
                    style="max-height: {{ $administracionGroupActive ? '460px' : '0px' }}; opacity: {{ $administracionGroupActive ? '1' : '0' }};"
                    :style="submenuStyle('administracion', 'administracionPanel')"
                    :aria-hidden="(!isOpen('administracion')).toString()"
                >
                    <div x-ref="administracionPanel" class="app-sidebar-submenu-inner">
                        <a href="{{ route('admin.users.index') }}" class="app-sidebar-sublink {{ request()->routeIs('admin.users.*') ? 'app-sidebar-sublink-active' : '' }}" :tabindex="isOpen('administracion') ? 0 : -1">
                            <x-sidebar.icon name="usuarios" class="h-4 w-4 shrink-0" />
                            <span>Usuarios</span>
                        </a>

                        <a href="{{ route('admin.audit.index') }}" class="app-sidebar-sublink {{ request()->routeIs('admin.audit.*') ? 'app-sidebar-sublink-active' : '' }}" :tabindex="isOpen('administracion') ? 0 : -1">
                            <x-sidebar.icon name="auditoria" class="h-4 w-4 shrink-0" />
                            <span>Auditoria</span>
                        </a>

                        <a href="{{ route('admin.configuracion.general.edit') }}" class="app-sidebar-sublink {{ request()->routeIs('admin.configuracion.general.*') ? 'app-sidebar-sublink-active' : '' }}" :tabindex="isOpen('administracion') ? 0 : -1">
                            <x-sidebar.icon name="configuracion" class="h-4 w-4 shrink-0" />
                            <span>Configuracion general</span>
                        </a>
                    </div>
                </div>
            </section>
        @endif
    </nav>
</aside>