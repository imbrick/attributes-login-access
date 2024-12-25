<?php
/**
 * Template for the registration form
 *
 * @package AttributeLoginAccess
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Check if registration is allowed
if (!get_option('users_can_register')) {
    wp_die(__('Registration is currently disabled.', 'attrla'));
}
?>

<div class="attrla-form-container" id="attrla-register-form">
    <?php 
    // Display any stored messages
    $messages = ATTRLA_UI::get_messages();
    if (!empty($messages)) {
        foreach ($messages as $message) {
            printf(
                '<div class="attrla-message attrla-message-%s">%s</div>',
                esc_attr($message['type']),
                esc_html($message['content'])
            );
        }
    }
    ?>

    <form method="post" action="<?php echo esc_url(ATTRLA_Auth::get_registration_url()); ?>" class="attrla-form">
        <?php wp_nonce_field('attrla_register_nonce', 'attrla_register_security'); ?>
        
        <div class="attrla-form-field">
            <label for="attrla_username">
                <?php esc_html_e('Username', 'attrla'); ?>
                <span class="required">*</span>
            </label>
            <input type="text" 
                   name="user_login" 
                   id="attrla_username" 
                   class="attrla-input" 
                   value="<?php echo esc_attr(ATTRLA_UI::get_posted_value('user_login')); ?>" 
                   required 
                   autocomplete="username" />
            <span class="attrla-form-hint">
                <?php esc_html_e('Username must be at least 4 characters and contain only letters, numbers, and underscores.', 'attrla'); ?>
            </span>
        </div>

        <div class="attrla-form-field">
            <label for="attrla_email">
                <?php esc_html_e('Email Address', 'attrla'); ?>
                <span class="required">*</span>
            </label>
            <input type="email" 
                   name="user_email" 
                   id="attrla_email" 
                   class="attrla-input" 
                   value="<?php echo esc_attr(ATTRLA_UI::get_posted_value('user_email')); ?>" 
                   required 
                   autocomplete="email" />
        </div>

        <div class="attrla-form-field">
            <label for="attrla_password">
                <?php esc_html_e('Password', 'attrla'); ?>
                <span class="required">*</span>
            </label>
            <div class="attrla-password-field">
                <input type="password" 
                       name="user_pass" 
                       id="attrla_password" 
                       class="attrla-input" 
                       required 
                       autocomplete="new-password" />
                <button type="button" 
                        class="attrla-toggle-password" 
                        aria-label="<?php esc_attr_e('Toggle password visibility', 'attrla'); ?>">
                    <span class="dashicons dashicons-visibility"></span>
                </button>
            </div>
            <div class="attrla-password-strength"></div>
            <span class="attrla-form-hint">
                <?php esc_html_e('Password must be at least 8 characters long and include uppercase, lowercase, numbers, and special characters.', 'attrla'); ?>
            </span>
        </div>

        <div class="attrla-form-field">
            <label for="attrla_password_confirm">
                <?php esc_html_e('Confirm Password', 'attrla'); ?>
                <span class="required">*</span>
            </label>
            <div class="attrla-password-field">
                <input type="password" 
                       name="user_pass_confirm" 
                       id="attrla_password_confirm" 
                       class="attrla-input" 
                       required 
                       autocomplete="new-password" />
            </div>
        </div>

        <?php do_action('attrla_register_form'); // Hook for additional fields ?>

        <?php 
        // Privacy policy acceptance
        if (function_exists('get_privacy_policy_url') && get_privacy_policy_url()) : 
        ?>
            <div class="attrla-form-field attrla-privacy-policy">
                <label>
                    <input type="checkbox" 
                           name="privacy_policy" 
                           required />
                    <?php 
                    printf(
                        /* translators: %s: Privacy policy URL */
                        esc_html__('I have read and agree to the %s', 'attrla'),
                        sprintf(
                            '<a href="%s" target="_blank">%s</a>',
                            esc_url(get_privacy_policy_url()),
                            esc_html__('Privacy Policy', 'attrla')
                        )
                    );
                    ?>
                </label>
            </div>
        <?php endif; ?>

        <?php 
        // Security challenge if enabled
        if (ATTRLA_Security::is_challenge_required()) {
            ATTRLA_Security::render_security_challenge();
        }
        ?>

        <div class="attrla-form-submit">
            <button type="submit" class="attrla-button attrla-button-primary">
                <?php esc_html_e('Register', 'attrla'); ?>
            </button>
        </div>

        <div class="attrla-form-links">
            <a href="<?php echo esc_url(ATTRLA_Auth::get_login_url()); ?>" class="attrla-login-link">
                <?php esc_html_e('Already have an account? Log in', 'attrla'); ?>
            </a>
        </div>
    </form>

    <?php do_action('attrla_after_register_form'); // Hook for additional content ?>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the registration form handler
    if (typeof ATTRLA_Register !== 'undefined') {
        new ATTRLA_Register('#attrla-register-form');
    }
});
</script>