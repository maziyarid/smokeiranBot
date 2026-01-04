<?php
/**
 * Queue Manager - Handles queue operations for content generation
 */

if (!defined('ABSPATH')) exit;

class SIR_Queue_Manager {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sir_queue';
    }
    
    /**
     * Add item to queue
     */
    public function add_item($title, $keywords, $item_type = 'product', $priority = 0) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            [
                'item_type' => sanitize_text_field($item_type),
                'title' => sanitize_text_field($title),
                'keywords' => sanitize_textarea_field($keywords),
                'status' => 'pending',
                'priority' => intval($priority),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%d', '%s']
        );
        
        if ($result === false) {
            throw new Exception('خطا در افزودن به صف: ' . $wpdb->last_error);
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Add multiple items to queue (bulk)
     */
    public function add_bulk_items($items) {
        $added_ids = [];
        
        foreach ($items as $item) {
            try {
                $id = $this->add_item(
                    $item['title'] ?? '',
                    $item['keywords'] ?? '',
                    $item['item_type'] ?? 'product',
                    $item['priority'] ?? 0
                );
                $added_ids[] = $id;
            } catch (Exception $e) {
                // Log error but continue with other items
                $this->log_error('bulk_add', $item['title'] ?? 'Unknown', $e->getMessage());
            }
        }
        
        return $added_ids;
    }
    
    /**
     * Get queue items
     */
    public function get_items($status = null, $limit = 50, $offset = 0) {
        global $wpdb;
        
        $where = '';
        if ($status) {
            $where = $wpdb->prepare(' WHERE status = %s', $status);
        }
        
        $sql = "SELECT * FROM {$this->table_name}" . 
               $where . 
               " ORDER BY priority DESC, created_at ASC" . 
               " LIMIT " . intval($limit) . 
               " OFFSET " . intval($offset);
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Get single item by ID
     */
    public function get_item($id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id),
            ARRAY_A
        );
    }
    
    /**
     * Update item status
     */
    public function update_status($id, $status, $result_id = null, $error_message = null) {
        global $wpdb;
        
        $data = [
            'status' => sanitize_text_field($status)
        ];
        $formats = ['%s'];
        
        if ($result_id) {
            $data['result_id'] = intval($result_id);
            $formats[] = '%d';
        }
        
        if ($error_message) {
            $data['error_message'] = sanitize_text_field($error_message);
            $formats[] = '%s';
        }
        
        if (in_array($status, ['completed', 'failed'])) {
            $data['processed_at'] = current_time('mysql');
            $formats[] = '%s';
        }
        
        return $wpdb->update(
            $this->table_name,
            $data,
            ['id' => intval($id)],
            $formats,
            ['%d']
        );
    }
    
    /**
     * Delete item from queue
     */
    public function delete_item($id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            ['id' => intval($id)],
            ['%d']
        );
    }
    
    /**
     * Clear completed items
     */
    public function clear_completed() {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            ['status' => 'completed'],
            ['%s']
        );
    }
    
    /**
     * Get queue statistics
     */
    public function get_stats() {
        global $wpdb;
        
        $stats = [
            'total' => 0,
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0
        ];
        
        $results = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$this->table_name} GROUP BY status",
            ARRAY_A
        );
        
        foreach ($results as $row) {
            $stats[$row['status']] = intval($row['count']);
            $stats['total'] += intval($row['count']);
        }
        
        return $stats;
    }
    
    /**
     * Process next item in queue
     */
    public function process_next_item() {
        global $wpdb;
        
        // Get next pending item
        $item = $wpdb->get_row(
            "SELECT * FROM {$this->table_name} 
             WHERE status = 'pending' 
             ORDER BY priority DESC, created_at ASC 
             LIMIT 1",
            ARRAY_A
        );
        
        if (!$item) {
            return null;
        }
        
        // Mark as processing
        $this->update_status($item['id'], 'processing');
        
        try {
            // Generate content based on item type
            if ($item['item_type'] === 'product') {
                $result = $this->generate_product($item);
            } else {
                $result = $this->generate_post($item);
            }
            
            // Mark as completed
            $this->update_status($item['id'], 'completed', $result['id']);
            
            return [
                'success' => true,
                'item_id' => $item['id'],
                'result_id' => $result['id'],
                'title' => $result['title']
            ];
            
        } catch (Exception $e) {
            // Mark as failed
            $this->update_status($item['id'], 'failed', null, $e->getMessage());
            
            return [
                'success' => false,
                'item_id' => $item['id'],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process multiple items from queue
     */
    public function process_batch($count = 5) {
        $results = [];
        
        for ($i = 0; $i < $count; $i++) {
            $result = $this->process_next_item();
            
            if (!$result) {
                // No more items in queue
                break;
            }
            
            $results[] = $result;
            
            // Small delay between items to avoid API rate limits
            sleep(2);
        }
        
        return $results;
    }
    
    /**
     * Generate product from queue item
     */
    private function generate_product($item) {
        // Step 1: Research
        $tavily = new SIR_Tavily_API();
        $research_data = $tavily->research_product($item['title'], $item['keywords']);
        
        if (empty($research_data)) {
            throw new Exception('تحقیق محصول ناموفق بود');
        }
        
        // Step 2: Generate content
        $blackbox = new SIR_Blackbox_API();
        $content = $blackbox->generate_product_content($research_data, $item['title'], $item['keywords']);
        
        // Step 3: Create product
        $handler = new SIR_Product_Handler();
        $auto_publish = get_option('sir_auto_publish', 'draft');
        $result = $handler->create_product($content, $auto_publish);
        
        // Log success
        $this->log_success('queue_product', $item['title'], $result['product_id']);
        
        return [
            'id' => $result['product_id'],
            'title' => $result['title']
        ];
    }
    
    /**
     * Generate post from queue item
     */
    private function generate_post($item) {
        // Step 1: Research
        $tavily = new SIR_Tavily_API();
        $research_data = $tavily->research_topic($item['title'], $item['keywords']);
        
        // Step 2: Generate content
        $blackbox = new SIR_Blackbox_API();
        $content = $blackbox->generate_post_content($research_data, $item['title'], $item['keywords']);
        
        // Step 3: Create post
        $handler = new SIR_Post_Handler();
        $auto_publish = get_option('sir_auto_publish', 'draft');
        $result = $handler->create_post($content, $auto_publish);
        
        // Log success
        $this->log_success('queue_post', $item['title'], $result['post_id']);
        
        return [
            'id' => $result['post_id'],
            'title' => $result['title']
        ];
    }
    
    /**
     * Retry failed item
     */
    public function retry_item($id) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            [
                'status' => 'pending',
                'error_message' => null,
                'processed_at' => null
            ],
            ['id' => intval($id)],
            ['%s', '%s', '%s'],
            ['%d']
        );
    }
    
    /**
     * Log success
     */
    private function log_success($action, $name, $result_id) {
        if (get_option('sir_enable_logging') !== 'yes') {
            return;
        }
        
        global $wpdb;
        $logs_table = $wpdb->prefix . 'sir_logs';
        
        $wpdb->insert($logs_table, [
            'action_type' => $action,
            'product_name' => $name,
            'status' => 'success',
            'message' => "Item created with ID: {$result_id}",
            'created_at' => current_time('mysql')
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
        $logs_table = $wpdb->prefix . 'sir_logs';
        
        $wpdb->insert($logs_table, [
            'action_type' => $action,
            'product_name' => $name,
            'status' => 'failed',
            'message' => $message,
            'created_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Parse bulk input text
     * Format: Title | keyword1, keyword2, keyword3
     */
    public static function parse_bulk_text($text) {
        $items = [];
        $lines = explode("\n", $text);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // Split by pipe
            $parts = explode('|', $line);
            
            if (count($parts) >= 1) {
                $items[] = [
                    'title' => trim($parts[0]),
                    'keywords' => isset($parts[1]) ? trim($parts[1]) : '',
                    'item_type' => 'product',
                    'priority' => 0
                ];
            }
        }
        
        return $items;
    }
    
    /**
     * Parse CSV file
     */
    public static function parse_csv($file_path) {
        if (!file_exists($file_path)) {
            throw new Exception('فایل CSV یافت نشد');
        }
        
        $items = [];
        $handle = fopen($file_path, 'r');
        
        if ($handle === false) {
            throw new Exception('خطا در خواندن فایل CSV');
        }
        
        // Skip header row
        $header = fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= 2) {
                $items[] = [
                    'title' => trim($data[0] ?? ''),
                    'keywords' => trim($data[1] ?? ''),
                    'item_type' => isset($data[2]) ? trim($data[2]) : 'product',
                    'priority' => isset($data[3]) ? intval($data[3]) : 0
                ];
            }
        }
        
        fclose($handle);
        
        return $items;
    }
}
