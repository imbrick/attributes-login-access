<?php
/**
 * Settings page template
 *
 * @package AttributeLoginAccess
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get current settings
$settings = get_option('attrla_settings', array());
?>

<div class="wrap attrla-admin-wrap">
    <h1 class="wp-heading-inline">
        <?php esc_html_e('Login Security Settings', 'attrla'); ?>
    </h1>

    <!-- Settings Form -->
    <form method="post" action="options.php" id="attrla-settings-form" class="attrla-settings-form">
        <?php settings_fields('attrla_settings'); ?>

        <!-- Settings Navigation -->
        <div class="attrla-settings-nav">
            <nav class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active">
                    <?php esc_html_e('General', 'attrla'); ?>
                </a>
                <a href="#login-protection" class="nav-tab">
                    <?php esc_html_e('Login Protection', 'attrla'); ?>
                </a>
                <a href="#passwords" class="nav-tab">
                    <?php esc_html_e('Passwords', 'attrla'); ?>
                </a>
                <a href="#notifications" class="nav-tab">
                    <?php esc_html_e('Notifications', 'attrla'); ?>
                </a>
                <a href="#ip-management" class="nav-tab">
                    <?php esc_html_e('IP Management', 'attrla'); ?>
                </a>
                <a href="#logging" class="nav-tab">
                    <?php esc_html_e('Logging', 'attrla'); ?>
                </a>
            </nav>
        </div>

        <!-- General Settings -->
        <div id="general" class="attrla-settings-panel active">
            <div class="attrla-settings-section">
                <h2><?php esc_html_e('General Settings', 'attrla'); ?></h2>
                
                <?php
                ATTRLA_UI::render_form_field(array(
                    'type' => 'select',
                    'id' => 'security_mode',
                    'name' => 'attrla_settings[security_mode]',
                    'label' => __('Security Mode', 'attrla'),
                    'description' => __('Select the overall security level for your site.', 'attrla'),
                    'options' => array(
                        'normal' => __('Normal - Balanced security for most sites', 'attrla'),
                        'strict' => __('Strict - Enhanced security for sensitive sites', 'attrla'),
                        'custom' => __('Custom - Manually configure all settings', 'attrla')
                    ),
                    'value' => isset($settings['security_mode']) ? $settings['security_mode'] : 'normal'
                ));

                ATTRLA_UI::render_form_field(array(
                    'type' => 'checkbox',
                    'id' => 'enable_two_factor',
                    'name' => 'attrla_settings[enable_two_factor]',
                    'label' => __('Enable Two-Factor Authentication', 'attrla'),
                    'description' => __('Require two-factor authentication for specified user roles.', 'attrla'),
                    'value' => isset($settings['enable_two_factor']) ? $settings['enable_two_factor'] : false
                ));
                ?>
            </div>
        </div>

        <!-- Login Protection Settings -->
        <div id="login-protection" class="attrla-settings-panel">
            <div class="attrla-settings-section">
                <h2><?php esc_html_e('Login Protection Settings', 'attrla'); ?></h2>
                
                <?php
                ATTRLA_UI::render_form_field(array(
                    'type' => 'number',
                    'id' => 'max_login_attempts',
                    'name' => 'attrla_settings[max_login_attempts]',
                    'label' => __('Maximum Login Attempts', 'attrla'),
                    'description' => __('Number of failed attempts before lockout.', 'attrla'),
                    'value' => isset($settings['max_login_attempts']) ? $settings['max_login_attempts'] : 5,
                    'min' => 1,
                    'max' => 20
                ));

                ATTRLA_UI::render_form_field(array(
                    'type' => 'number',
                    'id' => 'lockout_duration',
                    'name' => 'attrla_settings[lockout_duration]',
                    'label' => __('Lockout Duration (minutes)', 'attrla'),
                    'description' => __('How long to lock out users after failed attempts.', 'attrla'),
                    'value' => isset($settings['lockout_duration']) ? $settings['lockout_duration'] : 30,
                    'min' => 5,
                    'max' => 1440
                ));

                ATTRLA_UI::render_form_field(array(
                    'type' => 'checkbox',
                    'id' => 'progressive_lockout',
                    'name' => 'attrla_settings[progressive_lockout]',
                    'label' => __('Enable Progressive Lockouts', 'attrla'),
                    'description' => __('Increase lockout duration for repeat offenders.', 'attrla'),
                    'value' => isset($settings['progressive_lockout']) ? $settings['progressive_lockout'] : true
                ));
                ?>
            </div>
        </div>

        <!-- Password Settings -->
        <div id="passwords" class="attrla-settings-panel">
            <div class="attrla-settings-section">
                <h2><?php esc_html_e('Password Settings', 'attrla'); ?></h2>
                
                <?php
                ATTRLA_UI::render_form_field(array(
                    'type' => 'number',
                    'id' => 'min_password_length',
                    'name' => 'attrla_settings[min_password_length]',
                    'label' => __('Minimum Password Length', 'attrla'),
                    'description' => __('Minimum required password length.', 'attrla'),
                    'value' => isset($settings['min_password_length']) ? $settings['min_password_length'] : 12,
                    'min' => 8,
                    'max' => 64
                ));

                ATTRLA_UI::render_form_field(array(
                    'type' => 'checkbox',
                    'id' => 'require_special_char',
                    'name' => 'attrla_settings[require_special_char]',
                    'label' => __('Require Special Character', 'attrla'),
                    'description' => __('Require at least one special character in passwords.', 'attrla'),
                    'value' => isset($settings['require_special_char']) ? $settings['require_special_char'] : true
                ));

                ATTRLA_UI::render_form_field(array(
                    'type' => 'number',
                    'id' => 'password_expiry_days',
                    'name' => 'attrla_settings[password_expiry_days]',
                    'label' => __('Password Expiry (days)', 'attrla'),
                    'description' => __('Number of days before passwords expire. Set to 0 to disable.', 'attrla'),
                    'value' => isset($settings['password_expiry_days']) ? $settings['password_expiry_days'] : 90,
                    'min' => 0,
                    'max' => 365
                ));
                ?>
            </div>
        </div>

        <!-- Notification Settings -->
        <div id="notifications" class="attrla-settings-panel">
            <div class="attrla-settings-section">
                <h2><?php esc_html_e('Notification Settings', 'attrla'); ?></h2>
                
                <?php
                ATTRLA_UI::render_form_field(array(
                    'type' => 'checkbox',
                    'id' => 'notify_admin_lockout',
                    'name' => 'attrla_settings[notify_admin_lockout]',
                    'label' => __('Notify on Lockout', 'attrla'),
                    'description' => __('Send email notification when a user is locked out.', 'attrla'),
                    'value' => isset($settings['notify_admin_lockout']) ? $settings['notify_admin_lockout'] : true
                ));

                ATTRLA_UI::render_form_field(array(
                    'type' => 'checkbox',
                    'id' => 'notify_user_lockout',
                    'name' => 'attrla_settings[notify_user_lockout]',
                    'label' => __('Notify User on Lockout', 'attrla'),
                    'description' => __('Send email notification to user when they are locked out.', 'attrla'),
                    'value' => isset($settings['notify_user_lockout']) ? $settings['notify_user_lockout'] : true
                ));

                ATTRLA_UI::render_form_field(array(
                    'type' => 'textarea',
                    'id' => 'notification_email',
                    'name' => 'attrla_settings[notification_email]',
                    'label' => __('Additional Notification Emails', 'attrla'),
                    'description' => __('Enter additional email addresses to receive notifications (one per line).', 'attrla'),
                    'value' => isset($settings['notification_email']) ? $settings['notification_email'] : ''
                ));
                ?>
            </div>
        </div>

        <!-- IP Management Settings -->
        <div id="ip-management" class="attrla-settings-panel">
            <div class="attrla-settings-section">
                <h2><?php esc_html_e('IP Management Settings', 'attrla'); ?></h2>
                
                <?php
                ATTRLA_UI::render_form_field(array(
                    'type' => 'textarea',
                    'id' => 'ip_whitelist',
                    'name' => 'attrla_settings[ip_whitelist]',
                    'label' => __('IP Whitelist', 'attrla'),
                    'description' => __('Enter IP addresses to whitelist (one per line).', 'attrla'),
                    'value' => isset($settings['ip_whitelist']) ? $settings['ip_whitelist'] : ''
                ));

                ATTRLA_UI::render_form_field(array(
                    'type' => 'textarea',
                    'id' => 'ip_blacklist',
                    'name' => 'attrla_settings[ip_blacklist]',
                    'label' => __('IP Blacklist', 'attrla'),
                    'description' => __('Enter IP addresses to blacklist (one per line).', 'attrla'),
                    'value' => isset($settings['ip_blacklist']) ? $settings['ip_blacklist'] : ''
                ));

                ATTRLA_UI::render_form_field(array(
                    'type' => 'checkbox',
                    'id' => 'enable_country_blocking',
                    'name' => 'attrla_settings[enable_country_blocking]',
                    'label' => __('Enable Country Blocking', 'attrla'),
                    'description' => __('Block login attempts from specific countries.', 'attrla'),
                    'value' => isset($settings['enable_country_blocking']) ? $settings['enable_country_blocking'] : false
                ));
                ?>
            </div>
        </div>

        <!-- Logging Settings -->
        <div id="logging" class="attrla-settings-panel">
            <div class="attrla-settings-section">
                <h2><?php esc_html_e('Logging Settings', 'attrla'); ?></h2>
                
                <?php
                ATTRLA_UI::render_form_field(array(
                    'type' => 'checkbox',
                    'id' => 'enable_logging',
                    'name' => 'attrla_settings[enable_logging]',
                    'label' => __('Enable Logging', 'attrla'),
                    'description' => __('Enable detailed security event logging.', 'attrla'),
                    'value' => isset($settings['enable_logging']) ? $settings['enable_logging'] : true
                ));

                ATTRLA_UI::render_form_field(array(
                    'type' => 'number',
                    'id' => 'log_retention_days',
                    'name' => 'attrla_settings[log_retention_days]',
                    'label' => __('Log Retention (days)', 'attrla'),
                    'description' => __('Number of days to keep security logs.', 'attrla'),
                    'value' => isset($settings['log_retention_days']) ? $settings['log_retention_days'] : 30,
                    'min' => 1,
                    'max' => 365
                ));
                ?>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="attrla-form-footer">
            <?php submit_button(); ?>
            
            <button type="button" class="button" id="attrla-reset-settings">
                <?php esc_html_e('Reset to Defaults', 'attrla'); ?>
            </button>
        </div>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Tab navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        const target = $(this).attr('href');
        
        // Update tabs
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Update panels
        $('.attrla-settings-panel').removeClass('active');
        $(target).addClass('active');

        // Store active tab
        localStorage.setItem('attrlaActiveSettingsTab', target);
    });

    // Handle security mode changes
    $('#security_mode').on('change', function() {
        const mode = $(this).val();
        if (mode !== 'custom') {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'attrla_get_security_preset',
                    mode: mode,
                    security: attrlaAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update form fields with preset values
                        $.each(response.data.settings, function(key, value) {
                            const field = $(`[name="attrla_settings[${key}]"]`);
                            if (field.is(':checkbox')) {
                                field.prop('checked', value);
                            } else {
                                field.val(value);
                            }
                        });
                    }
                }
            });
        }
    });

    // Form validation
    $('#attrla-settings-form').on('submit', function(e) {
        const maxAttempts = parseInt($('#max_login_attempts').val());
        const lockoutDuration = parseInt($('#lockout_duration').val());
        const passwordLength = parseInt($('#min_password_length').val());

        let errors = [];

        if (maxAttempts < 1 || maxAttempts > 20) {
            errors.push(attrlaAdmin.i18n.invalidMaxAttempts);
        }

        if (lockoutDuration < 5 || lockoutDuration > 1440) {
            errors.push(attrlaAdmin.i18n.invalidLockoutDuration);
        }

        if (passwordLength < 8 || passwordLength > 64) {
            errors.push(attrlaAdmin.i18n.invalidPasswordLength);
        }

        if (errors.length > 0) {
            e.preventDefault();
            alert(errors.join("\n"));
        }
    });

    // IP list validation
    function validateIPList(input) {
        const ips = input.val().split('\n').filter(ip => ip.trim() !== '');
        const invalidIPs = ips.filter(ip => !isValidIP(ip.trim()));
        
        if (invalidIPs.length > 0) {
            input.addClass('error');
            const errorMessage = `Invalid IP addresses:\n${invalidIPs.join('\n')}`;
            input.next('.attrla-field-error').remove();
            $(`<div class="attrla-field-error">${errorMessage}</div>`).insertAfter(input);
            return false;
        }
        
        input.removeClass('error');
        input.next('.attrla-field-error').remove();
        return true;
    }

    // Validate IP format
    function isValidIP(ip) {
        const ipv4Regex = /^(\d{1,3}\.){3}\d{1,3}$/;
        if (!ipv4Regex.test(ip)) return false;
        
        const parts = ip.split('.');
        return parts.every(part => {
            const num = parseInt(part, 10);
            return num >= 0 && num <= 255;
        });
    }

    // Real-time IP validation
    $('#ip_whitelist, #ip_blacklist').on('change', function() {
        validateIPList($(this));
    });

    // Initialize tooltips
    $('.attrla-help-tip').tipTip({
        attribute: 'data-tip',
        fadeIn: 50,
        fadeOut: 50,
        delay: 200
    });

    // Handle dependent settings
    function updateDependentFields() {
        $('[data-depends-on]').each(function() {
            const target = $(this);
            const dependency = target.data('depends-on');
            const dependencyField = $(`[name="attrla_settings[${dependency}]"]`);
            const shouldShow = dependencyField.is(':checkbox') ? 
                dependencyField.is(':checked') : 
                dependencyField.val() !== '';

            target.closest('.attrla-field-wrapper').toggle(shouldShow);
        });
    }

    // Monitor changes to fields that others depend on
    $('input, select').on('change', function() {
        updateDependentFields();
    });

    // Initial update of dependent fields
    updateDependentFields();
});
</script>