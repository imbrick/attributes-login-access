<?php
/**
 * Handle UI elements and rendering.
 *
 * @package    AttributeLoginAccess
 * @subpackage AttributeLoginAccess/includes
 */

class ATTRLA_UI {
    /**
     * Initialize the class.
     */
    public function __construct() {
        add_action('admin_notices', array($this, 'display_admin_notices'));
    }

    /**
     * Display form field with label and description.
     *
     * @param array $args Field arguments
     */
    public static function render_form_field($args) {
        $defaults = array(
            'type' => 'text',
            'id' => '',
            'name' => '',
            'value' => '',
            'label' => '',
            'description' => '',
            'placeholder' => '',
            'class' => '',
            'required' => false,
            'options' => array(),
            'multiple' => false,
            'data' => array()
        );

        $args = wp_parse_args($args, $defaults);
        $field_id = !empty($args['id']) ? $args['id'] : sanitize_key($args['name']);

        // Start field wrapper
        echo '<div class="attrla-field-wrapper">';

        // Label
        if (!empty($args['label'])) {
            printf(
                '<label for="%s" class="attrla-label">%s%s</label>',
                esc_attr($field_id),
                esc_html($args['label']),
                $args['required'] ? ' <span class="required">*</span>' : ''
            );
        }

        // Field
        switch ($args['type']) {
            case 'textarea':
                self::render_textarea($args, $field_id);
                break;

            case 'select':
                self::render_select($args, $field_id);
                break;

            case 'checkbox':
                self::render_checkbox($args, $field_id);
                break;

            case 'radio':
                self::render_radio($args, $field_id);
                break;

            case 'password':
                self::render_password_field($args, $field_id);
                break;

            default:
                self::render_text_field($args, $field_id);
        }

        // Description
        if (!empty($args['description'])) {
            printf(
                '<p class="attrla-field-description">%s</p>',
                wp_kses_post($args['description'])
            );
        }

        echo '</div>';
    }

    /**
     * Render textarea field.
     *
     * @param array  $args     Field arguments
     * @param string $field_id Field ID
     */
    private static function render_textarea($args, $field_id) {
        printf(
            '<textarea id="%1$s" name="%2$s" class="attrla-textarea %3$s" placeholder="%4$s" %5$s>%6$s</textarea>',
            esc_attr($field_id),
            esc_attr($args['name']),
            esc_attr($args['class']),
            esc_attr($args['placeholder']),
            $args['required'] ? 'required' : '',
            esc_textarea($args['value'])
        );
    }

    /**
     * Render select field.
     *
     * @param array  $args     Field arguments
     * @param string $field_id Field ID
     */
    private static function render_select($args, $field_id) {
        printf(
            '<select id="%1$s" name="%2$s%3$s" class="attrla-select %4$s" %5$s %6$s>',
            esc_attr($field_id),
            esc_attr($args['name']),
            $args['multiple'] ? '[]' : '',
            esc_attr($args['class']),
            $args['multiple'] ? 'multiple' : '',
            $args['required'] ? 'required' : ''
        );

        foreach ($args['options'] as $value => $label) {
            $selected = $args['multiple'] 
                ? in_array($value, (array)$args['value']) 
                : $value == $args['value'];

            printf(
                '<option value="%1$s" %2$s>%3$s</option>',
                esc_attr($value),
                selected($selected, true, false),
                esc_html($label)
            );
        }

        echo '</select>';
    }

    /**
     * Render checkbox field.
     *
     * @param array  $args     Field arguments
     * @param string $field_id Field ID
     */
    private static function render_checkbox($args, $field_id) {
        printf(
            '<label class="attrla-checkbox-label">
                <input type="checkbox" id="%1$s" name="%2$s" value="1" class="attrla-checkbox %3$s" %4$s %5$s>
                <span class="attrla-checkbox-text">%6$s</span>
            </label>',
            esc_attr($field_id),
            esc_attr($args['name']),
            esc_attr($args['class']),
            checked($args['value'], true, false),
            $args['required'] ? 'required' : '',
            wp_kses_post($args['label'])
        );
    }

    /**
     * Render radio field.
     *
     * @param array  $args     Field arguments
     * @param string $field_id Field ID
     */
    private static function render_radio($args, $field_id) {
        echo '<div class="attrla-radio-group">';
        foreach ($args['options'] as $value => $label) {
            printf(
                '<label class="attrla-radio-label">
                    <input type="radio" name="%1$s" value="%2$s" class="attrla-radio %3$s" %4$s %5$s>
                    <span class="attrla-radio-text">%6$s</span>
                </label>',
                esc_attr($args['name']),
                esc_attr($value),
                esc_attr($args['class']),
                checked($args['value'], $value, false),
                $args['required'] ? 'required' : '',
                esc_html($label)
            );
        }
        echo '</div>';
    }

