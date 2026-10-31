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
</style>
