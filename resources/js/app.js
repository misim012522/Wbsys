import './bootstrap';
import { displaySessionToasts, setupAxiosToastInterceptor } from './toastNotifications';
import { setupFocusHandling, realtimeRefresh, setupQueueRefresh, setupAppointmentsRefresh, setupListRefresh } from './realtimeRefresh';

// Setup axios interceptor for automatic toast notifications
setupAxiosToastInterceptor(window.axios);

// Display any session messages as toasts on page load
document.addEventListener('DOMContentLoaded', () => {
    displaySessionToasts();
    setupFocusHandling();

    const monitorUrl = document.body.dataset.tenantSessionMonitorUrl;

    if (monitorUrl) {
        let deactivationHandled = false;

        const checkTenantSessionStatus = async () => {
            if (deactivationHandled || document.hidden) {
                return;
            }

            try {
                const response = await fetch(monitorUrl, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                if (response.status !== 423) {
                    return;
                }

                const payload = await response.json();

                if (! payload?.deactivated) {
                    return;
                }

                deactivationHandled = true;
                window.showToast.info(payload.message || 'Logging out due to deactivation.', 1800);

                window.setTimeout(() => {
                    window.location.assign(payload.redirect_url || '/login?force_login=1');
                }, 900);
            } catch (error) {
                console.error('Unable to monitor tenant session status:', error);
            }
        };

        checkTenantSessionStatus();
        window.setInterval(checkTenantSessionStatus, 5000);
    }
});

// Expose utilities globally for use in data-attributes and inline scripts
import { showToast } from './toastNotifications';

window.showToast = showToast;
window.realtimeRefresh = realtimeRefresh;
window.setupQueueRefresh = setupQueueRefresh;
window.setupAppointmentsRefresh = setupAppointmentsRefresh;
window.setupListRefresh = setupListRefresh;
