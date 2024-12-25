/**
 * Login form handling
 */
class ATTRLA_Login {
    /**
     * Initialize the login form handler
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

            // Get form data
            const formData = new FormData(this.form);
            formData.append('action', 'attrla_process_login');
            formData.append('security', this.form.querySelector('[name="attrla_login_security"]').value);

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