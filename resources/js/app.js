import './bootstrap';
import { Toaster } from 'sonner';
import { displaySessionToasts, setupAxiosToastInterceptor } from './toastNotifications';
import { setupFocusHandling, realtimeRefresh, setupQueueRefresh, setupAppointmentsRefresh, setupListRefresh } from './realtimeRefresh';

// Initialize Toaster
new Toaster({
    position: 'top-right',
    richColors: true,
});

// Setup axios interceptor for automatic toast notifications
setupAxiosToastInterceptor(window.axios);

// Display any session messages as toasts on page load
document.addEventListener('DOMContentLoaded', () => {
    displaySessionToasts();
    setupFocusHandling();
});

// Expose utilities globally for use in data-attributes and inline scripts
import { showToast } from './toastNotifications';

window.showToast = showToast;
window.realtimeRefresh = realtimeRefresh;
window.setupQueueRefresh = setupQueueRefresh;
window.setupAppointmentsRefresh = setupAppointmentsRefresh;
window.setupListRefresh = setupListRefresh;
