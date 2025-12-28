<?php
/**
 * Post Handler for Blog Posts
 */

if (!defined('ABSPATH')) exit;

class SIR_Post_Handler {
    
    private $parser;
    
    public function __construct() {
        $this->parser = new SIR_Content_Parser();
    }
    
    /**
     * Create new blog post
     */
    public function create_post($raw_content, $status = 'draft') {
        if (empty($raw_content)) {
            throw new Exception('محتوای خالی برای ایجاد پست دریافت شد.');
        }
        
        // Parse content for posts
        $parsed = $this->parse_post_content($raw_content);
        
        // Validate
        if (empty($parsed['title'])) {
            throw new Exception('عنوان پست یافت نشد.');
        }
        
        // Create post
        $post_data = [
            'post_title' => $parsed['title'],
            'post_name' => $parsed['slug'],
            'post_content' => $parsed['content'],
            'post_excerpt' => $parsed['excerpt'],
            'post_status' => $status,
            'post_type' => 'post',
            'post_author' => get_current_user_id()
        ];
        
        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            throw new Exception('خطا در ایجاد پست: ' . $post_id->get_error_message());
        }
        
        // Set SEO meta
        $this->set_seo_meta($post_id, $parsed);
        
        // Set category and tags
        $this->set_taxonomies($post_id, $parsed);
        
        // Store additional data
        update_post_meta($post_id, '_sir_post_data', $parsed);
        update_post_meta($post_id, '_sir_raw_content', $raw_content);
        update_post_meta($post_id, '_sir_generated_date', current_time('mysql'));
        
        // Store FAQ
        if (!empty($parsed['faq'])) {
            update_post_meta($post_id, '_sir_faq', $parsed['faq']);
            $faq_schema = $this->parser->generate_faq_schema($parsed['faq']);
            update_post_meta($post_id, '_sir_faq_schema', $faq_schema);
        }
        
        // Log action
        $this->log_action('create_post', $parsed['title'], 'success', "پست #{$post_id} ایجاد شد");
        