    /**
     * Render password field with toggle.
     *
     * @param array  $args     Field arguments
     * @param string $field_id Field ID
     */
    private static function render_password_field($args, $field_id) {
        echo '<div class="attrla-password-field">';
        printf(
            '<input type="password" id="%1$s" name="%2$s" value="%3$s" class="attrla-input %4$s" 
                placeholder="%5$s" %6$s autocomplete="new-password">
            <button type="button" class="attrla-toggle-password" aria-label="%7$s">
                <span class="dashicons dashicons-visibility"></span>
            </button>',
            esc_attr($field_id),
            esc_attr($args['name']),
            esc_attr($args['value']),
            esc_attr($args['class']),
            esc_attr($args['placeholder']),
            $args['required'] ? 'required' : '',
            esc_attr__('Toggle password visibility', 'attrla')
        );
        echo '</div>';
    }

    /**
     * Render text field.
     *
     * @param array  $args     Field arguments
     * @param string $field_id Field ID
     */
    private static function render_text_field($args, $field_id) {
        $data_attrs = '';
        foreach ($args['data'] as $key => $value) {
            $data_attrs .= sprintf(' data-%s="%s"', esc_attr($key), esc_attr($value));
        }

        printf(
            '<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="attrla-input %5$s" 
                placeholder="%6$s" %7$s%8$s>',
            esc_attr($args['type']),
            esc_attr($field_id),
            esc_attr($args['name']),
            esc_attr($args['value']),
            esc_attr($args['class']),
            esc_attr($args['placeholder']),
            $args['required'] ? 'required' : '',
            $data_attrs
        );
    }

    /**
     * Display admin notice.
     *
     * @param string $message Message to display
     * @param string $type    Notice type (success, error, warning, info)
     * @param bool   $dismissible Whether the notice is dismissible
     */
    public static function add_admin_notice($message, $type = 'info', $dismissible = true) {
        $notices = get_transient('attrla_admin_notices') ?: array();
        $notices[] = array(
            'message' => $message,
            'type' => $type,
            'dismissible' => $dismissible
        );
        set_transient('attrla_admin_notices', $notices, 45);
    }

    /**
     * Display all admin notices.
     */
    public function display_admin_notices() {
        $notices = get_transient('attrla_admin_notices');
        if (!$notices) {
            return;
        }

        foreach ($notices as $notice) {
            printf(
                '<div class="notice notice-%1$s%2$s"><p>%3$s</p></div>',
                esc_attr($notice['type']),
                $notice['dismissible'] ? ' is-dismissible' : '',
                wp_kses_post($notice['message'])
            );
        }

        delete_transient('attrla_admin_notices');
    }

    /**
     * Get status badge HTML.
     *
     * @param string $status  Status type
     * @param string $text    Status text
     * @return string        Badge HTML
     */
    public static function get_status_badge($status, $text) {
        return sprintf(
            '<span class="attrla-status-badge attrla-status-%s">%s</span>',
            esc_attr($status),
            esc_html($text)
        );
    }

    /**
     * Get pagination HTML.
     *
     * @param int $total      Total items
     * @param int $per_page   Items per page
     * @param int $current    Current page
     * @return string        Pagination HTML
     */
    public static function get_pagination($total, $per_page, $current) {
        $total_pages = ceil($total / $per_page);
        if ($total_pages <= 1) {
            return '';
        }

        $html = '<div class="attrla-pagination">';
        
        // Previous
        if ($current > 1) {
            $html .= sprintf(
                '<a href="%s" class="prev">%s</a>',
                esc_url(add_query_arg('paged', $current - 1)),
                __('Previous', 'attrla')
            );
        }

        // Page numbers
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i === $current) {
                $html .= sprintf('<span class="current">%d</span>', $i);
            } else {
                $html .= sprintf(
                    '<a href="%s">%d</a>',
                    esc_url(add_query_arg('paged', $i)),
                    $i
                );
            }
        }

        // Next
        if ($current < $total_pages) {
            $html .= sprintf(
                '<a href="%s" class="next">%s</a>',
                esc_url(add_query_arg('paged', $current + 1)),
                __('Next', 'attrla')
            );
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Get loader HTML.
     *
     * @param string $size Size (small, medium, large)
     * @return string    Loader HTML
     */
    public static function get_loader($size = 'medium') {
        return sprintf(
            '<div class="attrla-loader attrla-loader-%s">
                <div class="attrla-loader-spinner"></div>
            </div>',
            esc_attr($size)
        );
    }
}