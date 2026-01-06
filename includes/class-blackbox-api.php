<?php
/**
 * Blackbox API Handler
 */

if (!defined('ABSPATH')) exit;

class SIR_Blackbox_API {
    
    private $api_key;
    private $model;
    private $base_url = 'https://api.blackbox.ai/v1/chat/completions';
    private $timeout = 300;
    
    public function __construct() {
        $this->api_key = trim(get_option('sir_blackbox_api_key', ''));
        $this->model = get_option('sir_claude_model', 'openai/gpt-4o-mini');
    }
    
    /**
     * Get primary color from settings
     */
    private function get_primary_color() {
        if (get_option('sir_use_theme_color') === 'yes') {
            $theme_color = get_theme_mod('primary_color');
            if (!empty($theme_color)) return $theme_color;
        }
        return get_option('sir_primary_color', '#e91e63');
    }
    
    /**
     * Log API request
     */
    private function log_api_request($message, $max_tokens) {
        if (get_option('sir_enable_logging') !== 'yes') return;
        
        update_option('sir_last_api_request', [
            'type' => 'api_request',
            'model' => $this->model,
            'message_length' => strlen($message),
            'max_tokens' => $max_tokens,
            'timestamp' => current_time('mysql')
        ], false);
    }
    
    /**
     * Log API response
     */
    private function log_api_response($status_code, $body) {
        if (get_option('sir_enable_logging') !== 'yes') return;
        
        $log_data = [
            'type' => 'api_response',
            'status_code' => $status_code,
            'model' => $this->model,
            'timestamp' => current_time('mysql')
        ];
        
        if ($status_code === 200) {
            $parsed = is_string($body) ? json_decode($body, true) : $body;
            if (isset($parsed['usage'])) {
                $log_data['usage'] = $parsed['usage'];
            }
        }
        
        update_option('sir_last_api_response', $log_data, false);
    }
    
    /**
     * Log API error
     */
    private function log_api_error($error_msg, $body = null) {
        if (get_option('sir_enable_logging') !== 'yes') return;
        
        update_option('sir_last_api_error', [
            'type' => 'api_error',
            'model' => $this->model,
            'error' => $error_msg,
            'response_body' => $body,
            'timestamp' => current_time('mysql')
        ], false);
    }
    
