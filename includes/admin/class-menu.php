<?php
/**
 * Handles the admin menu functionality.
 *
 * @package    AttributeLoginAccess
 * @subpackage AttributeLoginAccess/admin
 */

class ATTRLA_Menu {
    /**
     * The capability required to access plugin settings.
     *
     * @var string
     */
    private $capability = 'manage_options';

    /**
     * Initialize the class.
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'create_admin_menus'));
        add_action('admin_head', array($this, 'add_menu_icons_styles'));
        add_filter('admin_body_class', array($this, 'add_admin_body_class'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
    }

    /**
     * Create admin menus and submenus.
     */
    public function create_admin_menus() {
        // Main menu
        add_menu_page(
            __('Login Security', 'attrla'),
            __('Login Security', 'attrla'),
            $this->capability,
            'attrla-dashboard',
            array($this, 'render_dashboard_page'),
            'dashicons-shield',
            80
        );

        // Dashboard submenu
        add_submenu_page(
            'attrla-dashboard',
            __('Dashboard', 'attrla'),
            __('Dashboard', 'attrla'),
            $this->capability,
            'attrla-dashboard',
            array($this, 'render_dashboard_page')
        );

        // Settings submenu
        add_submenu_page(
            'attrla-dashboard',
            __('Settings', 'attrla'),
            __('Settings', 'attrla'),
            $this->capability,
            'attrla-settings',
            array($this, 'render_settings_page')
        );

        // Security Logs submenu
        add_submenu_page(
            'attrla-dashboard',
            __('Security Logs', 'attrla'),
            __('Security Logs', 'attrla'),
            $this->capability,
            'attrla-logs',
            array($this, 'render_logs_page')
        );

        // IP Management submenu
        add_submenu_page(
            'attrla-dashboard',
            __('IP Management', 'attrla'),
            __('IP Management', 'attrla'),
            $this->capability,
            'attrla-ip-management',
            array($this, 'render_ip_management_page')
        );

        // User Sessions submenu
        add_submenu_page(
            'attrla-dashboard',
            __('Active Sessions', 'attrla'),
            __('Active Sessions', 'attrla'),
            $this->capability,
            'attrla-sessions',
            array($this, 'render_sessions_page')
        );

        // Add hidden export page
        add_submenu_page(
            null,
            __('Export Logs', 'attrla'),
            __('Export Logs', 'attrla'),
            $this->capability,
            'attrla-export-logs',
            array($this, 'handle_logs_export')
        );
    }

