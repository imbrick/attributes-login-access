<?php
/**
 * Dashboard page template
 *
 * @package AttributeLoginAccess
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get statistics
$stats = ATTRLA_Data::get_statistics();
$recent_events = ATTRLA_Data::get_security_events(array('limit' => 5));
$active_lockouts = ATTRLA_Security::get_active_lockouts();
?>

<div class="wrap attrla-admin-wrap">
    <h1 class="wp-heading-inline">
        <?php esc_html_e('Login Security Dashboard', 'attrla'); ?>
    </h1>

    <?php if (isset($_GET['settings-updated'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Settings updated successfully.', 'attrla'); ?></p>
        </div>
    <?php endif; ?>

    <!-- Overview Cards -->
    <div class="attrla-dashboard-grid">
        <!-- Failed Attempts Card -->
        <div class="attrla-card attrla-stat-card">
            <div class="attrla-stat-icon dashicons dashicons-shield"></div>
            <div class="attrla-stat-value"><?php echo esc_html($stats['failed_today']); ?></div>
            <div class="attrla-stat-label"><?php esc_html_e('Failed Attempts Today', 'attrla'); ?></div>
        </div>

        <!-- Active Lockouts Card -->
        <div class="attrla-card attrla-stat-card">
            <div class="attrla-stat-icon dashicons dashicons-lock"></div>
            <div class="attrla-stat-value"><?php echo esc_html(count($active_lockouts)); ?></div>
            <div class="attrla-stat-label"><?php esc_html_e('Active Lockouts', 'attrla'); ?></div>
        </div>

        <!-- Blocked IPs Card -->
        <div class="attrla-card attrla-stat-card">
            <div class="attrla-stat-icon dashicons dashicons-dismiss"></div>
            <div class="attrla-stat-value"><?php echo esc_html($stats['blocked_ips']); ?></div>
            <div class="attrla-stat-label"><?php esc_html_e('Blocked IPs', 'attrla'); ?></div>
        </div>

        <!-- Successful Logins Card -->
        <div class="attrla-card attrla-stat-card">
            <div class="attrla-stat-icon dashicons dashicons-yes-alt"></div>
            <div class="attrla-stat-value"><?php echo esc_html($stats['recent_success']); ?></div>
            <div class="attrla-stat-label"><?php esc_html_e('Successful Logins (24h)', 'attrla'); ?></div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="attrla-dashboard-main">
        <!-- Recent Events -->
        <div class="attrla-card">
            <div class="attrla-card-header">
                <h2><?php esc_html_e('Recent Security Events', 'attrla'); ?></h2>
            </div>
            <div class="attrla-card-content">
                <?php if (!empty($recent_events)) : ?>
                    <table class="widefat attrla-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Time', 'attrla'); ?></th>
                                <th><?php esc_html_e('Event', 'attrla'); ?></th>
                                <th><?php esc_html_e('IP Address', 'attrla'); ?></th>
                                <th><?php esc_html_e('Details', 'attrla'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_events as $event) : ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html(
                                            human_time_diff(
                                                strtotime($event->created_at),
                                                current_time('timestamp')
                                            ) . ' ago'
                                        ); ?>
                                    </td>
                                    <td>
                                        <?php echo ATTRLA_UI::get_status_badge(
                                            $event->event_type,
                                            esc_html(ucwords(str_replace('_', ' ', $event->event_type)))
                                        ); ?>
                                    </td>
                                    <td><?php echo esc_html($event->ip_address); ?></td>
                                    <td><?php echo wp_kses_post($event->details); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="attrla-card-footer">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=attrla-logs')); ?>" class="button">
                            <?php esc_html_e('View All Events', 'attrla'); ?>
                        </a>
                    </div>
                <?php else : ?>
                    <p class="attrla-empty-state">
                        <?php esc_html_e('No recent security events.', 'attrla'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Active Lockouts -->
        <div class="attrla-card">
            <div class="attrla-card-header">
                <h2><?php esc_html_e('Active Lockouts', 'attrla'); ?></h2>
            </div>
            <div class="attrla-card-content">
                <?php if (!empty($active_lockouts)) : ?>
                    <table class="widefat attrla-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Username/IP', 'attrla'); ?></th>
                                <th><?php esc_html_e('Reason', 'attrla'); ?></th>
                                <th><?php esc_html_e('Expires', 'attrla'); ?></th>
                                <th><?php esc_html_e('Actions', 'attrla'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_lockouts as $lockout) : ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($lockout->username)) : ?>
                                            <strong><?php echo esc_html($lockout->username); ?></strong>
                                            <br>
                                        <?php endif; ?>
                                        <?php echo esc_html($lockout->ip_address); ?>
                                    </td>
                                    <td><?php echo esc_html($lockout->reason); ?></td>
                                    <td>
                                        <?php echo esc_html(
                                            human_time_diff(
                                                current_time('timestamp'),
                                                strtotime($lockout->expires)
                                            )
                                        ); ?>
                                    </td>
                                    <td>
                                        <button type="button" 
                                                class="button button-small attrla-unlock-button" 
                                                data-id="<?php echo esc_attr($lockout->id); ?>"
                                                data-nonce="<?php echo esc_attr(wp_create_nonce('attrla-unlock-' . $lockout->id)); ?>">
                                            <?php esc_html_e('Unlock', 'attrla'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p class="attrla-empty-state">
                        <?php esc_html_e('No active lockouts.', 'attrla'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- System Status -->
        <div class="attrla-card">
            <div class="attrla-card-header">
                <h2><?php esc_html_e('System Status', 'attrla'); ?></h2>
            </div>
            <div class="attrla-card-content">
                <table class="widefat attrla-table">
                    <tbody>
                        <tr>
                            <td><?php esc_html_e('Plugin Version', 'attrla'); ?></td>
                            <td><?php echo esc_html(ATTRLA_VERSION); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('WordPress Version', 'attrla'); ?></td>
                            <td><?php echo esc_html($GLOBALS['wp_version']); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('PHP Version', 'attrla'); ?></td>
                            <td><?php echo esc_html(PHP_VERSION); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('Database Size', 'attrla'); ?></td>
                            <td><?php echo esc_html(size_format(ATTRLA_Data::get_database_size())); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('Security Mode', 'attrla'); ?></td>
                            <td>
                                <?php echo ATTRLA_UI::get_status_badge(
                                    'success',
                                    esc_html(get_option('attrla_security_mode', 'Normal'))
                                ); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Handle unlock button clicks
    $('.attrla-unlock-button').on('click', function() {
        const button = $(this);
        const id = button.data('id');
        const nonce = button.data('nonce');

        button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'attrla_unlock_lockout',
                id: id,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    button.closest('tr').fadeOut();
                } else {
                    alert(response.data.message);
                    button.prop('disabled', false);
                }
            },
            error: function() {
                alert('Error processing request');
                button.prop('disabled', false);
            }
        });
    });
});
</script>