<?php
/**
 * Handles the registration functionality.
 *
 * @package    AttributeLoginAccess
 * @subpackage AttributeLoginAccess/public
 */

class ATTRLA_Register {
    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        add_action('wp_ajax_nopriv_attrla_process_registration', array($this, 'process_registration'));
        add_action('register_new_user', array($this, 'handle_successful_registration'));
        add_filter('registration_errors', array($this, 'validate_registration'), 10, 3);
    }

    /**
     * Process the registration form submission.
     */
    public function process_registration() {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'attrla_register_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'attrla')
            ));
        }

        // Check if registration is allowed
        if (!get_option('users_can_register')) {
            wp_send_json_error(array(
                'message' => __('Registration is currently disabled.', 'attrla')
            ));
        }

        // Verify required fields
        $required_fields = array(
            'user_login' => __('Username is required.', 'attrla'),
            'user_email' => __('Email address is required.', 'attrla'),
            'user_pass' => __('Password is required.', 'attrla'),
            'user_pass_confirm' => __('Password confirmation is required.', 'attrla')
        );

        foreach ($required_fields as $field => $message) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array('message' => $message));
            }
        }

        // Validate passwords match
        if ($_POST['user_pass'] !== $_POST['user_pass_confirm']) {
            wp_send_json_error(array(
                'message' => __('Passwords do not match.', 'attrla')
            ));
        }

        // Check password strength
        if (!ATTRLA_Security::is_password_strong($_POST['user_pass'])) {
            wp_send_json_error(array(
                'message' => __('Password does not meet strength requirements.', 'attrla')
            ));
        }

        // Check if security challenge is required and validate it
        if (ATTRLA_Security::is_challenge_required()) {
            $challenge_valid = ATTRLA_Security::validate_security_challenge($_POST);
            if (!$challenge_valid) {
                wp_send_json_error(array(
                    'message' => __('Security challenge failed.', 'attrla')
                ));
            }
        }

        // Sanitize user input
        $username = sanitize_user($_POST['user_login']);
        $email = sanitize_email($_POST['user_email']);
        $password = $_POST['user_pass'];

        // Validate through WordPress
        $validation_errors = $this->validate_registration(
            new WP_Error(),
            $username,
            $email
        );

        if (is_wp_error($validation_errors) && $validation_errors->has_errors()) {
            wp_send_json_error(array(
                'message' => $validation_errors->get_error_message()
            ));
        }

        // Create the user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array(
                'message' => $user_id->get_error_message()
            ));
        }

        // Log successful registration
        ATTRLA_Data::log_login_attempt(array(
            'user_id' => $user_id,
            'username' => $username,
            'ip_address' => ATTRLA_Security::get_client_ip(),
            'status' => 'success',
            'type' => 'registration'
        ));

        // Send notifications
        $this->send_registration_notifications($user_id, $email);

        wp_send_json_success(array(
            'message' => __('Registration successful. Please check your email for further instructions.', 'attrla'),
            'redirect_url' => apply_filters('attrla_registration_redirect', wp_login_url())
        ));
    }

    /**
     * Additional validation for registration form.
     *
     * @param  WP_Error $errors               WP_Error object.
     * @param  string   $sanitized_user_login Username after sanitization.
     * @param  string   $user_email           User email.
     * @return WP_Error                       WP_Error object with any errors found.
     */
    public function validate_registration($errors, $sanitized_user_login, $user_email) {
        // Check username length
        if (strlen($sanitized_user_login) < 4) {
            $errors->add(
                'username_length',
                __('Username must be at least 4 characters long.', 'attrla')
            );
        }

        // Check username characters
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $sanitized_user_login)) {
            $errors->add(
                'username_characters',
                __('Username may only contain alphanumeric characters and underscores.', 'attrla')
            );
        }

        // Check email domain
        $email_parts = explode('@', $user_email);
        if (count($email_parts) === 2) {
            $domain = $email_parts[1];
            if (ATTRLA_Security::is_domain_blacklisted($domain)) {
                $errors->add(
                    'invalid_email_domain',
                    __('This email domain is not allowed.', 'attrla')
                );
            }
        }

        // Additional custom validation can be added here
        return apply_filters('attrla_registration_validation', $errors, $sanitized_user_login, $user_email);
    }

    /**
     * Handle tasks after successful registration.
     *
     * @param int $user_id The new user ID.
     */
    public function handle_successful_registration($user_id) {
        // Set default user meta
        update_user_meta($user_id, 'attrla_registration_ip', ATTRLA_Security::get_client_ip());
        update_user_meta($user_id, 'attrla_registration_date', current_time('mysql'));

        do_action('attrla_after_registration', $user_id);
    }

    /**
     * Send registration notification emails.
     *
     * @param int    $user_id    The new user ID.
     * @param string $user_email The user's email address.
     */
    private function send_registration_notifications($user_id, $user_email) {
        $user = get_userdata($user_id);
        $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

        // Send user notification
        $user_subject = sprintf(
            /* translators: %s: Site name */
            __('[%s] Registration Successful', 'attrla'),
            $blogname
        );

        $user_message = sprintf(
            /* translators: %s: Site name */
            __('Thank you for registering at %s!', 'attrla'),
            $blogname
        ) . "\r\n\r\n";

        $user_message .= wp_login_url() . "\r\n";

        wp_mail($user_email, $user_subject, $user_message);

        // Send admin notification if enabled
        if (get_option('attrla_notify_admin_registration', true)) {
            $admin_subject = sprintf(
                /* translators: %s: Site name */
                __('[%s] New User Registration', 'attrla'),
                $blogname
            );

            $admin_message = sprintf(
                /* translators: %1$s: Username, %2$s: Email */
                __('New user registration on your site:

Username: %1$s
Email: %2$s', 'attrla'),
                $user->user_login,
                $user->user_email
            );

            wp_mail(get_option('admin_email'), $admin_subject, $admin_message);
        }
    }
}