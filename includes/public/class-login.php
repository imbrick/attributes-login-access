<?php
/**
 * Handles the login functionality.
 *
 * @package    AttributeLoginAccess
 * @subpackage AttributeLoginAccess/public
 */

class ATTRLA_Login {
    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        add_action('wp_ajax_nopriv_attrla_process_login', array($this, 'process_login'));
        add_action('wp_login_failed', array($this, 'handle_failed_login'), 10, 2);
        add_filter('authenticate', array($this, 'check_login_restrictions'), 30, 3);
    }

    /**
     * Process the login form submission.
     */
    public function process_login() {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'attrla_login_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'attrla')
            ));
        }

        // Verify required fields
        if (empty($_POST['log']) || empty($_POST['pwd'])) {
            wp_send_json_error(array(
                'message' => __('Username and password are required.', 'attrla')
            ));
        }

        $username = sanitize_user($_POST['log']);
        $password = $_POST['pwd'];
        $remember = isset($_POST['rememberme']) ? true : false;

        // Check if security challenge is required and validate it
        if (ATTRLA_Security::is_challenge_required()) {
            $challenge_valid = ATTRLA_Security::validate_security_challenge($_POST);
            if (!$challenge_valid) {
                wp_send_json_error(array(
                    'message' => __('Security challenge failed.', 'attrla')
                ));
            }
        }

        // Check login restrictions before proceeding
        $restrictions = $this->check_login_restrictions(null, $username, $password);
        if (is_wp_error($restrictions)) {
            wp_send_json_error(array(
                'message' => $restrictions->get_error_message()
            ));
        }

        // Attempt authentication
        $user = wp_signon(array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember
        ), is_ssl());

        if (is_wp_error($user)) {
            wp_send_json_error(array(
                'message' => $user->get_error_message()
            ));
        }

        // Log successful login
        ATTRLA_Data::log_login_attempt(array(
            'user_id' => $user->ID,
            'username' => $username,
            'ip_address' => ATTRLA_Security::get_client_ip(),
            'status' => 'success',
            'type' => 'login'
        ));

        wp_send_json_success(array(
            'message' => __('Login successful. Redirecting...', 'attrla'),
            'redirect_url' => apply_filters('attrla_login_redirect', admin_url(), $user)
        ));
    }

    /**
     * Handle failed login attempts.
     *
     * @param string $username The username attempted.
     * @param WP_Error $error  The error object.
     */
    public function handle_failed_login($username, $error) {
        // Log failed attempt
        ATTRLA_Data::log_login_attempt(array(
            'username' => $username,
            'ip_address' => ATTRLA_Security::get_client_ip(),
            'status' => 'failed',
            'type' => 'login',
            'error' => $error->get_error_message()
        ));

        // Increment failed attempts counter
        ATTRLA_Security::increment_failed_attempts($username);
    }

    /**
     * Check login restrictions before authentication.
     *
     * @param  null|WP_User|WP_Error $user     WP_User if the user is authenticated.
     * @param  string                $username  The username or email address.
     * @param  string                $password  The password.
     * @return null|WP_User|WP_Error
     */
    public function check_login_restrictions($user, $username, $password) {
        // If authentication has already failed, return that error
        if (is_wp_error($user)) {
            return $user;
        }

        // Check IP blacklist
        if (ATTRLA_Security::is_ip_blacklisted()) {
            return new WP_Error(
                'ip_blacklisted',
                __('Access denied from your IP address.', 'attrla')
            );
        }

        // Check if user is locked out
        if (ATTRLA_Security::is_user_locked_out($username)) {
            $lockout_time = ATTRLA_Security::get_lockout_time_remaining($username);
            return new WP_Error(
                'user_locked',
                sprintf(
                    __('Too many failed login attempts. Please try again in %d minutes.', 'attrla'),
                    ceil($lockout_time / 60)
                )
            );
        }

        return $user;
    }

    /**
     * Get the redirect URL after successful login.
     *
     * @param  WP_User $user  The authenticated user object.
     * @return string         The URL to redirect to.
     */
    public static function get_redirect_url($user) {
        // Check if a specific redirect URL was requested
        $requested_redirect = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : '';
        
        if (!empty($requested_redirect)) {
            return wp_validate_redirect($requested_redirect, home_url());
        }

        // Default redirect based on user role
        if (user_can($user, 'manage_options')) {
            return admin_url();
        }

        return home_url();
    }
}