@once
    <style>
        .mt-panel {
            position: relative;
            overflow: hidden;
            border-color: rgba(221, 228, 238, 0.98);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(249, 251, 255, 0.98) 100%);
        }

        .mt-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 1.5rem;
            width: 7rem;
            height: 1px;
            background: linear-gradient(90deg, rgba(79, 70, 229, 0.22) 0%, rgba(148, 163, 184, 0) 100%);
            pointer-events: none;
        }

        .mt-panel-soft {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(248, 250, 252, 0.97) 100%);
        }

        .mt-panel-ready {
            border-color: rgba(167, 243, 208, 0.95);
            background: linear-gradient(180deg, rgba(247, 255, 251, 0.99) 0%, rgba(236, 253, 245, 0.96) 100%);
        }

        .mt-panel-warm {
            border-color: rgba(253, 230, 138, 0.95);
            background: linear-gradient(180deg, rgba(255, 251, 235, 0.98) 0%, rgba(255, 247, 237, 0.96) 100%);
        }

        .mt-panel-danger {
            border-color: rgba(254, 205, 211, 0.95);
            background: linear-gradient(180deg, rgba(255, 251, 251, 0.99) 0%, rgba(255, 241, 242, 0.96) 100%);
        }

        .mt-card-lift {
            transition: none;
        }

        .mt-card-lift:hover {
            transform: none;
            box-shadow: var(--app-panel-shadow);
        }

        .mt-card-lift:focus-visible,
        .mt-action-card:focus-visible,
        .mt-quick-filter:focus-visible {
            outline: 2px solid rgba(var(--primary-color-rgb), 0.34);
            outline-offset: 3px;
        }

        .mt-action-card {
            display: flex;
            height: 100%;
            flex-direction: column;
            border: 1px solid rgba(226, 232, 240, 0.96);
            border-radius: 1.75rem;
            padding: 1.25rem;
            text-align: left;
            box-shadow: var(--app-subpanel-shadow);
            transition: none;
        }

        .mt-action-card:hover {
            transform: none;
            box-shadow: var(--app-subpanel-shadow);
        }

        .mt-action-card-primary {
            border-color: rgba(199, 210, 254, 0.95);
            background: linear-gradient(180deg, rgba(238, 242, 255, 0.98) 0%, rgba(224, 231, 255, 0.94) 100%);
        }

        .mt-action-card-soft {
            border-color: rgba(226, 232, 240, 0.98);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(248, 250, 252, 0.95) 100%);
        }

        .mt-action-card-ready {
            border-color: rgba(167, 243, 208, 0.95);
            background: linear-gradient(180deg, rgba(240, 253, 244, 0.98) 0%, rgba(220, 252, 231, 0.94) 100%);
        }

        .mt-kpi-card {
            border: 1px solid rgba(226, 232, 240, 0.96);
            border-radius: 1rem;
            padding: 1rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(248, 250, 252, 0.96) 100%);
            box-shadow: var(--app-subpanel-shadow);
            transition: none;
        }

        .mt-kpi-card:hover {
            transform: none;
            box-shadow: var(--app-subpanel-shadow);
        }

        .mt-kpi-card-ready {
            border-color: rgba(167, 243, 208, 0.95);
            background: linear-gradient(180deg, rgba(247, 255, 251, 0.99) 0%, rgba(236, 253, 245, 0.95) 100%);
        }

        .mt-kpi-card-warm {
            border-color: rgba(253, 230, 138, 0.95);
            background: linear-gradient(180deg, rgba(255, 251, 235, 0.99) 0%, rgba(255, 247, 237, 0.95) 100%);
        }

        .mt-kpi-card-danger {
            border-color: rgba(254, 205, 211, 0.95);
            background: linear-gradient(180deg, rgba(255, 251, 251, 0.99) 0%, rgba(255, 241, 242, 0.95) 100%);
        }

        .mt-icon-chip {
            display: inline-flex;
            height: 2.5rem;
            width: 2.5rem;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(226, 232, 240, 0.96);
            border-radius: 1rem;
            background-color: rgba(255, 255, 255, 0.84);
            box-shadow: 0 10px 24px -22px rgba(15, 23, 42, 0.52);
        }

        .mt-icon-chip-sm {
            height: 2rem;
            width: 2rem;
            border-radius: 0.75rem;
        }

        .mt-quick-filter {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(203, 213, 225, 0.95);
            border-radius: 9999px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
            background-color: rgba(255, 255, 255, 0.98);
            box-shadow: 0 12px 20px -24px rgba(15, 23, 42, 0.46);
            transition: none;
        }

        .mt-quick-filter:hover {
            transform: none;
            border-color: rgba(148, 163, 184, 0.95);
            background-color: rgba(248, 250, 252, 0.98);
        }

        .mt-quick-filter-active {
            border-color: rgba(15, 23, 42, 0.98);
            color: #fff;
            background-color: rgba(15, 23, 42, 0.98);
            box-shadow: 0 18px 32px -24px rgba(15, 23, 42, 0.58);
        }

        .mt-filter-count {
            border-radius: 9999px;
            padding: 0.125rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #334155;
            background-color: rgba(226, 232, 240, 0.9);
        }

        .mt-quick-filter-active .mt-filter-count {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.16);
        }

        .mt-ticket-card {
            border: 1px solid rgba(226, 232, 240, 0.96);
            border-radius: 1rem;
            padding: 1rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(249, 250, 251, 0.96) 100%);
            box-shadow: var(--app-subpanel-shadow);
        }

        .mt-state-neutral {
            border-color: rgba(226, 232, 240, 0.96);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(248, 250, 252, 0.96) 100%);
        }

        .mt-state-ready {
            border-color: rgba(167, 243, 208, 0.95);
            background: linear-gradient(180deg, rgba(247, 255, 251, 0.99) 0%, rgba(236, 253, 245, 0.95) 100%);
        }

        .mt-state-repair {
            border-color: rgba(199, 210, 254, 0.95);
            background: linear-gradient(180deg, rgba(245, 247, 255, 0.99) 0%, rgba(238, 242, 255, 0.95) 100%);
        }

        .mt-state-closed {
            border-color: rgba(203, 213, 225, 0.95);
            background: linear-gradient(180deg, rgba(249, 250, 251, 0.99) 0%, rgba(241, 245, 249, 0.95) 100%);
        }

        .mt-state-cancelled {
            border-color: rgba(254, 205, 211, 0.95);
            background: linear-gradient(180deg, rgba(255, 251, 251, 0.99) 0%, rgba(255, 241, 242, 0.95) 100%);
        }

        .mt-inline-meta {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.875rem;
            line-height: 1.5rem;
        }

        .mt-note-block {
            border: 1px solid rgba(226, 232, 240, 0.96);
            border-radius: 1rem;
            padding: 1rem;
            font-size: 0.875rem;
            background-color: rgba(248, 250, 252, 0.84);
            box-shadow: var(--app-subpanel-shadow);
        }

        .mt-trace-item {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            border: 1px solid rgba(226, 232, 240, 0.96);
            border-radius: 0.75rem;
            padding: 0.75rem;
            background-color: rgba(248, 250, 252, 0.86);
        }

        .mt-input,
        .mt-filter-shell .app-input {
            transition: none;
        }

        .mt-input:hover,
        .mt-filter-shell .app-input:hover {
            border-color: rgba(197, 210, 223, 0.95);
            background-color: rgba(255, 255, 255, 0.98);
        }

        .mt-input:focus,
        .mt-filter-shell .app-input:focus {
            box-shadow: 0 0 0 4px rgba(var(--primary-color-rgb), 0.16);
        }

        .mt-primary-action {
            box-shadow: 0 18px 30px -22px rgba(79, 70, 229, 0.62);
        }

        .mt-primary-action:hover {
            box-shadow: 0 18px 30px -22px rgba(79, 70, 229, 0.62);
        }

        .mt-operational-shell {
            gap: 1rem;
        }

        .mt-operational-panel {
            position: relative;
            overflow: hidden;
            border-color: rgba(221, 228, 238, 0.98);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(249, 251, 255, 0.98) 100%);
            box-shadow: var(--app-panel-shadow);
        }

        .mt-operational-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.04) 0%, rgba(255, 255, 255, 0) 42%);
        }

        .mt-mode-grid {
            display: grid;
            gap: 0.75rem;
        }

        @media (min-width: 1024px) {
            .mt-mode-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .mt-mode-card {
            position: relative;
            border: 1px solid rgba(226, 232, 240, 0.96);
            border-radius: 1.4rem;
            padding: 1rem;
            text-align: left;
            background: rgba(255, 255, 255, 0.96);
            transition: none;
            box-shadow: var(--app-subpanel-shadow);
        }

        .mt-mode-card:hover {
            transform: none;
            box-shadow: var(--app-subpanel-shadow);
        }

        .mt-mode-card-active {
            color: #fff;
            border-color: rgba(15, 23, 42, 0.98);
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.98) 0%, rgba(30, 41, 59, 0.98) 100%);
        }

        .mt-mode-card-active .mt-mode-card-note,
        .mt-mode-card-active .mt-mode-card-kicker {
            color: rgba(255, 255, 255, 0.8);
        }

        .mt-mode-card-kicker {
            display: block;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            color: #64748b;
        }

        .mt-mode-card-note {
            display: block;
            margin-top: 0.35rem;
            font-size: 0.875rem;
            color: #475569;
        }

        .mt-essential-panel {
            border: 1px solid rgba(226, 232, 240, 0.96);
            border-radius: 1.5rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: var(--app-subpanel-shadow);
        }

        .mt-inline-note {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(199, 210, 254, 0.94);
            border-radius: 9999px;
            padding: 0.4rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #4338ca;
            background: rgba(238, 242, 255, 0.95);
        }

        .mt-form-divider {
            border-top: 1px solid rgba(226, 232, 240, 0.92);
            padding-top: 1rem;
        }

        .mt-form-kicker {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            color: #64748b;
        }

        .mt-form-title {
            margin-top: 0.35rem;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #0f172a;
        }

        .mt-form-muted {
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: #64748b;
        }

        .mt-dense-grid {
            display: grid;
            gap: 0.75rem;
        }

        @media (min-width: 768px) {
            .mt-dense-grid-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .mt-dense-grid-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .mt-search-results {
            max-height: 20rem;
            overflow: auto;
        }

        .mt-sticky-submit {
            position: sticky;
            bottom: 0.75rem;
            z-index: 20;
            margin-top: 1rem;
        }

        .mt-sticky-submit-bar {
            border: 1px solid rgba(203, 213, 225, 0.96);
            border-radius: 1.4rem;
            padding: 0.9rem 1rem;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(14px);
            box-shadow: 0 24px 40px -28px rgba(15, 23, 42, 0.52);
        }

        .mt-toolbar-panel {
            border: 1px solid rgba(217, 226, 236, 0.96);
            border-radius: 1.5rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 100%);
            box-shadow: var(--app-panel-shadow);
        }

        .mt-toolbar-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(203, 213, 225, 0.96);
            border-radius: 9999px;
            padding: 0.6rem 0.95rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
            background: rgba(255, 255, 255, 0.98);
            transition: none;
        }

        .mt-toolbar-toggle:hover {
            border-color: rgba(203, 213, 225, 0.96);
            background: rgba(255, 255, 255, 0.98);
        }

        .mt-kpi-card-compact {
            padding: 0.9rem 1rem;
            border-radius: 1.1rem;
        }

        .mt-kpi-card-compact p:last-child {
            line-height: 1.35rem;
        }

        .mt-search-shell {
            border: 1px solid rgba(217, 226, 236, 0.96);
            border-radius: 1.75rem;
            padding: 1rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(248, 250, 252, 0.96) 100%);
            box-shadow: var(--app-panel-shadow);
        }

        .mt-search-grid {
            display: grid;
            gap: 0.75rem;
            align-items: center;
        }

        @media (min-width: 1024px) {
            .mt-search-grid {
                grid-template-columns: minmax(0, 1fr) auto;
            }
        }

        .mt-tray-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .mt-tray-tab {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(203, 213, 225, 0.96);
            border-radius: 9999px;
            padding: 0.6rem 0.95rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #475569;
            background: rgba(255, 255, 255, 0.96);
            white-space: nowrap;
        }

        .mt-tray-tab-active {
            border-color: rgba(15, 23, 42, 0.14);
            color: #0f172a;
            background: rgba(241, 245, 249, 0.98);
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.04);
        }

        .mt-tray-tab-count {
            border-radius: 9999px;
            padding: 0.125rem 0.45rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #475569;
            background: rgba(226, 232, 240, 0.94);
        }

        .mt-tray-tab-active .mt-tray-tab-count {
            color: #0f172a;
            background: rgba(203, 213, 225, 0.9);
        }

        .mt-queue-board {
            border: 1px solid rgba(217, 226, 236, 0.96);
            border-radius: 1.5rem;
            padding: 1rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(248, 250, 252, 0.96) 100%);
            box-shadow: var(--app-panel-shadow);
        }

        .mt-queue-stack {
            display: grid;
            gap: 0.85rem;
        }

        .mt-queue-card {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            min-height: 11.25rem;
            border: 1px solid rgba(226, 232, 240, 0.96);
            border-radius: 1.25rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--app-subpanel-shadow);
        }

        @media (min-width: 1024px) {
            .mt-queue-card {
                flex-direction: row;
                align-items: stretch;
                justify-content: space-between;
                gap: 1.25rem;
            }
        }

        .mt-queue-card-state-active {
            border-color: rgba(217, 226, 236, 0.96);
        }

        .mt-queue-card-state-ready {
            border-color: rgba(187, 247, 208, 0.92);
            background: linear-gradient(180deg, rgba(252, 255, 253, 0.99) 0%, rgba(244, 251, 246, 0.96) 100%);
        }

        .mt-queue-card-state-pending {
            border-color: rgba(253, 230, 138, 0.92);
            background: linear-gradient(180deg, rgba(255, 253, 247, 0.99) 0%, rgba(255, 250, 235, 0.96) 100%);
        }

        .mt-queue-card-state-final {
            border-color: rgba(203, 213, 225, 0.96);
            background: linear-gradient(180deg, rgba(250, 251, 252, 0.99) 0%, rgba(244, 246, 248, 0.96) 100%);
        }

        .mt-queue-card-main {
            display: flex;
            min-width: 0;
            flex: 1 1 auto;
            flex-direction: column;
            gap: 0.85rem;
        }

        .mt-queue-card-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
        }

        .mt-queue-card-facts {
            display: grid;
            gap: 0.55rem;
        }

        @media (min-width: 768px) {
            .mt-queue-card-facts {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .mt-queue-fact {
            display: flex;
            min-width: 0;
            align-items: flex-start;
            gap: 0.45rem;
            font-size: 0.875rem;
            line-height: 1.35rem;
            color: #475569;
        }

        .mt-queue-fact svg {
            margin-top: 0.1rem;
            flex-shrink: 0;
            color: #94a3b8;
        }

        .mt-queue-card-actions {
            display: flex;
            width: 100%;
            flex-direction: column;
            gap: 0.5rem;
        }

        @media (min-width: 1024px) {
            .mt-queue-card-actions {
                width: 14rem;
                min-width: 14rem;
                justify-content: center;
            }
        }

        .mt-queue-card-actions .btn {
            width: 100%;
            justify-content: center;
        }

        .mt-queue-card-summary {
            border: 1px solid rgba(226, 232, 240, 0.96);
            border-radius: 1rem;
            padding: 0.8rem 0.9rem;
            background: rgba(248, 250, 252, 0.9);
        }

        .mt-queue-card-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            border-radius: 0.9rem;
            padding: 0.55rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #475569;
            background: transparent;
            text-decoration: none;
        }

        .mt-queue-card-link:hover {
            color: #0f172a;
            background: rgba(241, 245, 249, 0.9);
        }

        .mt-secondary-grid {
            display: grid;
            gap: 0.75rem;
        }

        @media (min-width: 768px) {
            .mt-secondary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .mt-secondary-link {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            border: 1px solid rgba(226, 232, 240, 0.96);
            border-radius: 1rem;
            padding: 0.95rem 1rem;
            color: #0f172a;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--app-subpanel-shadow);
        }

        .mt-secondary-link:hover {
            border-color: rgba(203, 213, 225, 0.96);
            background: rgba(248, 250, 252, 0.98);
        }

        .mt-empty-state {
            border: 1px dashed rgba(203, 213, 225, 0.96);
            border-radius: 1.25rem;
            padding: 1.5rem;
            text-align: center;
            font-size: 0.95rem;
            color: #64748b;
            background: rgba(248, 250, 252, 0.75);
        }

        .mt-dashboard-shell,
        .mt-alert-board,
        .mt-queue-panel,
        .mt-analytics-board {
            border: 1px solid rgba(217, 226, 236, 0.96);
            border-radius: 1.75rem;
            padding: 1.25rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(248, 250, 252, 0.96) 100%);
            box-shadow: var(--app-panel-shadow);
        }

        .mt-dashboard-kicker,
        .mt-section-kicker {
            display: inline-flex;
            align-items: center;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: #64748b;
        }

        .mt-section-title {
            margin-top: 0.35rem;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #0f172a;
        }

        .mt-section-copy {
            margin-top: 0.35rem;
            font-size: 0.95rem;
            color: #64748b;
        }

        .mt-dashboard-meta {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        @media (min-width: 640px) {
            .mt-dashboard-meta {
                flex-direction: row;
                align-items: center;
            }
        }

        .mt-dashboard-search {
            display: grid;
            gap: 0.85rem;
            margin-top: 1rem;
        }

        @media (min-width: 1024px) {
            .mt-dashboard-search {
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: center;
            }
        }

        .mt-dashboard-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .mt-dashboard-tag {
            display: inline-flex;
            align-items: center;
            border: 1px solid rgba(226, 232, 240, 0.96);
            border-radius: 9999px;
            padding: 0.45rem 0.8rem;
            font-size: 0.78rem;
            font-weight: 600;
            color: #475569;
            background: rgba(255, 255, 255, 0.95);
        }

        .mt-kpi-grid {
            display: grid;
            gap: 0.85rem;
        }

        @media (min-width: 768px) {
            .mt-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .mt-kpi-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 1536px) {
            .mt-kpi-grid {
                grid-template-columns: repeat(6, minmax(0, 1fr));
            }
        }

        .mt-kpi-link {
            display: flex;
            min-height: 11.75rem;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid rgba(226, 232, 240, 0.96);
            border-radius: 1.25rem;
            padding: 1rem;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--app-subpanel-shadow);
        }

        .mt-kpi-link:hover {
            border-color: rgba(203, 213, 225, 0.96);
            background: rgba(248, 250, 252, 0.98);
        }

        .mt-kpi-link-success {
            border-color: rgba(187, 247, 208, 0.96);
        }

        .mt-kpi-link-warning {
            border-color: rgba(253, 230, 138, 0.96);
        }

        .mt-kpi-link-danger {
            border-color: rgba(254, 202, 202, 0.96);
        }

        .mt-kpi-dot {
            display: inline-flex;
            width: 0.8rem;
            height: 0.8rem;
            border-radius: 9999px;
            flex-shrink: 0;
            margin-top: 0.2rem;
        }

        .mt-kpi-dot-success {
            background: #059669;
        }

        .mt-kpi-dot-warning {
            background: #d97706;
        }

        .mt-kpi-dot-danger {
            background: #dc2626;
        }

        .mt-alert-grid {
            display: grid;
            gap: 0.85rem;
            margin-top: 1rem;
        }

        @media (min-width: 1024px) {
            .mt-alert-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .mt-alert-card {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            border: 1px solid rgba(226, 232, 240, 0.96);
            border-radius: 1.25rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--app-subpanel-shadow);
        }

        .mt-alert-card-warning {
            border-color: rgba(253, 230, 138, 0.96);
            background: linear-gradient(180deg, rgba(255, 251, 235, 0.98) 0%, rgba(255, 247, 237, 0.95) 100%);
        }

        .mt-alert-card-danger {
            border-color: rgba(254, 202, 202, 0.96);
            background: linear-gradient(180deg, rgba(255, 250, 250, 0.99) 0%, rgba(254, 242, 242, 0.95) 100%);
        }

        .mt-alert-count {
            display: inline-flex;
            min-width: 2.25rem;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            padding: 0.35rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 700;
            color: #0f172a;
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(226, 232, 240, 0.96);
        }

        .mt-alert-samples {
            display: grid;
            gap: 0.65rem;
        }

        .mt-alert-sample-link {
            display: block;
            border: 1px solid rgba(226, 232, 240, 0.96);
            border-radius: 1rem;
            padding: 0.75rem 0.85rem;
            background: rgba(255, 255, 255, 0.92);
        }

        .mt-alert-sample-link:hover {
            border-color: rgba(203, 213, 225, 0.96);
            background: rgba(248, 250, 252, 0.96);
        }

        .mt-alert-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            align-self: flex-start;
            border-radius: 0.9rem;
            padding: 0.65rem 0.9rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #0f172a;
            background: rgba(241, 245, 249, 0.95);
            text-decoration: none;
        }

        .mt-alert-action:hover {
            background: rgba(226, 232, 240, 0.95);
        }

        .mt-queue-panel {
            padding-bottom: 1.1rem;
        }

        .mt-queue-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }

        .mt-queue-card {
            gap: 1.1rem;
            min-height: 0;
            padding: 1rem;
        }

        .mt-queue-card-priority-critical {
            border-color: rgba(254, 202, 202, 0.96);
            background: linear-gradient(180deg, rgba(255, 250, 250, 0.99) 0%, rgba(255, 247, 247, 0.96) 100%);
        }

        .mt-queue-card-priority-delayed {
            border-color: rgba(253, 230, 138, 0.96);
            background: linear-gradient(180deg, rgba(255, 252, 245, 0.99) 0%, rgba(255, 248, 235, 0.96) 100%);
        }

        .mt-queue-card-priority-ready {
            border-color: rgba(187, 247, 208, 0.96);
            background: linear-gradient(180deg, rgba(248, 255, 250, 0.99) 0%, rgba(240, 253, 244, 0.96) 100%);
        }

        .mt-queue-card-priority-recent {
            border-color: rgba(217, 226, 236, 0.96);
        }

        .mt-queue-card-facts {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        @media (min-width: 768px) {
            .mt-queue-card-facts {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1280px) {
            .mt-queue-card-facts {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .mt-queue-fact {
            align-items: flex-start;
        }

        .mt-queue-fact-label {
            display: block;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            color: #64748b;
        }

        .mt-queue-fact-value {
            display: block;
            margin-top: 0.2rem;
            font-size: 0.875rem;
            line-height: 1.4rem;
            color: #0f172a;
        }

        .mt-queue-card-actions {
            gap: 0.6rem;
        }

        @media (min-width: 1024px) {
            .mt-queue-card-actions {
                width: 14.5rem;
                min-width: 14.5rem;
            }
        }

        .mt-analytics-grid {
            display: grid;
            gap: 0.85rem;
            margin-top: 1rem;
        }

        @media (min-width: 1024px) {
            .mt-analytics-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .mt-analytics-card {
            border: 1px solid rgba(226, 232, 240, 0.96);
            border-radius: 1.25rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--app-subpanel-shadow);
        }

        .mt-analytics-card-wide {
            grid-column: 1 / -1;
        }

        .mt-analytics-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            padding: 0.45rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            border: 1px solid rgba(226, 232, 240, 0.96);
        }

        .mt-analytics-pill-success {
            color: #047857;
            background: rgba(236, 253, 245, 0.95);
            border-color: rgba(167, 243, 208, 0.96);
        }

        .mt-analytics-pill-warning {
            color: #b45309;
            background: rgba(255, 247, 237, 0.95);
            border-color: rgba(253, 230, 138, 0.96);
        }

        .mt-analytics-pill-danger {
            color: #b91c1c;
            background: rgba(254, 242, 242, 0.95);
            border-color: rgba(254, 202, 202, 0.96);
        }

        .mt-analytics-stats {
            display: grid;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        @media (min-width: 640px) {
            .mt-analytics-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .mt-analytics-stat {
            border: 1px solid rgba(226, 232, 240, 0.96);
            border-radius: 1rem;
            padding: 0.9rem;
            background: rgba(248, 250, 252, 0.95);
        }

        .mt-analytics-stat-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            color: #64748b;
        }

        .mt-analytics-stat-value {
            display: block;
            margin-top: 0.35rem;
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
        }

        .mt-analytics-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            padding-bottom: 0.75rem;
            font-size: 0.95rem;
            color: #475569;
        }

        .mt-analytics-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .mt-bar-list {
            display: grid;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .mt-bar-item {
            display: grid;
            gap: 0.45rem;
        }

        .mt-bar-track {
            width: 100%;
            height: 0.45rem;
            border-radius: 9999px;
            overflow: hidden;
            background: rgba(226, 232, 240, 0.9);
        }

        .mt-bar-fill {
            display: block;
            height: 100%;
            border-radius: 9999px;
            background: linear-gradient(90deg, rgba(79, 70, 229, 0.9) 0%, rgba(99, 102, 241, 0.75) 100%);
        }

        .mt-bar-fill-muted {
            background: linear-gradient(90deg, rgba(71, 85, 105, 0.88) 0%, rgba(148, 163, 184, 0.82) 100%);
        }

        .mt-status-grid {
            display: grid;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        @media (min-width: 768px) {
            .mt-status-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .mt-status-card {
            border: 1px solid rgba(226, 232, 240, 0.96);
            border-radius: 1rem;
            padding: 0.9rem;
            background: rgba(248, 250, 252, 0.94);
        }

        .mt-secondary-actions-grid {
            display: grid;
            gap: 0.75rem;
        }

        @media (min-width: 768px) {
            .mt-secondary-actions-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .mt-action-shortcut {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            border: 1px solid rgba(226, 232, 240, 0.96);
            border-radius: 1.1rem;
            padding: 0.95rem 1rem;
            text-align: left;
            text-decoration: none;
            box-shadow: var(--app-subpanel-shadow);
        }

        .mt-action-shortcut-icon {
            display: inline-flex;
            width: 2rem;
            height: 2rem;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border-radius: 0.75rem;
        }

        .mt-action-shortcut-arrow {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 0.15rem;
        }
    </style>
@endonce
