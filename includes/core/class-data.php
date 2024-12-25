<?php
/**
 * Handle data management functionality.
 *
 * @package    AttributeLoginAccess
 * @subpackage AttributeLoginAccess/includes
 */

class ATTRLA_Data {
    /**
     * Initialize the class.
     */
    public function __construct() {
        // Add cleanup schedule if not exists
        if (!wp_next_scheduled('attrla_cleanup_data')) {
            wp_schedule_event(time(), 'daily', 'attrla_cleanup_data');
        }
        add_action('attrla_cleanup_data', array($this, 'cleanup_old_data'));
    }

    /**
     * Create plugin database tables.
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();

        // Login attempts table
        $table_attempts = $wpdb->prefix . 'attrla_login_attempts';
        $sql_attempts = "CREATE TABLE IF NOT EXISTS $table_attempts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            username varchar(60) NOT NULL,
            ip_address varchar(45) NOT NULL,
            status varchar(20) NOT NULL,
            type varchar(20) NOT NULL,
            user_agent varchar(255) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY username (username),
            KEY ip_address (ip_address),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Security events table
        $table_events = $wpdb->prefix . 'attrla_security_events';
        $sql_events = "CREATE TABLE IF NOT EXISTS $table_events (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            ip_address varchar(45) NOT NULL,
            details text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // IP tracking table
        $table_ips = $wpdb->prefix . 'attrla_ip_tracking';
        $sql_ips = "CREATE TABLE IF NOT EXISTS $table_ips (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            country_code char(2) DEFAULT NULL,
            last_seen datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            total_attempts int(11) UNSIGNED DEFAULT 0,
            failed_attempts int(11) UNSIGNED DEFAULT 0,
            is_blocked tinyint(1) NOT NULL DEFAULT 0,
            block_expires datetime DEFAULT NULL,
            notes text DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY ip_address (ip_address),
            KEY is_blocked (is_blocked)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_attempts);
        dbDelta($sql_events);
        dbDelta($sql_ips);
    }

    /**
     * Log login attempt.
     *
     * @param array $data Login attempt data
     * @return int|false The number of rows inserted, or false on error
     */
    public static function log_login_attempt($data) {
        global $wpdb;
        
        $defaults = array(
            'user_id' => null,
            'username' => '',
            'ip_address' => '',
            'status' => '',
            'type' => 'login',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
        );

        $data = wp_parse_args($data, $defaults);
        
        return $wpdb->insert(
            $wpdb->prefix . 'attrla_login_attempts',
            array(
                'user_id' => $data['user_id'],
                'username' => $data['username'],
                'ip_address' => $data['ip_address'],
                'status' => $data['status'],
                'type' => $data['type'],
                'user_agent' => $data['user_agent'],
                'created_at' => current_time('mysql')
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s'
            )
        );
    }

    /**
     * Log security event.
     *
     * @param string $event_type Event type
     * @param array  $data      Event data
     * @return int|false The number of rows inserted, or false on error
     */
    public static function log_security_event($event_type, $data = array()) {
        global $wpdb;
        
        $defaults = array(
            'user_id' => get_current_user_id(),
            'ip_address' => ATTRLA_Security::get_client_ip(),
            'details' => ''
        );

        $data = wp_parse_args($data, $defaults);
        
        if (is_array($data['details'])) {
            $data['details'] = wp_json_encode($data['details']);
        }

        return $wpdb->insert(
            $wpdb->prefix . 'attrla_security_events',
            array(
                'event_type' => $event_type,
                'user_id' => $data['user_id'],
                'ip_address' => $data['ip_address'],
                'details' => $data['details'],
                'created_at' => current_time('mysql')
            ),
            array(
                '%s',
                '%d',
                '%s',
                '%s',
                '%s'
            )
        );
    }

    /**
     * Get recent login attempts.
     *
     * @param array $args Query arguments
     * @return array Array of login attempts
     */
    public static function get_recent_attempts($args = array()) {
        global $wpdb;

        $defaults = array(
            'username' => '',
            'ip_address' => '',
            'status' => '',
            'type' => '',
            'timeframe' => 3600, // Last hour
            'limit' => 100
        );

        $args = wp_parse_args($args, $defaults);
        $where = array('1=1');
        $prepare = array();

        if (!empty($args['username'])) {
            $where[] = 'username = %s';
            $prepare[] = $args['username'];
        }

        if (!empty($args['ip_address'])) {
            $where[] = 'ip_address = %s';
            $prepare[] = $args['ip_address'];
        }

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $prepare[] = $args['status'];
        }

