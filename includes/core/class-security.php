<?php
/**
 * Handle core security functionality.
 *
 * @package    AttributeLoginAccess
 * @subpackage AttributeLoginAccess/includes
 */

class ATTRLA_Security {
    /**
     * Initialize the class.
     */
    public function __construct() {
        // Initialize rate limiting
        add_filter('wp_authenticate_user', array($this, 'check_login_restrictions'), 10, 2);
        add_action('wp_login_failed', array($this, 'handle_failed_login'));
    }

    /**
     * Check if IP is blocked
     *
     * @param string $ip IP address to check
     * @return boolean True if IP is blocked
     */
    public static function is_ip_blocked($ip = null) {
        if (null === $ip) {
            $ip = self::get_client_ip();
        }

        // Check whitelist first
        if (self::is_ip_whitelisted($ip)) {
            return false;
        }

        // Check blacklist
        if (self::is_ip_blacklisted($ip)) {
            return true;
        }

        // Check temporary blocks
        $blocked_ips = get_option('attrla_blocked_ips', array());
        if (isset($blocked_ips[$ip])) {
            $block = $blocked_ips[$ip];
            if ($block['expires'] > time()) {
                return true;
            } else {
                // Clean up expired block
                unset($blocked_ips[$ip]);
                update_option('attrla_blocked_ips', $blocked_ips);
            }
        }

        return false;
    }

    /**
     * Check if IP is whitelisted
     *
     * @param string $ip IP address to check
     * @return boolean True if IP is whitelisted
     */
    public static function is_ip_whitelisted($ip) {
        $whitelist = get_option('attrla_ip_whitelist', '');
        if (empty($whitelist)) {
            return false;
        }

        $whitelisted_ips = array_map('trim', explode(',', $whitelist));
        return in_array($ip, $whitelisted_ips);
    }

    /**
     * Check if IP is blacklisted
     *
     * @param string $ip IP address to check
     * @return boolean True if IP is blacklisted
     */
    public static function is_ip_blacklisted($ip) {
        $blacklist = get_option('attrla_ip_blacklist', '');
        if (empty($blacklist)) {
            return false;
        }

        $blacklisted_ips = array_map('trim', explode(',', $blacklist));
        return in_array($ip, $blacklisted_ips);
    }

