<?php
/**
 * Handles the password reset functionality.
 *
 * @package    AttributeLoginAccess
 * @subpackage AttributeLoginAccess/public
 */

class ATTRLA_Reset {
    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        add_action('wp_ajax_nopriv_attrla_process_reset_password', array($this, 'process_reset_password'));
        add_action('attrla_before_reset_password_form', array($this, 'validate_reset_key'));
        add_action('password_reset', array($this, 'after_password_reset'), 10, 2);
        add_filter('attrla_password_strength_requirements', array($this, 'get_password_requirements'));
    }

    /**
     * Process the password reset form submission.
     */
    public function process_reset_password() {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'attrla_reset_password_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'attrla')
            ));
        }

        // Verify required fields
        $required_fields = array(
            'rp_key' => __('Reset key is missing.', 'attrla'),
            'rp_login' => __('Username is missing.', 'attrla'),
            'new_password' => __('New password is required.', 'attrla'),
            'confirm_password' => __('Password confirmation is required.', 'attrla')
        );

        foreach ($required_fields as $field => $message) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array('message' => $message));
            }
        }

        // Sanitize inputs
        $rp_key = sanitize_text_field($_POST['rp_key']);
        $rp_login = sanitize_text_field($_POST['rp_login']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate passwords match
        if ($new_password !== $confirm_password) {
            wp_send_json_error(array(
                'message' => __('Passwords do not match.', 'attrla')
            ));
        }

        // Check password strength
        if (!$this->validate_password_strength($new_password)) {
            wp_send_json_error(array(
                'message' => $this->get_password_requirements_message()
            ));
        }

        // Validate security challenge if enabled
        if (ATTRLA_Security::is_challenge_required()) {
            $challenge_valid = ATTRLA_Security::validate_security_challenge($_POST);
            if (!$challenge_valid) {
                wp_send_json_error(array(
                    'message' => __('Security challenge failed.', 'attrla')
                ));
            }
        }

        // Check the reset key and login
        $user = check_password_reset_key($rp_key, $rp_login);
        if (is_wp_error($user)) {
            ATTRLA_Data::log_security_event('invalid_reset_key', array(
                'username' => $rp_login,
                'ip_address' => ATTRLA_Security::get_client_ip()
            ));

            wp_send_json_error(array(
                'message' => __('This password reset link has expired or is invalid.', 'attrla')
            ));
        }

        // Prevent reset if user is locked
        if (ATTRLA_Security::is_user_locked_out($user->user_login)) {
            wp_send_json_error(array(
                'message' => __('This account is currently locked. Please try again later.', 'attrla')
            ));
        }

        // Check if new password is different from the old one
        if ($user && wp_check_password($new_password, $user->user_pass, $user->ID)) {
            wp_send_json_error(array(
                'message' => __('New password must be different from the current password.', 'attrla')
            ));
        }

        // Reset the password
        reset_password($user, $new_password);

        // Log successful password reset
        ATTRLA_Data::log_login_attempt(array(
            'user_id' => $user->ID,
            'username' => $user->user_login,
            'ip_address' => ATTRLA_Security::get_client_ip(),
            'status' => 'success',
            'type' => 'password_reset'
        ));

        // Clear any failed login attempts
        ATTRLA_Security::clear_failed_attempts($user->user_login);

        wp_send_json_success(array(
            'message' => __('Your password has been reset successfully. You can now log in.', 'attrla'),
            'redirect_url' => wp_login_url()
        ));
    }

    /**
     * Validate the reset key before showing the form.
     */
    public function validate_reset_key() {
        $rp_login = isset($_GET['login']) ? sanitize_text_field($_GET['login']) : '';
        $rp_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';

        if (empty($rp_login) || empty($rp_key)) {
            wp_die(__('Invalid password reset link.', 'attrla'), 
                   __('Reset Link Error', 'attrla'), 
                   array('response' => 403));
        }

        $user = check_password_reset_key($rp_key, $rp_login);
        if (is_wp_error($user)) {
            ATTRLA_Data::log_security_event('expired_reset_key', array(
                'username' => $rp_login,
                'ip_address' => ATTRLA_Security::get_client_ip()
            ));

            wp_die(
                __('This password reset link has expired or is invalid. Please request a new one.', 'attrla'),
                __('Reset Link Expired', 'attrla'),
                array(
                    'response' => 403,
                    'back_link' => true
                )
            );
        }
    }

    /**
     * Actions to perform after password reset.
     *
     * @param WP_User $user     The user.
     * @param string  $new_pass The new password.
     */
    public function after_password_reset($user, $new_pass) {
        // Update user meta
        update_user_meta($user->ID, 'attrla_password_reset_date', current_time('mysql'));
        update_user_meta($user->ID, 'attrla_password_reset_ip', ATTRLA_Security::get_client_ip());

        // Send notification email
        $this->send_reset_notification($user);

        do_action('attrla_after_password_reset', $user);
    }

    /**
     * Validate password strength.
     *
     * @param string $password The password to validate.
     * @return bool True if password meets requirements.
     */
    private function validate_password_strength($password) {
        $requirements = $this->get_password_requirements();
        
        return (
            strlen($password) >= $requirements['min_length'] &&
            preg_match('/[A-Z]/', $password) >= $requirements['uppercase'] &&
            preg_match('/[a-z]/', $password) >= $requirements['lowercase'] &&
            preg_match('/[0-9]/', $password) >= $requirements['numbers'] &&
            preg_match('/[^A-Za-z0-9]/', $password) >= $requirements['special']
        );
    }

    /**
     * Get password requirements.
     *
     * @return array Password requirements.
     */
    public function get_password_requirements() {
        $defaults = array(
            'min_length' => 12,
            'uppercase' => 1,
            'lowercase' => 1,
            'numbers' => 1,
            'special' => 1
        );

        return apply_filters('attrla_password_requirements', $defaults);
    }

    /**
     * Get formatted password requirements message.
     *
     * @return string
     */
    private function get_password_requirements_message() {
        $requirements = $this->get_password_requirements();
        
        return sprintf(
            __('Password must be at least %1$d characters long and contain at least %2$d uppercase letter, %3$d lowercase letter, %4$d number, and %5$d special character.', 'attrla'),
            $requirements['min_length'],
            $requirements['uppercase'],
            $requirements['lowercase'],
            $requirements['numbers'],
            $requirements['special']
        );
    }

    /**
     * Send notification email after successful password reset.
     *
     * @param WP_User $user The user.
     */
    private function send_reset_notification($user) {
        $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        
        $title = sprintf(
            /* translators: Password changed notification email subject. %s: Site name */
            __('[%s] Password Changed', 'attrla'),
            $site_name
        );

        $message = sprintf(
            /* translators: %1$s: Site name, %2$s: User login */
            __('Hi %2$s,

This notice confirms that your password was changed on %1$s.

If you did not change your password, please contact the Site Administrator immediately.

This email has been sent to %3$s

Regards,
All at %1$s
%4$s', 'attrla'),
            $site_name,
            $user->user_login,
            $user->user_email,
            home_url('/')
        );

        wp_mail($user->user_email, $title, $message);
    }
}