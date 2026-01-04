<?php
/**
 * AJAX Handlers for SmokeIran Robot
 */

if (!defined('ABSPATH')) exit;

class SIR_Ajax_Handlers {
    
    public function __construct() {
        // Product generation
        add_action('wp_ajax_sir_generate_product', [$this, 'generate_product']);
        
        // Post generation
        add_action('wp_ajax_sir_generate_post', [$this, 'generate_post']);
        
        // Update content
        add_action('wp_ajax_sir_update_content', [$this, 'update_content']);
        
        // Load existing content
        add_action('wp_ajax_sir_load_content', [$this, 'load_content']);
        
        // Settings
        add_action('wp_ajax_sir_save_settings', [$this, 'save_settings']);
        add_action('wp_ajax_sir_save_prompts', [$this, 'save_prompts']);
        add_action('wp_ajax_sir_reset_prompt', [$this, 'reset_prompt']);
        
        // API tests
        add_action('wp_ajax_sir_test_api', [$this, 'test_api']);
        
        // Check API status
        add_action('wp_ajax_sir_check_api_status', [$this, 'check_api_status']);
        
        // Logs
        add_action('wp_ajax_sir_clear_logs', [$this, 'clear_logs']);
        add_action('wp_ajax_sir_export_logs', [$this, 'export_logs']);
        
        // Queue operations
        add_action('wp_ajax_sir_add_to_queue', [$this, 'add_to_queue']);
        add_action('wp_ajax_sir_bulk_add_queue', [$this, 'bulk_add_queue']);
        add_action('wp_ajax_sir_process_queue', [$this, 'process_queue']);
        add_action('wp_ajax_sir_get_queue_status', [$this, 'get_queue_status']);
        add_action('wp_ajax_sir_delete_queue_item', [$this, 'delete_queue_item']);
        add_action('wp_ajax_sir_retry_queue_item', [$this, 'retry_queue_item']);
        add_action('wp_ajax_sir_clear_completed', [$this, 'clear_completed']);
        add_action('wp_ajax_sir_import_csv', [$this, 'import_csv']);
    }
    