    /**
     * Add custom menu icons styles.
     */
    public function add_menu_icons_styles() {
        ?>
        <style>
            #adminmenu .toplevel_page_attrla-dashboard .wp-menu-image img {
                padding: 7px 0 0;
                opacity: 0.8;
            }
            .attrla-menu-badge {
                display: inline-block;
                vertical-align: top;
                margin: 1px 0 0 2px;
                padding: 0 5px;
                min-width: 7px;
                height: 17px;
                border-radius: 11px;
                background-color: #ca4a1f;
                color: #fff;
                font-size: 9px;
                line-height: 17px;
                text-align: center;
                z-index: 26;
            }
        </style>
        <?php
    }

    /**
     * Add admin body class for plugin pages.
     *
     * @param string $classes Space-separated list of classes.
     * @return string Modified list of classes.
     */
    public function add_admin_body_class($classes) {
        $screen = get_current_screen();
        if (strpos($screen->id, 'attrla') !== false) {
            $classes .= ' attrla-admin-page';
        }
        return $classes;
    }

    /**
     * Handle admin actions like exports and bulk operations.
     */
    public function handle_admin_actions() {
        if (!current_user_can($this->capability)) {
            return;
        }

        if (isset($_GET['action']) && isset($_GET['page'])) {
            switch ($_GET['action']) {
                case 'export_logs':
                    if ($_GET['page'] === 'attrla-logs' && 
                        check_admin_referer('attrla_export_logs')) {
                        $this->export_security_logs();
                    }
                    break;

                case 'clear_logs':
                    if ($_GET['page'] === 'attrla-logs' && 
                        check_admin_referer('attrla_clear_logs')) {
                        $this->clear_security_logs();
                        wp_redirect(add_query_arg(
                            'message', 
                            'logs-cleared', 
                            admin_url('admin.php?page=attrla-logs')
                        ));
                        exit;
                    }
                    break;

                case 'unlock_ip':
                    if ($_GET['page'] === 'attrla-ip-management' && 
                        isset($_GET['ip']) && 
                        check_admin_referer('attrla_unlock_ip_' . $_GET['ip'])) {
                        $this->unlock_ip($_GET['ip']);
                        wp_redirect(add_query_arg(
                            'message', 
                            'ip-unlocked', 
                            admin_url('admin.php?page=attrla-ip-management')
                        ));
                        exit;
                    }
                    break;
            }
        }
    }

    /**
     * Render dashboard page.
     */
    public function render_dashboard_page() {
        // Get statistics
        $stats = $this->get_dashboard_statistics();
        
        // Include dashboard template
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/dashboard-page.php';
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        // Include settings template
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/settings-page.php';
    }

    /**
     * Render security logs page.
     */
    public function render_logs_page() {
        // Create an instance of the logs table
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-logs-table.php';
        $logs_table = new ATTRLA_Logs_Table();
        $logs_table->prepare_items();

        // Include logs template
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/logs-page.php';
    }

    /**
     * Render IP management page.
     */
    public function render_ip_management_page() {
        // Get IP data
        $blocked_ips = ATTRLA_Security::get_blocked_ips();
        $whitelisted_ips = ATTRLA_Security::get_whitelisted_ips();

        // Include IP management template
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ip-management-page.php';
    }

    /**
     * Render active sessions page.
     */
    public function render_sessions_page() {
        // Get active sessions
        $sessions = ATTRLA_Security::get_active_sessions();

        // Include sessions template
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/sessions-page.php';
    }

    /**
     * Get dashboard statistics.
     *
     * @return array Statistics data.
     */
    private function get_dashboard_statistics() {
        return array(
            'total_blocked_attempts' => ATTRLA_Data::get_blocked_attempts_count(),
            'active_lockouts' => ATTRLA_Security::get_active_lockouts_count(),
            'blocked_ips' => count(ATTRLA_Security::get_blocked_ips()),
            'last_week_attempts' => ATTRLA_Data::get_attempts_by_period('week'),
            'recent_events' => ATTRLA_Data::get_recent_security_events(5)
        );
    }

    /**
     * Export security logs.
     */
    private function export_security_logs() {
        $logs = ATTRLA_Data::get_all_logs();
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="security-logs-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, array(
            'Date',
            'Event Type',
            'IP Address',
            'Username',
            'Status',
            'Message'
        ));
        
        // Add log entries
        foreach ($logs as $log) {
            fputcsv($output, array(
                $log->created_at,
                $log->event_type,
                $log->ip_address,
                $log->username,
                $log->status,
                $log->message
            ));
        }
        
        fclose($output);
        exit;
    }

    /**
     * Clear security logs.
     */
    private function clear_security_logs() {
        ATTRLA_Data::clear_logs();
    }

    /**
     * Unlock an IP address.
     *
     * @param string $ip The IP address to unlock.
     */
    private function unlock_ip($ip) {
        ATTRLA_Security::unlock_ip($ip);
    }

    /**
     * Get admin menu items.
     *
     * @return array Menu items configuration.
     */
    public function get_menu_items() {
        return array(
            array(
                'title' => __('Dashboard', 'attrla'),
                'slug' => 'attrla-dashboard',
                'icon' => 'dashicons-dashboard'
            ),
            array(
                'title' => __('Settings', 'attrla'),
                'slug' => 'attrla-settings',
                'icon' => 'dashicons-admin-generic'
            ),
            array(
                'title' => __('Security Logs', 'attrla'),
                'slug' => 'attrla-logs',
                'icon' => 'dashicons-list-view'
            ),
            array(
                'title' => __('IP Management', 'attrla'),
                'slug' => 'attrla-ip-management',
                'icon' => 'dashicons-shield'
            ),
            array(
                'title' => __('Active Sessions', 'attrla'),
                'slug' => 'attrla-sessions',
                'icon' => 'dashicons-groups'
            )
        );
    }
}