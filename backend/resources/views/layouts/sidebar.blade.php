@php
    use App\Models\Equipo;
    use App\Models\User;

    $systemConfig = system_config();
    $siteName = trim((string) ($siteName ?? $systemConfig->nombre_sistema));
    $sidebarHeaderTitle = $sidebarHeaderTitle ?? $systemConfig->sidebar_header_title ?? $siteName;
    $sidebarHeaderDescription = $sidebarHeaderDescription ?? $systemConfig->sidebar_header_description ?? 'Sistema de Gestion de Activos';
    $sidebarHeaderSubtitle = $sidebarHeaderSubtitle ?? $systemConfig->sidebar_header_subtitle ?? 'Panel administrativo';
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
        $store.appShell.isDesktopSidebarCollapsed() ? 'lg:w-[5.25rem]' : 'lg:w-72',
    ]"
    :aria-hidden="(!$store.appShell.isDesktop() && !$store.appShell.mobileSidebarOpen).toString()"
>
    <div class="app-sidebar !px-0 !py-4" style="background-color: {{ $systemConfig->color_sidebar }}">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-36 bg-gradient-to-b from-white/12 to-transparent"></div>

        <div class="relative flex flex-col gap-4 border-b border-white/10 pb-4" :class="$store.appShell.isDesktopSidebarCollapsed() ? 'px-2' : 'px-4'">
            <button
                type="button"
                class="inline-flex h-11 w-full items-center gap-3 rounded-2xl border border-white/15 bg-white/10 px-3 text-sm font-semibold text-white transition-all duration-200 hover:bg-white/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white/60"
                :class="$store.appShell.isDesktopSidebarCollapsed() ? 'justify-center px-0' : ''"
                @click="$store.appShell.toggleSidebar()"
                :aria-expanded="($store.appShell.isDesktop() ? $store.appShell.isDesktopSidebarOpen() : $store.appShell.mobileSidebarOpen).toString()"
                :aria-label="$store.appShell.isDesktop() ? ($store.appShell.isDesktopSidebarCollapsed() ? 'Abrir menu lateral' : 'Cerrar menu lateral') : 'Cerrar menu lateral'"
                :title="$store.appShell.isDesktop() ? ($store.appShell.isDesktopSidebarCollapsed() ? 'Abrir menu lateral' : 'Cerrar menu lateral') : 'Cerrar menu lateral'"
            >
                <x-icon name="menu" class="h-5 w-5" />
                <span x-cloak x-show="!$store.appShell.isDesktopSidebarCollapsed()" x-transition.opacity.duration.150ms>Cerrar menu</span>
            </button>

            <div class="app-sidebar-brand-card" :class="$store.appShell.isDesktopSidebarCollapsed() ? 'px-2 py-3' : 'px-4 py-4'">
                <div class="flex" :class="$store.appShell.isDesktopSidebarCollapsed() ? 'justify-center' : 'justify-start'">
                    <div
                        class="app-sidebar-brand-mark"
                        :class="$store.appShell.isDesktopSidebarCollapsed() ? 'min-h-[3.5rem] w-full max-w-[3.5rem] px-2 py-2' : 'min-h-[4.5rem] w-full max-w-[10rem]'"
                    >
                        @if ($logoInstitucionalUrl)
                            <img
                                src="{{ $logoInstitucionalUrl }}"
                                alt="Logo institucional"
                                class="app-sidebar-logo"
                                :class="$store.appShell.isDesktopSidebarCollapsed() ? 'max-h-10 max-w-[2.5rem]' : 'max-h-12 max-w-full'"
                            >
                        @else
                            <img
                                src="{{ $systemLogoUrl }}"
                                alt="Logo del sistema"
                                class="app-sidebar-logo"
                                :class="$store.appShell.isDesktopSidebarCollapsed() ? 'max-h-10 max-w-[2.5rem]' : 'max-h-12 max-w-full'"
                            >
                        @endif
                    </div>
                </div>

                <div x-cloak x-show="!$store.appShell.isDesktopSidebarCollapsed()" x-transition.opacity.duration.150ms class="app-sidebar-brand-copy">
                    <h1 class="text-[1.375rem] font-semibold leading-tight tracking-tight text-white">{{ $sidebarHeaderTitle }}</h1>
                    <p class="mt-1.5 text-sm leading-5 text-white/80">{{ $sidebarHeaderDescription }}</p>
                    <p class="mt-2 text-[11px] font-medium tracking-[0.08em] text-white/65">{{ $sidebarHeaderSubtitle }}</p>
                </div>
            </div>
        </div>

        <nav class="app-sidebar-nav mt-5 space-y-2 px-3 pr-2">
            <a
                href="{{ route('dashboard') }}"
                class="app-sidebar-link min-h-[3rem] rounded-2xl {{ request()->routeIs('dashboard') ? 'app-sidebar-link-active' : '' }}"
                :class="$store.appShell.isDesktopSidebarCollapsed() ? 'justify-center px-0' : ''"
                :aria-label="$store.appShell.isDesktopSidebarCollapsed() ? 'Panel' : null"
                :title="$store.appShell.isDesktopSidebarCollapsed() ? 'Panel' : ''"
                @click="$store.appShell.closeMobileSidebar()"
            >
                <x-sidebar.icon name="panel" class="h-5 w-5 shrink-0" />
                <span x-cloak x-show="!$store.appShell.isDesktopSidebarCollapsed()" x-transition.opacity.duration.150ms>Panel</span>
            </a>

            @if ($institucionesGroupVisible)
                <section class="space-y-1">
                    <button
                        type="button"
                        class="app-sidebar-group-button min-h-[3rem] rounded-2xl {{ $institucionesGroupActive ? 'app-sidebar-group-button-active' : '' }}"
                        :class="$store.appShell.isDesktopSidebarCollapsed() ? 'justify-center px-0' : ''"
                        @click="toggle('instituciones')"
                        :aria-expanded="(!$store.appShell.isDesktopSidebarCollapsed() && isOpen('instituciones')).toString()"
                        :aria-label="$store.appShell.isDesktopSidebarCollapsed() ? 'Instituciones' : null"
                        :title="$store.appShell.isDesktopSidebarCollapsed() ? 'Instituciones' : ''"
                        aria-controls="sidebar-group-instituciones"
                    >
                        <x-sidebar.icon name="instituciones" class="h-5 w-5 shrink-0" />
                        <span x-cloak x-show="!$store.appShell.isDesktopSidebarCollapsed()" x-transition.opacity.duration.150ms class="truncate">Instituciones</span>
                        <x-icon
                            name="chevron-down"
                            class="app-sidebar-group-chevron"
                            x-cloak
                            x-show="!$store.appShell.isDesktopSidebarCollapsed()"
                            x-bind:class="{ 'rotate-180': isOpen('instituciones') }"
                        />
                    </button>

                    <div
                        id="sidebar-group-instituciones"
                        class="app-sidebar-submenu {{ $institucionesGroupActive ? 'mt-1.5' : 'mt-0' }}"
                        :class="isOpen('instituciones') && !$store.appShell.isDesktopSidebarCollapsed() ? 'mt-1.5' : 'mt-0'"
                        style="max-height: {{ $institucionesGroupActive ? '560px' : '0px' }}; opacity: {{ $institucionesGroupActive ? '1' : '0' }};"
                        :style="submenuStyle('instituciones', 'institucionesPanel')"
                        :aria-hidden="($store.appShell.isDesktopSidebarCollapsed() || !isOpen('instituciones')).toString()"
                    >
                        <div x-ref="institucionesPanel" class="app-sidebar-submenu-inner">
                            @if ($canInstitutions)
                                <a
                                    href="{{ route('institutions.index') }}"
                                    class="app-sidebar-sublink min-h-[2.75rem] rounded-xl {{ request()->routeIs('institutions.*') ? 'app-sidebar-sublink-active' : '' }}"
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
                                    class="app-sidebar-sublink min-h-[2.75rem] rounded-xl {{ request()->routeIs('services.*') ? 'app-sidebar-sublink-active' : '' }}"
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
                                    class="app-sidebar-sublink min-h-[2.75rem] rounded-xl {{ request()->routeIs('offices.*') ? 'app-sidebar-sublink-active' : '' }}"
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
                        class="app-sidebar-group-button min-h-[3rem] rounded-2xl {{ $equiposGroupActive ? 'app-sidebar-group-button-active' : '' }}"
                        :class="$store.appShell.isDesktopSidebarCollapsed() ? 'justify-center px-0' : ''"
                        @click="toggle('equipos')"
                        :aria-expanded="(!$store.appShell.isDesktopSidebarCollapsed() && isOpen('equipos')).toString()"
                        :aria-label="$store.appShell.isDesktopSidebarCollapsed() ? 'Equipos' : null"
                        :title="$store.appShell.isDesktopSidebarCollapsed() ? 'Equipos' : ''"
                        aria-controls="sidebar-group-equipos"
                    >
                        <x-sidebar.icon name="equipos-group" class="h-5 w-5 shrink-0" />
                        <span x-cloak x-show="!$store.appShell.isDesktopSidebarCollapsed()" x-transition.opacity.duration.150ms class="truncate">Equipos</span>
                        <x-icon
                            name="chevron-down"
                            class="app-sidebar-group-chevron"
                            x-cloak
                            x-show="!$store.appShell.isDesktopSidebarCollapsed()"
                            x-bind:class="{ 'rotate-180': isOpen('equipos') }"
                        />
                    </button>

                    <div
                        id="sidebar-group-equipos"
                        class="app-sidebar-submenu {{ $equiposGroupActive ? 'mt-1.5' : 'mt-0' }}"
                        :class="isOpen('equipos') && !$store.appShell.isDesktopSidebarCollapsed() ? 'mt-1.5' : 'mt-0'"
                        style="max-height: {{ $equiposGroupActive ? '460px' : '0px' }}; opacity: {{ $equiposGroupActive ? '1' : '0' }};"
                        :style="submenuStyle('equipos', 'equiposPanel')"
                        :aria-hidden="($store.appShell.isDesktopSidebarCollapsed() || !isOpen('equipos')).toString()"
                    >
                        <div x-ref="equiposPanel" class="app-sidebar-submenu-inner">
                            @if ($canTiposEquipos)
                                <a
                                    href="{{ route('tipos-equipos.index') }}"
                                    class="app-sidebar-sublink min-h-[2.75rem] rounded-xl {{ request()->routeIs('tipos-equipos.*') ? 'app-sidebar-sublink-active' : '' }}"
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
                                    class="app-sidebar-sublink min-h-[2.75rem] rounded-xl {{ request()->routeIs('equipos.*') ? 'app-sidebar-sublink-active' : '' }}"
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
                    class="app-sidebar-link min-h-[3rem] rounded-2xl {{ request()->routeIs('actas.*') ? 'app-sidebar-link-active' : '' }}"
                    :class="$store.appShell.isDesktopSidebarCollapsed() ? 'justify-center px-0' : ''"
                    :aria-label="$store.appShell.isDesktopSidebarCollapsed() ? 'Actas' : null"
                    :title="$store.appShell.isDesktopSidebarCollapsed() ? 'Actas' : ''"
                    @click="$store.appShell.closeMobileSidebar()"
                >
                    <x-sidebar.icon name="actas" class="h-5 w-5 shrink-0" />
                    <span x-cloak x-show="!$store.appShell.isDesktopSidebarCollapsed()" x-transition.opacity.duration.150ms>Actas</span>
                </a>
            @endif

            @if ($administracionGroupVisible)
                <div class="app-sidebar-divider"></div>

                <section class="space-y-1">
                    <button
                        type="button"
                        class="app-sidebar-group-button min-h-[3rem] rounded-2xl {{ $administracionGroupActive ? 'app-sidebar-group-button-active' : '' }}"
                        :class="$store.appShell.isDesktopSidebarCollapsed() ? 'justify-center px-0' : ''"
                        @click="toggle('administracion')"
                        :aria-expanded="(!$store.appShell.isDesktopSidebarCollapsed() && isOpen('administracion')).toString()"
                        :aria-label="$store.appShell.isDesktopSidebarCollapsed() ? 'Administracion' : null"
                        :title="$store.appShell.isDesktopSidebarCollapsed() ? 'Administracion' : ''"
                        aria-controls="sidebar-group-administracion"
                    >
                        <x-sidebar.icon name="administracion" class="h-5 w-5 shrink-0" />
                        <span x-cloak x-show="!$store.appShell.isDesktopSidebarCollapsed()" x-transition.opacity.duration.150ms class="truncate">Administracion</span>
                        <x-icon
                            name="chevron-down"
                            class="app-sidebar-group-chevron"
                            x-cloak
                            x-show="!$store.appShell.isDesktopSidebarCollapsed()"
                            x-bind:class="{ 'rotate-180': isOpen('administracion') }"
                        />
                    </button>

                    <div
                        id="sidebar-group-administracion"
                        class="app-sidebar-submenu {{ $administracionGroupActive ? 'mt-1.5' : 'mt-0' }}"
                        :class="isOpen('administracion') && !$store.appShell.isDesktopSidebarCollapsed() ? 'mt-1.5' : 'mt-0'"
                        style="max-height: {{ $administracionGroupActive ? '460px' : '0px' }}; opacity: {{ $administracionGroupActive ? '1' : '0' }};"
                        :style="submenuStyle('administracion', 'administracionPanel')"
                        :aria-hidden="($store.appShell.isDesktopSidebarCollapsed() || !isOpen('administracion')).toString()"
                    >
                        <div x-ref="administracionPanel" class="app-sidebar-submenu-inner">
                            <a
                                href="{{ route('admin.users.index') }}"
                                class="app-sidebar-sublink min-h-[2.75rem] rounded-xl {{ request()->routeIs('admin.users.*') ? 'app-sidebar-sublink-active' : '' }}"
                                :tabindex="submenuTabIndex('administracion')"
                                @click="$store.appShell.closeMobileSidebar()"
                            >
                                <x-sidebar.icon name="usuarios" class="h-4 w-4 shrink-0" />
                                <span>Usuarios</span>
                            </a>

                            <a
                                href="{{ route('admin.audit.live') }}"
                                class="app-sidebar-sublink min-h-[2.75rem] rounded-xl {{ request()->routeIs('admin.audit.*') ? 'app-sidebar-sublink-active' : '' }}"
                                :tabindex="submenuTabIndex('administracion')"
                                @click="$store.appShell.closeMobileSidebar()"
                            >
                                <x-sidebar.icon name="auditoria" class="h-4 w-4 shrink-0" />
                                <span>Auditoria</span>
                            </a>

                            <a
                                href="{{ route('admin.configuracion.general.edit') }}"
                                class="app-sidebar-sublink min-h-[2.75rem] rounded-xl {{ request()->routeIs('admin.configuracion.general.*') ? 'app-sidebar-sublink-active' : '' }}"
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
