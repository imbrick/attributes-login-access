<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @package    AttributeLoginAccess
 * @subpackage AttributeLoginAccess/includes
 */

class ATTRLA_i18n {
    /**
     * The domain specified for this plugin.
     *
     * @access   private
     * @var      string    $domain    The domain identifier for this plugin.
     */
    private $domain;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        $this->domain = 'attrla';
    }

    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            $this->domain,
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    /**
     * Get translated string with context.
     *
     * @param string $text    Text to translate
     * @param string $context Context information for translators
     * @return string         Translated text
     */
    public function translate_with_context($text, $context) {
        return _x($text, $context, $this->domain);
    }

    /**
     * Get translated string.
     *
     * @param string $text Text to translate
     * @return string      Translated text
     */
    public function translate($text) {
        return __($text, $this->domain);
    }

    /**
     * Get translated string with placeholder substitution.
     *
     * @param string $text Text to translate with placeholders
     * @param mixed  $args Arguments to substitute
     * @return string      Translated text with substitutions
     */
    public function translate_with_placeholders($text, ...$args) {
        return sprintf(__($text, $this->domain), ...$args);
    }

    /**
     * Get translated plural string.
     *
     * @param string $single Single form
     * @param string $plural Plural form
     * @param int    $number Number for plural decision
     * @return string        Translated text in correct form
     */
    public function translate_plural($single, $plural, $number) {
        return _n($single, $plural, $number, $this->domain);
    }

    /**
     * Register translation strings for JavaScript.
     */
    public function register_javascript_translations() {
        $translations = array(
            'error' => array(
                'general' => __('An error occurred. Please try again.', $this->domain),
                'required' => __('This field is required.', $this->domain),
                'invalid_email' => __('Please enter a valid email address.', $this->domain),
                'password_mismatch' => __('Passwords do not match.', $this->domain),
                'weak_password' => __('Password is too weak.', $this->domain),
                'invalid_username' => __('Invalid username format.', $this->domain),
                'session_expired' => __('Your session has expired. Please log in again.', $this->domain)
            ),
            'success' => array(
                'login' => __('Login successful. Redirecting...', $this->domain),
                'logout' => __('Logout successful.', $this->domain),
                'password_reset' => __('Password reset successful.', $this->domain),
                'registration' => __('Registration successful.', $this->domain)
            ),
            'confirm' => array(
                'logout' => __('Are you sure you want to log out?', $this->domain),
                'delete' => __('Are you sure you want to delete this item?', $this->domain),
                'reset' => __('Are you sure you want to reset all settings?', $this->domain)
            ),
            'status' => array(
                'processing' => __('Processing...', $this->domain),
                'loading' => __('Loading...', $this->domain),
                'saving' => __('Saving...', $this->domain),
                'waiting' => __('Please wait...', $this->domain)
            ),
            'validation' => array(
                'min_length' => __('Must be at least %d characters long.', $this->domain),
                'max_length' => __('Must not exceed %d characters.', $this->domain),
                'contains_number' => __('Must contain at least one number.', $this->domain),
                'contains_special' => __('Must contain at least one special character.', $this->domain),
                'contains_uppercase' => __('Must contain at least one uppercase letter.', $this->domain),
                'contains_lowercase' => __('Must contain at least one lowercase letter.', $this->domain)
            ),
            'misc' => array(
                'show_password' => __('Show password', $this->domain),
                'hide_password' => __('Hide password', $this->domain),
                'remember_me' => __('Remember me', $this->domain),
                'forgot_password' => __('Forgot your password?', $this->domain),
                'back_to_login' => __('Back to login', $this->domain)
            )
        );

        wp_localize_script(
            'attrla-public',
            'attrlaI18n',
            apply_filters('attrla_javascript_translations', $translations)
        );
    }

    /**
     * Get the text domain.
     *
     * @return string Text domain
     */
    public function get_domain() {
        return $this->domain;
    }

    /**
     * Check if a translation exists.
     *
     * @param string $text Text to check
     * @return bool        True if translation exists
     */
    public function translation_exists($text) {
        return $text !== __($text, $this->domain);
    }

    /**
     * Get available languages.
     *
     * @return array Array of available language codes
     */
    public function get_available_languages() {
        $languages_dir = plugin_dir_path(dirname(__FILE__)) . 'languages/';
        $languages = array();

        if (is_dir($languages_dir)) {
            $files = scandir($languages_dir);
            foreach ($files as $file) {
                if (preg_match('/^' . $this->domain . '-([a-z]{2}_[A-Z]{2})\.mo$/', $file, $matches)) {
                    $languages[] = $matches[1];
                }
            }
        }

        return $languages;
    }

    /**
     * Get language name from locale code.
     *
     * @param string $locale Locale code (e.g., 'en_US')
     * @return string        Language name in current locale
     */
    public function get_language_name($locale) {
        require_once(ABSPATH . 'wp-admin/includes/translation-install.php');
        $translations = wp_get_available_translations();
        
        return isset($translations[$locale]) 
            ? $translations[$locale]['native_name'] 
            : $locale;
    }
}