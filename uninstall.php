<?php
/**
 * Uninstall script for SmokeIran Robot
 * 
 * Removes all plugin data when plugin is deleted
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove plugin options
$options_to_delete = [
    'sir_blackbox_api_key',
    'sir_tavily_api_key',
    'sir_claude_model',
    'sir_auto_publish',
    'sir_enable_logging',
    'sir_field_mappings',
    'sir_prompt_content',
    'sir_prompt_post',
    'sir_prompt_update',
];

foreach ($options_to_delete as $option) {
    delete_option($option);
}

// Drop logs table
global $wpdb;
$table_name = $wpdb->prefix . 'sir_logs';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

// Clean up post meta with sir_ prefix
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'sir_%'");

// Clear any scheduled tasks
wp_clear_scheduled_hook('sir_scheduled_generation');
