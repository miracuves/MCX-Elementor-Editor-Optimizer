<?php
/**
 * Plugin Name: MCX Elementor Editor Optimizer
 * Plugin URI: https://miracuves.com
 * Description: Advanced performance optimization plugin for Elementor page builder. Speeds up editor loading, disables unused widgets, optimizes assets, and improves overall performance. A product of Miracuves.com — powered by Miracuves.
 * Version: 1.0.0
 * Author: Miracuves
 * Author URI: https://miracuves.com
 * Text Domain: elementor-editor-optimizer
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Elementor requires at least: 3.0.0
 * Elementor tested up to: 3.18.0
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EEO_VERSION', '1.0.0');
define('EEO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EEO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EEO_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main MCX Elementor Editor Optimizer Class
 */
class Elementor_Editor_Optimizer {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Plugin settings
     */
    private $settings;
    
    /**
     * Per-request launch modes selected for Elementor editor
     * Example: ['firewall', 'snapshot', 'diet']
     */
    private $launch_modes = [];
    
    /**
     * AJAX: Full reset (settings + analytics)
     */
    public function ajax_full_reset() {
        check_ajax_referer('eeo_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        // Reset settings
        delete_option('elementor_editor_optimizer_settings');
        $this->settings = $this->get_default_settings();
        // Reset analytics
        delete_option('eeo_widget_usage_data');
        delete_option('eeo_widget_usage_log');
        delete_option('eeo_last_full_scan');
        wp_send_json_success(['message' => 'All plugin settings and analytics have been reset.']);
    }
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        try {
            $this->settings = $this->get_default_settings();
            $saved_settings = get_option('elementor_editor_optimizer_settings', []);
            $this->settings = is_array($saved_settings) ? wp_parse_args($saved_settings, $this->settings) : $this->settings;
            
            $this->check_compatibility();
            
            if ($this->is_compatible()) {
                add_action('admin_init', [$this, 'maybe_handle_elementor_launch'], 1);
                $this->init_plugin();
            }
        } catch (Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                error_log('EEOptimizer init: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Get default settings
     */
    private function get_default_settings() {
        return [
            'disable_widgets' => [],
            'disable_unused_fonts' => 'no',
            'disable_unused_icons' => 'no',
            // New: make widget usage tracking explicitly opt-in
            'enable_widget_tracking' => 'no',
            'editor_memory_limit' => '512M',
            'editor_optimization' => 'yes',
            'switch_editor_method' => 'no',
            'enable_lazy_load' => 'yes',
            'minify_html' => 'no',
            'optimize_css_js' => 'yes',
            'disable_emojis' => 'no',
            'disable_embeds' => 'no',
            'disable_jquery_migrate' => 'no',
            'enable_cache' => 'yes',
            'cache_expiration' => 3600,
            'debug_mode' => 'no',
            'advanced_mode' => 'no',
            'whitelisted_widgets' => [],
            'safe_mode' => 'yes',
            'firewall_plugins' => [],
        ];
    }
    
    /**
     * Check plugin compatibility
     */
    public function check_compatibility() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', [$this, 'php_version_notice']);
            return;
        }
        
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            add_action('admin_notices', [$this, 'wp_version_notice']);
            return;
        }
        
        // Check if Elementor is installed and activated
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', [$this, 'elementor_not_found_notice']);
            return;
        }
        
