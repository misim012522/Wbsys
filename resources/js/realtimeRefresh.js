/**
 * Real-time data refresh utility
 * Automatically polls for data updates without manual page refresh
 */

class RealtimeRefresh {
    constructor() {
        this.refreshIntervals = new Map();
        this.isEnabled = true;
        this.defaultInterval = 5000; // 5 seconds
    }

    /**
     * Register an element for real-time refresh
     * @param {string} elementId - ID of the element to refresh
     * @param {string} url - URL to fetch data from
     * @param {Function} updateCallback - Callback to process and update the DOM
     * @param {number} interval - Refresh interval in milliseconds
     */
    register(elementId, url, updateCallback, interval = this.defaultInterval) {
        const element = document.getElementById(elementId);
        if (!element) {
            console.warn(`Element with ID "${elementId}" not found`);
            return;
        }

        // Clear any existing interval
        if (this.refreshIntervals.has(elementId)) {
            clearInterval(this.refreshIntervals.get(elementId));
        }

        // Initial fetch
        this.refreshElement(elementId, url, updateCallback);

        // Set up interval
        const intervalId = setInterval(() => {
            if (this.isEnabled) {
                this.refreshElement(elementId, url, updateCallback);
            }
        }, interval);

        this.refreshIntervals.set(elementId, intervalId);
    }

    /**
     * Refresh a single element
     */
    async refreshElement(elementId, url, updateCallback) {
        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (response.status === 401 || response.status === 419) {
                // Stop polling this element when session/auth is no longer valid.
                this.unregister(elementId);
                return;
            }

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            const element = document.getElementById(elementId);
            if (element && updateCallback) {
                updateCallback(element, data);
            }
        } catch (error) {
            console.error(`Error refreshing element "${elementId}":`, error);
        }
    }

    /**
     * Unregister an element from real-time refresh
     */
    unregister(elementId) {
        if (this.refreshIntervals.has(elementId)) {
            clearInterval(this.refreshIntervals.get(elementId));
            this.refreshIntervals.delete(elementId);
        }
    }

    /**
     * Pause all refreshes
     */
    pauseAll() {
        this.isEnabled = false;
    }

    /**
     * Resume all refreshes
     */
    resumeAll() {
        this.isEnabled = true;
    }

    /**
     * Stop all refreshes and clear intervals
     */
    destroy() {
        for (const intervalId of this.refreshIntervals.values()) {
            clearInterval(intervalId);
        }
        this.refreshIntervals.clear();
    }

    /**
     * Manually trigger a refresh for specific element
     */
    refresh(elementId, url, updateCallback) {
        this.refreshElement(elementId, url, updateCallback);
    }
}

// Export singleton instance
export const realtimeRefresh = new RealtimeRefresh();

/**
 * Helper function to set up queue count refresh
 */
export function setupQueueRefresh(elementId, officeId, interval = 5000) {
    realtimeRefresh.register(
        elementId,
        `/api/offices/${officeId}/queue-count`,
        (element, data) => {
            if (data.count !== undefined) {
                element.textContent = data.count;
            }
        },
        interval
    );
}

/**
 * Helper function to set up list refresh (e.g., queue list)
 */
export function setupListRefresh(elementId, url, interval = 5000) {
    realtimeRefresh.register(
        elementId,
        url,
        (element, data) => {
            if (data.html) {
                element.innerHTML = data.html;
            }
        },
        interval
    );
}

/**
 * Pause refresh when page loses focus
 */
export function setupFocusHandling() {
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            realtimeRefresh.pauseAll();
        } else {
            realtimeRefresh.resumeAll();
        }
    });

    window.addEventListener('blur', () => {
        realtimeRefresh.pauseAll();
    });

    window.addEventListener('focus', () => {
        realtimeRefresh.resumeAll();
    });
}

export default realtimeRefresh;
