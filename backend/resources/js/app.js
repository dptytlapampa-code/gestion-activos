import Alpine from 'alpinejs';

const SIDEBAR_STORAGE_KEY = 'gestion-activos.sidebar.collapsed';
const COLLAPSIBLE_STORAGE_KEY_PREFIX = 'gestion-activos.collapsible.';
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

const readCollapsiblePreference = (key) => {
    if (!key) {
        return null;
    }

    try {
        const rawValue = window.localStorage.getItem(`${COLLAPSIBLE_STORAGE_KEY_PREFIX}${key}`);

        if (rawValue === 'true') {
            return true;
        }

        if (rawValue === 'false') {
            return false;
        }
    } catch (error) {
        return null;
    }

    return null;
};

const persistCollapsiblePreference = (key, value) => {
    if (!key) {
        return;
    }

    try {
        window.localStorage.setItem(`${COLLAPSIBLE_STORAGE_KEY_PREFIX}${key}`, value ? 'true' : 'false');
    } catch (error) {
        // Ignore persistence failures to keep forms and details usable.
    }
};

const readShellConfig = () => {
    const body = document.body;

    return {
        context: body?.dataset.shellContext ?? 'default',
        desktopSidebarLock: body?.dataset.desktopSidebarLock ?? 'free',
    };
};

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.store('appShell', {
        sidebarCollapsed: readSidebarPreference(),
        mobileSidebarOpen: false,
        shellContext: 'default',
        desktopSidebarLock: 'free',

        init() {
            const shellConfig = readShellConfig();
            this.shellContext = shellConfig.context;
            this.desktopSidebarLock = shellConfig.desktopSidebarLock;

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

        isDesktopSidebarLocked() {
            return this.isDesktop() && this.desktopSidebarLock === 'collapsed';
        },

        isDesktopSidebarCollapsed() {
            return this.isDesktop() && (this.isDesktopSidebarLocked() || this.sidebarCollapsed);
        },

        isDesktopSidebarOpen() {
            return !this.isDesktopSidebarCollapsed();
        },

        toggleSidebar() {
            if (this.isDesktop()) {
                if (this.isDesktopSidebarLocked()) {
                    return;
                }

                this.toggleDesktopSidebar();

                return;
            }

            this.mobileSidebarOpen = !this.mobileSidebarOpen;
        },

        toggleDesktopSidebar() {
            if (!this.isDesktop() || this.isDesktopSidebarLocked()) {
                return;
            }

            this.sidebarCollapsed = !this.sidebarCollapsed;
            persistSidebarPreference(this.sidebarCollapsed);
        },

        openDesktopSidebar() {
            if (!this.isDesktopSidebarCollapsed() || this.isDesktopSidebarLocked()) {
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

    Alpine.data('collapsiblePanel', (config = {}) => ({
        open: Boolean(config.defaultOpen),
        defaultOpen: Boolean(config.defaultOpen),
        forceOpen: Boolean(config.forceOpen),
        persistKey: config.persistKey ? String(config.persistKey) : null,

        init() {
            if (this.forceOpen) {
                this.open = true;
            } else {
                const storedValue = readCollapsiblePreference(this.persistKey);

                if (storedValue !== null) {
                    this.open = storedValue;
                }
            }

            this.$watch('open', (value) => {
                persistCollapsiblePreference(this.persistKey, value);
            });
        },

        toggle() {
            this.open = !this.open;
        },
    }));

    Alpine.data('sidebarNavigation', (initialOpenGroups = {}) => ({
        openGroups: {
            instituciones: Boolean(initialOpenGroups.instituciones),
            equipos: Boolean(initialOpenGroups.equipos),
            administracion: Boolean(initialOpenGroups.administracion),
        },
        flyoutGroup: null,
        pendingOpenTimer: null,

        toggle(group) {
            const appShell = Alpine.store('appShell');

            if (appShell.isDesktopSidebarCollapsed()) {
                if (appShell.isDesktopSidebarLocked()) {
                    this.flyoutGroup = this.flyoutGroup === group ? null : group;

                    return;
                }

                appShell.openDesktopSidebar();
                this.queueGroupOpen(group);

                return;
            }

            this.flyoutGroup = null;
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

        showFlyout(group) {
            const appShell = Alpine.store('appShell');

            return appShell.isDesktopSidebarCollapsed() && appShell.isDesktopSidebarLocked() && this.flyoutGroup === group;
        },

        closeFlyout(group = null) {
            if (group === null || this.flyoutGroup === group) {
                this.flyoutGroup = null;
            }
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
            const appShell = Alpine.store('appShell');

            if (appShell.isDesktopSidebarCollapsed()) {
                return appShell.isDesktopSidebarLocked() && this.showFlyout(group) ? 0 : -1;
            }

            return !this.isOpen(group) ? -1 : 0;
        },
    }));
});

Alpine.start();
