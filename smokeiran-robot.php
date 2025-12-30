<?php
/**
 * Plugin Name: SmokeIran Robot 🤖
 * Plugin URI: https://smokeiran.com
 * Description: ربات هوشمند تولید محتوای سئو برای محصولات ویپ - با استفاده از هوش مصنوعی
 * Version: 1.0.1
 * Author: SmokeIran Team
 * Author URI: https://smokeiran.com
 * Text Domain: smokeiran-robot
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 8.1
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// Hard requirement checks for PHP version and core compatibility
if (version_compare(PHP_VERSION, '8.1', '<')) {
    // Deactivate the plugin immediately with a clear admin message
    if (is_admin()) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('SmokeIran Robot requires PHP 8.1 or higher. Current version: ' . PHP_VERSION);
    }
    return;
}

// Plugin Constants
define('SIR_VERSION', '1.0.1');
define('SIR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SIR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SIR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Ensure prompts class is available for activation routines
require_once SIR_PLUGIN_DIR . 'includes/class-prompts.php';

/**
 * Migrate invalid model names to correct format
 */
function sir_migrate_model_names() {
    $current_model = get_option('sir_claude_model', '');
    
    // Invalid models that need migration
    $invalid_models = [
        'blackboxai', 'blackboxai-pro', 
        'claude-sonnet-4-20250514', 'claude-3-5-sonnet-20241022',
        'gpt-4o', 'gpt-4-turbo', 'gpt-4', 
        'claude-3-opus', 'claude-3-sonnet', 'claude-3-haiku',
        'gemini-1.5-pro', 'gpt-3.5-turbo', 'gpt-4o-mini',
        'blackboxai/anthropic/claude-3.5-sonnet',
        'blackboxai/anthropic/claude-3-sonnet',
        'blackboxai/openai/gpt-4o',
        'blackboxai/openai/gpt-4-turbo',
        'blackboxai/google/gemini-1.5-pro',
        'blackboxai/openai/gpt-3.5-turbo'
    ];
    
    // Model migration map - map to valid documented models
    $migration_map = [
        'claude-sonnet-4-20250514' => 'blackboxai/anthropic/claude-3-opus',
        'claude-3-5-sonnet-20241022' => 'blackboxai/anthropic/claude-3-opus',
        'blackboxai/anthropic/claude-3.5-sonnet' => 'blackboxai/anthropic/claude-3-opus',
        'blackboxai/anthropic/claude-3-sonnet' => 'blackboxai/anthropic/claude-3-opus',
        'claude-3-opus' => 'blackboxai/anthropic/claude-3-opus',
        'claude-3-sonnet' => 'blackboxai/anthropic/claude-3-opus',
        'claude-3-haiku' => 'blackboxai/anthropic/claude-3-haiku',
        'gpt-4o' => 'blackboxai/amazon/nova-pro-v1',
        'blackboxai/openai/gpt-4o' => 'blackboxai/amazon/nova-pro-v1',
        'gpt-4-turbo' => 'blackboxai/amazon/nova-pro-v1',
        'blackboxai/openai/gpt-4-turbo' => 'blackboxai/amazon/nova-pro-v1',
        'gpt-4' => 'blackboxai/amazon/nova-pro-v1',
        'gemini-1.5-pro' => 'blackboxai/google/gemini-2.0-flash-exp:free',
        'blackboxai/google/gemini-1.5-pro' => 'blackboxai/google/gemini-2.0-flash-exp:free',
        'gpt-3.5-turbo' => 'blackboxai/amazon/nova-lite-v1',
        'blackboxai/openai/gpt-3.5-turbo' => 'blackboxai/amazon/nova-lite-v1',
        'gpt-4o-mini' => 'blackboxai/amazon/nova-lite-v1',
        'blackboxai' => 'blackboxai/x-ai/grok-code-fast-1:free',
        'blackboxai-pro' => 'blackboxai/anthropic/claude-3-opus',
    ];
    
    // Check if migration is needed
    if (in_array($current_model, $invalid_models)) {
        $new_model = isset($migration_map[$current_model]) 
            ? $migration_map[$current_model] 
            : 'blackboxai/x-ai/grok-code-fast-1:free';
        
        update_option('sir_claude_model', $new_model);
    }
}

/**
 * Main Plugin Class
 */
final class SmokeIran_Robot {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        // Core classes
        require_once SIR_PLUGIN_DIR . 'includes/class-prompts.php';
        require_once SIR_PLUGIN_DIR . 'includes/class-blackbox-api.php';
        require_once SIR_PLUGIN_DIR . 'includes/class-tavily-api.php';
        require_once SIR_PLUGIN_DIR . 'includes/class-content-parser.php';
        require_once SIR_PLUGIN_DIR . 'includes/class-custom-fields.php';
        require_once SIR_PLUGIN_DIR . 'includes/class-product-handler.php';
        require_once SIR_PLUGIN_DIR . 'includes/class-post-handler.php';
        
        // Admin classes
        if (is_admin()) {
            require_once SIR_PLUGIN_DIR . 'admin/class-admin-pages.php';
            require_once SIR_PLUGIN_DIR . 'admin/class-ajax-handlers.php';
        }
    }
    
    private function init_hooks() {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('plugins_loaded', 'sir_migrate_model_names', 5);
        add_action('admin_init', [$this, 'check_requirements']);
        
        if (is_admin()) {
            new SIR_Admin_Pages();
            new SIR_Ajax_Handlers();
        }
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('smokeiran-robot', false, dirname(SIR_PLUGIN_BASENAME) . '/languages');
    }
    
    public function check_requirements() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning"><p>';
                echo '<strong>SmokeIran Robot:</strong> ووکامرس نصب نیست. برخی قابلیت‌ها غیرفعال خواهند بود.';
                echo '</p></div>';
            });
        }
    }
}

// Activation Hook
register_activation_hook(__FILE__, function() {
    // Validate minimum WordPress and PHP versions before proceeding
    global $wp_version;
    if (version_compare(PHP_VERSION, '8.1', '<')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('SmokeIran Robot requires PHP 8.1 or higher.');
    }
    if (version_compare($wp_version, '5.8', '<')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('SmokeIran Robot requires WordPress 5.8 or higher.');
    }

    // Default options
    $defaults = [
        'sir_blackbox_api_key' => '',
        'sir_tavily_api_key' => '',
        'sir_claude_model' => 'blackboxai/x-ai/grok-code-fast-1:free',
        'sir_auto_publish' => 'draft',
        'sir_enable_logging' => 'yes',
    ];
    
    foreach ($defaults as $key => $value) {
        if (get_option($key) === false) {
            add_option($key, $value);
        }
    }
    
    // Run model migration for existing installations
    sir_migrate_model_names();
    
    // Initialize default prompts (class required above for activation compatibility)
    if (class_exists('SIR_Prompts')) {
        SIR_Prompts::init_default_prompts();
    }
    
    // Create logs table
    global $wpdb;
    $table_name = $wpdb->prefix . 'sir_logs';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        action_type varchar(50) NOT NULL,
        product_name varchar(255) NOT NULL,
        status varchar(20) NOT NULL,
        message text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});

// Deactivation Hook
register_deactivation_hook(__FILE__, function() {
    // Clean up scheduled tasks if any
    wp_clear_scheduled_hook('sir_scheduled_generation');
});

// Initialize Plugin
add_action('plugins_loaded', function() {
    SmokeIran_Robot::get_instance();
}, 10);