    /**
     * Generate content using Blackbox API
     */
    public function generate($prompt, $user_message, $max_tokens = 50000) {
        if (empty($this->api_key)) {
            throw new Exception('کلید API بلک‌باکس تنظیم نشده است.');
        }
        
        if (empty($prompt) || empty($user_message)) {
            throw new Exception('پرامپت یا پیام کاربر خالی است.');
        }
        
        $full_message = $prompt . "\n\n---\n\n" . $user_message;
        
        // Add primary color to message
        $primary_color = $this->get_primary_color();
        $full_message .= "\n\nرنگ اصلی برند: " . $primary_color;
        $full_message .= "\nاز این رنگ برای گرادیانها و هایلایتها استفاده کن.";
        
        // Add strong instruction for complete output
        $full_message .= "\n\n⚠️⚠️⚠️ بسیار مهم: تمام بخش‌ها را کامل بنویس. اگر به حد توکن نزدیک شدی، خلاصه‌تر بنویس ولی همه بخش‌ها را کامل کن. محتوا را نیمه‌کاره رها نکن.";
        
        // Log request
        $this->log_api_request($full_message, $max_tokens);
        
        $response = wp_remote_post($this->base_url, [
            'timeout' => $this->timeout,
            'sslverify' => true,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $full_message
                    ]
                ],
                'model' => $this->model,
                'max_tokens' => $max_tokens,
                'temperature' => 0.7,
                'top_p' => 0.9,
                'stream' => false
            ])
        ]);
        
        return $this->handle_response($response);
    }
    
    /**
     * Generate content for product
     */
    public function generate_product_content($research_data, $product_name, $keywords) {
        $prompt = SIR_Prompts::get_prompt('content');
        
        $user_message = "## داده‌های تحقیق محصول:\n\n";
        $user_message .= $research_data . "\n\n";
        $user_message .= "## نام محصول: {$product_name}\n\n";
        $user_message .= "## کلیدواژه‌های هدف:\n{$keywords}\n\n";
        $user_message .= "لطفاً بر اساس پرامپت و داده‌های بالا، محتوای کامل ۱۸ بخشی را به همراه خروجی JSON تولید کن.";
        $user_message .= "\n\nمطمئن شو تمام فیلدهای سفارشی (customFields) در JSON خروجی پر شده‌اند.";
        
        return $this->generate($prompt, $user_message);
    }
    
    /**
     * Generate content for blog post
     */
    public function generate_post_content($research_data, $topic, $keywords) {
        $prompt = SIR_Prompts::get_prompt('post');
        
        $user_message = "## داده‌های تحقیق:\n\n";
        $user_message .= $research_data . "\n\n";
        $user_message .= "## موضوع پست: {$topic}\n\n";
        $user_message .= "## کلیدواژه‌های هدف:\n{$keywords}\n\n";
        $user_message .= "لطفاً یک پست بلاگ کامل با ساختار مشخص شده تولید کن.";
        
        return $this->generate($prompt, $user_message);
    }
    
    /**
     * Update existing content
     */
    public function update_content($current_content, $research_data, $update_instructions) {
        $prompt = SIR_Prompts::get_prompt('update');
        
        $user_message = "## محتوای فعلی:\n\n";
        $user_message .= $current_content . "\n\n";
        $user_message .= "## داده‌های جدید از تحقیق:\n\n";
        $user_message .= $research_data . "\n\n";
        $user_message .= "## دستورالعمل‌های به‌روزرسانی:\n";
        $user_message .= $update_instructions . "\n\n";
        $user_message .= "لطفاً محتوا را به‌روزرسانی کن و تغییرات را گزارش بده.";
        
        return $this->generate($prompt, $user_message);
    }
    
    /**
     * Handle API response
     */
    private function handle_response($response) {
        if (is_wp_error($response)) {
            $error_msg = 'خطای اتصال: ' . $response->get_error_message();
            $this->log_api_error($error_msg);
            throw new Exception($error_msg);
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // Log response
        $this->log_api_response($status_code, $body);
        
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error_msg = isset($data['error']['message']) 
                ? $data['error']['message'] 
                : "خطای HTTP {$status_code}";
            $this->log_api_error($error_msg, $body);
            throw new Exception("خطای API: {$error_msg}");
        }
        
        // Extract content from various response formats
        $content = '';
        
        if (isset($data['choices'][0]['message']['content'])) {
            $content = $data['choices'][0]['message']['content'];
        } elseif (isset($data['response'])) {
            $content = $data['response'];
        } elseif (isset($data['text'])) {
            $content = $data['text'];
        } elseif (isset($data['content'])) {
            $content = is_array($data['content']) ? $data['content'][0]['text'] : $data['content'];
        }
        
        if (empty($content)) {
            $error_msg = 'پاسخ خالی از API دریافت شد.';
            $this->log_api_error($error_msg, $body);
            throw new Exception($error_msg);
        }
        
        return $content;
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        try {
            $response = wp_remote_post($this->base_url, [
                'timeout' => 30,
                'sslverify' => true,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'messages' => [
                        ['role' => 'user', 'content' => 'بگو: اتصال برقرار شد']
                    ],
                    'model' => $this->model,
                    'max_tokens' => 50,
                    'stream' => false
                ])
            ]);
            
            if (is_wp_error($response)) {
                return [
                    'success' => false,
                    'message' => $response->get_error_message()
                ];
            }
            
            $code = wp_remote_retrieve_response_code($response);
            
            if ($code === 200) {
                return [
                    'success' => true,
                    'message' => "✅ اتصال به Blackbox API برقرار است (مدل: {$this->model})"
                ];
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            $error_msg = isset($data['error']['message']) ? $data['error']['message'] : "خطای HTTP {$code}";
            
            return [
                'success' => false,
                'message' => $error_msg
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
