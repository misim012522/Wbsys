import './bootstrap';
import './echo';
import { displaySessionToasts, setupAxiosToastInterceptor } from './toastNotifications';
import { setupFocusHandling, realtimeRefresh, setupQueueRefresh, setupListRefresh } from './realtimeRefresh';

// Setup axios interceptor for automatic toast notifications
setupAxiosToastInterceptor(window.axios);

// Display any session messages as toasts on page load.
// Run immediately when DOM is already ready to avoid missing the event.
const initializeAppUi = () => {
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

    const tenantId = document.body.dataset.realtimeTenantId;
    const routeName = document.body.dataset.routeName || '';
    const supportsPartialRefresh = [
        'office.dashboard',
        'office.activity',
        'office.reports',
        'admin.dashboard',
        'admin.reports',
    ].includes(routeName);
    const isWorkspaceLivePage = routeName.startsWith('office.') || routeName.startsWith('admin.');

    if (tenantId && isWorkspaceLivePage && window.Echo) {
        let refreshTimer = null;

        const refreshWorkspaceRegion = async () => {
            const currentRegion = document.querySelector('[data-live-refresh-root="workspace"]');
            if (!currentRegion) {
                window.location.reload();
                return;
            }

            const currentScrollTop = currentRegion.scrollTop;

            const response = await fetch(window.location.href, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
            });

            if (!response.ok) {
                window.location.reload();
                return;
            }

            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const nextRegion = doc.querySelector('[data-live-refresh-root="workspace"]');

            if (!nextRegion) {
                window.location.reload();
                return;
            }

            currentRegion.innerHTML = nextRegion.innerHTML;
            currentRegion.scrollTop = currentScrollTop;
        };

        const triggerRefresh = () => {
            if (refreshTimer) {
                return;
            }

            refreshTimer = window.setTimeout(() => {
                refreshTimer = null;
                if (!supportsPartialRefresh) {
                    window.location.reload();
                    return;
                }

                refreshWorkspaceRegion().catch(() => {
                    window.location.reload();
                });
            }, 300);
        };

        window.Echo.private(`tenant.${tenantId}`)
            .listen('.queue.updated', () => {
                triggerRefresh();
            });
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAppUi);
} else {
    initializeAppUi();
}

// Expose utilities globally for use in data-attributes and inline scripts
import { showToast } from './toastNotifications';

function createConfirmDialog() {
    if (document.getElementById('app-confirm-overlay')) {
        return;
    }

    const overlay = document.createElement('div');
    overlay.id = 'app-confirm-overlay';
    overlay.hidden = true;

    Object.assign(overlay.style, {
        position: 'fixed',
        inset: '0',
        zIndex: '10000',
        background: 'rgba(15, 23, 42, 0.45)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '1rem',
    });

    overlay.innerHTML = `
        <div id="app-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="app-confirm-title" style="width:min(100%,28rem);border:1px solid #e2e8f0;border-radius:1.5rem;background:#ffffff;box-shadow:0 24px 80px rgba(15,23,42,.24);overflow:hidden;">
            <div style="padding:1.25rem 1.25rem .75rem;">
                <p id="app-confirm-title" style="margin:0;font-size:.75rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:#0f766e;">Please confirm</p>
                <p id="app-confirm-message" style="margin:.75rem 0 0;color:#0f172a;font-size:.95rem;line-height:1.6;"></p>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.75rem;padding:0 1.25rem 1.25rem;">
                <button type="button" id="app-confirm-cancel" style="border:1px solid #cbd5e1;background:#ffffff;color:#334155;border-radius:.9rem;padding:.7rem 1rem;font-size:.875rem;font-weight:600;cursor:pointer;">Cancel</button>
                <button type="button" id="app-confirm-ok" style="border:1px solid #059669;background:#059669;color:#ffffff;border-radius:.9rem;padding:.7rem 1rem;font-size:.875rem;font-weight:600;cursor:pointer;">Confirm</button>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);
}

function showConfirm(message, options = {}) {
    createConfirmDialog();

    const overlay = document.getElementById('app-confirm-overlay');
    const messageNode = document.getElementById('app-confirm-message');
    const okButton = document.getElementById('app-confirm-ok');
    const cancelButton = document.getElementById('app-confirm-cancel');
    const titleNode = document.getElementById('app-confirm-title');

    if (!overlay || !messageNode || !okButton || !cancelButton || !titleNode) {
        return Promise.resolve(window.confirm(message));
    }

    messageNode.textContent = message || 'Are you sure?';
    titleNode.textContent = options.title || 'Please confirm';
    okButton.textContent = options.confirmLabel || 'Confirm';
    cancelButton.textContent = options.cancelLabel || 'Cancel';

    overlay.hidden = false;
    document.body.classList.add('overflow-hidden');

    return new Promise((resolve) => {
        let settled = false;

        const cleanup = (result) => {
            if (settled) {
                return;
            }

            settled = true;
            overlay.hidden = true;
            document.body.classList.remove('overflow-hidden');
            okButton.removeEventListener('click', onConfirm);
            cancelButton.removeEventListener('click', onCancel);
            overlay.removeEventListener('click', onOverlayClick);
            document.removeEventListener('keydown', onKeydown);
            resolve(result);
        };

        const onConfirm = () => cleanup(true);
        const onCancel = () => cleanup(false);
        const onOverlayClick = (event) => {
            if (event.target === overlay) {
                cleanup(false);
            }
        };
        const onKeydown = (event) => {
            if (event.key === 'Escape') {
                cleanup(false);
            }
        };

        okButton.addEventListener('click', onConfirm);
        cancelButton.addEventListener('click', onCancel);
        overlay.addEventListener('click', onOverlayClick);
        document.addEventListener('keydown', onKeydown);

        window.setTimeout(() => okButton.focus(), 0);
    });
}

window.showToast = showToast;
window.showConfirm = showConfirm;
window.realtimeRefresh = realtimeRefresh;
window.setupQueueRefresh = setupQueueRefresh;
window.setupListRefresh = setupListRefresh;
