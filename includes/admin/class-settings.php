<?php
/**
 * Handles the settings functionality of the plugin.
 *
 * @package    AttributeLoginAccess
 * @subpackage AttributeLoginAccess/admin
 */

class ATTRLA_Settings {
    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_attrla_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_attrla_reset_settings', array($this, 'ajax_reset_settings'));
    }

    /**
     * Register all settings sections and fields.
     */
    public function register_settings() {
        // General Security Settings
        add_settings_section(
            'attrla_security_settings',
            __('General Security Settings', 'attrla'),
            array($this, 'render_security_settings_section'),
            'attrla_settings'
        );

        // Login Protection Settings
        $this->add_login_protection_fields();

        // Password Requirements Settings
        $this->add_password_requirement_fields();

        // Notification Settings
        $this->add_notification_fields();

        // IP Management Settings
        $this->add_ip_management_fields();

        // Logging Settings
        $this->add_logging_fields();
    }

    /**
     * Add login protection fields.
     */
    private function add_login_protection_fields() {
        $fields = array(
            'max_login_attempts' => array(
                'title' => __('Maximum Login Attempts', 'attrla'),
                'callback' => 'render_number_field',
                'args' => array(
                    'label_for' => 'max_login_attempts',
                    'description' => __('Number of failed attempts before lockout.', 'attrla'),
                    'min' => 1,
                    'max' => 20,
                    'default' => 5
                )
            ),
            'lockout_duration' => array(
                'title' => __('Lockout Duration (minutes)', 'attrla'),
                'callback' => 'render_number_field',
                'args' => array(
                    'label_for' => 'lockout_duration',
                    'description' => __('How long to lock out users after failed attempts.', 'attrla'),
                    'min' => 5,
                    'max' => 1440,
                    'default' => 30
                )
            ),
            'progressive_lockout' => array(
                'title' => __('Progressive Lockout', 'attrla'),
                'callback' => 'render_checkbox_field',
                'args' => array(
                    'label_for' => 'progressive_lockout',
                    'description' => __('Increase lockout duration for repeat offenders.', 'attrla'),
                    'default' => true
                )
            )
        );

        foreach ($fields as $id => $field) {
            add_settings_field(
                'attrla_' . $id,
                $field['title'],
                array($this, $field['callback']),
                'attrla_settings',
                'attrla_security_settings',
                array_merge($field['args'], array('id' => $id))
            );
        }
    }

    /**
     * Add password requirement fields.
     */
    private function add_password_requirement_fields() {
        $fields = array(
            'min_password_length' => array(
                'title' => __('Minimum Password Length', 'attrla'),
                'callback' => 'render_number_field',
                'args' => array(
                    'label_for' => 'min_password_length',
                    'description' => __('Minimum required password length.', 'attrla'),
                    'min' => 8,
                    'max' => 64,
                    'default' => 12
                )
            ),
            'require_special_char' => array(
                'title' => __('Require Special Character', 'attrla'),
                'callback' => 'render_checkbox_field',
                'args' => array(
                    'label_for' => 'require_special_char',
                    'description' => __('Require at least one special character.', 'attrla'),
                    'default' => true
                )
            ),
            'require_uppercase' => array(
                'title' => __('Require Uppercase', 'attrla'),
                'callback' => 'render_checkbox_field',
                'args' => array(
                    'label_for' => 'require_uppercase',
                    'description' => __('Require at least one uppercase letter.', 'attrla'),
                    'default' => true
                )
            ),
            'require_number' => array(
                'title' => __('Require Number', 'attrla'),
                'callback' => 'render_checkbox_field',
                'args' => array(
                    'label_for' => 'require_number',
                    'description' => __('Require at least one number.', 'attrla'),
                    'default' => true
                )
            )
        );

        foreach ($fields as $id => $field) {
            add_settings_field(
                'attrla_' . $id,
                $field['title'],
                array($this, $field['callback']),
                'attrla_settings',
                'attrla_security_settings',
                array_merge($field['args'], array('id' => $id))
            );
        }
    }

    /**
     * Add notification fields.
     */
    private function add_notification_fields() {
        $fields = array(
            'notify_admin_lockout' => array(
                'title' => __('Notify on Lockout', 'attrla'),
                'callback' => 'render_checkbox_field',
                'args' => array(
                    'label_for' => 'notify_admin_lockout',
                    'description' => __('Send email notification when a user is locked out.', 'attrla'),
                    'default' => true
                )
            ),
            'notify_user_lockout' => array(
                'title' => __('Notify User on Lockout', 'attrla'),
                'callback' => 'render_checkbox_field',
                'args' => array(
                    'label_for' => 'notify_user_lockout',
                    'description' => __('Send email notification to user when they are locked out.', 'attrla'),
                    'default' => true
                )
            )
        );

        foreach ($fields as $id => $field) {
            add_settings_field(
                'attrla_' . $id,
                $field['title'],
                array($this, $field['callback']),
                'attrla_settings',
                'attrla_security_settings',
                array_merge($field['args'], array('id' => $id))
            );
        }
    }

    /**
     * Add IP management fields.
     */
    private function add_ip_management_fields() {
        $fields = array(
            'ip_whitelist' => array(
                'title' => __('IP Whitelist', 'attrla'),
                'callback' => 'render_textarea_field',
                'args' => array(
                    'label_for' => 'ip_whitelist',
                    'description' => __('Comma-separated list of IPs to always allow.', 'attrla'),
                    'placeholder' => '192.168.1.1, 10.0.0.1'
                )
            ),
            'ip_blacklist' => array(
                'title' => __('IP Blacklist', 'attrla'),
                'callback' => 'render_textarea_field',
                'args' => array(
                    'label_for' => 'ip_blacklist',
                    'description' => __('Comma-separated list of IPs to always block.', 'attrla'),
                    'placeholder' => '192.168.1.2, 10.0.0.2'
                )
            )
        );

        foreach ($fields as $id => $field) {
            add_settings_field(
                'attrla_' . $id,
                $field['title'],
                array($this, $field['callback']),
                'attrla_settings',
                'attrla_security_settings',
                array_merge($field['args'], array('id' => $id))
            );
        }
    }

    /**
     * Add logging fields.
     */
    private function add_logging_fields() {
        $fields = array(
            'enable_logging' => array(
                'title' => __('Enable Logging', 'attrla'),
                'callback' => 'render_checkbox_field',
                'args' => array(
                    'label_for' => 'enable_logging',
                    'description' => __('Enable detailed security event logging.', 'attrla'),
                    'default' => true
                )
            ),
            'log_retention_days' => array(
                'title' => __('Log Retention (days)', 'attrla'),
                'callback' => 'render_number_field',
                'args' => array(
                    'label_for' => 'log_retention_days',
                    'description' => __('Number of days to keep security logs.', 'attrla'),
                    'min' => 1,
                    'max' => 365,
                    'default' => 30
                )
            )
        );

        foreach ($fields as $id => $field) {
            add_settings_field(
                'attrla_' . $id,
                $field['title'],
                array($this, $field['callback']),
                'attrla_settings',
                'attrla_security_settings',
                array_merge($field['args'], array('id' => $id))
            );
        }
    }

    /**
     * Render form field callbacks
     */
    public function render_number_field($args) {
        $options = get_option('attrla_settings');
        $value = isset($options[$args['id']]) ? $options[$args['id']] : $args['default'];
        
        printf(
            '<input type="number" id="attrla_%1$s" name="attrla_settings[%1$s]" value="%2$s" min="%3$s" max="%4$s" class="regular-text">
            <p class="description">%5$s</p>',
            esc_attr($args['id']),
            esc_attr($value),
            esc_attr($args['min']),
            esc_attr($args['max']),
            esc_html($args['description'])
        );
    }

    public function render_checkbox_field($args) {
        $options = get_option('attrla_settings');
        $checked = isset($options[$args['id']]) ? $options[$args['id']] : $args['default'];
        
        printf(
            '<label for="attrla_%1$s">
                <input type="checkbox" id="attrla_%1$s" name="attrla_settings[%1$s]" %2$s>
                %3$s
            </label>',
            esc_attr($args['id']),
            checked($checked, true, false),
            esc_html($args['description'])
        );
    }

    public function render_textarea_field($args) {
        $options = get_option('attrla_settings');
        $value = isset($options[$args['id']]) ? $options[$args['id']] : '';
        
        printf(
            '<textarea id="attrla_%1$s" name="attrla_settings[%1$s]" rows="4" class="large-text" placeholder="%2$s">%3$s</textarea>
            <p class="description">%4$s</p>',
            esc_attr($args['id']),
            esc_attr($args['placeholder']),
            esc_textarea($value),
            esc_html($args['description'])
        );
    }

    /**
     * AJAX handlers
     */
    public function ajax_save_settings() {
        // Verify nonce and permissions
        if (!check_ajax_referer('attrla-admin-nonce', 'nonce', false) || 
            !current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized access.', 'attrla'));
        }

        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        $sanitized = $this->sanitize_settings($settings);
        
        update_option('attrla_settings', $sanitized);
        wp_send_json_success(__('Settings saved successfully.', 'attrla'));
    }

    public function ajax_reset_settings() {
        // Verify nonce and permissions
        if (!check_ajax_referer('attrla-admin-nonce', 'nonce', false) || 
            !current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized access.', 'attrla'));
        }

        // Get default settings
        $defaults = $this->get_default_settings();
        update_option('attrla_settings', $defaults);
        
        wp_send_json_success(array(
            'message' => __('Settings reset to defaults.', 'attrla'),
            'settings' => $defaults
        ));
    }

    /**
     * Get default settings.
     *
     * @return array Default settings.
     */
    private function get_default_settings() {
        return array(
            'max_login_attempts' => 5,
            'lockout_duration' => 30,
            'progressive_lockout' => true,
            'min_password_length' => 12,
            'require_special_char' => true,
            'require_uppercase' => true,
            'require_number' => true,
            'notify_admin_lockout' => true,
            'notify_user_lockout' => true,
            'enable_logging' => true,
            'log_retention_days' => 30,
            'ip_whitelist' => '',
            'ip_blacklist' => ''
        );
    }
}