<?php
/**
 * Must-Use Plugin: MCX Elementor Editor Optimizer - Editor Firewall
 * A product of Miracuves.com — powered by Miracuves.
 *
 * When the Elementor editor is opened with "Editor Firewall" mode (via EEO launch token),
 * this filters active_plugins so that only essential plugins load during that session.
 *
 * Installation: Copy this file to wp-content/mu-plugins/eeo-firewall.php
 * (or the main plugin can copy it on activation).
 *
 * @package MCX_Elementor_Editor_Optimizer
 */

if (!defined('ABSPATH')) {
    return;
}

/**
 * Detect if current request is for Elementor editor (admin or frontend).
 */
function eeo_firewall_is_editor_request() {
    if (!is_array($_GET)) {
        return false;
    }
    if (!empty($_GET['action']) && $_GET['action'] === 'elementor') {
        return true;
    }
    if (!empty($_GET['elementor']) || !empty($_GET['elementor-preview'])) {
        return true;
    }
    return false;
}

/**
 * Check if firewall should run: editor request + (valid token with firewall mode, or remembered modes include firewall).
 */
function eeo_firewall_should_apply() {
    if (!eeo_firewall_is_editor_request()) {
        return false;
    }
    $token = isset($_GET['eeo_launch_token']) ? sanitize_text_field(wp_unslash($_GET['eeo_launch_token'])) : '';
    if ($token !== '') {
        $stored = get_transient('eeo_launch_' . $token);
        if (is_array($stored) && !empty($stored['modes']) && in_array('firewall', $stored['modes'], true)) {
            return true;
        }
    }
    // No token or token without firewall: check remembered modes (per-page or global)
    $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
    if ($post_id > 0) {
        $modes = get_post_meta($post_id, '_eeo_launch_modes', true);
        if (is_array($modes) && in_array('firewall', $modes, true)) {
            return true;
        }
    }
    $modes = get_option('eeo_global_launch_modes', []);
    if (is_array($modes) && in_array('firewall', $modes, true)) {
        return true;
    }
    return false;
}

/**
 * Plugins that must never be disabled (Elementor core, Pro, EEO).
 */
function eeo_firewall_never_disable() {
    return [
        'elementor/elementor.php',
        'elementor-pro/elementor-pro.php',
        'elementor-editor-optimizer/elementor-editor-optimizer.php',
        'EEOptimizer/elementor-editor-optimizer.php',
    ];
}

/**
 * Filter option_active_plugins to disable selected plugins in this editor session.
 */
function eeo_firewall_filter_active_plugins($plugins) {
    if (!eeo_firewall_should_apply()) {
        return $plugins;
    }
    $settings = get_option('elementor_editor_optimizer_settings', []);
    $to_disable = isset($settings['firewall_plugins']) && is_array($settings['firewall_plugins'])
        ? $settings['firewall_plugins']
        : [];
    $never = eeo_firewall_never_disable();
    $to_disable = array_diff($to_disable, $never);
    if (empty($to_disable)) {
        return $plugins;
    }
    $filtered = array_values(array_diff((array) $plugins, $to_disable));
    return $filtered;
}

add_filter('option_active_plugins', 'eeo_firewall_filter_active_plugins', 1);
