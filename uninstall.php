<?php
/**
 * Uninstall plugin
 * 
 * This file is executed when the plugin is uninstalled.
 * It cleans up all plugin data from the database.
 */

// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define plugin options
$plugin_options = [
    'elementor_editor_optimizer_settings',
    'eeo_debug_log',
    'elementor_editor_loader_method', // Modified by our plugin
];

// Delete plugin options
foreach ($plugin_options as $option) {
    delete_option($option);
}

// Clean up any transients
$transients = [
    'eeo_used_fonts',
    'eeo_used_icons',
    'eeo_widget_stats',
    'eeo_cache_',
];

global $wpdb;
foreach ($transients as $transient) {
    if (strpos($transient, '_') === false) {
        delete_transient($transient);
    } else {
        // Delete transients with wildcard
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $transient . '%'
            )
        );
    }
}

// Clean up any scheduled cron jobs
wp_clear_scheduled_hook('eeo_cache_cleanup');

// Clean up any user meta if added in the future
// This is a placeholder for future user meta cleanup
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
        'eeo_%'
    )
);

// Log the uninstall (if debug mode was enabled)
if (get_option('elementor_editor_optimizer_settings')) {
    $settings = get_option('elementor_editor_optimizer_settings');
    if (isset($settings['debug_mode']) && 'yes' === $settings['debug_mode']) {
        error_log('Elementor Editor Optimizer plugin uninstalled at ' . current_time('mysql'));
    }
}