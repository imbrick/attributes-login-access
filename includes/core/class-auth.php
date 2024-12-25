<?php
/**
 * Handle authentication functionality.
 *
 * @package    AttributeLoginAccess
 * @subpackage AttributeLoginAccess/includes
 */

class ATTRLA_Auth {
    /**
     * Initialize the class.
     */
    public function __construct() {
        add_action('wp_authenticate', array($this, 'pre_authentication_check'), 10, 2);
        add_filter('authenticate', array($this, 'authenticate_user'), 30, 3);
        add_action('wp_login', array($this, 'handle_successful_login'), 10, 2);
        add_action('password_reset', array($this, 'handle_password_reset'), 10, 2);
    }

    /**
     * Perform pre-authentication checks
     *
     * @param string $username Username or email
     * @param string $password Password
     */
    public function pre_authentication_check($username, $password) {
        // Check if authentication is allowed
        if (!$this->is_authentication_allowed()) {
            wp_die(
                __('Authentication is temporarily disabled. Please try again later.', 'attrla'),
                __('Authentication Disabled', 'attrla'),
                array('response' => 403)
            );
        }

        // Validate basic credentials format
        if (empty($username) || empty($password)) {
            wp_die(
                __('Username and password are required.', 'attrla'),
                __('Invalid Credentials', 'attrla'),
                array('response' => 400)
            );
        }
    }

    /**
     * Custom authentication handler
     *
     * @param null|WP_User|WP_Error $user     User object or error
     * @param string                $username  Username or email
     * @param string                $password  Password
     * @return null|WP_User|WP_Error          User object or error
     */
    public function authenticate_user($user, $username, $password) {
        // If authentication has already failed, return the error
        if (is_wp_error($user)) {
            return $user;
        }

        // Get user by username or email
        $user_data = $this->get_user_by_login($username);
        if (!$user_data) {
            return new WP_Error(
                'invalid_username',
                __('Invalid username or password.', 'attrla')
            );
        }

        // Verify password
        if (!wp_check_password($password, $user_data->user_pass, $user_data->ID)) {
            return new WP_Error(
                'incorrect_password',
                __('Invalid username or password.', 'attrla')
            );
        }

        // Check if user is allowed to login
        $auth_check = $this->check_user_authentication($user_data);
        if (is_wp_error($auth_check)) {
            return $auth_check;
        }

        return $user_data;
    }

