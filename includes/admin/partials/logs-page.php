<?php
/**
 * Security logs page template
 *
 * @package AttributeLoginAccess
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get current filters
$filters = array(
    'event_type' => isset($_GET['event_type']) ? sanitize_text_field($_GET['event_type']) : '',
    'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '',
    'date_range' => isset($_GET['date_range']) ? sanitize_text_field($_GET['date_range']) : '7days',
    'ip_address' => isset($_GET['ip_address']) ? sanitize_text_field($_GET['ip_address']) : '',
    'username' => isset($_GET['username']) ? sanitize_text_field($_GET['username']) : ''
);

// Handle log actions
if (!empty($_POST['action']) && check_admin_referer('attrla_logs_action', 'attrla_nonce')) {
    $action = sanitize_text_field($_POST['action']);
    $log_ids = isset($_POST['log_ids']) ? array_map('intval', $_POST['log_ids']) : array();

    switch ($action) {
        case 'delete':
            if (!empty($log_ids)) {
                ATTRLA_Data::delete_logs($log_ids);
                add_settings_error(
                    'attrla_logs',
                    'logs_deleted',
                    __('Selected logs have been deleted.', 'attrla'),
                    'success'
                );
            }
            break;

        case 'export':
            if (!empty($log_ids)) {
                ATTRLA_Data::export_logs($log_ids);
            }
            break;

        case 'clear_all':
            ATTRLA_Data::clear_all_logs();
            add_settings_error(
                'attrla_logs',
                'logs_cleared',
                __('All logs have been cleared.', 'attrla'),
                'success'
            );
            break;
    }
}

// Get pagination parameters
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = apply_filters('attrla_logs_per_page', 50);

// Apply date range filter
switch ($filters['date_range']) {
    case 'today':
        $filters['date_from'] = date('Y-m-d');
        break;
    case '7days':
        $filters['date_from'] = date('Y-m-d', strtotime('-7 days'));
        break;
    case '30days':
        $filters['date_from'] = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'custom':
        $filters['date_from'] = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $filters['date_to'] = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        break;
}

// Get logs with filters
$logs = ATTRLA_Data::get_security_events(array_merge($filters, array(
    'offset' => ($page - 1) * $per_page,
    'limit' => $per_page
)));

// Get total count for pagination
$total_items = ATTRLA_Data::get_security_events_count($filters);
$total_pages = ceil($total_items / $per_page);

// Get available event types for filter
$event_types = ATTRLA_Data::get_event_types();
?>

<div class="wrap attrla-admin-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Security Logs', 'attrla'); ?></h1>
    
    <?php settings_errors('attrla_logs'); ?>

    <!-- Action Buttons -->
    <div class="attrla-header-actions">
        <?php if (!empty($logs)) : ?>
            <button type="button" class="button attrla-export-all">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Export All', 'attrla'); ?>
            </button>
            
            <button type="button" class="button attrla-clear-all">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e('Clear All', 'attrla'); ?>
            </button>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="attrla-filters-wrap">
        <form method="get" class="attrla-filters-form">
            <input type="hidden" name="page" value="attrla-logs">
            
            <div class="attrla-filters-row">
                <!-- Event Type Filter -->
                <div class="attrla-filter-group">
                    <label for="event_type"><?php esc_html_e('Event Type', 'attrla'); ?></label>
                    <select name="event_type" id="event_type">
                        <option value=""><?php esc_html_e('All Events', 'attrla'); ?></option>
                        <?php foreach ($event_types as $type => $label) : ?>
                            <option value="<?php echo esc_attr($type); ?>" 
                                    <?php selected($filters['event_type'], $type); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="attrla-filter-group">
                    <label for="status"><?php esc_html_e('Status', 'attrla'); ?></label>
                    <select name="status" id="status">
                        <option value=""><?php esc_html_e('All Statuses', 'attrla'); ?></option>
                        <option value="success" <?php selected($filters['status'], 'success'); ?>>
                            <?php esc_html_e('Success', 'attrla'); ?>
                        </option>
                        <option value="failed" <?php selected($filters['status'], 'failed'); ?>>
                            <?php esc_html_e('Failed', 'attrla'); ?>
                        </option>
                        <option value="blocked" <?php selected($filters['status'], 'blocked'); ?>>
                            <?php esc_html_e('Blocked', 'attrla'); ?>
                        </option>
                    </select>
                </div>

                <!-- Date Range Filter -->
                <div class="attrla-filter-group">
                    <label for="date_range"><?php esc_html_e('Date Range', 'attrla'); ?></label>
                    <select name="date_range" id="date_range">
                        <option value="today" <?php selected($filters['date_range'], 'today'); ?>>
                            <?php esc_html_e('Today', 'attrla'); ?>
                        </option>
                        <option value="7days" <?php selected($filters['date_range'], '7days'); ?>>
                            <?php esc_html_e('Last 7 Days', 'attrla'); ?>
                        </option>
                        <option value="30days" <?php selected($filters['date_range'], '30days'); ?>>
                            <?php esc_html_e('Last 30 Days', 'attrla'); ?>
                        </option>
                        <option value="custom" <?php selected($filters['date_range'], 'custom'); ?>>
                            <?php esc_html_e('Custom Range', 'attrla'); ?>
                        </option>
                    </select>
                </div>

                <!-- Custom Date Range -->
                <div class="attrla-filter-group custom-date-range" <?php echo $filters['date_range'] !== 'custom' ? 'style="display:none;"' : ''; ?>>
                    <label for="date_from"><?php esc_html_e('From', 'attrla'); ?></label>
                    <input type="date" 
                           name="date_from" 
                           id="date_from" 
                           value="<?php echo esc_attr($filters['date_from'] ?? ''); ?>">
                    
                    <label for="date_to"><?php esc_html_e('To', 'attrla'); ?></label>
                    <input type="date" 
                           name="date_to" 
                           id="date_to" 
                           value="<?php echo esc_attr($filters['date_to'] ?? ''); ?>">
                </div>

                <!-- IP Address Filter -->
                <div class="attrla-filter-group">
                    <label for="ip_address"><?php esc_html_e('IP Address', 'attrla'); ?></label>
                    <input type="text" 
                           name="ip_address" 
                           id="ip_address" 
                           value="<?php echo esc_attr($filters['ip_address']); ?>"
                           placeholder="<?php esc_attr_e('Filter by IP', 'attrla'); ?>">
                </div>

                <!-- Username Filter -->
                <div class="attrla-filter-group">
                    <label for="username"><?php esc_html_e('Username', 'attrla'); ?></label>
                    <input type="text" 
                           name="username" 
                           id="username" 
                           value="<?php echo esc_attr($filters['username']); ?>"
                           placeholder="<?php esc_attr_e('Filter by username', 'attrla'); ?>">
                </div>
            </div>

            <div class="attrla-filters-actions">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e('Apply Filters', 'attrla'); ?>
                </button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=attrla-logs')); ?>" class="button">
                    <?php esc_html_e('Reset Filters', 'attrla'); ?>
                </a>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <form method="post" id="attrla-logs-form">
        <?php wp_nonce_field('attrla_logs_action', 'attrla_nonce'); ?>

        <div class="tablenav top">
            <!-- Bulk Actions -->
            <div class="alignleft actions bulkactions">
                <select name="action">
                    <option value=""><?php esc_html_e('Bulk Actions', 'attrla'); ?></option>
                    <option value="delete"><?php esc_html_e('Delete', 'attrla'); ?></option>
                    <option value="export"><?php esc_html_e('Export', 'attrla'); ?></option>
                </select>
                <input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'attrla'); ?>">
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1) : ?>
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(
                            /* translators: %s: Number of items */
                            esc_html__('%s items', 'attrla'),
                            number_format_i18n($total_items)
                        ); ?>
                    </span>
                    
                    <span class="pagination-links">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_pages,
                            'current' => $page,
                            'type' => 'list'
                        ));
                        ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <table class="wp-list-table widefat fixed striped attrla-logs-table">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all-1">
                    </td>
                    <th scope="col" class="manage-column column-date">
                        <?php esc_html_e('Date', 'attrla'); ?>
                    </th>
                    <th scope="col" class="manage-column column-event">
                        <?php esc_html_e('Event', 'attrla'); ?>
                    </th>
                    <th scope="col" class="manage-column column-username">
                        <?php esc_html_e('Username', 'attrla'); ?>
                    </th>
                    <th scope="col" class="manage-column column-ip">
                        <?php esc_html_e('IP Address', 'attrla'); ?>
                    </th>
                    <th scope="col" class="manage-column column-status">
                        <?php esc_html_e('Status', 'attrla'); ?>
                    </th>
                    <th scope="col" class="manage-column column-details">
                        <?php esc_html_e('Details', 'attrla'); ?>
                    </th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($logs)) : ?>
                    <?php foreach ($logs as $log) : ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="log_ids[]" value="<?php echo esc_attr($log->id); ?>">
                            </th>
                            <td class="column-date">
                                <?php 
                                echo esc_html(
                                    date_i18n(
                                        get_option('date_format') . ' ' . get_option('time_format'),
                                        strtotime($log->created_at)
                                    )
                                ); 
                                ?>
                            </td>
                            <td class="column-event">
                                <?php 
                                echo ATTRLA_UI::get_status_badge(
                                    $log->event_type,
                                    esc_html($event_types[$log->event_type] ?? ucwords(str_replace('_', ' ', $log->event_type)))
                                ); 
                                ?>
                            </td>
                            <td class="column-username">
                                <?php if ($log->user_id) : ?>
                                    <a href="<?php echo esc_url(get_edit_user_link($log->user_id)); ?>">
                                        <?php echo esc_html($log->username); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo esc_html($log->username); ?>
                                <?php endif; ?>
                            </td>
                            <td class="column-ip">
                                <a href="#" class="attrla-copy-ip" title="<?php esc_attr_e('Click to copy IP', 'attrla'); ?>">
                                    <?php echo esc_html($log->ip_address); ?>
                                </a>
                                <?php if (ATTRLA_Security::is_ip_blocked($log->ip_address)) : ?>
                                    <span class="attrla-badge attrla-badge-error">
                                        <?php esc_html_e('Blocked', 'attrla'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="column-status">
                                <?php 
                                $status_class = array(
                                    'success' => 'success',
                                    'failed' => 'error',
                                    'blocked' => 'warning'
                                );
                                echo ATTRLA_UI::get_status_badge(
                                    $status_class[$log->status] ?? 'default',
                                    esc_html(ucfirst($log->status))
                                ); 
                                ?>
                            </td>
                            <td class="column-details">
                                <?php echo wp_kses_post($log->details); ?>
                                <?php if (!empty($log->extra_data)) : ?>
                                    <button type="button" 
                                            class="button button-small attrla-view-details" 
                                            data-details="<?php echo esc_attr(wp_json_encode($log->extra_data)); ?>">
                                        <?php esc_html_e('View Details', 'attrla'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" class="attrla-no-items">
                            <?php esc_html_e('No logs found.', 'attrla'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>

            <tfoot>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all-2">
                    </td>
                    <th scope="col" class="manage-column column-date">
                        <?php esc_html_e('Date', 'attrla'); ?>
                    </th>
                    <th scope="col" class="manage-column column-event">
                        <?php esc_html_e('Event', 'attrla'); ?>
                    </th>
                    <th scope="col" class="manage-column column-username">
                        <?php esc_html_e('Username', 'attrla'); ?>
                    </th>
                    <th scope="col" class="manage-column column-ip">
                        <?php esc_html_e('IP Address', 'attrla'); ?>
                    </th>
                    <th scope="col" class="manage-column column-status">
                        <?php esc_html_e('Status', 'attrla'); ?>
                    </th>
                    <th scope="col" class="manage-column column-details">
                        <?php esc_html_e('Details', 'attrla'); ?>
                    </th>
                </tr>
            </tfoot>
        </table>
    </form>
</div>

<!-- Details Modal -->
<div id="attrla-details-modal" class="attrla-modal" style="display: none;">
    <div class="attrla-modal-content">
        <span class="attrla-modal-close">&times;</span>
        <h2><?php esc_html_e('Event Details', 'attrla'); ?></h2>
        <div class="attrla-modal-body"></div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Handle date range selector
    $('#date_range').on('change', function() {
        $('.custom-date-range').toggle($(this).val() === 'custom');
    });

    // Handle bulk action checkboxes
    $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.attrla-logs-table input[type="checkbox"]').prop('checked', isChecked);
    });

    // Handle bulk actions
    $('#attrla-logs-form').on('submit', function(e) {
        const action = $(this).find('select[name="action"]').val();
        if (action === 'delete') {
            if (!confirm(attrlaAdmin.i18n.confirmDelete)) {
                e.preventDefault();
            }
        }
    });

    // Handle IP address copying
    $('.attrla-copy-ip').on('click', function(e) {
        e.preventDefault();
        const ip = $(this).text().trim();
        
        navigator.clipboard.writeText(ip).then(() => {
            const originalText = $(this).text();
            $(this).text(attrlaAdmin.i18n.copied);
            
            setTimeout(() => {
                $(this).text(originalText);
            }, 1000);
        });
    });

    // Handle clear all logs
    $('.attrla-clear-all').on('click', function() {
        if (confirm(attrlaAdmin.i18n.confirmClearAll)) {
            const form = $('#attrla-logs-form');
            $('<input>').attr({
                type: 'hidden',
                name: 'action',
                value: 'clear_all'
            }).appendTo(form);
            form.submit();
        }
    });

    // Handle export all logs
    $('.attrla-export-all').on('click', function() {
        const form = $('#attrla-logs-form');
        $('<input>').attr({
            type: 'hidden',
            name: 'action',
            value: 'export'
        }).appendTo(form);
        form.submit();
    });

    // Handle view details modal
    $('.attrla-view-details').on('click', function() {
        const details = JSON.parse($(this).data('details'));
        let html = '<dl class="attrla-details-list">';
        
        Object.entries(details).forEach(([key, value]) => {
            html += `<dt>${key}</dt><dd>${value}</dd>`;
        });
        
        html += '</dl>';
        
        $('.attrla-modal-body').html(html);
        $('#attrla-details-modal').show();
    });

    // Handle modal close
    $('.attrla-modal-close').on('click', function() {
        $('#attrla-details-modal').hide();
    });

    // Close modal on outside click
    $(window).on('click', function(e) {
        if ($(e.target).is('.attrla-modal')) {
            $('.attrla-modal').hide();
        }
    });

    // Auto-refresh if enabled
    <?php if (get_option('attrla_auto_refresh_logs', false)) : ?>
    const refreshInterval = <?php echo absint(get_option('attrla_refresh_interval', 30)) * 1000; ?>;
    setInterval(() => {
        if (document.visibilityState === 'visible') {
            location.reload();
        }
    }, refreshInterval);
    <?php endif; ?>
});
</script>