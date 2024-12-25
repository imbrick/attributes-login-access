<?php
/**
 * Handles the lost password functionality.
 *
 * @package    AttributeLoginAccess
 * @subpackage AttributeLoginAccess/public
 */

class ATTRLA_Lost {
    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        add_action('wp_ajax_nopriv_attrla_process_lost_password', array($this, 'process_lost_password'));
        add_filter('retrieve_password_message', array($this, 'customize_password_reset_message'), 10, 4);
        add_filter('retrieve_password_title', array($this, 'customize_password_reset_title'), 10, 3);
        add_action('attrla_before_lost_password_form', array($this, 'maybe_show_rate_limit_notice'));
    }

    /**
     * Process the lost password form submission.
     */
    public function process_lost_password() {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'attrla_lost_password_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'attrla')
            ));
        }

        // Check if user login is provided
        if (empty($_POST['user_login'])) {
            wp_send_json_error(array(
                'message' => __('Please enter a username or email address.', 'attrla')
            ));
        }

        // Sanitize user input
        $user_login = sanitize_text_field($_POST['user_login']);

        // Check for rate limiting
        if ($this->is_request_rate_limited()) {
            ATTRLA_Data::log_security_event('rate_limit_exceeded', array(
                'ip_address' => ATTRLA_Security::get_client_ip(),
                'type' => 'lost_password'
            ));
            
            wp_send_json_error(array(
                'message' => __('Too many password reset attempts. Please try again in 1 hour.', 'attrla')
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

        // Get user data
        $user_data = $this->get_user_by_login_or_email($user_login);
        if (!$user_data) {
            // Log invalid attempt but return generic message
            ATTRLA_Data::log_login_attempt(array(
                'username' => $user_login,
                'ip_address' => ATTRLA_Security::get_client_ip(),
                'status' => 'failed',
                'type' => 'lost_password',
                'error' => 'invalid_user'
            ));

            wp_send_json_error(array(
                'message' => __('If that username or email address exists, you will receive a password reset email.', 'attrla')
            ));
        }

        // Generate and get reset key
        $key = get_password_reset_key($user_data);
        if (is_wp_error($key)) {
            wp_send_json_error(array(
                'message' => __('Error generating password reset key. Please try again later.', 'attrla')
            ));
        }

        // Send reset email
        $sent = $this->send_reset_email($user_data, $key);
        if (!$sent) {
            wp_send_json_error(array(
                'message' => __('Error sending password reset email. Please try again later.', 'attrla')
            ));
        }

        // Log successful request
        ATTRLA_Data::log_login_attempt(array(
            'user_id' => $user_data->ID,
            'username' => $user_data->user_login,
            'ip_address' => ATTRLA_Security::get_client_ip(),
            'status' => 'success',
            'type' => 'lost_password'
        ));

        wp_send_json_success(array(
            'message' => __('If that username or email address exists, you will receive a password reset email.', 'attrla')
        ));
    }

    /**
     * Check if the request is rate limited.
     *
     * @return boolean True if rate limited, false otherwise.
     */
    private function is_request_rate_limited() {
        $ip_address = ATTRLA_Security::get_client_ip();
        $attempts = ATTRLA_Data::get_recent_attempts(array(
            'ip_address' => $ip_address,
            'type' => 'lost_password',
            'timeframe' => 3600 // 1 hour
        ));

        return count($attempts) >= 3; // Maximum 3 attempts per hour
    }

    /**
     * Get user by login or email.
     *
     * @param string $login_or_email
     * @return WP_User|false
     */
    private function get_user_by_login_or_email($login_or_email) {
        if (is_email($login_or_email)) {
            return get_user_by('email', $login_or_email);
        }
        return get_user_by('login', $login_or_email);
    }

    /**
     * Send password reset email.
     *
     * @param WP_User $user
     * @param string  $key
     * @return boolean
     */
    private function send_reset_email($user, $key) {
        $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        $reset_url = add_query_arg(
            array(
                'action' => 'rp',
                'key' => $key,
                'login' => rawurlencode($user->user_login)
            ),
            wp_login_url()
        );

        $message = $this->get_reset_email_message($user, $reset_url, $site_name);
        $title = $this->get_reset_email_title($site_name);

        return wp_mail($user->user_email, $title, $message);
    }

    /**
     * Get reset email message.
     *
     * @param WP_User $user
     * @param string  $reset_url
     * @param string  $site_name
     * @return string
     */
    private function get_reset_email_message($user, $reset_url, $site_name) {
        $message = __('Someone has requested a password reset for the following account:', 'attrla') . "\r\n\r\n";
        $message .= sprintf(__('Site Name: %s', 'attrla'), $site_name) . "\r\n";
        $message .= sprintf(__('Username: %s', 'attrla'), $user->user_login) . "\r\n\r\n";
        $message .= __('If this was a mistake, ignore this email and nothing will happen.', 'attrla') . "\r\n\r\n";
        $message .= __('To reset your password, visit the following address:', 'attrla') . "\r\n\r\n";
        $message .= $reset_url . "\r\n\r\n";
        $message .= __('This link will expire in 24 hours.', 'attrla') . "\r\n";

        return apply_filters('attrla_reset_password_message', $message, $user, $reset_url);
    }

    /**
     * Get reset email title.
     *
     * @param string $site_name
     * @return string
     */
    private function get_reset_email_title($site_name) {
        return sprintf(
            /* translators: Password reset email subject. %s: Site name */
            __('[%s] Password Reset Request', 'attrla'),
            $site_name
        );
    }

    /**
     * Show rate limit notice if applicable.
     */
    public function maybe_show_rate_limit_notice() {
        if ($this->is_request_rate_limited()) {
            echo '<div class="attrla-message attrla-message-error">';
            esc_html_e('Too many password reset attempts. Please try again in 1 hour.', 'attrla');
            echo '</div>';
        }
    }
}