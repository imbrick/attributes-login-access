<?php
/**
 * Template for the reset password form
 *
 * @package AttributeLoginAccess
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Verify reset key and login
$rp_login = isset($_GET['login']) ? sanitize_user($_GET['login']) : '';
$rp_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';

if (empty($rp_login) || empty($rp_key)) {
    wp_die(__('Invalid password reset link.', 'attrla'));
}

// Verify the reset key
$user = check_password_reset_key($rp_key, $rp_login);
if (is_wp_error($user)) {
    wp_die($user);
}
?>

<div class="attrla-form-container" id="attrla-reset-password-form">
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

    <div class="attrla-form-header">
        <h2><?php esc_html_e('Reset Password', 'attrla'); ?></h2>
        <p class="attrla-form-description">
            <?php esc_html_e('Please enter your new password below.', 'attrla'); ?>
        </p>
    </div>

    <form method="post" action="<?php echo esc_url(ATTRLA_Auth::get_reset_password_url()); ?>" class="attrla-form">
        <?php wp_nonce_field('attrla_reset_password_nonce', 'attrla_reset_password_security'); ?>
        
        <input type="hidden" name="rp_key" value="<?php echo esc_attr($rp_key); ?>" />
        <input type="hidden" name="rp_login" value="<?php echo esc_attr($rp_login); ?>" />

        <div class="attrla-form-field">
            <label for="attrla_new_password">
                <?php esc_html_e('New Password', 'attrla'); ?>
                <span class="required">*</span>
            </label>
            <div class="attrla-password-field">
                <input type="password" 
                       name="new_password" 
                       id="attrla_new_password" 
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
            <label for="attrla_confirm_password">
                <?php esc_html_e('Confirm New Password', 'attrla'); ?>
                <span class="required">*</span>
            </label>
            <div class="attrla-password-field">
                <input type="password" 
                       name="confirm_password" 
                       id="attrla_confirm_password" 
                       class="attrla-input" 
                       required 
                       autocomplete="new-password" />
            </div>
        </div>

        <?php do_action('attrla_reset_password_form'); // Hook for additional fields ?>

        <?php 
        // Security challenge if enabled
        if (ATTRLA_Security::is_challenge_required()) {
            ATTRLA_Security::render_security_challenge();
        }
        ?>

        <div class="attrla-form-submit">
            <button type="submit" class="attrla-button attrla-button-primary">
                <?php esc_html_e('Reset Password', 'attrla'); ?>
            </button>
        </div>

        <div class="attrla-form-links">
            <a href="<?php echo esc_url(ATTRLA_Auth::get_login_url()); ?>" class="attrla-login-link">
                <?php esc_html_e('Back to Login', 'attrla'); ?>
            </a>
        </div>
    </form>

    <?php do_action('attrla_after_reset_password_form'); // Hook for additional content ?>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the reset password form handler
    if (typeof ATTRLA_Reset !== 'undefined') {
        new ATTRLA_Reset('#attrla-reset-password-form');
    }
});
</script>