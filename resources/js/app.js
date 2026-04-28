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

    // Check for app updates
    checkForAppUpdates();

    // Setup real-time queue status updates for public track page
    if (window.queueData && window.Echo) {
        const { tenantId, queueEntryId, referenceCode } = window.queueData;

        window.Echo.private(`tenant.${tenantId}`)
            .listen('.queue.updated', (e) => {
                // Only update if this queue entry is affected
                if (e.queue_entry_id === queueEntryId) {
                    // Fetch updated queue data
                    fetch(`/t/${referenceCode}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Update status
                        const statusEl = document.getElementById('queue-status');
                        if (statusEl && data.status) {
                            statusEl.textContent = data.status.replace('_', ' ');
                        }

                        // Update position
                        const positionEl = document.getElementById('position');
                        if (positionEl && data.position !== undefined) {
                            positionEl.textContent = data.position;
                        }

                        // Update ahead text
                        const aheadTextEl = document.getElementById('ahead-text');
                        if (aheadTextEl && data.ahead !== undefined) {
                            if (data.ahead > 0) {
                                aheadTextEl.textContent = `${data.ahead} person${data.ahead > 1 ? 's' : ''} ahead of you`;
                            } else {
                                aheadTextEl.innerHTML = '<span class="font-medium text-emerald-600">You are next.</span>';
                            }
                        }
                    })
                    .catch(error => console.error('Failed to fetch queue update:', error));
                }
            });
    }

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

// Check for app updates from GitHub
function checkForAppUpdates() {
    fetch('/api/tenant-update/status')
        .then(response => response.json())
        .then(data => {
            if (data.update_available) {
                const notification = document.createElement('div');
                notification.className = 'fixed top-20 right-4 z-50 max-w-sm bg-amber-50 border border-amber-200 rounded-lg p-4 shadow-lg';
                notification.innerHTML = `
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-amber-800">Version outdated</p>
                            <p class="mt-1 text-xs text-amber-700">
                                Current: ${data.current_version} | Latest: ${data.latest_version}
                            </p>
                            <button id="apply-update-btn" class="mt-2 text-sm font-medium text-amber-800 hover:text-amber-900 underline">
                                Click here to download and apply the latest release
                            </button>
                        </div>
                        <button id="dismiss-update-btn" class="flex-shrink-0 text-amber-500 hover:text-amber-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                `;

                document.body.appendChild(notification);

                // Handle dismiss
                document.getElementById('dismiss-update-btn').addEventListener('click', () => {
                    notification.remove();
                });

                // Handle apply update
                document.getElementById('apply-update-btn').addEventListener('click', () => {
                    if (confirm('This will download and apply the latest update. The system may be temporarily unavailable. Continue?')) {
                        notification.innerHTML = `
                            <div class="flex items-center gap-3">
                                <div class="animate-spin h-5 w-5 border-2 border-amber-600 border-t-transparent rounded-full"></div>
                                <p class="text-sm text-amber-800">Downloading and applying update...</p>
                            </div>
                        `;

                        fetch('/api/tenant-update/apply', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json',
                            },
                        })
                        .then(response => response.json())
                        .then(result => {
                            notification.innerHTML = `
                                <div class="flex items-center gap-3">
                                    <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <p class="text-sm text-emerald-800">Update applied successfully! Reloading...</p>
                                </div>
                            `;
                            setTimeout(() => location.reload(), 2000);
                        })
                        .catch(error => {
                            notification.innerHTML = `
                                <div class="flex items-center gap-3">
                                    <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    <p class="text-sm text-red-800">Failed to apply update: ${error.message}</p>
                                </div>
                            `;
                        });
                    }
                });
            }
        })
        .catch(error => {
            console.error('Failed to check for updates:', error);
        });
}
window.realtimeRefresh = realtimeRefresh;
window.setupQueueRefresh = setupQueueRefresh;
window.setupListRefresh = setupListRefresh;
