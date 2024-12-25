<?php
/**
 * Template for the login form
 *
 * @package AttributeLoginAccess
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="attrla-form-container" id="attrla-login-form">
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

    <form method="post" action="<?php echo esc_url(ATTRLA_Auth::get_login_url()); ?>" class="attrla-form">
        <?php wp_nonce_field('attrla_login_nonce', 'attrla_login_security'); ?>
        
        <div class="attrla-form-field">
            <label for="attrla_username">
                <?php esc_html_e('Username or Email Address', 'attrla'); ?>
                <span class="required">*</span>
            </label>
            <input type="text" 
                   name="log" 
                   id="attrla_username" 
                   class="attrla-input" 
                   value="<?php echo esc_attr(ATTRLA_UI::get_posted_value('log')); ?>" 
                   required 
                   autocomplete="username" />
        </div>

        <div class="attrla-form-field">
            <label for="attrla_password">
                <?php esc_html_e('Password', 'attrla'); ?>
                <span class="required">*</span>
            </label>
            <div class="attrla-password-field">
                <input type="password" 
                       name="pwd" 
                       id="attrla_password" 
                       class="attrla-input" 
                       required 
                       autocomplete="current-password" />
                <button type="button" 
                        class="attrla-toggle-password" 
                        aria-label="<?php esc_attr_e('Toggle password visibility', 'attrla'); ?>">
                    <span class="dashicons dashicons-visibility"></span>
                </button>
            </div>
        </div>

        <?php do_action('attrla_login_form'); // Hook for additional fields ?>

        <div class="attrla-form-field attrla-remember-me">
            <label>
                <input type="checkbox" 
                       name="rememberme" 
                       id="attrla_rememberme" 
                       value="forever" 
                       <?php checked(ATTRLA_UI::get_posted_value('rememberme', false)); ?> />
                <?php esc_html_e('Remember Me', 'attrla'); ?>
            </label>
        </div>

        <?php 
        // Security challenge if enabled
        if (ATTRLA_Security::is_challenge_required()) {
            ATTRLA_Security::render_security_challenge();
        }
        ?>

        <div class="attrla-form-submit">
            <button type="submit" class="attrla-button attrla-button-primary">
                <?php esc_html_e('Log In', 'attrla'); ?>
            </button>
        </div>

        <div class="attrla-form-links">
            <?php if (get_option('users_can_register')) : ?>
                <a href="<?php echo esc_url(ATTRLA_Auth::get_registration_url()); ?>" class="attrla-register-link">
                    <?php esc_html_e('Register', 'attrla'); ?>
                </a>
            <?php endif; ?>
            
            <a href="<?php echo esc_url(ATTRLA_Auth::get_lost_password_url()); ?>" class="attrla-lost-password-link">
                <?php esc_html_e('Lost your password?', 'attrla'); ?>
            </a>
        </div>
    </form>

    <?php do_action('attrla_after_login_form'); // Hook for additional content ?>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the login form handler
    if (typeof ATTRLA_Login !== 'undefined') {
        new ATTRLA_Login('#attrla-login-form');
    }
});
</script>