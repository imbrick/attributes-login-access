<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    AttributeLoginAccess
 * @subpackage AttributeLoginAccess/admin
 */

class ATTRLA_Admin {
    /**
     * The ID of this plugin.
     *
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Load dependencies
        $this->load_dependencies();

        // Add menu items
        add_action('admin_menu', array($this, 'add_menu_pages'));

        // Add settings link to plugins page
        add_filter('plugin_action_links_' . plugin_basename(ATTRLA_PLUGIN_DIR . 'attribute-login-access.php'), 
                  array($this, 'add_settings_link'));
    }

    /**
     * Load the required dependencies for the admin area.
     *
     * @access   private
     */
    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-menu.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-settings.php';
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name . '-admin',
            plugin_dir_url(__FILE__) . 'assets/css/admin-style.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name . '-admin',
            plugin_dir_url(__FILE__) . 'assets/js/admin-script.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_enqueue_script(
            $this->plugin_name . '-settings',
            plugin_dir_url(__FILE__) . 'assets/js/settings-manager.js',
            array('jquery', $this->plugin_name . '-admin'),
            $this->version,
            true
        );

        wp_localize_script($this->plugin_name . '-admin', 'attrlaAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('attrla-admin-nonce'),
            'i18n' => array(
                'confirmReset' => __('Are you sure you want to reset these settings to defaults?', 'attrla'),
                'confirmClearLogs' => __('Are you sure you want to clear all logs? This cannot be undone.', 'attrla'),
                'saveSuccess' => __('Settings saved successfully.', 'attrla'),
                'saveError' => __('Error saving settings. Please try again.', 'attrla'),
                'invalidData' => __('Invalid data provided. Please check your inputs.', 'attrla')
            )
        ));
    }

    /**
     * Add menu items to the admin area.
     */
    public function add_menu_pages() {
        // Main menu item
        add_menu_page(
            __('Login Security', 'attrla'),
            __('Login Security', 'attrla'),
            'manage_options',
            'attrla-settings',
            array($this, 'display_settings_page'),
            'dashicons-shield',
            80
        );

        // Settings submenu
        add_submenu_page(
            'attrla-settings',
            __('Settings', 'attrla'),
            __('Settings', 'attrla'),
            'manage_options',
            'attrla-settings',
            array($this, 'display_settings_page')
        );

        // Security Logs submenu
        add_submenu_page(
            'attrla-settings',
            __('Security Logs', 'attrla'),
            __('Security Logs', 'attrla'),
            'manage_options',
            'attrla-logs',
            array($this, 'display_logs_page')
        );

        // IP Management submenu
        add_submenu_page(
            'attrla-settings',
            __('IP Management', 'attrla'),
            __('IP Management', 'attrla'),
            'manage_options',
            'attrla-ip-management',
            array($this, 'display_ip_management_page')
        );
    }

    /**
     * Add settings link to plugins page.
     *
     * @param  array $links Array of plugin action links.
     * @return array        Modified array of plugin action links.
     */
    public function add_settings_link($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=attrla-settings'),
            __('Settings', 'attrla')
        );
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Display the settings page content.
     */
    public function display_settings_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/settings-page.php';
    }

    /**
     * Display the security logs page content.
     */
    public function display_logs_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/logs-page.php';
    }

    /**
     * Display the IP management page content.
     */
    public function display_ip_management_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ip-management-page.php';
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting(
            'attrla_settings',
            'attrla_security_options',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_settings')
            )
        );

        // Settings sections and fields will be added in class-settings.php
    }

    /**
     * Sanitize settings before saving.
     *
     * @param  array $input The settings input array.
     * @return array        The sanitized settings array.
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        // Basic settings
        $sanitized['max_login_attempts'] = absint($input['max_login_attempts']);
        $sanitized['lockout_duration'] = absint($input['lockout_duration']);
        $sanitized['track_failed_attempts'] = isset($input['track_failed_attempts']);
        $sanitized['notify_admin'] = isset($input['notify_admin']);

        // Password requirements
        $sanitized['min_password_length'] = absint($input['min_password_length']);
        $sanitized['require_special_char'] = isset($input['require_special_char']);
        $sanitized['require_uppercase'] = isset($input['require_uppercase']);
        $sanitized['require_number'] = isset($input['require_number']);

        // IP settings
        $sanitized['ip_blacklist'] = $this->sanitize_ip_list($input['ip_blacklist']);
        $sanitized['ip_whitelist'] = $this->sanitize_ip_list($input['ip_whitelist']);

        return apply_filters('attrla_sanitize_settings', $sanitized, $input);
    }

    /**
     * Sanitize list of IP addresses.
     *
     * @param  string $ip_list Comma-separated list of IPs.
     * @return string          Sanitized comma-separated list of IPs.
     */
    private function sanitize_ip_list($ip_list) {
        if (empty($ip_list)) {
            return '';
        }

        $ips = array_map('trim', explode(',', $ip_list));
        $valid_ips = array();

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $valid_ips[] = $ip;
            }
        }

        return implode(',', $valid_ips);
    }
}