<?php
/**
 * Plugin Name: Attribute Login Access
 * Plugin URI: https://imbrick.com/plugins/attribute-login-access
 * Description: Enhanced login security with IP tracking, attempt limiting, and temporary lockouts.
 * Version: 1.0.0
 * Author: Imbrick
 * Author URI: https://imbrick.com
 * Text Domain: attrla
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version
define('ATTRLA_VERSION', '1.0.0');

// Plugin directory
define('ATTRLA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ATTRLA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoloader function
function attrla_autoloader($class) {
    // Base directory for classes
    $base_dir = ATTRLA_PLUGIN_DIR . 'includes/';
    
    // Class map for core files
    $core_classes = array(
        'ATTRLA_Core' => 'core/class-core.php',
        'ATTRLA_Loader' => 'core/class-loader.php',
        'ATTRLA_i18n' => 'core/class-i18n.php',
        'ATTRLA_Security' => 'core/class-security.php',
        'ATTRLA_Auth' => 'core/class-auth.php',
        'ATTRLA_Session' => 'core/class-session.php',
        'ATTRLA_Data' => 'core/class-data.php'
    );
    
    // Check if the class is in our map
    if (isset($core_classes[$class])) {
        $file = $base_dir . $core_classes[$class];
        if (file_exists($file)) {
            require $file;
        }
    }
}

// Register the autoloader
spl_autoload_register('attrla_autoloader');

/**
 * Begin execution of the plugin
 */
function run_attribute_login_access() {
    // Initialize the core class
    $plugin = new ATTRLA_Core();
    $plugin->run();
}

// Hook into WordPress init
add_action('plugins_loaded', 'run_attribute_login_access');

// Activation hook
register_activation_hook(__FILE__, 'attrla_activate');
function attrla_activate() {
    // Create necessary database tables
    require_once ATTRLA_PLUGIN_DIR . 'includes/core/class-data.php';
    ATTRLA_Data::create_tables();
    
    // Set default options
    if (!get_option('attrla_settings')) {
        $default_settings = array(
            'max_attempts' => 5,
            'lockout_duration' => 15, // minutes
            'track_failed_attempts' => true,
            'track_successful_attempts' => false,
            'notify_admin' => true
        );
        update_option('attrla_settings', $default_settings);
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'attrla_deactivate');
function attrla_deactivate() {
    // Clean up scheduled tasks
    wp_clear_scheduled_hook('attrla_cleanup_lockouts');
}