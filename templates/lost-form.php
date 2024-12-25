<?php
/**
 * Template for the lost password form
 *
 * @package AttributeLoginAccess
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="attrla-form-container" id="attrla-lost-password-form">
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
        <h2><?php esc_html_e('Lost Password Recovery', 'attrla'); ?></h2>
        <p class="attrla-form-description">
            <?php esc_html_e('Please enter your username or email address. You will receive a link to create a new password via email.', 'attrla'); ?>
        </p>
    </div>

    <form method="post" action="<?php echo esc_url(ATTRLA_Auth::get_lost_password_url()); ?>" class="attrla-form">
        <?php wp_nonce_field('attrla_lost_password_nonce', 'attrla_lost_password_security'); ?>
        
        <div class="attrla-form-field">
            <label for="attrla_user_login">
                <?php esc_html_e('Username or Email Address', 'attrla'); ?>
                <span class="required">*</span>
            </label>
            <input type="text" 
                   name="user_login" 
                   id="attrla_user_login" 
                   class="attrla-input" 
                   value="<?php echo esc_attr(ATTRLA_UI::get_posted_value('user_login')); ?>" 
                   required 
                   autocomplete="username" />
        </div>

        <?php do_action('attrla_lost_password_form'); // Hook for additional fields ?>

        <?php 
        // Security challenge if enabled
        if (ATTRLA_Security::is_challenge_required()) {
            ATTRLA_Security::render_security_challenge();
        }
        ?>

        <div class="attrla-form-submit">
            <button type="submit" class="attrla-button attrla-button-primary">
                <?php esc_html_e('Get New Password', 'attrla'); ?>
            </button>
        </div>

        <div class="attrla-form-links">
            <a href="<?php echo esc_url(ATTRLA_Auth::get_login_url()); ?>" class="attrla-login-link">
                <?php esc_html_e('Back to Login', 'attrla'); ?>
            </a>
            
            <?php if (get_option('users_can_register')) : ?>
                <a href="<?php echo esc_url(ATTRLA_Auth::get_registration_url()); ?>" class="attrla-register-link">
                    <?php esc_html_e('Register', 'attrla'); ?>
                </a>
            <?php endif; ?>
        </div>
    </form>

    <?php do_action('attrla_after_lost_password_form'); // Hook for additional content ?>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the lost password form handler
    if (typeof ATTRLA_Lost !== 'undefined') {
        new ATTRLA_Lost('#attrla-lost-password-form');
    }
});
</script>