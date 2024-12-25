/**
 * Core public-facing functionality
 */
class ATTRLA_Core {
    /**
     * Initialize the core functionality
     */
    constructor() {
        // Initialize password visibility toggles
        this.initPasswordToggles();
        
        // Initialize password strength meters
        this.initPasswordStrength();
        
        // Setup AJAX handling
        this.setupAjax();
    }

    /**
     * Initialize password visibility toggles
     */
    initPasswordToggles() {
        document.querySelectorAll('.attrla-toggle-password').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                const button = e.currentTarget;
                const input = button.previousElementSibling;
                const icon = button.querySelector('.dashicons');

                // Toggle password visibility
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('dashicons-visibility');
                    icon.classList.add('dashicons-hidden');
                    button.setAttribute('aria-label', attrlAjax.i18n.hidePassword);
                } else {
                    input.type = 'password';
                    icon.classList.remove('dashicons-hidden');
                    icon.classList.add('dashicons-visibility');
                    button.setAttribute('aria-label', attrlAjax.i18n.showPassword);
                }
            });
        });
    }

    /**
     * Initialize password strength meter
     */
    initPasswordStrength() {
        document.querySelectorAll('.attrla-password-field input[type="password"]').forEach(input => {
            input.addEventListener('input', (e) => {
                const password = e.target.value;
                const meter = e.target.closest('.attrla-form-field').querySelector('.attrla-password-strength');
                
                if (!meter) return;

                const strength = this.checkPasswordStrength(password);
                meter.className = 'attrla-password-strength';
                meter.classList.add(strength.class);
                meter.textContent = strength.message;
            });
        });
    }

    /**
     * Check password strength
     * 
     * @param {string} password
     * @returns {Object} Strength information
     */
    checkPasswordStrength(password) {
        let score = 0;
        
        // Length check
        if (password.length >= 12) score += 2;
        else if (password.length >= 8) score += 1;

        // Character type checks
        if (/[A-Z]/.test(password)) score += 1;
        if (/[a-z]/.test(password)) score += 1;
        if (/[0-9]/.test(password)) score += 1;
        if (/[^A-Za-z0-9]/.test(password)) score += 1;

        // Determine strength level
        if (score >= 6) {
            return {
                class: 'strong',
                message: attrlAjax.i18n.strongPassword
            };
        } else if (score >= 4) {
            return {
                class: 'medium',
                message: attrlAjax.i18n.mediumPassword
            };
        } else if (score >= 2) {
            return {
                class: 'weak',
                message: attrlAjax.i18n.weakPassword
            };
        } else {
            return {
                class: 'very-weak',
                message: attrlAjax.i18n.veryWeakPassword
            };
        }
    }

    /**
     * Setup AJAX handling
     */
    setupAjax() {
        // Add CSRF token to all AJAX requests
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
        }

        // Add loading state to forms during submission
        document.querySelectorAll('.attrla-form').forEach(form => {
            form.addEventListener('submit', () => {
                form.classList.add('is-loading');
            });
        });
    }

    /**
     * Show message
     * 
     * @param {string} message Message text
     * @param {string} type Message type (success, error, info, warning)
     * @param {HTMLElement} container Container element
     */
    showMessage(message, type, container) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `attrla-message attrla-message-${type}`;
        messageDiv.textContent = message;

        // Remove any existing messages
        container.querySelectorAll('.attrla-message').forEach(msg => msg.remove());

        // Add new message at the top of the container
        container.insertBefore(messageDiv, container.firstChild);

        // Scroll message into view
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /**
     * Handle AJAX response
     * 
     * @param {Object} response AJAX response
     * @param {HTMLElement} form Form element
     */
    handleResponse(response, form) {
        // Remove loading state
        form.classList.remove('is-loading');

        if (response.success) {
            // Show success message
            this.showMessage(response.data.message, 'success', form.closest('.attrla-form-container'));

            // Handle redirect if provided
            if (response.data.redirect_url) {
                setTimeout(() => {
                    window.location.href = response.data.redirect_url;
                }, 1500);
            }

            // Reset form on success
            form.reset();
        } else {
            // Show error message
            this.showMessage(response.data.message, 'error', form.closest('.attrla-form-container'));

            // Re-enable form submission
            const submitButton = form.querySelector('[type="submit"]');
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    }

    /**
     * Handle AJAX errors
     * 
     * @param {Error} error Error object
     * @param {HTMLElement} form Form element
     */
    handleError(error, form) {
        // Remove loading state
        form.classList.remove('is-loading');

        // Show generic error message
        this.showMessage(attrlAjax.i18n.error, 'error', form.closest('.attrla-form-container'));

        // Re-enable form submission
        const submitButton = form.querySelector('[type="submit"]');
        if (submitButton) {
            submitButton.disabled = false;
        }

        // Log error for debugging
        console.error('ATTRLA Error:', error);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.ATTRLA = new ATTRLA_Core();
});