        return [
            'post_id' => $post_id,
            'title' => $parsed['title'],
            'slug' => $parsed['slug'],
            'edit_link' => admin_url("post.php?post={$post_id}&action=edit"),
            'view_link' => get_permalink($post_id)
        ];
    }
    
    /**
     * Update existing post
     */
    public function update_post($post_id, $raw_content) {
        $post = get_post($post_id);
        
        if (!$post) {
            throw new Exception('پست یافت نشد.');
        }
        
        // Parse new content
        $parsed = $this->parse_post_content($raw_content);
        
        // Update post
        $post_data = [
            'ID' => $post_id,
            'post_title' => !empty($parsed['title']) ? $parsed['title'] : $post->post_title,
            'post_content' => !empty($parsed['content']) ? $parsed['content'] : $post->post_content,
            'post_excerpt' => !empty($parsed['excerpt']) ? $parsed['excerpt'] : $post->post_excerpt,
        ];
        
        if (!empty($parsed['slug'])) {
            $post_data['post_name'] = $parsed['slug'];
        }
        
        $result = wp_update_post($post_data, true);
        
        if (is_wp_error($result)) {
            throw new Exception('خطا در به‌روزرسانی پست: ' . $result->get_error_message());
        }
        
        // Update SEO meta
        $this->set_seo_meta($post_id, $parsed);
        
        // Store update history
        update_post_meta($post_id, '_sir_last_updated', current_time('mysql'));
        update_post_meta($post_id, '_sir_raw_content', $raw_content);
        
        // Log action
        $this->log_action('update_post', $post->post_title, 'success', "پست #{$post_id} به‌روزرسانی شد");
        
        return [
            'post_id' => $post_id,
            'updated' => true,
            'title' => $post->post_title,
            'edit_link' => admin_url("post.php?post={$post_id}&action=edit")
        ];
    }
    
    /**
     * Parse post content
     */
    private function parse_post_content($raw_content) {
        $parsed = [
            'title' => '',
            'slug' => '',
            'meta_title' => '',
            'meta_description' => '',
            'content' => '',
            'excerpt' => '',
            'category' => '',
            'tags' => [],
            'faq' => []
        ];
        
        // Try to extract JSON
        if (preg_match('/```json\s*([\s\S]*?)\s*```/m', $raw_content, $match)) {
            $json = json_decode(trim($match[1]), true);
            
            if ($json && isset($json['post'])) {
                $post = $json['post'];
                $parsed['title'] = $post['title'] ?? '';
                $parsed['slug'] = $post['slug'] ?? '';
                $parsed['meta_title'] = $post['metaTitle'] ?? '';
                $parsed['meta_description'] = $post['metaDescription'] ?? '';
                $parsed['content'] = $post['content'] ?? '';
                $parsed['excerpt'] = $post['excerpt'] ?? '';
                $parsed['category'] = $post['category'] ?? '';
                $parsed['tags'] = $post['tags'] ?? [];
                $parsed['faq'] = $post['faq'] ?? [];
            }
        }
        
        // Fallback: Extract from content
        if (empty($parsed['title'])) {
            if (preg_match('/^#\s+(.+)$/m', $raw_content, $match)) {
                $parsed['title'] = trim($match[1]);
            } elseif (preg_match('/عنوان پست.*?:\s*(.+)/u', $raw_content, $match)) {
                $parsed['title'] = trim($match[1]);
            }
        }
        
        if (empty($parsed['slug'])) {
            if (preg_match('/پیوند یکتا.*?:\s*([a-z0-9\-]+)/ui', $raw_content, $match)) {
                $parsed['slug'] = strtolower(trim($match[1]));
            }
        }
        
        if (empty($parsed['content'])) {
            // Remove JSON block and meta section
            $content = preg_replace('/```json[\s\S]*?```/m', '', $raw_content);
            $content = preg_replace('/^###\s*۱\..*?(?=###\s*۲\.|## )/ms', '', $content);
            $parsed['content'] = trim($content);
        }
        
        // Extract FAQ
        if (empty($parsed['faq'])) {
            preg_match_all('/Q:\s*(.+?)\s*\nA:\s*(.+?)(?=\nQ:|\n\n##|\z)/us', $raw_content, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $parsed['faq'][] = [
                    'question' => trim($match[1]),
                    'answer' => trim($match[2])
                ];
            }
        }
        
        return $parsed;
    }
    
    /**
     * Set SEO metadata
     */
    private function set_seo_meta($post_id, $parsed) {
        // Yoast
        if (!empty($parsed['meta_title'])) {
            update_post_meta($post_id, '_yoast_wpseo_title', $parsed['meta_title']);
        }
        if (!empty($parsed['meta_description'])) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $parsed['meta_description']);
        }
        
        // Rank Math
        if (!empty($parsed['meta_title'])) {
            update_post_meta($post_id, 'rank_math_title', $parsed['meta_title']);
        }
        if (!empty($parsed['meta_description'])) {
            update_post_meta($post_id, 'rank_math_description', $parsed['meta_description']);
        }
    }
    
    /**
     * Set taxonomies
     */
    private function set_taxonomies($post_id, $parsed) {
        // Set category
        if (!empty($parsed['category'])) {
            $cat = get_cat_ID($parsed['category']);
            if (!$cat) {
                $cat = wp_create_category($parsed['category']);
            }
            if ($cat) {
                wp_set_post_categories($post_id, [$cat]);
            }
        }
        
        // Set tags
        if (!empty($parsed['tags']) && is_array($parsed['tags'])) {
            wp_set_post_tags($post_id, $parsed['tags']);
        }
    }
    
    /**
     * Get post content for update
     */
    public function get_post_content($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            throw new Exception('پست یافت نشد.');
        }
        
        return [
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'raw_content' => get_post_meta($post_id, '_sir_raw_content', true)
        ];
    }
    
    /**
     * Log action
     */
    private function log_action($action_type, $title, $status, $message) {
        if (get_option('sir_enable_logging') !== 'yes') {
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sir_logs';
        
        $wpdb->insert($table_name, [
            'action_type' => $action_type,
            'product_name' => $title,
            'status' => $status,
            'message' => $message,
            'created_at' => current_time('mysql')
        ]);
    }
}
