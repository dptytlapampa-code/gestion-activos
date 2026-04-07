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
    </style>
@endonce