    /**
     * Generate new product
     */
    public function generate_product() {
        check_ajax_referer('sir_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $product_name = sanitize_text_field($_POST['product_name'] ?? '');
        $keywords = sanitize_textarea_field($_POST['keywords'] ?? '');
        $research_method = sanitize_text_field($_POST['research_method'] ?? 'auto');
        $manual_research = sanitize_textarea_field($_POST['manual_research'] ?? '');
        $publish_status = sanitize_text_field($_POST['publish_status'] ?? 'draft');
        
        if (empty($product_name)) {
            wp_send_json_error(['message' => 'نام محصول الزامی است']);
        }
        
        try {
            $research_data = '';
            
            // Step 1: Research
            if ($research_method === 'auto') {
                $tavily = new SIR_Tavily_API();
                $research_data = $tavily->research_product($product_name, $keywords);
            } else {
                $research_data = $manual_research;
            }
            
            if (empty($research_data)) {
                wp_send_json_error(['message' => 'داده تحقیق در دسترس نیست']);
            }
            
            // Step 2: Generate content
            $blackbox = new SIR_Blackbox_API();
            $content = $blackbox->generate_product_content($research_data, $product_name, $keywords);
            
            // Step 3: Create product
            $handler = new SIR_Product_Handler();
            $result = $handler->create_product($content, $publish_status);
            
            wp_send_json_success([
                'message' => '✅ محصول با موفقیت ایجاد شد!',
                'product_id' => $result['product_id'],
                'title' => $result['title'],
                'edit_link' => $result['edit_link'],
                'view_link' => $result['view_link'],
                'faq_count' => $result['faq_count'],
                'custom_fields_count' => $result['custom_fields_count']
            ]);
            
        } catch (Exception $e) {
            $this->log_error('create_product', $product_name, $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Generate new post
     */
    public function generate_post() {
        check_ajax_referer('sir_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $topic = sanitize_text_field($_POST['topic'] ?? '');
        $keywords = sanitize_textarea_field($_POST['keywords'] ?? '');
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'guide');
        $research_method = sanitize_text_field($_POST['research_method'] ?? 'auto');
        $manual_research = sanitize_textarea_field($_POST['manual_research'] ?? '');
        $publish_status = sanitize_text_field($_POST['publish_status'] ?? 'draft');
        
        if (empty($topic)) {
            wp_send_json_error(['message' => 'موضوع پست الزامی است']);
        }
        
        try {
            $research_data = '';
            
            // Step 1: Research
            if ($research_method === 'auto') {
                $tavily = new SIR_Tavily_API();
                $research_data = $tavily->research_topic($topic, $keywords);
            } else {
                $research_data = $manual_research;
            }
            
            // Step 2: Generate content
            $blackbox = new SIR_Blackbox_API();
            $content = $blackbox->generate_post_content($research_data, $topic, $keywords);
            
            // Step 3: Create post
            $handler = new SIR_Post_Handler();
            $result = $handler->create_post($content, $publish_status);
            
            wp_send_json_success([
                'message' => '✅ پست با موفقیت ایجاد شد!',
                'post_id' => $result['post_id'],
                'title' => $result['title'],
                'edit_link' => $result['edit_link'],
                'view_link' => $result['view_link']
            ]);
            
        } catch (Exception $e) {
            $this->log_error('create_post', $topic, $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Update existing content
     */
    public function update_content() {
        check_ajax_referer('sir_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $update_type = sanitize_text_field($_POST['update_type'] ?? 'product');
        $item_id = intval($_POST['item_id'] ?? 0);
        $instructions = sanitize_textarea_field($_POST['instructions'] ?? '');
        $refresh_research = sanitize_text_field($_POST['refresh_research'] ?? 'no');
        
        if (!$item_id) {
            wp_send_json_error(['message' => 'شناسه مورد الزامی است']);
        }
        
        $item_name = '';
        
        try {
            $current_content = '';
            
            // Get current content
            if ($update_type === 'product') {
                $handler = new SIR_Product_Handler();
                $data = $handler->get_product_content($item_id);
                $current_content = $data['raw_content'] ?: $data['description'];
                $item_name = $data['title'];
            } else {
                $handler = new SIR_Post_Handler();
                $data = $handler->get_post_content($item_id);
                $current_content = $data['raw_content'] ?: $data['content'];
                $item_name = $data['title'];
            }
            
            // Research if requested
            $research_data = '';
            if ($refresh_research === 'yes') {
                $tavily = new SIR_Tavily_API();
                $research_data = $tavily->research_product($item_name);
            }
            
            // Generate updated content
            $blackbox = new SIR_Blackbox_API();
            $new_content = $blackbox->update_content($current_content, $research_data, $instructions);
            
            // Update item
            if ($update_type === 'product') {
                $product_handler = new SIR_Product_Handler();
                $result = $product_handler->update_product($item_id, $new_content);
            } else {
                $post_handler = new SIR_Post_Handler();
                $result = $post_handler->update_post($item_id, $new_content);
            }
            
            wp_send_json_success([
                'message' => '✅ محتوا با موفقیت به‌روزرسانی شد!',
                'item_id' => $result['product_id'] ?? $result['post_id'],
                'edit_link' => $result['edit_link']
            ]);
            
        } catch (Exception $e) {
            $this->log_error('update_' . $update_type, $item_name ?? 'Unknown', $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Load existing content
     */
    public function load_content() {
        check_ajax_referer('sir_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $type = sanitize_text_field($_POST['type'] ?? 'product');
        $item_id = intval($_POST['item_id'] ?? 0);
        
        try {
            if ($type === 'product') {
                $handler = new SIR_Product_Handler();
                $data = $handler->get_product_content($item_id);
            } else {
                $handler = new SIR_Post_Handler();
                $data = $handler->get_post_content($item_id);
            }
            
            wp_send_json_success($data);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Save settings
     */
    public function save_settings() {
        check_ajax_referer('sir_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        // Save API keys
        if (isset($_POST['blackbox_api_key'])) {
            update_option('sir_blackbox_api_key', sanitize_text_field($_POST['blackbox_api_key']));
        }
        if (isset($_POST['tavily_api_key'])) {
            update_option('sir_tavily_api_key', sanitize_text_field($_POST['tavily_api_key']));
        }
        
        // Save model settings
        if (isset($_POST['claude_model'])) {
            update_option('sir_claude_model', sanitize_text_field($_POST['claude_model']));
        }
        
        // Save content settings
        if (isset($_POST['auto_publish'])) {
            update_option('sir_auto_publish', sanitize_text_field($_POST['auto_publish']));
        }
        
        $enable_logging = isset($_POST['enable_logging']) ? 'yes' : 'no';
        update_option('sir_enable_logging', $enable_logging);
        
        // Save design settings
        if (isset($_POST['primary_color'])) {
            $color = sanitize_text_field($_POST['primary_color']);
            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                update_option('sir_primary_color', $color);
            }
        }
        
        $use_theme_color = isset($_POST['use_theme_color']) ? 'yes' : 'no';
        update_option('sir_use_theme_color', $use_theme_color);
        
        // Save field mappings
        if (isset($_POST['field_mapping']) && is_array($_POST['field_mapping'])) {
            $mappings = [];
            foreach ($_POST['field_mapping'] as $key => $field) {
                $mappings[sanitize_key($key)] = [
                    'meta_key' => sanitize_text_field($field['meta_key']),
                    'enabled' => isset($field['enabled']),
                    'label' => SIR_Custom_Fields::get_product_fields_mapping()[$key]['label'] ?? $key
                ];
            }
            update_option('sir_field_mappings', $mappings);
        }
        
        wp_send_json_success(['message' => '✅ تنظیمات ذخیره شد']);
    }
    
    /**
     * Save prompts
     */
    public function save_prompts() {
        check_ajax_referer('sir_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $prompts = ['research', 'content', 'post', 'update'];
        
        foreach ($prompts as $prompt_type) {
            $key = 'prompt_' . $prompt_type;
            if (isset($_POST[$key])) {
                update_option('sir_' . $prompt_type . '_prompt', wp_kses_post($_POST[$key]));
            }
        }
        
        wp_send_json_success(['message' => '✅ پرامپت‌ها ذخیره شدند']);
    }
    
    /**
     * Reset prompt to default
     */
    public function reset_prompt() {
        check_ajax_referer('sir_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $prompt_type = sanitize_text_field($_POST['prompt_type'] ?? '');
        
        $defaults = [
            'research' => SIR_Prompts::get_default_research_prompt(),
            'content' => SIR_Prompts::get_default_content_prompt(),
            'post' => SIR_Prompts::get_default_post_prompt(),
            'update' => SIR_Prompts::get_default_update_prompt(),
        ];
        
        if (isset($defaults[$prompt_type])) {
            update_option('sir_' . $prompt_type . '_prompt', $defaults[$prompt_type]);
            wp_send_json_success([
                'message' => '✅ پرامپت به پیش‌فرض بازگردانی شد',
                'content' => $defaults[$prompt_type]
            ]);
        }
        
        wp_send_json_error(['message' => 'نوع پرامپت نامعتبر']);
    }
    
    /**
     * Test API connection
     */
    public function test_api() {
        check_ajax_referer('sir_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $api_type = sanitize_text_field($_POST['api_type'] ?? '');
        
        if ($api_type === 'blackbox') {
            $api = new SIR_Blackbox_API();
            $result = $api->test_connection();
        } elseif ($api_type === 'tavily') {
            $api = new SIR_Tavily_API();
            $result = $api->test_connection();
        } else {
            $result = ['success' => false, 'message' => 'نوع API نامعتبر'];
        }
        
        wp_send_json($result);
    }
    
    /**
     * Check all API status
     */
    public function check_api_status() {
        check_ajax_referer('sir_ajax_nonce', 'nonce');
        
        $blackbox = new SIR_Blackbox_API();
        $tavily = new SIR_Tavily_API();
        
        wp_send_json_success([
            'blackbox' => $blackbox->test_connection(),
            'tavily' => $tavily->test_connection()
        ]);
    }
    
    /**
     * Clear all logs
     */
    public function clear_logs() {
        check_ajax_referer('sir_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sir_logs';
        $wpdb->query("TRUNCATE TABLE $table_name");
        
        wp_send_json_success(['message' => '✅ گزارش‌ها پاک شدند']);
    }
    
    /**
     * Export logs as CSV
     */
    public function export_logs() {
        check_ajax_referer('sir_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sir_logs';
        $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);
        
        if (empty($logs)) {
            wp_send_json_error(['message' => 'گزارشی برای خروجی وجود ندارد']);
        }
        
        // Generate CSV content
        $csv_content = implode(',', array_keys($logs[0])) . "\n";
        foreach ($logs as $log) {
            $csv_content .= implode(',', array_map(function($val) {
                return '"' . str_replace('"', '""', $val) . '"';
            }, $log)) . "\n";
        }
        
        wp_send_json_success([
            'csv' => base64_encode($csv_content),
            'filename' => 'smokeiran-logs-' . date('Y-m-d') . '.csv'
        ]);
    }
    
    /**
     * Log error
     */
    private function log_error($action, $name, $message) {
        if (get_option('sir_enable_logging') !== 'yes') {
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sir_logs';
        
        $wpdb->insert($table_name, [
            'action_type' => $action,
            'product_name' => $name,
            'status' => 'failed',
            'message' => $message,
            'created_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Add single item to queue
     */
    public function add_to_queue() {
        check_ajax_referer('sir_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $title = sanitize_text_field($_POST['title'] ?? '');
        $keywords = sanitize_textarea_field($_POST['keywords'] ?? '');
        $item_type = sanitize_text_field($_POST['item_type'] ?? 'product');
        $priority = intval($_POST['priority'] ?? 0);
        
        if (empty($title)) {
            wp_send_json_error(['message' => 'عنوان الزامی است']);
        }
        
        try {
            $queue = new SIR_Queue_Manager();
            $id = $queue->add_item($title, $keywords, $item_type, $priority);
            
            wp_send_json_success([
                'message' => '✅ به صف اضافه شد',
                'id' => $id
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Bulk add to queue
     */
    public function bulk_add_queue() {
        check_ajax_referer('sir_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $bulk_text = $_POST['bulk_text'] ?? '';
        $bulk_type = sanitize_text_field($_POST['bulk_type'] ?? 'product');
        $bulk_priority = intval($_POST['bulk_priority'] ?? 0);
        
        if (empty($bulk_text)) {
            wp_send_json_error(['message' => 'متن ورودی خالی است']);
        }
        
        try {
            $items = SIR_Queue_Manager::parse_bulk_text($bulk_text);
            
            if (empty($items)) {
                wp_send_json_error(['message' => 'هیچ موردی برای افزودن یافت نشد']);
            }
            
            // Set bulk type and priority
            foreach ($items as &$item) {
                $item['item_type'] = $bulk_type;
                $item['priority'] = $bulk_priority;
            }
            
            $queue = new SIR_Queue_Manager();
            $added_ids = $queue->add_bulk_items($items);
            
            wp_send_json_success([
                'message' => sprintf('✅ %d مورد به صف اضافه شد', count($added_ids)),
                'count' => count($added_ids),
                'ids' => $added_ids
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Process queue
     */
    public function process_queue() {
        check_ajax_referer('sir_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $count = intval($_POST['count'] ?? 5);
        
        try {
            $queue = new SIR_Queue_Manager();
            $results = $queue->process_batch($count);
            
            $success_count = count(array_filter($results, function($r) {
                return $r['success'];
            }));
            
            $failed_count = count($results) - $success_count;
            
            wp_send_json_success([
                'message' => sprintf('✅ پردازش تکمیل شد: %d موفق، %d ناموفق', $success_count, $failed_count),
                'results' => $results,
                'success_count' => $success_count,
                'failed_count' => $failed_count
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Get queue status
     */
    public function get_queue_status() {
        check_ajax_referer('sir_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        try {
            $queue = new SIR_Queue_Manager();
            $stats = $queue->get_stats();
            $items = $queue->get_items(null, 100);
            
            wp_send_json_success([
                'stats' => $stats,
                'items' => $items
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Delete queue item
     */
    public function delete_queue_item() {
        check_ajax_referer('sir_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $item_id = intval($_POST['item_id'] ?? 0);
        
        if (!$item_id) {
            wp_send_json_error(['message' => 'شناسه مورد نامعتبر است']);
        }
        
        try {
            $queue = new SIR_Queue_Manager();
            $result = $queue->delete_item($item_id);
            
            if ($result) {
                wp_send_json_success(['message' => '✅ مورد حذف شد']);
            } else {
                wp_send_json_error(['message' => 'خطا در حذف مورد']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Retry failed queue item
     */
    public function retry_queue_item() {
        check_ajax_referer('sir_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $item_id = intval($_POST['item_id'] ?? 0);
        
        if (!$item_id) {
            wp_send_json_error(['message' => 'شناسه مورد نامعتبر است']);
        }
        
        try {
            $queue = new SIR_Queue_Manager();
            $result = $queue->retry_item($item_id);
            
            if ($result) {
                wp_send_json_success(['message' => '✅ مورد برای تلاش مجدد آماده شد']);
            } else {
                wp_send_json_error(['message' => 'خطا در تلاش مجدد']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Clear completed queue items
     */
    public function clear_completed() {
        check_ajax_referer('sir_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        try {
            $queue = new SIR_Queue_Manager();
            $count = $queue->clear_completed();
            
            wp_send_json_success([
                'message' => sprintf('✅ %d مورد تکمیل شده پاک شد', $count),
                'count' => $count
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Import CSV file
     */
    public function import_csv() {
        check_ajax_referer('sir_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        if (empty($_FILES['csv_file'])) {
            wp_send_json_error(['message' => 'فایل انتخاب نشده است']);
        }
        
        $file = $_FILES['csv_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => 'خطا در آپلود فایل']);
        }
        
        // Validate file extension
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_ext !== 'csv') {
            wp_send_json_error(['message' => 'فقط فایل‌های CSV مجاز هستند']);
        }
        
        try {
            $items = SIR_Queue_Manager::parse_csv($file['tmp_name']);
            
            if (empty($items)) {
                wp_send_json_error(['message' => 'هیچ موردی در فایل CSV یافت نشد']);
            }
            
            $queue = new SIR_Queue_Manager();
            $added_ids = $queue->add_bulk_items($items);
            
            wp_send_json_success([
                'message' => sprintf('✅ %d مورد از CSV به صف اضافه شد', count($added_ids)),
                'count' => count($added_ids),
                'ids' => $added_ids,
                'items' => $items
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