    /**
     * Handle successful login
     *
     * @param string  $username Username
     * @param WP_User $user    User object
     */
    public function handle_successful_login($username, $user) {
        // Clear failed attempts
        ATTRLA_Security::clear_failed_attempts($username);

        // Update user meta
        update_user_meta($user->ID, 'attrla_last_login', current_time('mysql'));
        update_user_meta($user->ID, 'attrla_last_login_ip', ATTRLA_Security::get_client_ip());

        // Log successful login
        ATTRLA_Data::log_login_attempt(array(
            'user_id' => $user->ID,
            'username' => $username,
            'ip_address' => ATTRLA_Security::get_client_ip(),
            'status' => 'success',
            'type' => 'login'
        ));

        // Regenerate session ID
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * Handle password reset
     *
     * @param WP_User $user     User object
     * @param string  $new_pass New password
     */
    public function handle_password_reset($user, $new_pass) {
        // Update password-related meta
        update_user_meta($user->ID, 'attrla_password_reset_date', current_time('mysql'));
        update_user_meta($user->ID, 'attrla_password_reset_ip', ATTRLA_Security::get_client_ip());

        // Clear any lockouts
        ATTRLA_Security::clear_user_lockout($user->user_login);

        // Log password reset
        ATTRLA_Data::log_security_event('password_reset', array(
            'user_id' => $user->ID,
            'username' => $user->user_login,
            'ip_address' => ATTRLA_Security::get_client_ip()
        ));

        // Send notification if enabled
        if (get_option('attrla_notify_password_reset', true)) {
            $this->send_password_change_notification($user);
        }
    }

    /**
     * Check if user is allowed to authenticate
     *
     * @param WP_User $user User object
     * @return true|WP_Error True if allowed, WP_Error if not
     */
    private function check_user_authentication($user) {
        // Check user status
        if (!$user->exists()) {
            return new WP_Error(
                'invalid_user',
                __('Invalid user account.', 'attrla')
            );
        }

        // Check if user is blocked
        if ($this->is_user_blocked($user->ID)) {
            return new WP_Error(
                'user_blocked',
                __('This account has been blocked. Please contact the administrator.', 'attrla')
            );
        }

        // Check password expiration if enabled
        if (get_option('attrla_enable_password_expiration', false)) {
            $expired = $this->is_password_expired($user->ID);
            if ($expired) {
                return new WP_Error(
                    'password_expired',
                    __('Your password has expired. Please reset your password to continue.', 'attrla')
                );
            }
        }

        return true;
    }

    /**
     * Check if authentication is currently allowed
     *
     * @return boolean True if authentication is allowed
     */
    private function is_authentication_allowed() {
        // Check maintenance mode
        if (get_option('attrla_maintenance_mode', false)) {
            return false;
        }

        // Check rate limiting
        if ($this->is_rate_limited()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the current IP is rate limited
     *
     * @return boolean True if rate limited
     */
    private function is_rate_limited() {
        $ip = ATTRLA_Security::get_client_ip();
        $rate_limit = get_option('attrla_rate_limit', 20); // Requests per minute
        $attempts = ATTRLA_Data::get_recent_attempts(array(
            'ip_address' => $ip,
            'timeframe' => 60 // Last minute
        ));

        return count($attempts) >= $rate_limit;
    }

    /**
     * Get user by username or email
     *
     * @param string $login Username or email
     * @return false|WP_User User object if found
     */
    private function get_user_by_login($login) {
        if (is_email($login)) {
            return get_user_by('email', $login);
        }
        return get_user_by('login', $login);
    }

    /**
     * Check if user is blocked
     *
     * @param int $user_id User ID
     * @return boolean True if user is blocked
     */
    private function is_user_blocked($user_id) {
        return (bool) get_user_meta($user_id, 'attrla_blocked', true);
    }

    /**
     * Check if password is expired
     *
     * @param int $user_id User ID
     * @return boolean True if password is expired
     */
    private function is_password_expired($user_id) {
        $expiration_days = get_option('attrla_password_expiration_days', 90);
        $last_reset = get_user_meta($user_id, 'attrla_password_reset_date', true);

        if (!$last_reset) {
            return false; // No reset date set
        }

        $reset_time = strtotime($last_reset);
        $expiration_time = $reset_time + ($expiration_days * DAY_IN_SECONDS);

        return time() >= $expiration_time;
    }

    /**
     * Send password change notification
     *
     * @param WP_User $user User object
     */
    private function send_password_change_notification($user) {
        $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        $site_url = home_url('/');
        
        $subject = sprintf(
            __('[%s] Password Changed', 'attrla'),
            $site_name
        );

        $message = sprintf(
            __('Hi %1$s,

This notice confirms that your password was changed on %2$s.

If you did not change your password, please contact the site administrator at %3$s immediately.

Regards,
%4$s
%5$s', 'attrla'),
            $user->display_name,
            current_time('mysql'),
            get_option('admin_email'),
            $site_name,
            $site_url
        );

        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Get URL for various authentication pages
     *
     * @param string $type Page type (login, register, lost_password, reset)
     * @param array  $args Additional URL parameters
     * @return string URL
     */
    public static function get_auth_page_url($type, $args = array()) {
        $base_url = '';
        
        switch ($type) {
            case 'login':
                $base_url = wp_login_url();
                break;
            case 'register':
                $base_url = wp_registration_url();
                break;
            case 'lost_password':
                $base_url = wp_lostpassword_url();
                break;
            case 'reset':
                $base_url = network_site_url('wp-login.php?action=rp', 'login');
                break;
        }

        if (!empty($args)) {
            $base_url = add_query_arg($args, $base_url);
        }

        return $base_url;
    }
}