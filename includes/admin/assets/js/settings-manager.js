/**
 * Settings management functionality for Attribute Login Access
 */
class ATTRLA_Settings_Manager {
    /**
     * Initialize settings management
     */
    constructor() {
        this.form = document.querySelector('#attrla-settings-form');
        if (!this.form) return;

        this.initializeEvents();
        this.initializeDependencies();
        this.initializeValidation();
        this.setupResetHandler();
    }

    /**
     * Initialize event listeners
     */
    initializeEvents() {
        // Save settings via AJAX
        this.form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.saveSettings();
        });

        // Handle real-time validation
        this.form.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('change', () => {
                this.validateField(field);
            });
        });

        // Handle dependency triggers
        this.form.querySelectorAll('[data-depends-on]').forEach(field => {
            const trigger = this.form.querySelector(`[name="${field.dataset.dependsOn}"]`);
            if (trigger) {
                trigger.addEventListener('change', () => {
                    this.handleDependency(field, trigger);
                });
            }
        });
    }

    /**
     * Initialize field dependencies
     */
    initializeDependencies() {
        this.form.querySelectorAll('[data-depends-on]').forEach(field => {
            const trigger = this.form.querySelector(`[name="${field.dataset.dependsOn}"]`);
            if (trigger) {
                this.handleDependency(field, trigger);
            }
        });
    }

    /**
     * Initialize form validation
     */
    initializeValidation() {
        this.form.querySelectorAll('[data-validate]').forEach(field => {
            this.validateField(field);
        });
    }

    /**
     * Setup reset settings handler
     */
    setupResetHandler() {
        const resetButton = document.querySelector('#attrla-reset-settings');
        if (resetButton) {
            resetButton.addEventListener('click', async (e) => {
                e.preventDefault();
                if (confirm(attrlaAdmin.i18n.confirmReset)) {
                    await this.resetSettings();
                }
            });
        }
    }

    /**
     * Save settings via AJAX
     */
    async saveSettings() {
        if (!this.validateForm()) {
            this.showNotice(attrlaAdmin.i18n.validationError, 'error');
            return;
        }

        const submitButton = this.form.querySelector('[type="submit"]');
        submitButton.disabled = true;
        this.form.classList.add('attrla-loading');

        try {
            const formData = new FormData(this.form);
            formData.append('action', 'attrla_save_settings');
            formData.append('security', attrlaAdmin.nonce);

            const response = await fetch(attrlaAdmin.ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success) {
                this.showNotice(data.message, 'success');
                if (data.reload) {
                    window.location.reload();
                }
            } else {
                this.showNotice(data.message || attrlaAdmin.i18n.saveError, 'error');
            }
        } catch (error) {
            console.error('Settings save error:', error);
            this.showNotice(attrlaAdmin.i18n.saveError, 'error');
        } finally {
            submitButton.disabled = false;
            this.form.classList.remove('attrla-loading');
        }
    }

    /**
     * Reset settings to defaults
     */
    async resetSettings() {
        const resetButton = document.querySelector('#attrla-reset-settings');
        if (!resetButton) return;

        resetButton.disabled = true;
        this.form.classList.add('attrla-loading');

        try {
            const formData = new FormData();
            formData.append('action', 'attrla_reset_settings');
            formData.append('security', attrlaAdmin.nonce);

            const response = await fetch(attrlaAdmin.ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success) {
                this.showNotice(data.message, 'success');
                this.updateFormValues(data.settings);
            } else {
                this.showNotice(data.message || attrlaAdmin.i18n.resetError, 'error');
            }
        } catch (error) {
            console.error('Settings reset error:', error);
            this.showNotice(attrlaAdmin.i18n.resetError, 'error');
        } finally {
            resetButton.disabled = false;
            this.form.classList.remove('attrla-loading');
        }
    }

    /**
     * Validate entire form
     */
    validateForm() {
        let isValid = true;
        this.form.querySelectorAll('[data-validate]').forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        return isValid;
    }

    /**
     * Validate individual field
     */
    validateField(field) {
        const rules = field.dataset.validate ? field.dataset.validate.split(' ') : [];
        let isValid = true;
        let errorMessage = '';

        rules.forEach(rule => {
            switch (rule) {
                case 'required':
                    if (!field.value.trim()) {
                        isValid = false;
                        errorMessage = attrlaAdmin.i18n.requiredField;
                    }
                    break;

                case 'number':
                    if (field.value && !/^\d+$/.test(field.value)) {
                        isValid = false;
                        errorMessage = attrlaAdmin.i18n.numberRequired;
                    }
                    break;

                case 'email':
                    if (field.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
                        isValid = false;
                        errorMessage = attrlaAdmin.i18n.invalidEmail;
                    }
                    break;

                case 'ip':
                    if (field.value && !this.validateIP(field.value)) {
                        isValid = false;
                        errorMessage = attrlaAdmin.i18n.invalidIP;
                    }
                    break;
            }
        });

        this.toggleFieldError(field, isValid, errorMessage);
        return isValid;
    }

    /**
     * Validate IP address
     */
    validateIP(ip) {
        return /^(\d{1,3}\.){3}\d{1,3}$/.test(ip) && ip.split('.').every(num => {
            const n = parseInt(num, 10);
            return n >= 0 && n <= 255;
        });
    }

    /**
     * Toggle field error state
     */
    toggleFieldError(field, isValid, message = '') {
        const wrapper = field.closest('.attrla-field-wrapper');
        if (!wrapper) return;

        const error = wrapper.querySelector('.attrla-field-error');
        
        if (!isValid) {
            field.classList.add('attrla-error');
            if (error) {
                error.textContent = message;
            } else {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'attrla-field-error';
                errorDiv.textContent = message;
                wrapper.appendChild(errorDiv);
            }
        } else {
            field.classList.remove('attrla-error');
            if (error) {
                error.remove();
            }
        }
    }

    /**
     * Handle field dependencies
     */
    handleDependency(field, trigger) {
        const wrapper = field.closest('.attrla-field-wrapper');
        if (!wrapper) return;

        const shouldShow = this.evaluateDependency(trigger);
        wrapper.style.display = shouldShow ? '' : 'none';
        
        // Disable/enable field based on dependency
        field.disabled = !shouldShow;
    }

    /**
     * Evaluate dependency condition
     */
    evaluateDependency(trigger) {
        if (trigger.type === 'checkbox') {
            return trigger.checked;
        }
        return !!trigger.value;
    }

    /**
     * Update form values after reset
     */
    updateFormValues(settings) {
        Object.entries(settings).forEach(([key, value]) => {
            const field = this.form.querySelector(`[name="${key}"]`);
            if (!field) return;

            if (field.type === 'checkbox') {
                field.checked = value;
            } else {
                field.value = value;
            }

            // Trigger change event to update dependencies
            field.dispatchEvent(new Event('change'));
        });
    }

    /**
     * Show admin notice
     */
    showNotice(message, type = 'info') {
        if (typeof ATTRLA !== 'undefined') {
            ATTRLA.showNotice(message, type);
        } else {
            const notice = document.createElement('div');
            notice.className = `attrla-notice attrla-notice-${type}`;
            notice.textContent = message;

            const container = document.querySelector('.attrla-notices');
            if (container) {
                container.appendChild(notice);
                setTimeout(() => notice.remove(), 5000);
            }
        }
    }
}

// Initialize settings manager
document.addEventListener('DOMContentLoaded', () => {
    new ATTRLA_Settings_Manager();
});