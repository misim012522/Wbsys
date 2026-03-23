const DEFAULT_DURATIONS = {
    success: 3000,
    error: 4000,
    info: 3000,
    warning: 3500,
    loading: 0,
};

let toastCounter = 0;
let container = null;
const activeToasts = new Map();

function ensureContainer() {
    if (container) {
        return container;
    }

    container = document.getElementById('toast-container');

    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    Object.assign(container.style, {
        position: 'fixed',
        top: '1rem',
        right: '1rem',
        zIndex: '9999',
        display: 'flex',
        flexDirection: 'column',
        gap: '0.75rem',
        width: 'min(24rem, calc(100vw - 2rem))',
        pointerEvents: 'none',
    });

    return container;
}

function toastStyles(type) {
    switch (type) {
        case 'success':
            return {
                background: '#ecfdf5',
                border: '1px solid #a7f3d0',
                color: '#065f46',
            };
        case 'error':
            return {
                background: '#fef2f2',
                border: '1px solid #fecaca',
                color: '#991b1b',
            };
        case 'warning':
            return {
                background: '#fffbeb',
                border: '1px solid #fde68a',
                color: '#92400e',
            };
        default:
            return {
                background: '#eff6ff',
                border: '1px solid #bfdbfe',
                color: '#1d4ed8',
            };
    }
}

function createToast(type, message, duration = DEFAULT_DURATIONS[type] ?? 3000) {
    const id = `toast-${++toastCounter}`;
    const root = ensureContainer();
    const toast = document.createElement('div');
    const styles = toastStyles(type);

    toast.dataset.toastId = id;
    toast.textContent = message;

    Object.assign(toast.style, {
        ...styles,
        borderRadius: '1rem',
        padding: '0.875rem 1rem',
        boxShadow: '0 12px 30px rgba(15, 23, 42, 0.14)',
        fontSize: '0.875rem',
        fontWeight: '600',
        lineHeight: '1.4',
        pointerEvents: 'auto',
        opacity: '0',
        transform: 'translateY(-8px)',
        transition: 'opacity 180ms ease, transform 180ms ease',
    });

    root.appendChild(toast);
    activeToasts.set(id, toast);

    requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    });

    if (duration > 0) {
        window.setTimeout(() => dismissToast(id), duration);
    }

    return id;
}

function dismissToast(id) {
    const toast = activeToasts.get(id);

    if (!toast) {
        return;
    }

    toast.style.opacity = '0';
    toast.style.transform = 'translateY(-8px)';

    window.setTimeout(() => {
        toast.remove();
        activeToasts.delete(id);
    }, 180);
}

export const showToast = {
    success: (message, duration = DEFAULT_DURATIONS.success) => createToast('success', message, duration),
    error: (message, duration = DEFAULT_DURATIONS.error) => createToast('error', message, duration),
    info: (message, duration = DEFAULT_DURATIONS.info) => createToast('info', message, duration),
    warning: (message, duration = DEFAULT_DURATIONS.warning) => createToast('warning', message, duration),
    loading: (message) => createToast('info', message, DEFAULT_DURATIONS.loading),
    dismiss: (toastId) => dismissToast(toastId),
    promise: async (promise, messages) => {
        const loadingId = messages?.loading ? showToast.loading(messages.loading) : null;

        try {
            const result = await (promise instanceof Function ? promise() : promise);

            if (loadingId) {
                showToast.dismiss(loadingId);
            }

            if (messages?.success) {
                showToast.success(
                    typeof messages.success === 'function' ? messages.success(result) : messages.success
                );
            }

            return result;
        } catch (error) {
            if (loadingId) {
                showToast.dismiss(loadingId);
            }

            if (messages?.error) {
                showToast.error(
                    typeof messages.error === 'function' ? messages.error(error) : messages.error
                );
            }

            throw error;
        }
    },
};

export function displaySessionToasts() {
    const body = document.body;

    if (body.dataset.successMessage) {
        showToast.success(body.dataset.successMessage);
        delete body.dataset.successMessage;
    }

    if (body.dataset.errorMessage) {
        showToast.error(body.dataset.errorMessage);
        delete body.dataset.errorMessage;
    }

    if (body.dataset.infoMessage) {
        showToast.info(body.dataset.infoMessage);
        delete body.dataset.infoMessage;
    }

    if (body.dataset.warningMessage) {
        showToast.warning(body.dataset.warningMessage);
        delete body.dataset.warningMessage;
    }
}

export function setupAxiosToastInterceptor(axios) {
    if (!axios) {
        return;
    }

    axios.interceptors.response.use(
        (response) => {
            if (response.data?.message) {
                showToast.success(response.data.message);
            }

            return response;
        },
        (error) => {
            if (error.response?.data?.message) {
                showToast.error(error.response.data.message);
            } else if (error.message) {
                showToast.error(error.message);
            } else {
                showToast.error('An error occurred');
            }

            return Promise.reject(error);
        }
    );
}

export function showLogoutToast() {
    showToast.success('Logged out successfully. Redirecting...', 2000);
}

export default showToast;
