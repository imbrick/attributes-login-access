<?php
/**
 * Handle session management functionality.
 *
 * @package    AttributeLoginAccess
 * @subpackage AttributeLoginAccess/includes
 */

class ATTRLA_Session {
    /**
     * Session token key
     */
    const SESSION_TOKEN_KEY = 'attrla_session_token';

    /**
     * Initialize the class.
     */
    public function __construct() {
        // Initialize session handling
        add_action('init', array($this, 'initialize_session'), 0);
        
        // Session security
        add_action('wp_login', array($this, 'regenerate_session'), 10, 2);
        add_action('wp_logout', array($this, 'destroy_session'));
        
        // Session validation
        add_action('init', array($this, 'validate_session'), 1);
        
        // Cleanup
        add_action('wp_login', array($this, 'cleanup_old_sessions'), 10, 2);
        add_action('attrla_cleanup_data', array($this, 'cleanup_expired_sessions'));
    }

    /**
     * Initialize session handling
     */
    public function initialize_session() {
        if (!session_id() && !headers_sent()) {
            // Set secure session parameters
            $session_name = 'ATTRLASESS';
            $secure = is_ssl();
            $httponly = true;

            // Set session cookie parameters
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => COOKIEPATH,
                'domain' => COOKIE_DOMAIN,
                'secure' => $secure,
                'httponly' => $httponly,
                'samesite' => 'Lax'
            ]);

            session_name($session_name);
            session_start();

            // Initialize session token if not exists
            if (!isset($_SESSION[self::SESSION_TOKEN_KEY])) {
                $_SESSION[self::SESSION_TOKEN_KEY] = $this->generate_session_token();
            }
        }
    }

    /**
     * Validate current session
     */
    public function validate_session() {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        $session_token = $this->get_session_token();

        // Verify session token
        if (!$this->verify_session_token($user_id, $session_token)) {
            $this->terminate_session($user_id);
            wp_logout();
            wp_safe_redirect(wp_login_url());
            exit;
        }

        // Check for session expiration
        if ($this->is_session_expired($user_id)) {
            $this->terminate_session($user_id);
            wp_logout();
            wp_safe_redirect(wp_login_url());
            exit;
        }

        // Update last activity
        $this->update_session_activity($user_id);
    }

    /**
     * Regenerate session on login
     *
     * @param string  $user_login Username
     * @param WP_User $user      User object
     */
    public function regenerate_session($user_login, $user) {
        if (session_id()) {
            // Regenerate session ID
            session_regenerate_id(true);
            
            // Generate new session token
            $_SESSION[self::SESSION_TOKEN_KEY] = $this->generate_session_token();
            
            // Store session information
            $this->store_session_data($user->ID);
        }
    }

    /**
     * Destroy session on logout
     */
    public function destroy_session() {
        if (session_id()) {
            // Remove session from database
            $session_token = $this->get_session_token();
            $this->remove_session($session_token);

            // Destroy session
            session_unset();
            session_destroy();
            setcookie(session_name(), '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        }
    }

    /**
     * Generate unique session token
     *
     * @return string Session token
     */
    private function generate_session_token() {
        return bin2hex(random_bytes(32));
    }

    /**
     * Store session data in database
     *
     * @param int $user_id User ID
     */
    private function store_session_data($user_id) {
        global $wpdb;

        $session_token = $this->get_session_token();
        $ip_address = ATTRLA_Security::get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        $wpdb->insert(
            $wpdb->prefix . 'attrla_sessions',
            array(
                'session_token' => $session_token,
                'user_id' => $user_id,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'created' => current_time('mysql'),
                'last_activity' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Update session activity timestamp
     *
     * @param int $user_id User ID
     */
    private function update_session_activity($user_id) {
        global $wpdb;

        $session_token = $this->get_session_token();
        
        $wpdb->update(
            $wpdb->prefix . 'attrla_sessions',
            array('last_activity' => current_time('mysql')),
            array(
                'session_token' => $session_token,
                'user_id' => $user_id
            ),
            array('%s'),
            array('%s', '%d')
        );
    }

    /**
     * Verify session token
     *
     * @param int    $user_id       User ID
     * @param string $session_token Session token
     * @return bool                 True if valid
     */
    private function verify_session_token($user_id, $session_token) {
        global $wpdb;

        $valid = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}attrla_sessions 
                WHERE user_id = %d AND session_token = %s",
                $user_id,
                $session_token
            )
        );

        return (bool) $valid;
    }

    /**
     * Check if session is expired
     *
     * @param int $user_id User ID
     * @return bool        True if expired
     */
    private function is_session_expired($user_id) {
        global $wpdb;

        $session_token = $this->get_session_token();
        $timeout = get_option('attrla_session_timeout', 24) * HOUR_IN_SECONDS;

        $last_activity = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT last_activity FROM {$wpdb->prefix}attrla_sessions 
                WHERE user_id = %d AND session_token = %s",
                $user_id,
                $session_token
            )
        );

        if (!$last_activity) {
            return true;
        }

        return (strtotime($last_activity) + $timeout) < time();
    }

    /**
     * Remove session from database
     *
     * @param string $session_token Session token
     */
    private function remove_session($session_token) {
        global $wpdb;

        $wpdb->delete(
            $wpdb->prefix . 'attrla_sessions',
            array('session_token' => $session_token),
            array('%s')
        );
    }

    /**
     * Terminate all sessions for a user
     *
     * @param int $user_id User ID
     */
    public function terminate_user_sessions($user_id) {
        global $wpdb;

        $wpdb->delete(
            $wpdb->prefix . 'attrla_sessions',
            array('user_id' => $user_id),
            array('%d')
        );
    }

    /**
     * Get active sessions for a user
     *
     * @param int $user_id User ID
     * @return array       Active sessions
     */
    public function get_active_sessions($user_id) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}attrla_sessions 
                WHERE user_id = %d 
                ORDER BY last_activity DESC",
                $user_id
            )
        );
    }

    /**
     * Cleanup old sessions for a user on login
     *
     * @param string  $user_login Username
     * @param WP_User $user      User object
     */
    public function cleanup_old_sessions($user_login, $user) {
        global $wpdb;

        $max_sessions = get_option('attrla_max_sessions', 5);
        $user_sessions = $this->get_active_sessions($user->ID);

        if (count($user_sessions) > $max_sessions) {
            $sessions_to_remove = array_slice($user_sessions, $max_sessions);
            foreach ($sessions_to_remove as $session) {
                $this->remove_session($session->session_token);
            }
        }
    }

    /**
     * Cleanup expired sessions
     */
    public function cleanup_expired_sessions() {
        global $wpdb;

        $timeout = get_option('attrla_session_timeout', 24) * HOUR_IN_SECONDS;
        $expiry_date = date('Y-m-d H:i:s', time() - $timeout);

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}attrla_sessions 
                WHERE last_activity < %s",
                $expiry_date
            )
        );
    }

    /**
     * Get current session token
     *
     * @return string|null Session token
     */
    private function get_session_token() {
        return isset($_SESSION[self::SESSION_TOKEN_KEY]) ? $_SESSION[self::SESSION_TOKEN_KEY] : null;
    }
}