import Alpine from 'alpinejs';

const SIDEBAR_STORAGE_KEY = 'gestion-activos.sidebar.collapsed';
const desktopBreakpoint = window.matchMedia('(min-width: 1024px)');

const readSidebarPreference = () => {
    try {
        return window.localStorage.getItem(SIDEBAR_STORAGE_KEY) === 'true';
    } catch (error) {
        return false;
    }
};

const persistSidebarPreference = (value) => {
    try {
        window.localStorage.setItem(SIDEBAR_STORAGE_KEY, value ? 'true' : 'false');
    } catch (error) {
        // Ignore persistence failures to keep navigation usable.
    }
};

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.store('appShell', {
        sidebarCollapsed: readSidebarPreference(),
        mobileSidebarOpen: false,

        init() {
            const syncDesktopState = (event) => {
                if (event.matches) {
                    this.mobileSidebarOpen = false;
                }
            };

            if (typeof desktopBreakpoint.addEventListener === 'function') {
                desktopBreakpoint.addEventListener('change', syncDesktopState);
            } else {
                desktopBreakpoint.addListener(syncDesktopState);
            }
        },

        isDesktop() {
            return desktopBreakpoint.matches;
        },

        isDesktopSidebarCollapsed() {
            return this.isDesktop() && this.sidebarCollapsed;
        },

        isDesktopSidebarOpen() {
            return !this.isDesktopSidebarCollapsed();
        },

        toggleSidebar() {
            if (this.isDesktop()) {
                this.toggleDesktopSidebar();

                return;
            }

            this.mobileSidebarOpen = !this.mobileSidebarOpen;
        },

        toggleDesktopSidebar() {
            if (!this.isDesktop()) {
                return;
            }

            this.sidebarCollapsed = !this.sidebarCollapsed;
            persistSidebarPreference(this.sidebarCollapsed);
        },

        openDesktopSidebar() {
            if (!this.isDesktopSidebarCollapsed()) {
                return;
            }

            this.sidebarCollapsed = false;
            persistSidebarPreference(false);
        },

        closeMobileSidebar() {
            this.mobileSidebarOpen = false;
        },
    });

    Alpine.store('appShell').init();

    Alpine.data('sidebarNavigation', (initialOpenGroups = {}) => ({
        openGroups: {
            instituciones: Boolean(initialOpenGroups.instituciones),
            equipos: Boolean(initialOpenGroups.equipos),
            administracion: Boolean(initialOpenGroups.administracion),
        },
        pendingOpenTimer: null,

        toggle(group) {
            if (Alpine.store('appShell').isDesktopSidebarCollapsed()) {
                Alpine.store('appShell').openDesktopSidebar();
                this.queueGroupOpen(group);

                return;
            }

            this.openGroups[group] = !this.openGroups[group];
        },

        queueGroupOpen(group) {
            if (this.pendingOpenTimer) {
                window.clearTimeout(this.pendingOpenTimer);
            }

            this.pendingOpenTimer = window.setTimeout(() => {
                this.openGroups[group] = true;
                this.pendingOpenTimer = null;
            }, 160);
        },

        isOpen(group) {
            return this.openGroups[group] === true;
        },

        submenuStyle(group, panelRef) {
            if (Alpine.store('appShell').isDesktopSidebarCollapsed() || !this.isOpen(group)) {
                return 'max-height: 0px; opacity: 0;';
            }

            const panel = this.$refs[panelRef];

            if (!panel) {
                return 'max-height: 0px; opacity: 0;';
            }

            return `max-height: ${panel.scrollHeight}px; opacity: 1;`;
        },

        submenuTabIndex(group) {
            return Alpine.store('appShell').isDesktopSidebarCollapsed() || !this.isOpen(group) ? -1 : 0;
        },
    }));
});

Alpine.start();
