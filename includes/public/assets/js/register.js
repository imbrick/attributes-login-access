/**
 * Registration form handling
 */
class ATTRLA_Register {
    /**
     * Initialize the registration form handler
     * @param {string} formSelector Form selector
     */
    constructor(formSelector) {
        this.form = document.querySelector(formSelector);
        if (!this.form) return;

        this.initializeForm();
    }

    /**
     * Initialize form handlers
     */
    initializeForm() {
        this.form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Validate passwords match
            const password = this.form.querySelector('#attrla_password').value;
            const confirmPassword = this.form.querySelector('#attrla_password_confirm').value;

            if (password !== confirmPassword) {
                ATTRLA.showMessage(attrlAjax.i18n.passwordMismatch, 'error', this.form.closest('.attrla-form-container'));
                return;
            }

            // Get form data
            const formData = new FormData(this.form);
            formData.append('action', 'attrla_process_registration');
            formData.append('security', this.form.querySelector('[name="attrla_register_security"]').value);

            try {
                const response = await fetch(attrlAjax.ajaxurl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const data = await response.json();
                ATTRLA.handleResponse(data, this.form);
            } catch (error) {
                ATTRLA.handleError(error, this.form);
            }
        });
    }
}