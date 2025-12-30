<?php
/**
 * Blackbox API Handler
 */

if (!defined('ABSPATH')) exit;

class SIR_Blackbox_API {
    
    private $api_key;
    private $model;
    private $base_url = 'https://api.blackbox.ai/chat/completions';
    private $models_url = 'https://api.blackbox.ai/v1/models';
    private $timeout = 300;
    
    public function __construct() {
        $this->api_key = trim(get_option('sir_blackbox_api_key', ''));
        $this->model = get_option('sir_claude_model', 'blackboxai/anthropic/claude-3.5-sonnet');
    }
    
    /**
     * Generate content using Blackbox API
     */
    public function generate($prompt, $user_message, $max_tokens = 16000) {
        if (empty($this->api_key)) {
            throw new Exception('کلید API بلک‌باکس تنظیم نشده است.');
        }
        
        if (empty($prompt) || empty($user_message)) {
            throw new Exception('پرامپت یا پیام کاربر خالی است.');
        }
        
        $full_message = $prompt . "\n\n---\n\n" . $user_message;
        
        $response = wp_remote_post($this->base_url, [
            'timeout' => $this->timeout,
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
                'temperature' => 0.7
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
            throw new Exception('خطای اتصال: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code !== 200) {
            $error_msg = isset($body['error']['message']) 
                ? $body['error']['message'] 
                : "خطای HTTP {$status_code}";
            throw new Exception("خطای API: {$error_msg}");
        }
        
        // Extract content from various response formats
        $content = '';
        
        if (isset($body['choices'][0]['message']['content'])) {
            $content = $body['choices'][0]['message']['content'];
        } elseif (isset($body['response'])) {
            $content = $body['response'];
        } elseif (isset($body['text'])) {
            $content = $body['text'];
        } elseif (isset($body['content'])) {
            $content = is_array($body['content']) ? $body['content'][0]['text'] : $body['content'];
        }
        
        if (empty($content)) {
            throw new Exception('پاسخ خالی از API دریافت شد.');
        }
        
        return $content;
    }
    
    /**
     * Get available models from API
     */
    public function get_available_models() {
        if (empty($this->api_key)) {
            return [];
        }
        
        $response = wp_remote_get($this->models_url, [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ]
        ]);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return [];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['data']) || !is_array($body['data'])) {
            return [];
        }
        
        // Extract model IDs
        $models = [];
        foreach ($body['data'] as $model) {
            if (isset($model['id'])) {
                $models[] = $model['id'];
            }
        }
        
        return $models;
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        try {
            // First, try to get available models
            $available_models = $this->get_available_models();
            
            // Select a model to test with
            $test_model = $this->model;
            
            // If current model isn't available, use first available model
            if (!empty($available_models) && !in_array($this->model, $available_models)) {
                $test_model = $available_models[0];
            }
            
            $response = wp_remote_post($this->base_url, [
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'messages' => [
                        ['role' => 'user', 'content' => 'بگو: اتصال برقرار شد']
                    ],
                    'model' => $test_model,
                    'max_tokens' => 50
                ])
            ]);
            
            if (is_wp_error($response)) {
                return [
                    'success' => false,
                    'message' => $response->get_error_message()
                ];
            }
            
            $code = wp_remote_retrieve_response_code($response);
            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if ($code === 200) {
                return [
                    'success' => true,
                    'message' => '✅ اتصال به Blackbox API برقرار است',
                    'available_models' => $available_models
                ];
            }
            
            // Provide more detailed error message
            $error_msg = isset($body['error']['message']) 
                ? $body['error']['message'] 
                : "خطای HTTP {$code}";
            
            return [
                'success' => false,
                'message' => $error_msg,
                'available_models' => $available_models
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