    /**
     * Get client IP address
     *
     * @return string Client IP address
     */
    public static function get_client_ip() {
        $ip_headers = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    // Use first IP if multiple are provided
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Check login restrictions before authentication
     *
     * @param WP_User|WP_Error $user User object or error
     * @param string $password Password (not used)
     * @return WP_User|WP_Error User object or error
     */
    public function check_login_restrictions($user, $password) {
        if (is_wp_error($user)) {
            return $user;
        }

        $ip = self::get_client_ip();

        // Check IP blocks
        if (self::is_ip_blocked($ip)) {
            return new WP_Error(
                'ip_blocked',
                __('Access from your IP address has been temporarily blocked due to multiple failed login attempts.', 'attrla')
            );
        }

        // Check user lockout
        if (self::is_user_locked_out($user->user_login)) {
            $lockout_time = self::get_lockout_time_remaining($user->user_login);
            return new WP_Error(
                'user_locked',
                sprintf(
                    __('This account has been temporarily locked. Please try again in %d minutes.', 'attrla'),
                    ceil($lockout_time / 60)
                )
            );
        }

        return $user;
    }

    /**
     * Handle failed login attempt
     *
     * @param string $username Username or email
     */
    public function handle_failed_login($username) {
        $ip = self::get_client_ip();

        // Log failed attempt
        self::increment_failed_attempts($username, $ip);

        // Check if we should lock the account/IP
        $max_attempts = get_option('attrla_max_attempts', 5);
        $attempts = self::get_failed_attempts($username, $ip);

        if ($attempts >= $max_attempts) {
            self::apply_lockout($username, $ip);
        }
    }

    /**
     * Increment failed login attempts
     *
     * @param string $username Username
     * @param string $ip IP address
     */
    public static function increment_failed_attempts($username, $ip = null) {
        if (null === $ip) {
            $ip = self::get_client_ip();
        }

        $attempts = get_option('attrla_failed_attempts', array());
        $current_time = time();

        // Initialize or update username attempts
        if (!isset($attempts[$username])) {
            $attempts[$username] = array(
                'count' => 1,
                'last_attempt' => $current_time,
                'ips' => array($ip => 1)
            );
        } else {
            $attempts[$username]['count']++;
            $attempts[$username]['last_attempt'] = $current_time;
            if (!isset($attempts[$username]['ips'][$ip])) {
                $attempts[$username]['ips'][$ip] = 1;
            } else {
                $attempts[$username]['ips'][$ip]++;
            }
        }

        update_option('attrla_failed_attempts', $attempts);

        // Log the attempt
        ATTRLA_Data::log_login_attempt(array(
            'username' => $username,
            'ip_address' => $ip,
            'status' => 'failed',
            'type' => 'login'
        ));
    }

    /**
     * Apply lockout to user and/or IP
     *
     * @param string $username Username
     * @param string $ip IP address
     */
    public static function apply_lockout($username, $ip = null) {
        if (null === $ip) {
            $ip = self::get_client_ip();
        }

        $lockout_duration = get_option('attrla_lockout_duration', 15) * 60; // Convert minutes to seconds
        $current_time = time();

        // Lock username
        $locked_users = get_option('attrla_locked_users', array());
        $locked_users[$username] = array(
            'start_time' => $current_time,
            'expires' => $current_time + $lockout_duration,
            'ip' => $ip
        );
        update_option('attrla_locked_users', $locked_users);

        // Lock IP
        $blocked_ips = get_option('attrla_blocked_ips', array());
        $blocked_ips[$ip] = array(
            'start_time' => $current_time,
            'expires' => $current_time + $lockout_duration,
            'username' => $username
        );
        update_option('attrla_blocked_ips', $blocked_ips);

        // Log lockout
        ATTRLA_Data::log_security_event('lockout_applied', array(
            'username' => $username,
            'ip_address' => $ip,
            'duration' => $lockout_duration
        ));

        // Send notifications if enabled
        if (get_option('attrla_notify_lockout', true)) {
            self::send_lockout_notifications($username, $ip, $lockout_duration);
        }
    }

    /**
     * Check if user is locked out
     *
     * @param string $username Username
     * @return boolean True if user is locked out
     */
    public static function is_user_locked_out($username) {
        $locked_users = get_option('attrla_locked_users', array());
        if (isset($locked_users[$username])) {
            if ($locked_users[$username]['expires'] > time()) {
                return true;
            } else {
                // Clean up expired lockout
                unset($locked_users[$username]);
                update_option('attrla_locked_users', $locked_users);
            }
        }
        return false;
    }

    /**
     * Get remaining lockout time
     *
     * @param string $username Username
     * @return int Remaining lockout time in seconds
     */
    public static function get_lockout_time_remaining($username) {
        $locked_users = get_option('attrla_locked_users', array());
        if (isset($locked_users[$username])) {
            $remaining = $locked_users[$username]['expires'] - time();
            return max(0, $remaining);
        }
        return 0;
    }

    /**
     * Send lockout notifications
     *
     * @param string $username Username
     * @param string $ip IP address
     * @param int $duration Lockout duration in seconds
     */
    private static function send_lockout_notifications($username, $ip, $duration) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');

        // Admin notification
        $admin_subject = sprintf(
            __('[%s] Security Alert: Account Locked', 'attrla'),
            $site_name
        );

        $admin_message = sprintf(
            __('A user account has been locked due to multiple failed login attempts.

Username: %1$s
IP Address: %2$s
Lockout Duration: %3$d minutes
Time: %4$s

This is an automated message from your WordPress site security plugin.', 'attrla'),
            $username,
            $ip,
            $duration / 60,
            current_time('mysql')
        );

        wp_mail($admin_email, $admin_subject, $admin_message);

        // User notification
        $user = get_user_by('login', $username);
        if ($user && $user->user_email) {
            $user_subject = sprintf(
                __('[%s] Security Alert: Your Account Has Been Temporarily Locked', 'attrla'),
                $site_name
            );

            $user_message = sprintf(
                __('Your account has been temporarily locked due to multiple failed login attempts.

This is a security measure to protect your account.
The lockout will expire in %1$d minutes.

If you did not attempt to log in, please contact the site administrator immediately.

Time: %2$s', 'attrla'),
                $duration / 60,
                current_time('mysql')
            );

            wp_mail($user->user_email, $user_subject, $user_message);
        }
    }

    /**
     * Clean up expired data
     */
    public static function cleanup_expired_data() {
        $current_time = time();

        // Clean up locked users
        $locked_users = get_option('attrla_locked_users', array());
        foreach ($locked_users as $username => $lock) {
            if ($lock['expires'] <= $current_time) {
                unset($locked_users[$username]);
            }
        }
        update_option('attrla_locked_users', $locked_users);

        // Clean up blocked IPs
        $blocked_ips = get_option('attrla_blocked_ips', array());
        foreach ($blocked_ips as $ip => $block) {
            if ($block['expires'] <= $current_time) {
                unset($blocked_ips[$ip]);
            }
        }
        update_option('attrla_blocked_ips', $blocked_ips);

        // Clean up failed attempts older than 24 hours
        $attempts = get_option('attrla_failed_attempts', array());
        $cutoff = $current_time - (24 * 60 * 60);
        foreach ($attempts as $username => $data) {
            if ($data['last_attempt'] <= $cutoff) {
                unset($attempts[$username]);
            }
        }
        update_option('attrla_failed_attempts', $attempts);
    }
}