        // Check Elementor version
        if (defined('ELEMENTOR_VERSION') && version_compare(ELEMENTOR_VERSION, '3.0.0', '<')) {
            add_action('admin_notices', [$this, 'elementor_version_notice']);
            return;
        }
    }
    
    /**
     * Check if plugin is compatible
     */
    private function is_compatible() {
        return (
            version_compare(PHP_VERSION, '7.4', '>=') &&
            version_compare(get_bloginfo('version'), '5.0', '>=') &&
            (!defined('ELEMENTOR_VERSION') || version_compare(ELEMENTOR_VERSION, '3.0.0', '>='))
        );
    }
    
    /**
     * Initialize plugin functionality
     */
    private function init_plugin() {
        // Load text domain
        add_action('init', [$this, 'load_textdomain']);
        
        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        // Hidden launch screen for Elementor editor optimization modes
        add_action('admin_menu', [$this, 'register_launch_screen'], 99);
        
        // Register settings
        add_action('admin_init', [$this, 'register_settings']);
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // Run hydration + optimizations on init (after current user is set) so launch_modes and current_user_can() are reliable
        add_action('init', [$this, 'run_optimizations_after_hydration'], 1);
        
        // Add AJAX handlers
        $this->init_ajax_handlers();
        
        // Initialize widget usage tracking
        $this->init_widget_usage_tracking();
    }
    
    /**
     * Hydrate $this->launch_modes from eeo_launch_token (must run before init_optimizations).
     */
    private function maybe_hydrate_launch_modes_from_token() {
        if (!is_admin() || !current_user_can('edit_posts')) {
            return;
        }
        if (!isset($_GET['eeo_launch_token']) || !is_string($_GET['eeo_launch_token'])) {
            return;
        }
        $token = sanitize_text_field(wp_unslash($_GET['eeo_launch_token']));
        if ($token === '') {
            return;
        }
        $data = get_transient('eeo_launch_' . $token);
        if (!is_array($data) || empty($data['user']) || (int) $data['user'] !== get_current_user_id()) {
            return;
        }
        $this->launch_modes = isset($data['modes']) && is_array($data['modes']) ? $data['modes'] : [];
    }

    /**
     * Determine if current request is for Elementor editor
     */
    public static function is_elementor_editor_request() {
        if (!function_exists('is_user_logged_in') || !is_user_logged_in()) {
            return false;
        }
        if (!isset($_GET) || !is_array($_GET)) {
            return false;
        }
        // Elementor front-end editor: ?elementor or elementor-preview
        if (isset($_GET['elementor']) || isset($_GET['elementor-preview'])) {
            return true;
        }
        // Classic wp-admin editor URL: post.php?post=ID&action=elementor
        if (is_admin() && isset($_GET['action']) && $_GET['action'] === 'elementor' && isset($_GET['post'])) {
            return true;
        }
        return false;
    }
    
    /**
     * Register hidden launch screen page (no menu item)
     */
    public function register_launch_screen() {
        add_submenu_page(
            null, // no menu entry
            __('MCX Elementor Launch Modes', 'elementor-editor-optimizer'),
            __('MCX Elementor Launch Modes', 'elementor-editor-optimizer'),
            'edit_posts',
            'eeo-elementor-launch',
            [$this, 'render_launch_screen']
        );
    }
    
    /**
     * Handle Elementor launch flow:
     * - If editor request without token: redirect to launch screen
     * - If returning with token: hydrate $launch_modes from transient
     */
    public function maybe_handle_elementor_launch() {
        // Only run in admin area and for users with edit capabilities
        if (!is_admin() || !current_user_can('edit_posts')) {
            return;
        }

        // Never run during AJAX, REST, cron, or when headers were sent (no redirects)
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }
        if (defined('DOING_CRON') && DOING_CRON) {
            return;
        }
        if (headers_sent()) {
            return;
        }
        
        // If we are on the launch screen, handle form submission and bail
        if (isset($_GET['page']) && $_GET['page'] === 'eeo-elementor-launch') {
            if (isset($_POST['eeo_launch_nonce']) && wp_verify_nonce($_POST['eeo_launch_nonce'], 'eeo_launch_modes')) {
                $profile = isset($_POST['eeo_profile']) ? sanitize_text_field(wp_unslash($_POST['eeo_profile'])) : 'edit';
                $profile = in_array($profile, ['build', 'edit'], true) ? $profile : 'edit';
                
                // Build mode: load everything, no optimizations that can hide widgets/plugins
                if ($profile === 'build') {
                    $modes = ['build'];
                } else {
                    $modes = isset($_POST['eeo_modes']) ? array_map('sanitize_text_field', (array) $_POST['eeo_modes']) : [];
                    $modes = array_values(array_unique(array_intersect($modes, ['firewall', 'snapshot', 'diet'])));
                }
                
                // Remember for this page
                if (!empty($_POST['eeo_remember_page'])) {
                    $post_id = isset($_POST['eeo_post_id']) ? absint($_POST['eeo_post_id']) : 0;
                    if ($post_id > 0) {
                        update_post_meta($post_id, '_eeo_launch_modes', $modes);
                    }
                }
                // Remember globally
                if (!empty($_POST['eeo_remember_global'])) {
                    update_option('eeo_global_launch_modes', $modes);
                }
                
                $token = wp_generate_password(12, false, false);
                set_transient('eeo_launch_' . $token, [
                    'modes' => $modes,
                    'user'  => get_current_user_id(),
                ], MINUTE_IN_SECONDS * 10);
                
                $return_url = !empty($_POST['eeo_return']) ? esc_url_raw(wp_unslash($_POST['eeo_return'])) : admin_url();
                // Only redirect to same site (wp_safe_redirect allows same host; ensure no external)
                $return_url = wp_validate_redirect($return_url, admin_url());
                $separator = (strpos($return_url, '?') === false) ? '?' : '&';
                wp_safe_redirect($return_url . $separator . 'eeo_launch_token=' . rawurlencode($token));
                exit;
            }
            return;
        }
        
        // From here on we work only with Elementor editor requests
        if (!self::is_elementor_editor_request()) {
            return;
        }

        // Token case: already hydrated on init via run_optimizations_after_hydration
        if (!empty($_GET['eeo_launch_token'])) {
            return;
        }

        // Remembered choice: skip redirect and let editor load with saved modes
        if ($this->has_remembered_launch_modes()) {
            return;
        }

        // No token yet: redirect to launch screen
        if (empty($_SERVER['HTTP_HOST']) || empty($_SERVER['REQUEST_URI'])) {
            return;
        }
        
        $current_url = (isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS'] ? 'https://' : 'http://');
        $current_url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        
        $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
        $launch_url = add_query_arg(
            [
                'page'   => 'eeo-elementor-launch',
                'post'   => $post_id,
                'eeo_return' => rawurlencode($current_url),
            ],
            admin_url('admin.php')
        );
        
        wp_safe_redirect($launch_url);
        exit;
    }
    
    /**
     * Render lightweight launch screen with three mode options
     */
    public function render_launch_screen() {
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to access this page.', 'elementor-editor-optimizer'));
        }
        
        $post_id    = isset($_GET['post']) ? absint($_GET['post']) : 0;
        $return_url = isset($_GET['eeo_return']) ? esc_url_raw(wp_unslash($_GET['eeo_return'])) : '';
        ?>
        <div class="wrap" style="max-width: 720px;">
            <h1><?php esc_html_e('MCX Elementor Launch Modes', 'elementor-editor-optimizer'); ?></h1>
            <p><?php esc_html_e('Choose Build Mode to load everything (best for building), or Edit Mode to speed up the editor for quick changes.', 'elementor-editor-optimizer'); ?></p>
            
            <form method="post">
                <?php wp_nonce_field('eeo_launch_modes', 'eeo_launch_nonce'); ?>
                <input type="hidden" name="eeo_return" value="<?php echo esc_attr($return_url); ?>">
                <input type="hidden" name="eeo_post_id" value="<?php echo esc_attr($post_id); ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Session Type', 'elementor-editor-optimizer'); ?></th>
                        <td>
                            <label style="display:block;margin-bottom:8px;">
                                <input type="radio" name="eeo_profile" value="build">
                                <strong><?php esc_html_e('Build Mode', 'elementor-editor-optimizer'); ?></strong><br>
                                <span class="description"><?php esc_html_e('Loads all widgets and plugins. Use when building new sections or when something is missing.', 'elementor-editor-optimizer'); ?></span>
                            </label>
                            <label style="display:block;margin-bottom:8px;">
                                <input type="radio" name="eeo_profile" value="edit" checked>
                                <strong><?php esc_html_e('Edit Mode (faster)', 'elementor-editor-optimizer'); ?></strong><br>
                                <span class="description"><?php esc_html_e('Optimizes the editor so it loads faster for quick edits.', 'elementor-editor-optimizer'); ?></span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Edit Mode Options', 'elementor-editor-optimizer'); ?></th>
                        <td>
                            <label style="display:block;margin-bottom:8px;">
                                <input type="checkbox" name="eeo_modes[]" value="firewall" checked>
                                <strong><?php esc_html_e('Editor Firewall (recommended)', 'elementor-editor-optimizer'); ?></strong><br>
                                <span class="description"><?php esc_html_e('Loads Elementor with a minimal set of plugins and hooks for this editor session only.', 'elementor-editor-optimizer'); ?></span>
                            </label>
                            <label style="display:block;margin-bottom:8px;">
                                <input type="checkbox" name="eeo_modes[]" value="snapshot">
                                <strong><?php esc_html_e('Snapshot Cache', 'elementor-editor-optimizer'); ?></strong><br>
                                <span class="description"><?php esc_html_e('Reuses precomputed Elementor data where available to reduce database work.', 'elementor-editor-optimizer'); ?></span>
                            </label>
                            <label style="display:block;margin-bottom:8px;">
                                <input type="checkbox" name="eeo_modes[]" value="diet">
                                <strong><?php esc_html_e('Widget Diet', 'elementor-editor-optimizer'); ?></strong><br>
                                <span class="description"><?php esc_html_e('Disables unused Elementor widgets according to your optimizer settings.', 'elementor-editor-optimizer'); ?></span>
                            </label>
                            <p class="description"><?php esc_html_e('These options apply only when Edit Mode is selected.', 'elementor-editor-optimizer'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Remember choice', 'elementor-editor-optimizer'); ?></th>
                        <td>
                            <?php if ($post_id > 0) : ?>
                            <label style="display:block;margin-bottom:6px;">
                                <input type="checkbox" name="eeo_remember_page" value="1">
                                <?php esc_html_e('Remember for this page', 'elementor-editor-optimizer'); ?>
                            </label>
                            <?php endif; ?>
                            <label style="display:block;">
                                <input type="checkbox" name="eeo_remember_global" value="1">
                                <?php esc_html_e('Remember globally (use these modes by default)', 'elementor-editor-optimizer'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Launch Elementor Editor', 'elementor-editor-optimizer'); ?>
                    </button>
                </p>
            </form>
            
            <?php if ($post_id): ?>
                <p class="description">
                    <?php
                    /* translators: %d: post ID */
                    printf(esc_html__('You are about to open Elementor for post ID %d.', 'elementor-editor-optimizer'), $post_id);
                    ?>
                </p>
            <?php endif; ?>
            <p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #ddd; color: #666; font-size: 13px;">
                <?php esc_html_e('Powered by', 'elementor-editor-optimizer'); ?>
                <a href="https://miracuves.com" target="_blank" rel="noopener noreferrer">Miracuves</a>
                · <a href="https://miracuves.com" target="_blank" rel="noopener noreferrer">Miracuves.com</a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Initialize widget usage tracking
     */
    private function init_widget_usage_tracking() {
        // Only run tracking if explicitly enabled in settings to avoid overhead on large sites
        if (!isset($this->settings['enable_widget_tracking']) || $this->settings['enable_widget_tracking'] !== 'yes') {
            return;
        }
        
        // Track widget usage in Elementor editor
        add_action('elementor/editor/after_save', [$this, 'track_widget_usage_on_save'], 10, 2);
        
        // Track widget usage during page load (admin/editor context only)
        if (is_admin()) {
            add_action('elementor/frontend/widget/before_render', [$this, 'track_widget_usage_frontend'], 10, 1);
        }
    }
    
    /**
     * Track widget usage when page is saved in editor
     */
    public function track_widget_usage_on_save($post_id, $editor_data) {
        if (empty($editor_data)) {
            return;
        }
        
        $widgets_used = $this->extract_widgets_from_data($editor_data);
        $this->update_widget_usage_stats($widgets_used, $post_id);
    }
    
    /**
     * Track widget usage on frontend render
     */
    public function track_widget_usage_frontend($widget) {
        if (!$widget || !method_exists($widget, 'get_name')) {
            return;
        }
        
        $widget_type = $widget->get_name();
        $this->log_widget_usage($widget_type, 'frontend');
    }
    
    /**
     * Extract widgets from Elementor data
     */
    private function extract_widgets_from_data($data) {
        $widgets = [];
        
        if (!is_array($data)) {
            return $widgets;
        }
        
        foreach ($data as $element) {
            // Check for widget type - Elementor stores this in different ways
            if (isset($element['widgetType'])) {
                $widgets[] = $element['widgetType'];
            }
            
            // Also check elType for widgets
            if (isset($element['elType']) && $element['elType'] === 'widget' && isset($element['widgetType'])) {
                $widgets[] = $element['widgetType'];
            }
            
            // Check settings for widget type as fallback
            if (isset($element['settings']) && isset($element['settings']['_element_id'])) {
                // Try to extract widget type from element data
                if (isset($element['elType']) && $element['elType'] === 'widget') {
                    if (isset($element['widgetType'])) {
                        $widgets[] = $element['widgetType'];
                    }
                }
            }
            
            // Recursively check nested elements
            if (isset($element['elements']) && is_array($element['elements'])) {
                $nested_widgets = $this->extract_widgets_from_data($element['elements']);
                $widgets = array_merge($widgets, $nested_widgets);
            }
        }
        
        return array_unique($widgets);
    }
    
    /**
     * Update widget usage statistics
     */
    private function update_widget_usage_stats($widgets, $post_id = null) {
        $usage_data = get_option('eeo_widget_usage_data', []);
        $current_time = current_time('timestamp');
        
        foreach ($widgets as $widget) {
            if (!isset($usage_data[$widget])) {
                $usage_data[$widget] = [
                    'count' => 0,
                    'first_used' => $current_time,
                    'last_used' => $current_time,
                    'posts' => []
                ];
            }
            
            $usage_data[$widget]['count']++;
            $usage_data[$widget]['last_used'] = $current_time;
            
            if ($post_id && !in_array($post_id, $usage_data[$widget]['posts'])) {
                $usage_data[$widget]['posts'][] = $post_id;
            }
        }
        
        update_option('eeo_widget_usage_data', $usage_data);
    }
    
    /**
     * Log individual widget usage
     */
    private function log_widget_usage($widget_type, $context = 'unknown') {
        $usage_log = get_option('eeo_widget_usage_log', []);
        $today = date('Y-m-d');
        
        if (!isset($usage_log[$today])) {
            $usage_log[$today] = [];
        }
        
        if (!isset($usage_log[$today][$widget_type])) {
            $usage_log[$today][$widget_type] = 0;
        }
        
        $usage_log[$today][$widget_type]++;
        
        // Keep only last 30 days
        $cutoff_date = date('Y-m-d', strtotime('-30 days'));
        foreach ($usage_log as $date => $data) {
            if ($date < $cutoff_date) {
                unset($usage_log[$date]);
            }
        }
        
        update_option('eeo_widget_usage_log', $usage_log);
    }
    
    /**
     * Perform full widget usage scan
     */
    public function perform_full_widget_scan() {
        if (!did_action('elementor/loaded')) {
            $this->log_debug('Elementor not loaded, skipping widget scan');
            return;
        }
        
        $this->log_debug('Starting full widget usage scan');
        
        // Get all posts with Elementor data - expand post types
        $post_types = get_post_types(['public' => true]);
        $this->log_debug('Scanning post types: ' . implode(', ', $post_types));
        
        $posts = get_posts([
            'post_type' => $post_types,
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'private'],
            'meta_query' => [
                [
                    'key' => '_elementor_data',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);
        
        $this->log_debug('Found ' . count($posts) . ' posts with Elementor data');
        
        $all_widgets_found = [];
        $posts_processed = 0;
        
        foreach ($posts as $post) {
            $elementor_data = get_post_meta($post->ID, '_elementor_data', true);
            
            if (!empty($elementor_data)) {
                // Try to decode JSON data
                if (is_string($elementor_data)) {
                    $decoded_data = json_decode($elementor_data, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $elementor_data = $decoded_data;
                    } else {
                        $this->log_debug('JSON decode error for post ' . $post->ID . ': ' . json_last_error_msg());
                        continue;
                    }
                }
                
                $widgets = $this->extract_widgets_from_data($elementor_data);
                if (!empty($widgets)) {
                    $this->log_debug('Post ' . $post->ID . ' (' . $post->post_title . ') uses widgets: ' . implode(', ', $widgets));
                    $all_widgets_found = array_merge($all_widgets_found, $widgets);
                    
                    // Update usage stats for this post
                    $this->update_widget_usage_stats($widgets, $post->ID);
                    $posts_processed++;
                }
            }
        }
        
        $unique_widgets = array_unique($all_widgets_found);
        $this->log_debug('Full scan completed. Found ' . count($unique_widgets) . ' unique widgets in use across ' . $posts_processed . ' posts.');
        $this->log_debug('Unique widgets found: ' . implode(', ', $unique_widgets));
        
        // Store scan results
        update_option('eeo_last_full_scan', [
            'timestamp' => current_time('timestamp'),
            'widgets_found' => $unique_widgets,
            'total_posts_scanned' => count($posts),
            'posts_with_widgets' => $posts_processed
        ]);
        
        return [
            'widgets_found' => $unique_widgets,
            'total_posts' => count($posts),
            'posts_processed' => $posts_processed
        ];
    }
    
    /**
     * Get widget usage analytics
     */
    public function get_widget_usage_analytics() {
        $usage_data = get_option('eeo_widget_usage_data', []);
        $all_widgets = $this->get_all_elementor_widgets();
        $last_scan = get_option('eeo_last_full_scan', []);
        
        $analytics = [
            'used_widgets' => [],
            'unused_widgets' => [],
            'total_widgets' => count($all_widgets),
            'usage_stats' => $usage_data,
            'last_scan' => $last_scan
        ];
        
        foreach ($all_widgets as $widget_id => $widget_data) {
            if (isset($usage_data[$widget_id]) && $usage_data[$widget_id]['count'] > 0) {
                $analytics['used_widgets'][$widget_id] = array_merge(
                    $widget_data,
                    $usage_data[$widget_id]
                );
            } else {
                $analytics['unused_widgets'][$widget_id] = $widget_data;
            }
        }
        
        return $analytics;
    }
    
    /**
     * Run on init (priority 1) so current user is set before we hydrate and apply optimizations.
     * Wrapped so a plugin/theme error never breaks the platform.
     */
    public function run_optimizations_after_hydration() {
        try {
            $this->maybe_hydrate_launch_modes_from_token();
            if (empty($this->launch_modes)) {
                $this->load_remembered_launch_modes();
            }
            $this->init_optimizations();
        } catch (Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                error_log('EEOptimizer run_optimizations_after_hydration: ' . $e->getMessage());
            }
        }
    }

    /**
     * Load launch modes from remembered choice (per-page or global).
     */
    private function load_remembered_launch_modes() {
        if (!is_admin() || !current_user_can('edit_posts') || !isset($_GET) || !is_array($_GET)) {
            return;
        }
        $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
        $modes   = [];
        if ($post_id > 0) {
            $modes = get_post_meta($post_id, '_eeo_launch_modes', true);
        }
        if (empty($modes) || !is_array($modes)) {
            $modes = get_user_meta(get_current_user_id(), 'eeo_launch_modes', true);
        }
        if (empty($modes) || !is_array($modes)) {
            $modes = get_option('eeo_global_launch_modes', []);
        }
        if (!empty($modes) && is_array($modes)) {
            $this->launch_modes = array_values(array_intersect($modes, ['firewall', 'snapshot', 'diet', 'build']));
        }
    }

    /**
     * Whether the current request has a remembered launch choice (so we can skip redirect).
     */
    private function has_remembered_launch_modes() {
        if (!isset($_GET['post'])) {
            $post_id = 0;
        } else {
            $post_id = absint($_GET['post']);
        }
        if ($post_id > 0) {
            $m = get_post_meta($post_id, '_eeo_launch_modes', true);
            if (!empty($m) && is_array($m)) {
                return true;
            }
        }
        if (!empty(get_user_meta(get_current_user_id(), 'eeo_launch_modes', true))) {
            return true;
        }
        $global = get_option('eeo_global_launch_modes', []);
        return is_array($global) && !empty($global);
    }

    /**
     * Initialize optimizations based on settings
     */
    private function init_optimizations() {
        // Never run optimizations on normal frontend requests.
        // This plugin must not affect the public site unless explicitly intended.
        if (!self::is_elementor_editor_request()) {
            return;
        }

        // Always allow editor memory tweak if configured
        add_action('elementor/editor/before_enqueue_scripts', [$this, 'optimize_editor_loading']);

        // Build Mode: load everything (no diet/firewall/snapshot)
        if (in_array('build', $this->launch_modes, true)) {
            return;
        }

        // Apply Editor Firewall and Snapshot modes early for this request (placeholders for now)
        if (in_array('firewall', $this->launch_modes, true)) {
            $this->apply_editor_firewall_mode();
        }
        if (in_array('snapshot', $this->launch_modes, true)) {
            $this->apply_snapshot_mode();
        }
        
        // Widget optimizations (Widget Diet mode)
        // Only apply widget disabling in Elementor editor sessions and only when diet mode is selected.
        if (in_array('diet', $this->launch_modes, true) && !empty($this->settings['disable_widgets'])) {
            add_action('elementor/widgets/widgets_registered', [$this, 'disable_unused_elementor_widgets'], 15);
        }

        // NOTE: Frontend/core optimizations are intentionally NOT applied site-wide,
        // to avoid breaking themes/menus and to keep scope strictly editor-only.
    }
    
    /**
     * Editor Firewall: apply ultra-lean plugin/hooks profile for this request.
     * NOTE: First version is intentionally conservative and mainly a hook point for future expansion.
     */
    private function apply_editor_firewall_mode() {
        $this->log_debug('Editor Firewall mode active for this Elementor editor request');
        // Future: filter option_active_plugins to temporarily disable selected plugins for editor requests only.
    }
    
    /**
     * Snapshot Cache: prepare for using precomputed Elementor data where available.
     * For now this only logs activation and can be extended safely.
     */
    private function apply_snapshot_mode() {
        $this->log_debug('Snapshot Cache mode active for this Elementor editor request');
        // Future: prime cached Elementor data / widget usage to reduce DB work.
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('elementor-editor-optimizer', false, dirname(EEO_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            __('MCX Elementor Editor Optimizer', 'elementor-editor-optimizer'),
            __('MCX Elementor Editor Optimizer', 'elementor-editor-optimizer'),
            'manage_options',
            'elementor-editor-optimizer',
            [$this, 'admin_page']
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting(
            'elementor_editor_optimizer_group',
            'elementor_editor_optimizer_settings',
            [$this, 'sanitize_settings']
        );
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $new_input = [];
        $defaults = $this->get_default_settings();
        
        // Sanitize disable_widgets (array)
        $new_input['disable_widgets'] = isset($input['disable_widgets']) ? array_map('sanitize_text_field', (array) $input['disable_widgets']) : [];
        
        // Sanitize firewall_plugins (array of plugin basenames)
        $new_input['firewall_plugins'] = isset($input['firewall_plugins']) ? array_values(array_unique(array_map('sanitize_text_field', (array) $input['firewall_plugins']))) : [];
        
        // Sanitize yes/no options
        $yes_no_options = [
            'disable_unused_fonts',
            'disable_unused_icons',
            'enable_widget_tracking',
            'editor_optimization',
            'switch_editor_method',
            'enable_lazy_load',
            'minify_html',
            'optimize_css_js',
            'disable_emojis',
            'disable_embeds',
            'disable_jquery_migrate',
            'enable_cache',
            'debug_mode',
            'advanced_mode',
            'safe_mode',
        ];
        
        foreach ($yes_no_options as $option) {
            $new_input[$option] = isset($input[$option]) && $input[$option] === 'yes' ? 'yes' : 'no';
        }
        
        // Sanitize numeric options
        $new_input['cache_expiration'] = isset($input['cache_expiration']) ? absint($input['cache_expiration']) : $defaults['cache_expiration'];
        
        // Sanitize text options
        $text_options = ['editor_memory_limit'];
        foreach ($text_options as $option) {
            $new_input[$option] = isset($input[$option]) ? sanitize_text_field($input[$option]) : $defaults[$option];
        }
        
        return $new_input;
    }
    
    /**
     * Get all Elementor widgets
     */
    public function get_all_elementor_widgets() {
        $widgets = [];
        
        if (!did_action('elementor/loaded')) {
            return $widgets;
        }
        
        try {
            $widget_manager = \Elementor\Plugin::$instance->widgets_manager;
            $widget_types = $widget_manager->get_widget_types();
            
            foreach ($widget_types as $widget_type => $widget_obj) {
                // Determine widget source/addon
                $source = $this->detect_widget_source($widget_type, $widget_obj);
                
                $widgets[$widget_type] = [
                    'name' => $widget_obj->get_title(),
                    'icon' => $widget_obj->get_icon(),
                    'categories' => $widget_obj->get_categories(),
                    'source' => $source,
                    'class' => get_class($widget_obj)
                ];
            }
        } catch (Exception $e) {
            $this->log_debug('Error getting widgets: ' . $e->getMessage());
        }
        
        return $widgets;
    }
    
    /**
     * Detect widget source/addon
     */
    private function detect_widget_source($widget_type, $widget_obj) {
        $class_name = get_class($widget_obj);
        
        // Elementor Core widgets
        if (strpos($class_name, 'Elementor\\Widgets\\') === 0) {
            return 'Elementor Core';
        }
        
        // Elementor Pro widgets
        if (strpos($class_name, 'ElementorPro\\') === 0 || 
            strpos($widget_type, 'pro-') === 0) {
            return 'Elementor Pro';
        }
        
        // The Plus Addons for Elementor
        if (strpos($class_name, 'TheplusAddons\\') === 0 || 
            strpos($widget_type, 'plus-') === 0 ||
            strpos($widget_type, 'tp-') === 0) {
            return 'The Plus Addons';
        }
        
        // Happy Addons for Elementor
        if (strpos($class_name, 'Happy_Addons\\') === 0 || 
            strpos($widget_type, 'happy-') === 0 ||
            strpos($widget_type, 'ha-') === 0) {
            return 'Happy Addons';
        }
        
        // Essential Addons for Elementor
        if (strpos($class_name, 'Essential_Addons_Elementor\\') === 0 || 
            strpos($widget_type, 'eael-') === 0 ||
            strpos($widget_type, 'essential-') === 0) {
            return 'Essential Addons';
        }
        
        // JetElements
        if (strpos($class_name, 'Jet_Elements\\') === 0 || 
            strpos($widget_type, 'jet-') === 0) {
            return 'JetElements';
        }
        
        // PowerPack for Elementor
        if (strpos($class_name, 'PowerpackElements\\') === 0 || 
            strpos($widget_type, 'pp-') === 0 ||
            strpos($widget_type, 'powerpack-') === 0) {
            return 'PowerPack';
        }
        
        // Ultimate Addons for Elementor
        if (strpos($class_name, 'UltimateElementor\\') === 0 || 
            strpos($widget_type, 'uael-') === 0) {
            return 'Ultimate Addons';
        }
        
        // Unlimited Elements
        if (strpos($class_name, 'UnlimitedElements\\') === 0 || 
            strpos($widget_type, 'unlimited-') === 0) {
            return 'Unlimited Elements';
        }
        
        // Premium Addons for Elementor
        if (strpos($class_name, 'PremiumAddons\\') === 0 || 
            strpos($widget_type, 'premium-') === 0) {
            return 'Premium Addons';
        }
        
        // Exclusive Addons for Elementor
        if (strpos($class_name, 'ExclusiveAddons\\') === 0 || 
            strpos($widget_type, 'exad-') === 0) {
            return 'Exclusive Addons';
        }
        
        // Generic third-party detection
        if (strpos($class_name, 'Elementor\\') !== 0) {
            return 'Third-party Addon';
        }
        
        return 'Unknown';
    }
    
    /**
     * Get addon statistics for admin display
     */
    private function get_addon_statistics($widgets) {
        $analytics = $this->get_widget_usage_analytics();
        $stats = [];
        
        foreach ($widgets as $widget_id => $widget_data) {
            $source = isset($widget_data['source']) ? $widget_data['source'] : 'Unknown';
            
            if (!isset($stats[$source])) {
                $stats[$source] = [
                    'total' => 0,
                    'used' => 0,
                    'unused' => 0
                ];
            }
            
            $stats[$source]['total']++;
            
            if (isset($analytics['used_widgets'][$widget_id])) {
                $stats[$source]['used']++;
            } else {
                $stats[$source]['unused']++;
            }
        }
        
        // Sort by total widgets (most widgets first)
        uasort($stats, function($a, $b) {
            return $b['total'] - $a['total'];
        });
        
        return $stats;
    }
    
    /**
     * Disable unused Elementor widgets
     */
    public function disable_unused_elementor_widgets() {
        if (empty($this->settings['disable_widgets']) || !is_array($this->settings['disable_widgets'])) {
            return;
        }
        if (!did_action('elementor/loaded')) {
            return;
        }
        try {
            $widgets_manager = \Elementor\Plugin::$instance->widgets_manager;
            if (!$widgets_manager) {
                return;
            }
            $disabled_count = 0;
            foreach ($this->settings['disable_widgets'] as $widget_id) {
                $widget_id = is_string($widget_id) ? sanitize_text_field($widget_id) : '';
                if ($widget_id === '' || $this->is_core_widget($widget_id)) {
                    continue;
                }
                try {
                    $widgets_manager->unregister($widget_id);
                    $disabled_count++;
                } catch (Throwable $e) {
                    $this->log_debug('Error disabling widget ' . $widget_id . ': ' . $e->getMessage());
                }
            }
            $this->log_debug("Disabled {$disabled_count} widgets");
        } catch (Throwable $e) {
            $this->log_debug('Error in disable_unused_elementor_widgets: ' . $e->getMessage());
        }
    }
    
    /**
     * Check if widget is essential
     */
    private function is_core_widget($widget_id) {
        $essential_widgets = [
            'heading', 'image', 'text-editor', 'video', 'button', 'divider',
            'spacer', 'section', 'column', 'container', 'nav-menu'
        ];
        
        return in_array($widget_id, $essential_widgets);
    }
    
    /**
     * Optimize editor loading
     */
    public function optimize_editor_loading() {
        if (empty($this->settings['editor_memory_limit']) || !is_string($this->settings['editor_memory_limit'])) {
            return;
        }
        try {
            @ini_set('memory_limit', $this->settings['editor_memory_limit']);
        } catch (Throwable $e) {
            // Host may restrict ini_set; do not break the editor
        }
    }
    
    /**
     * Optimize fonts
     */
    public function optimize_fonts() {
        $this->log_debug('Font optimization triggered');
        // Basic font optimization implementation
    }
    
    /**
     * Optimize assets
     */
    public function optimize_assets() {
        // Remove unnecessary scripts
        wp_deregister_script('wp-embed');
        $this->log_debug('Asset optimization applied');
    }
    
    /**
     * Disable emojis
     */
    public function disable_emojis() {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
    }
    
    /**
     * Disable jQuery migrate
     */
    public function disable_jquery_migrate($scripts) {
        if (!is_admin() && isset($scripts->registered['jquery'])) {
            $script = $scripts->registered['jquery'];
            if ($script->deps) {
                $script->deps = array_diff($script->deps, ['jquery-migrate']);
            }
        }
    }
    
    /**
     * Initialize AJAX handlers
     */
    private function init_ajax_handlers() {
        $ajax_actions = [
            'scan_unused_widgets',
            'reset_settings',
            'get_widget_usage_analytics',
            'perform_widget_scan',
            'reset_widget_usage_data',
            'full_reset',
        ];
        
        foreach ($ajax_actions as $action) {
            add_action('wp_ajax_eeo_' . $action, [$this, 'ajax_' . $action]);
        }
    }
    
    /**
     * AJAX handlers
     */
    public function ajax_scan_unused_widgets() {
        check_ajax_referer('eeo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        $widgets = $this->get_all_elementor_widgets();
        wp_send_json_success(['widgets' => $widgets]);
    }
    
    public function ajax_reset_settings() {
        check_ajax_referer('eeo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        delete_option('elementor_editor_optimizer_settings');
        $this->settings = $this->get_default_settings();
        
        wp_send_json_success(['message' => 'Settings reset successfully']);
    }
    
    /**
     * AJAX: Get widget usage analytics
     */
    public function ajax_get_widget_usage_analytics() {
        check_ajax_referer('eeo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        $analytics = $this->get_widget_usage_analytics();
        wp_send_json_success($analytics);
    }
    
    /**
     * AJAX: Perform widget scan
     */
    public function ajax_perform_widget_scan() {
        check_ajax_referer('eeo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        // Enable debug mode temporarily for this scan
        $original_debug = $this->settings['debug_mode'];
        $this->settings['debug_mode'] = 'yes';
        
        $scan_results = $this->perform_full_widget_scan();
        $analytics = $this->get_widget_usage_analytics();
        
        // Restore original debug mode
        $this->settings['debug_mode'] = $original_debug;
        
        $response_data = [
            'message' => sprintf(
                'Widget scan completed successfully! Found %d widgets used across %d posts.',
                count($scan_results['widgets_found']),
                $scan_results['posts_processed']
            ),
            'analytics' => $analytics,
            'scan_details' => $scan_results
        ];
        
        wp_send_json_success($response_data);
    }
    
    /**
     * AJAX: Reset widget usage data
     */
    public function ajax_reset_widget_usage_data() {
        check_ajax_referer('eeo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        delete_option('eeo_widget_usage_data');
        delete_option('eeo_widget_usage_log');
        delete_option('eeo_last_full_scan');
        
        wp_send_json_success(['message' => 'Widget usage data reset successfully']);
    }
    
    /**
     * Admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_elementor-editor-optimizer' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'elementor-editor-optimizer-admin',
            EEO_PLUGIN_URL . 'assets/css/admin.css',
            [],
            EEO_VERSION
        );

        // Add a version query string to bust cache
        $js_url = EEO_PLUGIN_URL . 'assets/js/admin.js?v=' . time();
        wp_enqueue_script(
            'elementor-editor-optimizer-admin',
            $js_url,
            ['jquery'],
            EEO_VERSION,
            true
        );

        wp_localize_script('elementor-editor-optimizer-admin', 'eeo_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eeo_nonce'),
        ]);

        // Add a fallback warning if JS fails to load
        add_action('admin_footer', function() {
            echo '<noscript><div style="color: #fff; background: #d63638; padding: 10px; font-weight: bold;">MCX Elementor Editor Optimizer: JavaScript is required for widget analytics and optimization features.</div></noscript>';
            echo '<div id="eeo-js-warning" style="display:none;color:#fff;background:#d63638;padding:10px;font-weight:bold;">MCX Elementor Editor Optimizer: JavaScript failed to load or eeo_data is missing. Please check for plugin conflicts or browser errors.</div>';
            echo '<script>setTimeout(function(){if(typeof eeo_data==="undefined"||!window.jQuery){document.getElementById("eeo-js-warning").style.display="block";}},1000);</script>';
        });
        
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        $widgets = $this->get_all_elementor_widgets();
        $analytics = $this->get_widget_usage_analytics();
        
        // Detect addon statistics
        $addon_stats = $this->get_addon_statistics($widgets);
        ?>
        <div class="wrap">
            <h1><?php _e('MCX Elementor Editor Optimizer', 'elementor-editor-optimizer'); ?></h1>
            <p class="description" style="margin-top: 4px;">
                <?php _e('Powered by', 'elementor-editor-optimizer'); ?>
                <a href="https://miracuves.com" target="_blank" rel="noopener noreferrer">Miracuves</a>
                · <a href="https://miracuves.com" target="_blank" rel="noopener noreferrer">Miracuves.com</a>
            </p>
            
            <!-- Detected Addons Summary -->
            <?php if (!empty($addon_stats)): ?>
            <div class="postbox" style="margin: 20px 0;">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('🔌 Detected Elementor Addons & Widgets', 'elementor-editor-optimizer'); ?></h2>
                </div>
                <div class="inside">
                    <div class="addon-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                        <?php foreach ($addon_stats as $source => $stats): ?>
                        <div class="addon-card" style="background: #f9f9f9; padding: 15px; border-radius: 5px; border-left: 4px solid #0073aa;">
                            <h4 style="margin: 0 0 8px 0; color: #333;"><?php echo esc_html($source); ?></h4>
                            <p style="margin: 0; color: #666; font-size: 14px;">
                                <strong><?php echo $stats['total']; ?> widgets</strong>
                                <?php if ($stats['used'] > 0): ?>
                                <br><span style="color: #2e7d2e;">✓ <?php echo $stats['used']; ?> used</span>
                                <?php endif; ?>
                                <?php if ($stats['unused'] > 0): ?>
                                <br><span style="color: #d63638;">⚡ <?php echo $stats['unused']; ?> unused</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p style="margin-top: 15px; font-style: italic; color: #666;">
                        💡 Focus on disabling unused widgets from addons you don't actively use for maximum backend speed improvement.
                    </p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Widget Usage Analytics Section -->
            <div class="postbox" style="margin: 20px 0;">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('Widget Usage Analytics - Backend Performance Optimization', 'elementor-editor-optimizer'); ?></h2>
                </div>
                <div class="inside">
                    <div class="widget-analytics-dashboard">
                        <div class="analytics-summary" style="display: flex; gap: 20px; margin-bottom: 20px;">
                            <div class="analytics-card" style="background: #e8f5e8; padding: 15px; border-radius: 5px; flex: 1;">
                                <h3 style="margin: 0; color: #2e7d2e;"><?php _e('Used Widgets', 'elementor-editor-optimizer'); ?></h3>
                                <p style="font-size: 24px; margin: 5px 0; font-weight: bold;"><?php echo count($analytics['used_widgets']); ?></p>
                                <small><?php _e('Widgets actively used on your site', 'elementor-editor-optimizer'); ?></small>
                            </div>
                            <div class="analytics-card" style="background: #ffe8e8; padding: 15px; border-radius: 5px; flex: 1;">
                                <h3 style="margin: 0; color: #d63638;"><?php _e('Unused Widgets', 'elementor-editor-optimizer'); ?></h3>
                                <p style="font-size: 24px; margin: 5px 0; font-weight: bold;"><?php echo count($analytics['unused_widgets']); ?></p>
                                <small><?php _e('Widgets that can be disabled safely', 'elementor-editor-optimizer'); ?></small>
                            </div>
                            <div class="analytics-card" style="background: #e8f4fd; padding: 15px; border-radius: 5px; flex: 1;">
                                <h3 style="margin: 0; color: #0073aa;"><?php _e('Potential Speed Gain', 'elementor-editor-optimizer'); ?></h3>
                                <p style="font-size: 24px; margin: 5px 0; font-weight: bold;">
                                    <?php echo round((count($analytics['unused_widgets']) / $analytics['total_widgets']) * 100); ?>%
                                </p>
                                <small><?php _e('Estimated backend performance improvement', 'elementor-editor-optimizer'); ?></small>
                            </div>
                        </div>
                        
                        <div class="analytics-actions" style="margin-bottom: 20px;">
                            <button type="button" class="button button-primary" id="scan-widget-usage">
                                <?php _e('Scan Widget Usage Now', 'elementor-editor-optimizer'); ?>
                            </button>
                            <button type="button" class="button" id="auto-disable-unused">
                                <?php _e('Auto-Disable Unused Widgets', 'elementor-editor-optimizer'); ?>
                            </button>
                            <button type="button" class="button" id="reset-usage-data">
                                <?php _e('Reset Usage Data', 'elementor-editor-optimizer'); ?>
                            </button>
                            <?php if (!empty($analytics['last_scan'])): ?>
                            <p style="margin: 10px 0; font-style: italic;">
                                <?php printf(
                                    __('Last scan: %s (%d posts analyzed)', 'elementor-editor-optimizer'),
                                    date('M j, Y g:i A', $analytics['last_scan']['timestamp']),
                                    $analytics['last_scan']['total_posts_scanned']
                                ); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Unused Widgets Quick Disable -->
                        <?php if (!empty($analytics['unused_widgets'])): ?>
                        <div class="unused-widgets-section" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px; margin-bottom: 20px;">
                            <div style="margin-bottom: 12px;">
                                <input type="text" id="eeo-unused-widget-search" placeholder="Search unused widgets..." style="width: 100%; max-width: 400px; padding: 7px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 15px;" autocomplete="off">
                            </div>
                            <h3 style="margin-top: 0; color: #d63638;">
                                <?php _e('🚀 Quick Disable Unused Widgets (Boost Backend Speed)', 'elementor-editor-optimizer'); ?>
                            </h3>
                            <p><?php _e('These widgets were not found in any of your pages/posts. Disabling them will significantly speed up the Elementor editor backend:', 'elementor-editor-optimizer'); ?></p>
                            
                            <?php 
                            // Group widgets by source for better organization
                            $widgets_by_source = [];
                            foreach ($analytics['unused_widgets'] as $widget_id => $widget_data) {
                                $source = isset($widget_data['source']) ? $widget_data['source'] : 'Unknown';
                                if (!isset($widgets_by_source[$source])) {
                                    $widgets_by_source[$source] = [];
                                }
                                $widgets_by_source[$source][$widget_id] = $widget_data;
                            }
                            ?>
                            
                            <div class="unused-widgets-by-source" style="max-height: 400px; overflow-y: auto;">
                                <?php foreach ($widgets_by_source as $source => $source_widgets): ?>
                                <div class="widget-source-group" style="margin-bottom: 15px; border: 1px solid #e1e1e1; border-radius: 4px; background: white;">
                                    <div class="source-header" style="background: #f7f7f7; padding: 10px; border-bottom: 1px solid #e1e1e1; font-weight: bold; color: #333;">
                                        <?php echo esc_html($source); ?> 
                                        <span style="color: #666; font-weight: normal; font-size: 12px;">(<?php echo count($source_widgets); ?> widgets)</span>
                                    </div>
                                    <div class="source-widgets" style="padding: 10px; display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 8px;">
                                        <?php foreach ($source_widgets as $widget_id => $widget_data): ?>
                                        <label class="eeo-widget-label" 
                                            data-widget-name="<?php echo esc_attr(strtolower($widget_data['name'])); ?>" 
                                            data-widget-id="<?php echo esc_attr(strtolower($widget_id)); ?>"
                                            style="display: flex; align-items: center; padding: 5px; background: #f9f9f9; border-radius: 3px; border: 1px solid #e8e8e8; cursor: pointer;">
                                            <input type="checkbox" class="unused-widget-checkbox" value="<?php echo esc_attr($widget_id); ?>" style="margin-right: 8px;">
                                            <span style="flex: 1;"><?php echo esc_html($widget_data['name']); ?></span>
                                            <small style="color: #888; font-size: 11px;"><?php echo esc_html($widget_id); ?></small>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e1e1e1; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                                <button type="button" class="button button-secondary" id="select-all-unused"><?php _e('Select All', 'elementor-editor-optimizer'); ?></button>
                                <button type="button" class="button button-secondary" id="select-none-unused"><?php _e('Select None', 'elementor-editor-optimizer'); ?></button>
                                <button type="button" class="button button-secondary" id="reset-unused-selection"><?php _e('Reset Selection', 'elementor-editor-optimizer'); ?></button>
                                <button type="button" class="button button-primary" id="disable-selected-unused" style="margin-left: 10px;">
                                    <?php _e('Disable Selected Widgets', 'elementor-editor-optimizer'); ?>
                                </button>
                                <p style="margin: 10px 0; font-size: 12px; color: #666; flex: 1 1 100%;">
                                    💡 <?php _e('Tip: Focus on disabling widgets from addons you don\'t actively use for maximum speed improvement.', 'elementor-editor-optimizer'); ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('elementor_editor_optimizer_group');
                do_settings_sections('elementor_editor_optimizer_group');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Disable Widgets', 'elementor-editor-optimizer'); ?></th>
                        <td>
                            <?php if (empty($widgets)) : ?>
                                <p><em><?php _e('Elementor not detected or no widgets available.', 'elementor-editor-optimizer'); ?></em></p>
                            <?php else : ?>
                                <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; background: #f9f9f9;">
                                    <?php foreach ($widgets as $widget_id => $widget_data) : ?>
                                        <?php
                                        $is_used = isset($analytics['used_widgets'][$widget_id]);
                                        $usage_info = $is_used ? $analytics['used_widgets'][$widget_id] : null;
                                        $is_essential = $this->is_core_widget($widget_id);
                                        $source = isset($widget_data['source']) ? $widget_data['source'] : 'Unknown';
                                        ?>
                                        <label style="display: block; margin-bottom: 8px; padding: 8px; background: <?php echo $is_used ? '#e8f5e8' : '#ffe8e8'; ?>; border-radius: 3px;">
                                            <input type="checkbox" 
                                                   name="elementor_editor_optimizer_settings[disable_widgets][]" 
                                                   value="<?php echo esc_attr($widget_id); ?>"
                                                   <?php checked(in_array($widget_id, $this->settings['disable_widgets'])); ?>
                                                   <?php disabled($is_essential); ?>>
                                            
                                            <strong><?php echo esc_html($widget_data['name']); ?></strong>
                                            <small style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; margin-left: 8px; font-size: 11px;">
                                                <?php echo esc_html($source); ?>
                                            </small>
                                            
                                            <?php if ($is_essential) : ?>
                                                <span style="color: #d63638; font-weight: bold;">(Essential - Cannot be disabled)</span>
                                            <?php elseif ($is_used) : ?>
                                                <span style="color: #2e7d2e; font-weight: bold;">✓ Used</span>
                                                <?php if ($usage_info): ?>
                                                <small style="color: #666;">
                                                    (Used <?php echo $usage_info['count']; ?> times, 
                                                    last: <?php echo date('M j', $usage_info['last_used']); ?>)
                                                </small>
                                                <?php endif; ?>
                                            <?php else : ?>
                                                <span style="color: #d63638; font-weight: bold;">⚡ Not Used - Safe to Disable</span>
                                            <?php endif; ?>
                                            
                                            <small style="display: block; color: #666; margin-top: 3px;">
                                                ID: <?php echo esc_html($widget_id); ?>
                                            </small>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                
                                <p class="description" style="margin-top: 10px;">
                                    <strong><?php _e('Green = Used widgets', 'elementor-editor-optimizer'); ?></strong> | 
                                    <strong style="color: #d63638;"><?php _e('Red = Unused widgets (safe to disable)', 'elementor-editor-optimizer'); ?></strong>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Editor Firewall', 'elementor-editor-optimizer'); ?></th>
                        <td>
                            <?php
                            $active_plugins = (array) get_option('active_plugins', []);
                            $firewall_never_disable = ['elementor/elementor.php', 'elementor-pro/elementor-pro.php', EEO_PLUGIN_BASENAME];
                            $can_disable = array_diff($active_plugins, $firewall_never_disable);
                            $firewall_plugins = isset($this->settings['firewall_plugins']) ? (array) $this->settings['firewall_plugins'] : [];
                            ?>
                            <p class="description" style="margin-bottom:10px;"><?php _e('When you launch the editor with "Editor Firewall" mode, these plugins are disabled for that session only. Essential plugins (Elementor, Elementor Pro, this optimizer) cannot be disabled.', 'elementor-editor-optimizer'); ?></p>
                            <p class="description" style="margin-bottom:10px;"><strong><?php _e('Firewall requires the mu-plugin:', 'elementor-editor-optimizer'); ?></strong> <?php _e('Copy', 'elementor-editor-optimizer'); ?> <code>eeo-firewall.php</code> <?php _e('from this plugin folder to', 'elementor-editor-optimizer'); ?> <code>wp-content/mu-plugins/</code> <?php _e('(create the folder if needed).', 'elementor-editor-optimizer'); ?></p>
                            <?php if (empty($can_disable)) : ?>
                                <p><em><?php _e('No other plugins to optionally disable.', 'elementor-editor-optimizer'); ?></em></p>
                            <?php else : ?>
                                <div style="max-height:200px;overflow-y:auto;border:1px solid #ddd;padding:10px;background:#f9f9f9;">
                                    <?php foreach ($can_disable as $plugin_basename) :
                                        if (!function_exists('get_plugin_data')) {
                                            require_once ABSPATH . 'wp-admin/includes/plugin.php';
                                        }
                                        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_basename, false, false);
                                        $name = isset($plugin_data['Name']) ? $plugin_data['Name'] : $plugin_basename;
                                    ?>
                                    <label style="display:block;margin-bottom:6px;">
                                        <input type="checkbox" name="elementor_editor_optimizer_settings[firewall_plugins][]" value="<?php echo esc_attr($plugin_basename); ?>" <?php checked(in_array($plugin_basename, $firewall_plugins)); ?>>
                                        <?php echo esc_html($name); ?>
                                        <small style="color:#666;">(<?php echo esc_html($plugin_basename); ?>)</small>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Editor Memory Limit', 'elementor-editor-optimizer'); ?></th>
                        <td>
                            <input type="text" 
                                   name="elementor_editor_optimizer_settings[editor_memory_limit]" 
                                   value="<?php echo esc_attr($this->settings['editor_memory_limit']); ?>"
                                   placeholder="512M">
                            <p class="description"><?php _e('Set memory limit for Elementor editor (e.g., 512M, 1G)', 'elementor-editor-optimizer'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Optimize Fonts', 'elementor-editor-optimizer'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="elementor_editor_optimizer_settings[disable_unused_fonts]" 
                                       value="yes" 
                                       <?php checked($this->settings['disable_unused_fonts'], 'yes'); ?>>
                                <?php _e('Optimize Google Fonts loading', 'elementor-editor-optimizer'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Optimize Assets', 'elementor-editor-optimizer'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="elementor_editor_optimizer_settings[optimize_css_js]" 
                                       value="yes" 
                                       <?php checked($this->settings['optimize_css_js'], 'yes'); ?>>
                                <?php _e('Remove unnecessary scripts and styles', 'elementor-editor-optimizer'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('WordPress Optimizations', 'elementor-editor-optimizer'); ?></th>
                        <td>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" 
                                       name="elementor_editor_optimizer_settings[disable_emojis]" 
                                       value="yes" 
                                       <?php checked($this->settings['disable_emojis'], 'yes'); ?>>
                                <?php _e('Disable emoji scripts', 'elementor-editor-optimizer'); ?>
                            </label>
                            
                            <label>
                                <input type="checkbox" 
                                       name="elementor_editor_optimizer_settings[disable_jquery_migrate]" 
                                       value="yes" 
                                       <?php checked($this->settings['disable_jquery_migrate'], 'yes'); ?>>
                                <?php _e('Remove jQuery Migrate', 'elementor-editor-optimizer'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Debug Mode', 'elementor-editor-optimizer'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="elementor_editor_optimizer_settings[debug_mode]" 
                                       value="yes" 
                                       <?php checked($this->settings['debug_mode'], 'yes'); ?>>
                                <?php _e('Enable debug logging', 'elementor-editor-optimizer'); ?>
                            </label>
                            <p class="description"><?php _e('Logs will be written to the WordPress error log.', 'elementor-editor-optimizer'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-left: 4px solid #00a0d2;">
                <h3><?php _e('Performance Information', 'elementor-editor-optimizer'); ?></h3>
                <p><strong><?php _e('Expected Speed Improvements:', 'elementor-editor-optimizer'); ?></strong></p>
                <ul>
                    <li><?php _e('Editor Loading: 30-50% faster', 'elementor-editor-optimizer'); ?></li>
                    <li><?php _e('Frontend Speed: 15-25% improvement', 'elementor-editor-optimizer'); ?></li>
                    <li><?php _e('Memory Usage: 20-40% reduction', 'elementor-editor-optimizer'); ?></li>
                </ul>
                
                <p><strong><?php _e('Current Status:', 'elementor-editor-optimizer'); ?></strong></p>
                <ul>
                    <li><?php _e('Total Widgets Available:', 'elementor-editor-optimizer'); ?> <?php echo count($widgets); ?></li>
                    <li><?php _e('Widgets to Disable:', 'elementor-editor-optimizer'); ?> <?php echo count($this->settings['disable_widgets']); ?></li>
                    <li><?php _e('PHP Version:', 'elementor-editor-optimizer'); ?> <?php echo PHP_VERSION; ?></li>
                    <li><?php _e('WordPress Version:', 'elementor-editor-optimizer'); ?> <?php echo get_bloginfo('version'); ?></li>
                    <?php if (defined('ELEMENTOR_VERSION')) : ?>
                        <li><?php _e('Elementor Version:', 'elementor-editor-optimizer'); ?> <?php echo ELEMENTOR_VERSION; ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            <p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #ddd; color: #666; font-size: 13px;">
                <?php _e('Powered by', 'elementor-editor-optimizer'); ?>
                <a href="https://miracuves.com" target="_blank" rel="noopener noreferrer">Miracuves</a>
                · <a href="https://miracuves.com" target="_blank" rel="noopener noreferrer">Miracuves.com</a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Admin notices
     */
    public function php_version_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('MCX Elementor Editor Optimizer requires PHP 7.4 or higher.', 'elementor-editor-optimizer'); ?></p>
        </div>
        <?php
    }
    
    public function wp_version_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('MCX Elementor Editor Optimizer requires WordPress 5.0 or higher.', 'elementor-editor-optimizer'); ?></p>
        </div>
        <?php
    }
    
    public function elementor_not_found_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('MCX Elementor Editor Optimizer requires Elementor to be installed and activated.', 'elementor-editor-optimizer'); ?></p>
        </div>
        <?php
    }
    
    public function elementor_version_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('MCX Elementor Editor Optimizer requires Elementor 3.0.0 or higher.', 'elementor-editor-optimizer'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Log debug information
     */
    private function log_debug($message) {
        if ('yes' === $this->settings['debug_mode']) {
            error_log('MCX Elementor Editor Optimizer: ' . $message);
        }
    }
}

// Initialize the plugin
Elementor_Editor_Optimizer::get_instance();