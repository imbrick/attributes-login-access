<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package    AttributeLoginAccess
 * @subpackage AttributeLoginAccess/includes
 */

class ATTRLA_Core {
    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @access   protected
     * @var      ATTRLA_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->version = ATTRLA_VERSION;
        $this->plugin_name = 'attribute-login-access';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_security_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @access   private
     */
    private function load_dependencies() {
        // Core plugin classes
        require_once ATTRLA_PLUGIN_DIR . 'includes/core/class-loader.php';
        require_once ATTRLA_PLUGIN_DIR . 'includes/core/class-i18n.php';
        require_once ATTRLA_PLUGIN_DIR . 'includes/core/class-security.php';
        require_once ATTRLA_PLUGIN_DIR . 'includes/core/class-auth.php';
        require_once ATTRLA_PLUGIN_DIR . 'includes/core/class-data.php';
        require_once ATTRLA_PLUGIN_DIR . 'includes/core/class-session.php';

        // Admin and public classes
        require_once ATTRLA_PLUGIN_DIR . 'includes/admin/class-admin.php';
        require_once ATTRLA_PLUGIN_DIR . 'includes/public/class-public.php';

        $this->loader = new ATTRLA_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new ATTRLA_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     *
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new ATTRLA_Admin($this->get_plugin_name(), $this->get_version());

        // Admin assets
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Admin menu and settings
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_menu_pages');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');

        // Plugin action links
        $this->loader->add_filter(
            'plugin_action_links_' . plugin_basename(ATTRLA_PLUGIN_DIR . 'attribute-login-access.php'),
            $plugin_admin,
            'add_settings_link'
        );
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     *
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new ATTRLA_Public($this->get_plugin_name(), $this->get_version());

        // Public assets
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

        // Register shortcodes
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');

        // Login form customization
        $this->loader->add_action('login_form', $plugin_public, 'add_login_fields');
        $this->loader->add_filter('login_form_middle', $plugin_public, 'add_login_form_middle');
    }

    /**
     * Register all of the hooks related to security functionality.
     *
     * @access   private
     */
    private function define_security_hooks() {
        $security = new ATTRLA_Security();
        $auth = new ATTRLA_Auth();

        // Authentication hooks
        $this->loader->add_filter('authenticate', $security, 'check_authentication', 30, 3);
        $this->loader->add_action('wp_login_failed', $security, 'handle_failed_login');
        $this->loader->add_action('wp_login', $security, 'handle_successful_login', 10, 2);

        // Password reset hooks
        $this->loader->add_action('retrieve_password', $auth, 'handle_password_reset_request');
        $this->loader->add_action('password_reset', $auth, 'handle_password_reset', 10, 2);

        // Session management
        $this->loader->add_action('init', $security, 'initialize_session_protection');
        $this->loader->add_action('wp_login', $security, 'regenerate_session_id', 10);
        $this->loader->add_action('wp_logout', $security, 'destroy_session');

        // Cleanup tasks
        $this->loader->add_action('wp_scheduled_delete', $security, 'cleanup_expired_data');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    ATTRLA_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }
}