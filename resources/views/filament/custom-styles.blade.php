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

    /* Mobile Screen Enhancements */
    @media (max-width: 768px) {
        .fi-ta-content {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .fi-modal-window {
            max-height: 96vh !important;
            border-radius: 1.25rem 1.25rem 0 0 !important;
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
</style>
