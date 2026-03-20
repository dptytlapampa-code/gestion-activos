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

<aside
    id="app-sidebar"
    x-data="sidebarNavigation({
        instituciones: @js($institucionesGroupActive),
        equipos: @js($equiposGroupActive),
        administracion: @js($administracionGroupActive),
    })"
    class="app-sidebar-shell"
    :class="[
        $store.appShell.mobileSidebarOpen ? 'translate-x-0' : '-translate-x-[calc(100%+1.5rem)] lg:translate-x-0',
        $store.appShell.isCollapsedDesktop() ? 'lg:w-[5.5rem]' : 'lg:w-72',
    ]"
    :aria-hidden="(!$store.appShell.isDesktop() && ! $store.appShell.mobileSidebarOpen).toString()"
>
    <div class="app-sidebar" style="background-color: {{ system_config()->color_sidebar }}">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-36 bg-gradient-to-b from-white/12 to-transparent"></div>

        <div class="relative px-3 py-3" :class="$store.appShell.isCollapsedDesktop() ? 'px-2' : 'px-4'">
            <div class="flex items-start justify-between gap-3" :class="$store.appShell.isCollapsedDesktop() ? 'flex-col items-center' : ''">
                <div class="flex min-w-0 items-center gap-3" :class="$store.appShell.isCollapsedDesktop() ? 'flex-col text-center' : ''">
                    @if ($logoInstitucionalUrl)
                        <img
                            src="{{ $logoInstitucionalUrl }}"
                            alt="Logo institucional"
                            class="app-sidebar-logo"
                            :class="$store.appShell.isCollapsedDesktop() ? 'max-w-[3rem]' : 'max-w-[11rem]'"
                        >
                    @else
                        <img
                            src="{{ $systemLogoUrl }}"
                            alt="Logo del sistema"
                            class="app-sidebar-logo"
                            :class="$store.appShell.isCollapsedDesktop() ? 'max-w-[3rem]' : 'max-w-[11rem]'"
                        >
                    @endif

                    <div x-cloak x-show="!$store.appShell.isCollapsedDesktop()" x-transition.opacity.duration.150ms class="min-w-0">
                        <h1 class="truncate text-base font-semibold tracking-tight text-white">{{ $siteName }}</h1>
                        <p class="mt-1 text-xs font-medium uppercase tracking-[0.12em] text-white/70">Panel administrativo</p>
                    </div>
                </div>

                <button
                    type="button"
                    class="app-sidebar-toggle"
                    @click="$store.appShell.toggleSidebar()"
                    :aria-label="$store.appShell.isDesktop() ? ($store.appShell.isCollapsedDesktop() ? 'Expandir menu lateral' : 'Colapsar menu lateral') : 'Cerrar menu lateral'"
                    :title="$store.appShell.isDesktop() ? ($store.appShell.isCollapsedDesktop() ? 'Expandir menu lateral' : 'Colapsar menu lateral') : 'Cerrar menu lateral'"
                >
                    <x-icon name="panel-left-close" class="h-5 w-5" x-cloak x-show="$store.appShell.isDesktop() && ! $store.appShell.isCollapsedDesktop()" />
                    <x-icon name="panel-left-open" class="h-5 w-5" x-cloak x-show="$store.appShell.isDesktop() && $store.appShell.isCollapsedDesktop()" />
                    <x-icon name="x" class="h-5 w-5" x-cloak x-show="! $store.appShell.isDesktop()" />
                </button>
            </div>
        </div>

        <nav class="app-sidebar-nav">
            <a
                href="{{ route('dashboard') }}"
                class="app-sidebar-link group {{ request()->routeIs('dashboard') ? 'app-sidebar-link-active' : '' }}"
                :class="$store.appShell.isCollapsedDesktop() ? 'justify-center px-0' : ''"
                :aria-label="$store.appShell.isCollapsedDesktop() ? 'Panel' : null"
                :title="$store.appShell.isCollapsedDesktop() ? 'Panel' : ''"
                @click="$store.appShell.closeMobileSidebar()"
            >
                <x-sidebar.icon name="panel" class="h-5 w-5 shrink-0" />
                <span x-cloak x-show="!$store.appShell.isCollapsedDesktop()" x-transition.opacity.duration.150ms>Panel</span>
                <span x-cloak x-show="$store.appShell.isCollapsedDesktop()" class="app-sidebar-tooltip">Panel</span>
            </a>

            @if ($institucionesGroupVisible)
                <section class="space-y-1">
                    <button
                        type="button"
                        class="app-sidebar-group-button group {{ $institucionesGroupActive ? 'app-sidebar-group-button-active' : '' }}"
                        :class="$store.appShell.isCollapsedDesktop() ? 'justify-center px-0' : ''"
                        @click="toggle('instituciones')"
                        :aria-expanded="(!$store.appShell.isCollapsedDesktop() && isOpen('instituciones')).toString()"
                        :aria-label="$store.appShell.isCollapsedDesktop() ? 'Instituciones' : null"
                        :title="$store.appShell.isCollapsedDesktop() ? 'Instituciones' : ''"
                        aria-controls="sidebar-group-instituciones"
                    >
                        <x-sidebar.icon name="instituciones" class="h-5 w-5 shrink-0" />
                        <span x-cloak x-show="!$store.appShell.isCollapsedDesktop()" x-transition.opacity.duration.150ms class="truncate">Instituciones</span>
                        <x-icon
                            name="chevron-down"
                            class="app-sidebar-group-chevron"
                            x-cloak
                            x-show="!$store.appShell.isCollapsedDesktop()"
                            x-bind:class="{ 'rotate-180': isOpen('instituciones') }"
                        />
                        <span x-cloak x-show="$store.appShell.isCollapsedDesktop()" class="app-sidebar-tooltip">Instituciones</span>
                    </button>

                    <div
                        id="sidebar-group-instituciones"
                        class="app-sidebar-submenu {{ $institucionesGroupActive ? 'mt-1.5' : 'mt-0' }}"
                        :class="isOpen('instituciones') && ! $store.appShell.isCollapsedDesktop() ? 'mt-1.5' : 'mt-0'"
                        style="max-height: {{ $institucionesGroupActive ? '560px' : '0px' }}; opacity: {{ $institucionesGroupActive ? '1' : '0' }};"
                        :style="submenuStyle('instituciones', 'institucionesPanel')"
                        :aria-hidden="($store.appShell.isCollapsedDesktop() || !isOpen('instituciones')).toString()"
                    >
                        <div x-ref="institucionesPanel" class="app-sidebar-submenu-inner">
                            @if ($canInstitutions)
                                <a
                                    href="{{ route('institutions.index') }}"
                                    class="app-sidebar-sublink {{ request()->routeIs('institutions.*') ? 'app-sidebar-sublink-active' : '' }}"
                                    :tabindex="submenuTabIndex('instituciones')"
                                    @click="$store.appShell.closeMobileSidebar()"
                                >
                                    <x-sidebar.icon name="institucion-item" class="h-4 w-4 shrink-0" />
                                    <span>Instituciones</span>
                                </a>
                            @endif

                            @if ($canServices)
                                <a
                                    href="{{ route('services.index') }}"
                                    class="app-sidebar-sublink {{ request()->routeIs('services.*') ? 'app-sidebar-sublink-active' : '' }}"
                                    :tabindex="submenuTabIndex('instituciones')"
                                    @click="$store.appShell.closeMobileSidebar()"
                                >
                                    <x-sidebar.icon name="servicios" class="h-4 w-4 shrink-0" />
                                    <span>Servicios</span>
                                </a>
                            @endif

                            @if ($canOffices)
                                <a
                                    href="{{ route('offices.index') }}"
                                    class="app-sidebar-sublink {{ request()->routeIs('offices.*') ? 'app-sidebar-sublink-active' : '' }}"
                                    :tabindex="submenuTabIndex('instituciones')"
                                    @click="$store.appShell.closeMobileSidebar()"
                                >
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
                        class="app-sidebar-group-button group {{ $equiposGroupActive ? 'app-sidebar-group-button-active' : '' }}"
                        :class="$store.appShell.isCollapsedDesktop() ? 'justify-center px-0' : ''"
                        @click="toggle('equipos')"
                        :aria-expanded="(!$store.appShell.isCollapsedDesktop() && isOpen('equipos')).toString()"
                        :aria-label="$store.appShell.isCollapsedDesktop() ? 'Equipos' : null"
                        :title="$store.appShell.isCollapsedDesktop() ? 'Equipos' : ''"
                        aria-controls="sidebar-group-equipos"
                    >
                        <x-sidebar.icon name="equipos-group" class="h-5 w-5 shrink-0" />
                        <span x-cloak x-show="!$store.appShell.isCollapsedDesktop()" x-transition.opacity.duration.150ms class="truncate">Equipos</span>
                        <x-icon
                            name="chevron-down"
                            class="app-sidebar-group-chevron"
                            x-cloak
                            x-show="!$store.appShell.isCollapsedDesktop()"
                            x-bind:class="{ 'rotate-180': isOpen('equipos') }"
                        />
                        <span x-cloak x-show="$store.appShell.isCollapsedDesktop()" class="app-sidebar-tooltip">Equipos</span>
                    </button>

                    <div
                        id="sidebar-group-equipos"
                        class="app-sidebar-submenu {{ $equiposGroupActive ? 'mt-1.5' : 'mt-0' }}"
                        :class="isOpen('equipos') && ! $store.appShell.isCollapsedDesktop() ? 'mt-1.5' : 'mt-0'"
                        style="max-height: {{ $equiposGroupActive ? '460px' : '0px' }}; opacity: {{ $equiposGroupActive ? '1' : '0' }};"
                        :style="submenuStyle('equipos', 'equiposPanel')"
                        :aria-hidden="($store.appShell.isCollapsedDesktop() || !isOpen('equipos')).toString()"
                    >
                        <div x-ref="equiposPanel" class="app-sidebar-submenu-inner">
                            @if ($canTiposEquipos)
                                <a
                                    href="{{ route('tipos-equipos.index') }}"
                                    class="app-sidebar-sublink {{ request()->routeIs('tipos-equipos.*') ? 'app-sidebar-sublink-active' : '' }}"
                                    :tabindex="submenuTabIndex('equipos')"
                                    @click="$store.appShell.closeMobileSidebar()"
                                >
                                    <x-sidebar.icon name="tipos-equipo" class="h-4 w-4 shrink-0" />
                                    <span>Tipos de equipo</span>
                                </a>
                            @endif

                            @if ($canEquipos)
                                <a
                                    href="{{ route('equipos.index') }}"
                                    class="app-sidebar-sublink {{ request()->routeIs('equipos.*') ? 'app-sidebar-sublink-active' : '' }}"
                                    :tabindex="submenuTabIndex('equipos')"
                                    @click="$store.appShell.closeMobileSidebar()"
                                >
                                    <x-sidebar.icon name="equipos" class="h-4 w-4 shrink-0" />
                                    <span>Equipos</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </section>
            @endif

            @if ($canActas)
                <a
                    href="{{ route('actas.index') }}"
                    class="app-sidebar-link group {{ request()->routeIs('actas.*') ? 'app-sidebar-link-active' : '' }}"
                    :class="$store.appShell.isCollapsedDesktop() ? 'justify-center px-0' : ''"
                    :aria-label="$store.appShell.isCollapsedDesktop() ? 'Actas' : null"
                    :title="$store.appShell.isCollapsedDesktop() ? 'Actas' : ''"
                    @click="$store.appShell.closeMobileSidebar()"
                >
                    <x-sidebar.icon name="actas" class="h-5 w-5 shrink-0" />
                    <span x-cloak x-show="!$store.appShell.isCollapsedDesktop()" x-transition.opacity.duration.150ms>Actas</span>
                    <span x-cloak x-show="$store.appShell.isCollapsedDesktop()" class="app-sidebar-tooltip">Actas</span>
                </a>
            @endif

            @if ($administracionGroupVisible)
                <div class="app-sidebar-divider"></div>

                <section class="space-y-1">
                    <button
                        type="button"
                        class="app-sidebar-group-button group {{ $administracionGroupActive ? 'app-sidebar-group-button-active' : '' }}"
                        :class="$store.appShell.isCollapsedDesktop() ? 'justify-center px-0' : ''"
                        @click="toggle('administracion')"
                        :aria-expanded="(!$store.appShell.isCollapsedDesktop() && isOpen('administracion')).toString()"
                        :aria-label="$store.appShell.isCollapsedDesktop() ? 'Administracion' : null"
                        :title="$store.appShell.isCollapsedDesktop() ? 'Administracion' : ''"
                        aria-controls="sidebar-group-administracion"
                    >
                        <x-sidebar.icon name="administracion" class="h-5 w-5 shrink-0" />
                        <span x-cloak x-show="!$store.appShell.isCollapsedDesktop()" x-transition.opacity.duration.150ms class="truncate">Administracion</span>
                        <x-icon
                            name="chevron-down"
                            class="app-sidebar-group-chevron"
                            x-cloak
                            x-show="!$store.appShell.isCollapsedDesktop()"
                            x-bind:class="{ 'rotate-180': isOpen('administracion') }"
                        />
                        <span x-cloak x-show="$store.appShell.isCollapsedDesktop()" class="app-sidebar-tooltip">Administracion</span>
                    </button>

                    <div
                        id="sidebar-group-administracion"
                        class="app-sidebar-submenu {{ $administracionGroupActive ? 'mt-1.5' : 'mt-0' }}"
                        :class="isOpen('administracion') && ! $store.appShell.isCollapsedDesktop() ? 'mt-1.5' : 'mt-0'"
                        style="max-height: {{ $administracionGroupActive ? '460px' : '0px' }}; opacity: {{ $administracionGroupActive ? '1' : '0' }};"
                        :style="submenuStyle('administracion', 'administracionPanel')"
                        :aria-hidden="($store.appShell.isCollapsedDesktop() || !isOpen('administracion')).toString()"
                    >
                        <div x-ref="administracionPanel" class="app-sidebar-submenu-inner">
                            <a
                                href="{{ route('admin.users.index') }}"
                                class="app-sidebar-sublink {{ request()->routeIs('admin.users.*') ? 'app-sidebar-sublink-active' : '' }}"
                                :tabindex="submenuTabIndex('administracion')"
                                @click="$store.appShell.closeMobileSidebar()"
                            >
                                <x-sidebar.icon name="usuarios" class="h-4 w-4 shrink-0" />
                                <span>Usuarios</span>
                            </a>

                            <a
                                href="{{ route('admin.audit.index') }}"
                                class="app-sidebar-sublink {{ request()->routeIs('admin.audit.*') ? 'app-sidebar-sublink-active' : '' }}"
                                :tabindex="submenuTabIndex('administracion')"
                                @click="$store.appShell.closeMobileSidebar()"
                            >
                                <x-sidebar.icon name="auditoria" class="h-4 w-4 shrink-0" />
                                <span>Auditoria</span>
                            </a>

                            <a
                                href="{{ route('admin.configuracion.general.edit') }}"
                                class="app-sidebar-sublink {{ request()->routeIs('admin.configuracion.general.*') ? 'app-sidebar-sublink-active' : '' }}"
                                :tabindex="submenuTabIndex('administracion')"
                                @click="$store.appShell.closeMobileSidebar()"
                            >
                                <x-sidebar.icon name="configuracion" class="h-4 w-4 shrink-0" />
                                <span>Configuracion general</span>
                            </a>
                        </div>
                    </div>
                </section>
            @endif
        </nav>
    </div>
</aside>
