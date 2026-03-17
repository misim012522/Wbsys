import { toast } from 'sonner';

/**
 * Toast notification utility
 */
export const showToast = {
    success: (message, duration = 3000) => {
        toast.success(message, { duration });
    },
    error: (message, duration = 4000) => {
        toast.error(message, { duration });
    },
    info: (message, duration = 3000) => {
        toast.info(message, { duration });
    },
    warning: (message, duration = 3500) => {
        toast.warning(message, { duration });
    },
    loading: (message) => {
        return toast.loading(message);
    },
    dismiss: (toastId) => {
        toast.dismiss(toastId);
    },
    promise: (promise, messages) => {
        return toast.promise(promise, messages);
    }
};

/**
 * Display any session messages as toasts
 */
export function displaySessionToasts() {
    // Check for data attributes on body
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

/**
 * Global axios interceptor for handling responses with toast notifications
 */
export function setupAxiosToastInterceptor(axios) {
    // Response interceptor
    axios.interceptors.response.use(
        response => {
            // Show success toast if response has a message
            if (response.data?.message) {
                showToast.success(response.data.message);
            }
            return response;
        },
        error => {
            // Show error toast if error has a message
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

/**
 * Display logout toast with a custom message
 */
export function showLogoutToast() {
    toast.success('Logged out successfully. Redirecting...', { 
        duration: 2000 
    });
}

export default showToast;
