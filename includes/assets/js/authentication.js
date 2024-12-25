/**
 * Authentication functions for Attribute Login Access
 */
const ATTRLA_Auth = {
    /**
     * Initialize authentication features
     */
    init() {
        this.initializeEventListeners();
        this.checkSessionStatus();
        this.setupAutoLogout();
    },

    /**
     * Initialize authentication event listeners
     */
    initializeEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            // Login form handling
            const loginForm = document.querySelector('#attrla-login-form');
            if (loginForm) {
                loginForm.addEventListener('submit', this.handleLogin.bind(this));
            }

            // Password reset form handling
            const resetForm = document.querySelector('#attrla-reset-form');
            if (resetForm) {
                resetForm.addEventListener('submit', this.handlePasswordReset.bind(this));
            }

            // Logout button handling
            const logoutButtons = document.querySelectorAll('.attrla-logout');
            logoutButtons.forEach(button => {
                button.addEventListener('click', this.handleLogout.bind(this));
            });

            // Session keepalive
            document.addEventListener('mousemove', ATTRLA_Utils.throttle(() => {
                this.refreshSession();
            }, 60000)); // Refresh every minute of activity
        });
    },

    /**
     * Handle login form submission
     * @param {Event} event Form submit event
     */
    async handleLogin(event) {
        event.preventDefault();
        const form = event.target;
        const submitButton = form.querySelector('[type="submit"]');

        try {
            // Show loading state
            submitButton.disabled = true;
            form.classList.add('attrla-loading');

            // Validate form
            if (!this.validateLoginForm(form)) {
                return;
            }

            const formData = new FormData(form);
            formData.append('action', 'attrla_process_login');
            formData.append('security', attrlAjax.nonce);

            const response = await fetch(attrlAjax.ajaxurl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                ATTRLA_Utils.showNotification(data.message, 'success');
                this.handleSuccessfulLogin(data);
            } else {
                ATTRLA_Utils.showNotification(data.message || 'Login failed', 'error');
            }
        } catch (error) {
            console.error('Login error:', error);
            ATTRLA_Utils.showNotification('An error occurred during login', 'error');
        } finally {
            submitButton.disabled = false;
            form.classList.remove('attrla-loading');
        }
    },

    /**
     * Handle password reset form submission
     * @param {Event} event Form submit event
     */
    async handlePasswordReset(event) {
        event.preventDefault();
        const form = event.target;
        const submitButton = form.querySelector('[type="submit"]');

        try {
            submitButton.disabled = true;
            form.classList.add('attrla-loading');

            // Validate passwords match
            const password = form.querySelector('#new_password').value;
            const confirmPassword = form.querySelector('#confirm_password').value;

            if (password !== confirmPassword) {
                ATTRLA_Utils.showNotification('Passwords do not match', 'error');
                return;
            }

            // Validate password strength
            if (!ATTRLA_Security.validatePassword(password)) {
                ATTRLA_Utils.showNotification('Password does not meet requirements', 'error');
                return;
            }

            const formData = new FormData(form);
            formData.append('action', 'attrla_process_reset_password');
            formData.append('security', attrlAjax.nonce);

            const response = await fetch(attrlAjax.ajaxurl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                ATTRLA_Utils.showNotification(data.message, 'success');
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                }
            } else {
                ATTRLA_Utils.showNotification(data.message || 'Password reset failed', 'error');
            }
        } catch (error) {
            console.error('Password reset error:', error);
            ATTRLA_Utils.showNotification('An error occurred during password reset', 'error');
        } finally {
            submitButton.disabled = false;
            form.classList.remove('attrla-loading');
        }
    },

    /**
     * Handle logout action
     * @param {Event} event Click event
     */
    async handleLogout(event) {
        event.preventDefault();
        
        if (!confirm('Are you sure you want to log out?')) {
            return;
        }

        try {
            const response = await fetch(attrlAjax.ajaxurl, {
                method: 'POST',
                credentials: 'same-origin',
                body: new URLSearchParams({
                    action: 'attrla_process_logout',
                    security: attrlAjax.nonce
                })
            });

            const data = await response.json();

            if (data.success) {
                this.clearAuthData();
                window.location.href = data.redirect_url || '/';
            } else {
                ATTRLA_Utils.showNotification(data.message || 'Logout failed', 'error');
            }
        } catch (error) {
            console.error('Logout error:', error);
            ATTRLA_Utils.showNotification('An error occurred during logout', 'error');
        }
    },

    /**
     * Validate login form
     * @param {HTMLFormElement} form Form element
     * @returns {boolean} True if valid
     */
    validateLoginForm(form) {
        const username = form.querySelector('#username').value;
        const password = form.querySelector('#password').value;

        if (!username || !password) {
            ATTRLA_Utils.showNotification('Please fill in all required fields', 'error');
            return false;
        }

        return true;
    },

    /**
     * Handle successful login
     * @param {Object} data Login response data
     */
    handleSuccessfulLogin(data) {
        // Store authentication state
        this.setAuthData(data);

        // Setup session monitoring
        this.setupAutoLogout();

        // Redirect if URL provided
        if (data.redirect_url) {
            window.location.href = data.redirect_url;
        }
    },

    /**
     * Check session status
     */
    async checkSessionStatus() {
        try {
            const response = await fetch(attrlAjax.ajaxurl, {
                method: 'POST',
                credentials: 'same-origin',
                body: new URLSearchParams({
                    action: 'attrla_check_session',
                    security: attrlAjax.nonce
                })
            });

            const data = await response.json();

            if (!data.success) {
                this.handleSessionExpired();
            }
        } catch (error) {
            console.error('Session check error:', error);
        }
    },

    /**
     * Refresh session
     */
    async refreshSession() {
        if (!this.isAuthenticated()) {
            return;
        }

        try {
            const response = await fetch(attrlAjax.ajaxurl, {
                method: 'POST',
                credentials: 'same-origin',
                body: new URLSearchParams({
                    action: 'attrla_refresh_session',
                    security: attrlAjax.nonce
                })
            });

            const data = await response.json();

            if (!data.success) {
                this.handleSessionExpired();
            }
        } catch (error) {
            console.error('Session refresh error:', error);
        }
    },

    /**
     * Setup auto logout timer
     */
    setupAutoLogout() {
        // Clear existing timer
        if (this.autoLogoutTimer) {
            clearTimeout(this.autoLogoutTimer);
        }

        // Set new timer
        const sessionTimeout = attrlAjax.sessionTimeout || 3600; // 1 hour default
        this.autoLogoutTimer = setTimeout(() => {
            this.handleSessionExpired();
        }, sessionTimeout * 1000);
    },

    /**
     * Handle expired session
     */
    handleSessionExpired() {
        this.clearAuthData();
        ATTRLA_Utils.showNotification('Your session has expired. Please log in again.', 'warning');
        
        // Redirect to login page
        const currentPage = window.location.href;
        window.location.href = `${attrlAjax.loginUrl}?redirect_to=${encodeURIComponent(currentPage)}`;
    },

    /**
     * Set authentication data
     * @param {Object} data Authentication data
     */
    setAuthData(data) {
        localStorage.setItem('attrla_auth', JSON.stringify({
            timestamp: Date.now(),
            user_id: data.user_id
        }));
    },

    /**
     * Clear authentication data
     */
    clearAuthData() {
        localStorage.removeItem('attrla_auth');
    },

    /**
     * Check if user is authenticated
     * @returns {boolean} True if authenticated
     */
    isAuthenticated() {
        const auth = localStorage.getItem('attrla_auth');
        if (!auth) return false;

        try {
            const data = JSON.parse(auth);
            const sessionTimeout = attrlAjax.sessionTimeout || 3600;
            const isExpired = (Date.now() - data.timestamp) > (sessionTimeout * 1000);
            
            return !isExpired;
        } catch (error) {
            return false;
        }
    }
};

// Initialize authentication features
document.addEventListener('DOMContentLoaded', () => ATTRLA_Auth.init());