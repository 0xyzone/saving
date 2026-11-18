<style>
    /* Global Font Stack: Clean, high-legibility Sans-Serif like Waldo */
    body, button, input, select, textarea {
        font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    /* Remove number input spin buttons across browsers */
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
    input[type=number] { 
        -moz-appearance: textfield;
        appearance: textfield;
    }

    /* Consistent, Breathable Input Field Styling Across App */
    .fi-input-wrp:not(:has(textarea)) {
        min-height: 2.75rem !important;
        border-radius: 0.625rem !important;
    }

    .fi-input-wrp:has(textarea) {
        border-radius: 0.625rem !important;
    }

    .fi-input-wrp input:not([type="checkbox"]):not([type="radio"]):not([type="file"]) {
        padding-top: 0.55rem !important;
        padding-bottom: 0.55rem !important;
        padding-inline: 0.875rem !important;
        line-height: 1.45 !important;
    }

    .fi-input-wrp:has(.fi-input-wrp-prefix) input:not([type="checkbox"]):not([type="radio"]):not([type="file"]) {
        padding-inline-start: 0.5rem !important;
    }

    .fi-input-wrp:has(.fi-input-wrp-suffix) input:not([type="checkbox"]):not([type="radio"]):not([type="file"]) {
        padding-inline-end: 0.5rem !important;
    }

    .fi-input-wrp textarea {
        padding: 0.75rem 0.875rem !important;
        line-height: 1.5 !important;
    }

    .fi-input-wrp select {
        height: 2.75rem !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
        padding-inline-start: 0.875rem !important;
        padding-inline-end: 2.25rem !important;
        line-height: 2.75rem !important;
        align-self: stretch !important;
    }

    .fi-select-input .fi-select-input-btn {
        min-height: 2.75rem !important;
        padding-top: 0.5rem !important;
        padding-bottom: 0.5rem !important;
        padding-inline-start: 0.875rem !important;
        padding-inline-end: 2.25rem !important;
    }

    .fi-input-wrp .fi-input-wrp-prefix {
        padding-inline-start: 0.875rem !important;
        padding-inline-end: 0.5rem !important;
    }

    .fi-input-wrp .fi-input-wrp-suffix {
        padding-inline-start: 0.5rem !important;
        padding-inline-end: 0.875rem !important;
    }

    /* Consistent Styling for Custom Modal Inputs, Selects & Textareas */
    .sp-custom-modal input:not([type="checkbox"]):not([type="radio"]),
    .sp-custom-modal select,
    .sp-custom-modal textarea {
        width: 100%;
        padding: 0.625rem 0.875rem !important;
        font-size: 0.875rem !important;
        line-height: 1.5 !important;
        border-radius: 0.75rem !important;
        border: 1px solid rgba(148, 163, 184, 0.35) !important;
        background-color: rgba(148, 163, 184, 0.08) !important;
        color: inherit !important;
        transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
    }

    .sp-custom-modal input:not([type="checkbox"]):not([type="radio"]),
    .sp-custom-modal select {
        min-height: 2.75rem !important;
    }

    html.dark .sp-custom-modal input:not([type="checkbox"]):not([type="radio"]),
    html.dark .sp-custom-modal select,
    html.dark .sp-custom-modal textarea,
    .dark .sp-custom-modal input:not([type="checkbox"]):not([type="radio"]),
    .dark .sp-custom-modal select,
    .dark .sp-custom-modal textarea {
        border-color: rgba(255, 255, 255, 0.14) !important;
        background-color: rgba(30, 41, 59, 0.85) !important;
        color: #f8fafc !important;
    }

    .sp-custom-modal input:focus,
    .sp-custom-modal select:focus,
    .sp-custom-modal textarea:focus {
        outline: none !important;
        border-color: #f59e0b !important;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2) !important;
    }

    /* Brand Lockup Alignment in Sidebar & Topbar */
    .fi-sidebar-header .fi-logo,
    .fi-topbar .fi-logo {
        height: auto !important;
        display: inline-flex !important;
        align-items: center !important;
        white-space: nowrap !important;
        max-width: 100%;
    }

    .fi-sidebar-header {
        gap: 0.5rem !important;
    }

    .fi-sidebar-collapsed .sp-brand-title,
    .fi-sidebar-collapsed .sp-brand-version {
        display: none !important;
    }

    /* Clean Card Layout Styling for Grid View */
    .fi-ta-content-grid {
        gap: 1rem !important;
    }

    .fi-ta-record {
        border-radius: 1rem !important;
        transition: all 0.2s ease-in-out !important;
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
    }

    .dark .fi-ta-record {
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        background-color: rgba(19, 27, 46, 0.65) !important;
    }

    .fi-ta-record:hover {
        box-shadow: 0 10px 25px -5px rgba(245, 158, 11, 0.12), 0 8px 10px -6px rgba(245, 158, 11, 0.08) !important;
        border-color: rgba(245, 158, 11, 0.4) !important;
    }

    /* Responsive Table/Card Visibility Helpers */
    .sp-mobile-only {
        display: none !important;
    }

    .sp-desktop-only {
        display: block !important;
    }

    /* Mobile Screen Enhancements */
    @media (max-width: 767px) {
        .sp-mobile-only {
            display: block !important;
        }

        .sp-desktop-only {
            display: none !important;
        }

        .fi-ta-content {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .fi-header-actions {
            flex-wrap: wrap !important;
            gap: 0.5rem !important;
            width: 100% !important;
        }

        .fi-modal-window {
            width: 100% !important;
            max-width: 100vw !important;
            max-height: 96vh !important;
            margin: 0 !important;
            border-radius: 1.25rem 1.25rem 0 0 !important;
        }

        .fi-modal-content,
        .fi-modal-header,
        .fi-modal-footer {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        .fi-modal-footer-actions {
            flex-wrap: wrap !important;
            gap: 0.5rem !important;
        }

        .sp-round-header {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 0.75rem !important;
            padding: 1rem !important;
        }

        .sp-round-summary-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 0.625rem !important;
        }

        .fi-topbar {
            border-bottom: 1px solid rgba(226, 232, 240, 0.8) !important;
        }

        .dark .fi-topbar {
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        }

        /* Full width touch-friendly action buttons on mobile */
        .fi-ta-actions {
            flex-wrap: wrap !important;
            gap: 0.5rem !important;
        }
    }

    /* ==========================================================================
       Premium Login & Auth Screen (Waldo Design Language)
       ========================================================================== */
    .fi-simple-layout {
        position: relative;
        min-height: 100vh !important;
        background: radial-gradient(circle at 50% 0%, rgba(245, 158, 11, 0.08) 0%, transparent 50%),
                    radial-gradient(circle at 80% 80%, rgba(30, 41, 59, 0.4) 0%, transparent 50%),
                    #020617 !important;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem !important;
    }

    /* Ambient glowing background aura */
    .fi-simple-layout::before {
        content: "";
        position: absolute;
        top: -100px;
        left: 50%;
        transform: translateX(-50%);
        width: 600px;
        height: 350px;
        background: radial-gradient(ellipse at center, rgba(245, 158, 11, 0.15), transparent 70%);
        pointer-events: none;
        z-index: 0;
    }

    .fi-simple-main-ctn {
        position: relative;
        z-index: 1;
        width: 100% !important;
        max-width: 28rem !important;
        margin: 0 auto !important;
    }

    /* Glassmorphism Card Container */
    .fi-simple-main {
        background: rgba(15, 23, 42, 0.75) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6),
                    0 0 0 1px rgba(245, 158, 11, 0.08),
                    inset 0 1px 1px 0 rgba(255, 255, 255, 0.1) !important;
        border-radius: 1.5rem !important;
        padding: 2.25rem 2rem !important;
        transition: border-color 0.3s ease, box-shadow 0.3s ease !important;
    }

    .fi-simple-main:hover {
        border-color: rgba(245, 158, 11, 0.25) !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7),
                    0 0 30px -5px rgba(245, 158, 11, 0.12),
                    inset 0 1px 1px 0 rgba(255, 255, 255, 0.15) !important;
    }

    /* Brand Header in Login */
    .fi-simple-header {
        margin-bottom: 1.75rem !important;
        text-align: center !important;
    }

    .fi-simple-header .fi-logo {
        display: flex !important;
        justify-content: center !important;
        height: auto !important;
        margin-bottom: 1.25rem !important;
    }

    .fi-simple-header .fi-logo .sp-logo-light {
        display: none !important;
    }

    .fi-simple-header .fi-logo .sp-logo-dark {
        display: block !important;
    }

    .fi-simple-header .fi-logo .sp-brand-title {
        color: #f59e0b !important;
    }

    .fi-simple-header-heading {
        font-size: 1.5rem !important;
        font-weight: 800 !important;
        letter-spacing: -0.025em !important;
        color: #ffffff !important;
        margin-top: 0.25rem !important;
    }

    .fi-simple-header-subheading {
        font-size: 0.875rem !important;
        color: #94a3b8 !important;
        margin-top: 0.375rem !important;
        line-height: 1.45 !important;
    }

    /* Form Input Fields */
    .fi-simple-main .fi-input-wrp {
        background-color: rgba(2, 6, 23, 0.6) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.75rem !important;
        transition: all 0.2s ease !important;
        box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.2) !important;
    }

    .fi-simple-main .fi-input-wrp:focus-within {
        border-color: #f59e0b !important;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2), inset 0 2px 4px 0 rgba(0, 0, 0, 0.2) !important;
    }

    .fi-simple-main .fi-input {
        color: #f8fafc !important;
        font-size: 0.925rem !important;
    }

    .fi-simple-main .fi-input::placeholder {
        color: #475569 !important;
    }

    .fi-simple-main .fi-fo-field-label {
        font-size: 0.8125rem !important;
        font-weight: 600 !important;
        color: #cbd5e1 !important;
        letter-spacing: 0.01em !important;
    }

    /* Primary Continue / Sign In Buttons */
    .fi-simple-main .fi-btn-action {
        border-radius: 0.75rem !important;
        padding-top: 0.65rem !important;
        padding-bottom: 0.65rem !important;
        font-weight: 700 !important;
        font-size: 0.925rem !important;
        letter-spacing: 0.01em !important;
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
        color: #020617 !important;
        border: none !important;
        box-shadow: 0 4px 14px 0 rgba(245, 158, 11, 0.35) !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .fi-simple-main .fi-btn-action:hover {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
        box-shadow: 0 6px 20px 0 rgba(245, 158, 11, 0.45) !important;
        transform: translateY(-1px) !important;
    }

    .fi-simple-main .fi-btn-action:active {
        transform: translateY(0) !important;
    }

    /* ==========================================================================
       Potential Return Estimator Widget (Glassmorphism + 3D + Thin Numerals)
       ========================================================================== */
    .sp-calc-widget {
        grid-column: 1 / -1;
    }

    .sp-calc-card {
        position: relative;
        overflow: hidden;
        border-radius: 1.25rem;
        padding: 1.25rem 1.4rem;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.72) 0%, rgba(248, 250, 252, 0.56) 100%);
        backdrop-filter: blur(22px) saturate(165%);
        -webkit-backdrop-filter: blur(22px) saturate(165%);
        border: 1px solid rgba(255, 255, 255, 0.65);
        box-shadow:
            0 20px 44px -18px rgba(15, 23, 42, 0.16),
            inset 0 1px 0 rgba(255, 255, 255, 0.85),
            inset 0 -1px 0 rgba(148, 163, 184, 0.15);
        transform-style: preserve-3d;
        transition: transform 420ms cubic-bezier(0.22, 1, 0.36, 1), box-shadow 420ms cubic-bezier(0.22, 1, 0.36, 1);
        animation: spCalcCardEnter 520ms cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    .dark .sp-calc-card {
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.68) 0%, rgba(30, 41, 59, 0.48) 100%);
        border: 1px solid rgba(255, 255, 255, 0.11);
        box-shadow:
            0 24px 50px -16px rgba(2, 6, 23, 0.65),
            inset 0 1px 0 rgba(255, 255, 255, 0.14),
            inset 0 -1px 0 rgba(2, 6, 23, 0.4);
    }

    .sp-calc-card:hover {
        transform: translateY(-2px);
        box-shadow:
            0 26px 56px -18px rgba(15, 23, 42, 0.22),
            inset 0 1px 0 rgba(255, 255, 255, 0.9);
    }

    .dark .sp-calc-card:hover {
        box-shadow:
            0 28px 60px -16px rgba(2, 6, 23, 0.8),
            inset 0 1px 0 rgba(255, 255, 255, 0.2);
    }

    /* Ambient floating orbs */
    .sp-calc-orb {
        position: absolute;
        border-radius: 9999px;
        pointer-events: none;
        filter: blur(42px);
        opacity: 0.42;
        z-index: 0;
    }

    .sp-calc-orb-1 {
        width: 220px;
        height: 220px;
        top: -85px;
        right: -40px;
        background: radial-gradient(circle, rgba(245, 158, 11, 0.55) 0%, rgba(245, 158, 11, 0) 70%);
        animation: spCalcOrbFloatA 9s ease-in-out infinite alternate;
    }

    .sp-calc-orb-2 {
        width: 190px;
        height: 190px;
        bottom: -75px;
        left: -35px;
        background: radial-gradient(circle, rgba(16, 185, 129, 0.42) 0%, rgba(16, 185, 129, 0) 70%);
        animation: spCalcOrbFloatB 11s ease-in-out infinite alternate;
    }

    .sp-calc-sheen {
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: linear-gradient(115deg, transparent 25%, rgba(255, 255, 255, 0.16) 45%, transparent 65%);
        opacity: 0.45;
        z-index: 0;
    }

    .dark .sp-calc-sheen {
        background: linear-gradient(115deg, transparent 25%, rgba(255, 255, 255, 0.05) 45%, transparent 65%);
    }

    .sp-calc-inner {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        gap: 0.95rem;
    }

    /* Header & 3D Badge */
    .sp-calc-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .sp-calc-title-group {
        display: flex;
        align-items: center;
        gap: 0.85rem;
    }

    .sp-calc-3d-stage {
        perspective: 640px;
        width: 44px;
        height: 44px;
        flex-shrink: 0;
    }

    .sp-calc-3d-badge {
        position: relative;
        width: 44px;
        height: 44px;
        transform-style: preserve-3d;
        animation: spCalc3dFloat 6s cubic-bezier(0.22, 1, 0.36, 1) infinite;
    }

    .sp-calc-3d-face {
        position: relative;
        width: 44px;
        height: 44px;
        border-radius: 0.85rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(145deg, rgba(245, 158, 11, 0.95) 0%, rgba(217, 119, 6, 0.88) 100%);
        color: #020617;
        box-shadow:
            0 10px 20px -6px rgba(217, 119, 6, 0.55),
            inset 0 1.5px 1px rgba(255, 255, 255, 0.65),
            inset 0 -3px 6px rgba(120, 53, 15, 0.35);
        transform: translateZ(8px);
    }

    .sp-calc-3d-icon {
        width: 22px;
        height: 22px;
    }

    .sp-calc-3d-glow {
        position: absolute;
        inset: 4px;
        border-radius: 0.85rem;
        background: rgba(245, 158, 11, 0.4);
        filter: blur(10px);
        transform: translateZ(-6px);
    }

    .sp-calc-title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: #0f172a;
        line-height: 1.2;
    }

    .dark .sp-calc-title {
        color: #f8fafc;
    }

    .sp-calc-subtitle {
        margin: 0.15rem 0 0;
        font-size: 0.74rem;
        font-weight: 500;
        color: #64748b;
    }

    .dark .sp-calc-subtitle {
        color: #94a3b8;
    }

    .sp-calc-seat-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
    }

    .sp-calc-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.65rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        backdrop-filter: blur(8px);
    }

    .sp-calc-pill-seats {
        background: rgba(15, 23, 42, 0.07);
        color: #334155;
        border: 1px solid rgba(15, 23, 42, 0.1);
    }

    .dark .sp-calc-pill-seats {
        background: rgba(255, 255, 255, 0.08);
        color: #e2e8f0;
        border: 1px solid rgba(255, 255, 255, 0.12);
    }

    .sp-calc-pill-eligible {
        background: rgba(16, 185, 129, 0.14);
        color: #047857;
        border: 1px solid rgba(16, 185, 129, 0.28);
    }

    .dark .sp-calc-pill-eligible {
        color: #34d399;
    }

    .sp-calc-pill-done {
        background: rgba(245, 158, 11, 0.14);
        color: #b45309;
        border: 1px solid rgba(245, 158, 11, 0.28);
    }

    .dark .sp-calc-pill-done {
        color: #fbbf24;
    }

    /* Controls */
    .sp-calc-controls {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .sp-calc-field {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
    }

    .sp-calc-label {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
    }

    .dark .sp-calc-label {
        color: #94a3b8;
    }

    .sp-calc-select {
        width: 100%;
        border-radius: 0.75rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.84rem;
        font-weight: 600;
        color: #0f172a;
        background: rgba(255, 255, 255, 0.62);
        border: 1px solid rgba(148, 163, 184, 0.35);
        backdrop-filter: blur(10px);
        transition: border-color 220ms ease, box-shadow 220ms ease;
    }

    .dark .sp-calc-select {
        color: #f8fafc;
        background: rgba(15, 23, 42, 0.62);
        border: 1px solid rgba(255, 255, 255, 0.14);
    }

    .sp-calc-select:focus {
        outline: none;
        border-color: rgba(245, 158, 11, 0.75);
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.18);
    }

    /* Hero Numbers & Breakdown */
    .sp-calc-result-shell {
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
        padding-top: 0.25rem;
        animation: spCalcValueReveal 420ms cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    .sp-calc-hero-row {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.9rem 1.05rem;
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.45);
        border: 1px solid rgba(255, 255, 255, 0.55);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
    }

    .dark .sp-calc-hero-row {
        background: rgba(2, 6, 23, 0.34);
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06);
    }

    .sp-calc-eyebrow {
        display: block;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.09em;
        color: #64748b;
        margin-bottom: 0.2rem;
    }

    .dark .sp-calc-eyebrow {
        color: #94a3b8;
    }

    .sp-calc-hero-value {
        display: flex;
        align-items: baseline;
        gap: 0.4rem;
        color: #0f172a;
        line-height: 1;
    }

    .dark .sp-calc-hero-value {
        color: #f8fafc;
    }

    .sp-calc-hero-value.sp-calc-hero-muted {
        opacity: 0.52;
    }

    .sp-calc-currency {
        font-size: 1.15rem;
        font-weight: 300;
        color: #64748b;
    }

    .dark .sp-calc-currency {
        color: #94a3b8;
    }

    .sp-calc-hero-number {
        font-size: clamp(2.15rem, 4.4vw, 3.5rem);
        font-weight: 200;
        letter-spacing: -0.04em;
        font-variant-numeric: tabular-nums;
    }

    .sp-calc-gain-block {
        text-align: right;
    }

    .sp-calc-gain-value {
        font-size: clamp(1.35rem, 2.4vw, 1.95rem);
        font-weight: 200;
        letter-spacing: -0.03em;
        font-variant-numeric: tabular-nums;
        line-height: 1.1;
    }

    .sp-calc-positive {
        color: #059669;
    }

    .dark .sp-calc-positive {
        color: #34d399;
    }

    .sp-calc-negative {
        color: #dc2626;
    }

    .dark .sp-calc-negative {
        color: #f87171;
    }

    .sp-calc-gain-note {
        display: block;
        margin-top: 0.2rem;
        font-size: 0.7rem;
        color: #64748b;
    }

    .dark .sp-calc-gain-note {
        color: #94a3b8;
    }

    .sp-calc-metrics {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.6rem;
    }

    .sp-calc-metric {
        padding: 0.6rem 0.75rem;
        border-radius: 0.85rem;
        background: rgba(255, 255, 255, 0.38);
        border: 1px solid rgba(148, 163, 184, 0.18);
    }

    .dark .sp-calc-metric {
        background: rgba(15, 23, 42, 0.42);
        border: 1px solid rgba(255, 255, 255, 0.07);
    }

    .sp-calc-metric-label {
        display: block;
        font-size: 0.67rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 0.15rem;
    }

    .dark .sp-calc-metric-label {
        color: #94a3b8;
    }

    .sp-calc-metric-value {
        display: block;
        font-size: 0.92rem;
        font-weight: 300;
        letter-spacing: -0.01em;
        font-variant-numeric: tabular-nums;
        color: #0f172a;
    }

    .dark .sp-calc-metric-value {
        color: #f1f5f9;
    }

    .sp-calc-deduct {
        color: #d97706;
    }

    .dark .sp-calc-deduct {
        color: #fbbf24;
    }

    .sp-calc-empty {
        padding: 1rem;
        border-radius: 0.85rem;
        text-align: center;
        font-size: 0.82rem;
        color: #64748b;
        background: rgba(255, 255, 255, 0.32);
    }

    .dark .sp-calc-empty {
        color: #94a3b8;
        background: rgba(15, 23, 42, 0.35);
    }

    @keyframes spCalcCardEnter {
        from {
            opacity: 0;
            transform: translateY(10px) scale(0.99);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes spCalcValueReveal {
        from {
            opacity: 0;
            transform: translateY(6px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes spCalc3dFloat {
        0%, 100% {
            transform: rotateX(10deg) rotateY(-14deg) translateY(0);
        }
        50% {
            transform: rotateX(16deg) rotateY(14deg) translateY(-4px);
        }
    }

    @keyframes spCalcOrbFloatA {
        from { transform: translate(0, 0) scale(1); }
        to { transform: translate(-18px, 14px) scale(1.08); }
    }

    @keyframes spCalcOrbFloatB {
        from { transform: translate(0, 0) scale(1); }
        to { transform: translate(18px, -12px) scale(1.06); }
    }

    @media (max-width: 767px) {
        .sp-calc-controls {
            grid-template-columns: 1fr;
        }

        .sp-calc-gain-block {
            text-align: left;
        }

        .sp-calc-metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .sp-calc-card,
        .sp-calc-result-shell,
        .sp-calc-3d-badge,
        .sp-calc-orb-1,
        .sp-calc-orb-2 {
            animation: none !important;
            transition: none !important;
        }
    }
</style>
