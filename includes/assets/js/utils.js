/**
 * Utility functions for Attribute Login Access
 */
const ATTRLA_Utils = {
    /**
     * Initialize utilities
     */
    init() {
        this.initializeEventListeners();
    },

    /**
     * Initialize global event listeners
     */
    initializeEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            this.setupAjaxDefaults();
        });
    },

    /**
     * Setup default AJAX configurations
     */
    setupAjaxDefaults() {
        // Add CSRF token to all AJAX requests
        if (typeof attrlAjax !== 'undefined') {
            const originalFetch = window.fetch;
            window.fetch = function(url, options = {}) {
                if (typeof options.headers === 'undefined') {
                    options.headers = {};
                }
                options.headers['X-WP-Nonce'] = attrlAjax.nonce;
                return originalFetch(url, options);
            };
        }
    },

    /**
     * Format date string
     * @param {string|Date} date Date to format
     * @param {string} format Desired format (short, medium, long)
     * @returns {string} Formatted date
     */
    formatDate(date, format = 'medium') {
        const dateObj = new Date(date);
        const options = {
            short: { month: 'numeric', day: 'numeric', year: '2-digit' },
            medium: { month: 'short', day: 'numeric', year: 'numeric' },
            long: { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }
        };
        return dateObj.toLocaleDateString(undefined, options[format]);
    },

    /**
     * Debounce function calls
     * @param {Function} func Function to debounce
     * @param {number} wait Wait time in milliseconds
     * @returns {Function} Debounced function
     */
    debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Throttle function calls
     * @param {Function} func Function to throttle
     * @param {number} limit Limit in milliseconds
     * @returns {Function} Throttled function
     */
    throttle(func, limit = 300) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func(...args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    /**
     * Show notification message
     * @param {string} message Message to display
     * @param {string} type Message type (success, error, warning, info)
     * @param {number} duration Duration in milliseconds
     */
    showNotification(message, type = 'info', duration = 5000) {
        const container = document.querySelector('.attrla-notifications') || 
            this.createNotificationContainer();

        const notification = document.createElement('div');
        notification.className = `attrla-notification attrla-notification-${type}`;
        notification.textContent = message;

        container.appendChild(notification);

        // Trigger animation
        setTimeout(() => notification.classList.add('show'), 10);

        // Remove notification after duration
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, duration);
    },

    /**
     * Create notification container
     * @returns {HTMLElement} Notification container
     */
    createNotificationContainer() {
        const container = document.createElement('div');
        container.className = 'attrla-notifications';
        document.body.appendChild(container);
        return container;
    },

    /**
     * Validate email address
     * @param {string} email Email to validate
     * @returns {boolean} True if valid
     */
    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },

    /**
     * Sanitize string for safe display
     * @param {string} str String to sanitize
     * @returns {string} Sanitized string
     */
    sanitizeString(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    /**
     * Parse URL parameters
     * @param {string} url URL to parse
     * @returns {Object} Parsed parameters
     */
    parseUrlParams(url) {
        const params = {};
        new URL(url).searchParams.forEach((value, key) => {
            params[key] = value;
        });
        return params;
    },

    /**
     * Create URL with parameters
     * @param {string} base Base URL
     * @param {Object} params Parameters to add
     * @returns {string} Complete URL
     */
    buildUrl(base, params = {}) {
        const url = new URL(base, window.location.origin);
        Object.entries(params).forEach(([key, value]) => {
            url.searchParams.append(key, value);
        });
        return url.toString();
    },

    /**
     * Copy text to clipboard
     * @param {string} text Text to copy
     * @returns {Promise} Resolution status
     */
    copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();

            try {
                document.execCommand('copy');
                textArea.remove();
                return Promise.resolve();
            } catch (error) {
                textArea.remove();
                return Promise.reject(error);
            }
        }
    },

    /**
     * Format number with commas
     * @param {number} number Number to format
     * @returns {string} Formatted number
     */
    formatNumber(number) {
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    },

    /**
     * Get relative time string
     * @param {string|Date} date Date to compare
     * @returns {string} Relative time string
     */
    getRelativeTime(date) {
        const rtf = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });
        const diff = new Date(date) - new Date();
        const diffDays = Math.ceil(diff / (1000 * 60 * 60 * 24));

        if (Math.abs(diffDays) < 1) {
            const diffHours = Math.ceil(diff / (1000 * 60 * 60));
            return rtf.format(diffHours, 'hour');
        }
        return rtf.format(diffDays, 'day');
    }
};

// Initialize utilities
document.addEventListener('DOMContentLoaded', () => ATTRLA_Utils.init());