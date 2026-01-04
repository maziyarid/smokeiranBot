<?php
/**
 * Admin Pages Handler
 */

if (!defined('ABSPATH')) exit;

class SIR_Admin_Pages {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    /**
     * Add menu pages
     */
    public function add_menu_pages() {
        // Main menu
        add_menu_page(
            'ربات اسموک‌ایران',
            '🤖 اسموک‌ایران',
            'manage_options',
            'smokeiran-robot',
            [$this, 'render_main_page'],
            'dashicons-superhero',
            30
        );
        
        // Submenus
        add_submenu_page(
            'smokeiran-robot',
            'تولید محصول جدید',
            '📦 محصول جدید',
            'manage_options',
            'smokeiran-robot',
            [$this, 'render_main_page']
        );
        
        add_submenu_page(
            'smokeiran-robot',
            'تولید پست بلاگ',
            '📝 پست جدید',
            'manage_options',
            'smokeiran-post',
            [$this, 'render_post_page']
        );
        
        add_submenu_page(
            'smokeiran-robot',
            'به‌روزرسانی محتوا',
            '🔄 به‌روزرسانی',
            'manage_options',
            'smokeiran-update',
            [$this, 'render_update_page']
        );
        
        add_submenu_page(
            'smokeiran-robot',
            'صف تولید محتوا',
            '📋 صف تولید',
            'manage_options',
            'smokeiran-queue',
            [$this, 'render_queue_page']
        );
        
        add_submenu_page(
            'smokeiran-robot',
            'مدیریت پرامپت‌ها',
            '📝 پرامپت‌ها',
            'manage_options',
            'smokeiran-prompts',
            [$this, 'render_prompts_page']
        );
        
        add_submenu_page(
            'smokeiran-robot',
            'تنظیمات',
            '⚙️ تنظیمات',
            'manage_options',
            'smokeiran-settings',
            [$this, 'render_settings_page']
        );
        
        add_submenu_page(
            'smokeiran-robot',
            'گزارش‌ها',
            '📊 گزارش‌ها',
            'manage_options',
            'smokeiran-logs',
            [$this, 'render_logs_page']
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'smokeiran') === false) {
            return;
        }
        
        wp_enqueue_style(
            'sir-admin-css',
            SIR_PLUGIN_URL . 'admin/css/admin.css',
            [],
            SIR_VERSION
        );
        
        wp_enqueue_script(
            'sir-admin-js',
            SIR_PLUGIN_URL . 'admin/js/admin.js',
            ['jquery'],
            SIR_VERSION,
            true
        );
        
        wp_localize_script('sir-admin-js', 'sir_ajax', [
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sir_ajax_nonce')
        ]);
    }
    
    /**
     * Render main page (new product)
     */
    public function render_main_page() {
        include SIR_PLUGIN_DIR . 'admin/views/main-page.php';
    }
    
    /**
     * Render post page
     */
    public function render_post_page() {
        include SIR_PLUGIN_DIR . 'admin/views/post-page.php';
    }
    
    /**
     * Render update page
     */
    public function render_update_page() {
        include SIR_PLUGIN_DIR . 'admin/views/update-page.php';
    }
    
    /**
     * Render prompts page
     */
    public function render_prompts_page() {
        include SIR_PLUGIN_DIR . 'admin/views/prompts-page.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        include SIR_PLUGIN_DIR . 'admin/views/settings-page.php';
    }
    
    /**
     * Render logs page
     */
    public function render_logs_page() {
        include SIR_PLUGIN_DIR . 'admin/views/logs-page.php';
    }
    
    /**
     * Render queue page
     */
    public function render_queue_page() {
        include SIR_PLUGIN_DIR . 'admin/views/queue-page.php';
    }
}
