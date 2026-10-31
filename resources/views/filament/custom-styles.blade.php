<style>
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

    /* Clean, modern scrollbars & touch enhancements for mobile */
    @media (max-width: 768px) {
        .fi-ta-content {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .fi-modal-window {
            max-height: 94vh !important;
        }
    }
</style>

