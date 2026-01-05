<?php
/**
 * Product Handler for WooCommerce
 */

if (!defined('ABSPATH')) exit;

class SIR_Product_Handler {
    
    private $parser;
    
    public function __construct() {
        $this->parser = new SIR_Content_Parser();
    }
    
    /**
     * Create new WooCommerce product
     */
    public function create_product($raw_content, $status = 'draft') {
        if (!class_exists('WC_Product_Simple')) {
            throw new Exception('ووکامرس نصب نیست.');
        }
        
        if (empty($raw_content)) {
            throw new Exception('محتوای خالی برای ایجاد محصول دریافت شد.');
        }
        
        // Parse content with diagnostic logging
        if (get_option('sir_enable_logging') === 'yes') {
            error_log('SIR: Starting product content parsing...');
        }
        
        $parsed = $this->parser->parse($raw_content);
        
        // Diagnostic logging
        if (get_option('sir_enable_logging') === 'yes') {
            error_log('SIR: Parsed title: ' . ($parsed['h1_title'] ?: 'MISSING'));
            error_log('SIR: Parsed slug: ' . ($parsed['slug'] ?: 'MISSING'));
            error_log('SIR: Content length: ' . strlen($parsed['full_content']));
        }
        
        // Validate - but be more graceful
        if (empty($parsed['h1_title'])) {
            // Try to extract from content
            if (!empty($parsed['full_content'])) {
                $parsed['h1_title'] = 'محصول جدید - ' . date('Y-m-d H:i:s');
            } else {
                throw new Exception('عنوان محصول یافت نشد و محتوای قابل تجزیه نیست.');
            }
        }
        
        // Ensure slug exists
        if (empty($parsed['slug'])) {
            $parsed['slug'] = sanitize_title($parsed['h1_title']) . '-' . time();
        }
        
        // Create product
        $product = new WC_Product_Simple();
        
        // Basic fields
        $product->set_name($parsed['h1_title']);
        $product->set_slug($parsed['slug']);
        $product->set_status($status);
        $product->set_catalog_visibility('visible');
        $product->set_description($parsed['full_content']);
        $product->set_short_description($parsed['short_description']);
        
        // Save product
        $product_id = $product->save();
        
        if (!$product_id) {
            throw new Exception('خطا در ایجاد محصول.');
        }
        
        // Set SEO meta
        $this->set_seo_meta($product_id, $parsed);
        
        // Set custom fields
        SIR_Custom_Fields::save_product_fields($product_id, $parsed['custom_fields']);
        
        // Store additional data
        $this->store_additional_data($product_id, $parsed, $raw_content);
        
        // Log action
        $this->log_action('create_product', $parsed['h1_title'], 'success', "محصول #{$product_id} ایجاد شد");
        
        return [
            'product_id' => $product_id,
            'title' => $parsed['h1_title'],
            'slug' => $parsed['slug'],
            'edit_link' => admin_url("post.php?post={$product_id}&action=edit"),
            'view_link' => get_permalink($product_id),
            'faq_count' => count($parsed['faq']),
            'custom_fields_count' => count($parsed['custom_fields'])
        ];
    }
    
    /**
     * Update existing product
     */
    public function update_product($product_id, $raw_content) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            throw new Exception('محصول یافت نشد.');
        }
        
        // Parse new content
        $parsed = $this->parser->parse($raw_content);
        
        // Update product fields
        if (!empty($parsed['h1_title'])) {
            $product->set_name($parsed['h1_title']);
        }
        
        if (!empty($parsed['full_content'])) {
            $product->set_description($parsed['full_content']);
        }
        
        if (!empty($parsed['short_description'])) {
            $product->set_short_description($parsed['short_description']);
        }
        
        if (!empty($parsed['slug'])) {
            $product->set_slug($parsed['slug']);
        }
        
        $product->save();
        
        // Update SEO meta
        $this->set_seo_meta($product_id, $parsed);
        
        // Update custom fields
        SIR_Custom_Fields::save_product_fields($product_id, $parsed['custom_fields']);
        
        // Store update history
        $update_history = get_post_meta($product_id, '_sir_update_history', true) ?: [];
        $update_history[] = [
            'date' => current_time('mysql'),
            'changes' => 'Content updated via AI'
        ];
        update_post_meta($product_id, '_sir_update_history', $update_history);
        update_post_meta($product_id, '_sir_last_updated', current_time('mysql'));
        
        // Log action
        $this->log_action('update_product', $product->get_name(), 'success', "محصول #{$product_id} به‌روزرسانی شد");
        
        return [
            'product_id' => $product_id,
            'updated' => true,
            'title' => $product->get_name(),
            'edit_link' => admin_url("post.php?post={$product_id}&action=edit")
        ];
    }
    
    /**
     * Set SEO metadata
     */
    private function set_seo_meta($product_id, $parsed) {
        // Yoast SEO
        if (!empty($parsed['meta_title'])) {
            update_post_meta($product_id, '_yoast_wpseo_title', $parsed['meta_title']);
        }
        if (!empty($parsed['meta_description'])) {
            update_post_meta($product_id, '_yoast_wpseo_metadesc', $parsed['meta_description']);
        }
        
        // Rank Math
        if (!empty($parsed['meta_title'])) {
            update_post_meta($product_id, 'rank_math_title', $parsed['meta_title']);
        }
        if (!empty($parsed['meta_description'])) {
            update_post_meta($product_id, 'rank_math_description', $parsed['meta_description']);
        }
        
        // FAQ Schema for Rank Math
        if (!empty($parsed['faq'])) {
            $faq_schema = array_map(function($faq) {
                return [
                    'title' => $faq['question'],
                    'content' => $faq['answer'],
                    'visible' => true
                ];
            }, $parsed['faq']);
            
            update_post_meta($product_id, 'rank_math_faq_block', json_encode(['questions' => $faq_schema]));
        }
    }
    
    /**
     * Store additional data
     */
    private function store_additional_data($product_id, $parsed, $raw_content) {
        update_post_meta($product_id, '_sir_parsed_data', $parsed);
        update_post_meta($product_id, '_sir_raw_content', $raw_content);
        update_post_meta($product_id, '_sir_json_data', $parsed['json_data']);
        update_post_meta($product_id, '_sir_faq', $parsed['faq']);
        update_post_meta($product_id, '_sir_alt_texts', $parsed['alt_texts']);
        update_post_meta($product_id, '_sir_social_captions', $parsed['social_captions']);
        update_post_meta($product_id, '_sir_generated_date', current_time('mysql'));
        
        // Store FAQ Schema
        if (!empty($parsed['faq'])) {
            $faq_schema = $this->parser->generate_faq_schema($parsed['faq']);
            update_post_meta($product_id, '_sir_faq_schema', $faq_schema);
        }
    }
    
    /**
     * Get product content for update
     */
    public function get_product_content($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            throw new Exception('محصول یافت نشد.');
        }
        
        return [
            'title' => $product->get_name(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'custom_fields' => SIR_Custom_Fields::get_product_fields($product_id),
            'raw_content' => get_post_meta($product_id, '_sir_raw_content', true)
        ];
    }
    
    /**
     * Log action
     */
    private function log_action($action_type, $product_name, $status, $message) {
        if (get_option('sir_enable_logging') !== 'yes') {
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sir_logs';
        
        $wpdb->insert($table_name, [
            'action_type' => $action_type,
            'product_name' => $product_name,
            'status' => $status,
            'message' => $message,
            'created_at' => current_time('mysql')
        ]);
    }
}