        if (!empty($args['type'])) {
            $where[] = 'type = %s';
            $prepare[] = $args['type'];
        }

        if (!empty($args['timeframe'])) {
            $where[] = 'created_at >= DATE_SUB(NOW(), INTERVAL %d SECOND)';
            $prepare[] = $args['timeframe'];
        }

        $sql = sprintf(
            "SELECT * FROM {$wpdb->prefix}attrla_login_attempts WHERE %s ORDER BY created_at DESC LIMIT %d",
            implode(' AND ', $where),
            absint($args['limit'])
        );

        if (!empty($prepare)) {
            $sql = $wpdb->prepare($sql, $prepare);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Get security events.
     *
     * @param array $args Query arguments
     * @return array Array of security events
     */
    public static function get_security_events($args = array()) {
        global $wpdb;

        $defaults = array(
            'event_type' => '',
            'user_id' => '',
            'ip_address' => '',
            'timeframe' => 86400, // Last 24 hours
            'limit' => 100
        );

        $args = wp_parse_args($args, $defaults);
        $where = array('1=1');
        $prepare = array();

        if (!empty($args['event_type'])) {
            $where[] = 'event_type = %s';
            $prepare[] = $args['event_type'];
        }

        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $prepare[] = $args['user_id'];
        }

        if (!empty($args['ip_address'])) {
            $where[] = 'ip_address = %s';
            $prepare[] = $args['ip_address'];
        }

        if (!empty($args['timeframe'])) {
            $where[] = 'created_at >= DATE_SUB(NOW(), INTERVAL %d SECOND)';
            $prepare[] = $args['timeframe'];
        }

        $sql = sprintf(
            "SELECT * FROM {$wpdb->prefix}attrla_security_events WHERE %s ORDER BY created_at DESC LIMIT %d",
            implode(' AND ', $where),
            absint($args['limit'])
        );

        if (!empty($prepare)) {
            $sql = $wpdb->prepare($sql, $prepare);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Update IP tracking information.
     *
     * @param string $ip_address IP address
     * @param array  $data      Tracking data
     * @return int|false The number of rows affected, or false on error
     */
    public static function update_ip_tracking($ip_address, $data) {
        global $wpdb;

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}attrla_ip_tracking WHERE ip_address = %s",
                $ip_address
            )
        );

        if ($exists) {
            return $wpdb->update(
                $wpdb->prefix . 'attrla_ip_tracking',
                $data,
                array('ip_address' => $ip_address),
                array('%s', '%d', '%d', '%d', '%s', '%s'),
                array('%s')
            );
        } else {
            return $wpdb->insert(
                $wpdb->prefix . 'attrla_ip_tracking',
                array_merge(array('ip_address' => $ip_address), $data),
                array('%s', '%s', '%d', '%d', '%d', '%s', '%s')
            );
        }
    }

    /**
     * Clean up old data.
     */
    public function cleanup_old_data() {
        global $wpdb;

        $retention_days = get_option('attrla_data_retention_days', 30);
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));

        // Clean up old login attempts
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}attrla_login_attempts WHERE created_at < %s",
                $cutoff_date
            )
        );

        // Clean up old security events
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}attrla_security_events WHERE created_at < %s",
                $cutoff_date
            )
        );

        // Clean up IP tracking for IPs not seen in 30 days
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}attrla_ip_tracking WHERE last_seen < %s AND is_blocked = 0",
                $cutoff_date
            )
        );
    }

    /**
     * Get statistics for dashboard.
     *
     * @return array Statistics data
     */
    public static function get_statistics() {
        global $wpdb;

        $stats = array();

        // Total failed attempts today
        $stats['failed_today'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}attrla_login_attempts 
                WHERE status = 'failed' AND created_at >= %s",
                date('Y-m-d 00:00:00')
            )
        );

        // Currently blocked IPs
        $stats['blocked_ips'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}attrla_ip_tracking WHERE is_blocked = 1"
        );

        // Recent successful logins
        $stats['recent_success'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}attrla_login_attempts 
                WHERE status = 'success' AND created_at >= %s",
                date('Y-m-d H:i:s', strtotime('-24 hours'))
            )
        );

        return $stats;
    }
}