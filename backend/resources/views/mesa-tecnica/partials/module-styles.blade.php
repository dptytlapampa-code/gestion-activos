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
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
        }

        .mt-card-lift:hover {
            transform: translateY(-1px);
            box-shadow: var(--app-panel-shadow-hover);
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
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
        }

        .mt-action-card:hover {
            transform: translateY(-1px);
            box-shadow: var(--app-panel-shadow-hover);
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
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }

        .mt-kpi-card:hover {
            transform: translateY(-1px);
            box-shadow: var(--app-panel-shadow-hover);
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
            transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease, background-color 0.16s ease, color 0.16s ease;
        }

        .mt-quick-filter:hover {
            transform: translateY(-1px);
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
            transition: border-color 0.16s ease, box-shadow 0.16s ease, background-color 0.16s ease;
        }

        .mt-input:hover,
        .mt-filter-shell .app-input:hover {
            border-color: rgba(148, 163, 184, 0.95);
            background-color: rgba(255, 255, 255, 1);
        }

        .mt-input:focus,
        .mt-filter-shell .app-input:focus {
            box-shadow: 0 0 0 4px rgba(var(--primary-color-rgb), 0.16);
        }

        .mt-primary-action {
            box-shadow: 0 18px 30px -22px rgba(79, 70, 229, 0.62);
        }

        .mt-primary-action:hover {
            box-shadow: 0 22px 34px -22px rgba(79, 70, 229, 0.68);
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
            transition: transform 0.16s ease, border-color 0.16s ease, background-color 0.16s ease, color 0.16s ease, box-shadow 0.16s ease;
            box-shadow: var(--app-subpanel-shadow);
        }

        .mt-mode-card:hover {
            transform: translateY(-1px);
            box-shadow: var(--app-panel-shadow-hover);
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
            transition: border-color 0.16s ease, background-color 0.16s ease, color 0.16s ease;
        }

        .mt-toolbar-toggle:hover {
            border-color: rgba(148, 163, 184, 0.95);
            background: rgba(248, 250, 252, 0.98);
        }

        .mt-kpi-card-compact {
            padding: 0.9rem 1rem;
            border-radius: 1.1rem;
        }

        .mt-kpi-card-compact p:last-child {
            line-height: 1.35rem;
        }
    </style>
@endonce
