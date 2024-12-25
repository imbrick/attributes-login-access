<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    AttributeLoginAccess
 * @subpackage AttributeLoginAccess/public
 */

class ATTRLA_Public {
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
     * @param    string    $plugin_name    The name of the plugin.
     * @param    string    $version        The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        $this->load_dependencies();
    }

    /**
     * Load the required dependencies for the public functionality.
     *
     * @access   private
     */
    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-login.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-register.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-lost.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-reset.php';
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'assets/css/public-style.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name . '-core',
            plugin_dir_url(__FILE__) . 'assets/js/public-script.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_enqueue_script(
            $this->plugin_name . '-login',
            plugin_dir_url(__FILE__) . 'assets/js/login.js',
            array('jquery', $this->plugin_name . '-core'),
            $this->version,
            true
        );

        wp_enqueue_script(
            $this->plugin_name . '-register',
            plugin_dir_url(__FILE__) . 'assets/js/register.js',
            array('jquery', $this->plugin_name . '-core'),
            $this->version,
            true
        );

        wp_localize_script($this->plugin_name . '-core', 'attrlAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('attrla-ajax-nonce'),
            'i18n' => array(
                'error' => __('An error occurred. Please try again.', 'attrla'),
                'passwordMismatch' => __('Passwords do not match.', 'attrla'),
                'weakPassword' => __('Password is too weak.', 'attrla'),
                'processingRequest' => __('Processing request...', 'attrla')
            )
        ));
    }

    /**
     * Register the shortcodes for the public-facing side of the site.
     */
    public function register_shortcodes() {
        add_shortcode('attrla_login_form', array($this, 'render_login_form'));
        add_shortcode('attrla_register_form', array($this, 'render_register_form'));
        add_shortcode('attrla_lost_password_form', array($this, 'render_lost_password_form'));
        add_shortcode('attrla_reset_password_form', array($this, 'render_reset_password_form'));
    }

    /**
     * Render the login form via shortcode.
     *
     * @param  array $atts    Shortcode attributes.
     * @return string         The login form HTML.
     */
    public function render_login_form($atts = array()) {
        if (!is_user_logged_in()) {
            ob_start();
            include plugin_dir_path(__FILE__) . '../templates/login-form.php';
            return ob_get_clean();
        }
        return sprintf(
            '<p>%s <a href="%s">%s</a></p>',
            __('You are already logged in.', 'attrla'),
            wp_logout_url(home_url()),
            __('Logout', 'attrla')
        );
    }

    /**
     * Render the registration form via shortcode.
     *
     * @param  array $atts    Shortcode attributes.
     * @return string         The registration form HTML.
     */
    public function render_register_form($atts = array()) {
        if (!is_user_logged_in() && get_option('users_can_register')) {
            ob_start();
            include plugin_dir_path(__FILE__) . '../templates/register-form.php';
            return ob_get_clean();
        }
        return '';
    }

    /**
     * Render the lost password form via shortcode.
     *
     * @param  array $atts    Shortcode attributes.
     * @return string         The lost password form HTML.
     */
    public function render_lost_password_form($atts = array()) {
        if (!is_user_logged_in()) {
            ob_start();
            include plugin_dir_path(__FILE__) . '../templates/lost-form.php';
            return ob_get_clean();
        }
        return '';
    }

    /**
     * Render the reset password form via shortcode.
     *
     * @param  array $atts    Shortcode attributes.
     * @return string         The reset password form HTML.
     */
    public function render_reset_password_form($atts = array()) {
        if (!is_user_logged_in()) {
            ob_start();
            include plugin_dir_path(__FILE__) . '../templates/reset-form.php';
            return ob_get_clean();
        }
        return '';
    }
}