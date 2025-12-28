<?php
/**
 * Blackbox API Handler
 */

if (!defined('ABSPATH')) exit;

class SIR_Blackbox_API {
    
    private $api_key;
    private $model;
    private $base_url = 'https://api.blackbox.ai/api/chat';
    private $timeout = 300;
    
    public function __construct() {
        $this->api_key = trim(get_option('sir_blackbox_api_key', ''));
        $this->model = get_option('sir_claude_model', 'claude-sonnet-4-20250514');
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
        
        // Extract custom fields from research data first
        $detected_fields = SIR_Custom_Fields::generate_from_research($research_data);
        
        $user_message = "## داده‌های تحقیق محصول:\n\n";
        $user_message .= $research_data . "\n\n";
        $user_message .= "## نام محصول: {$product_name}\n\n";
        $user_message .= "## کلیدواژه‌های هدف:\n{$keywords}\n\n";
        
        // Add detected custom fields to guide AI
        if (!empty($detected_fields)) {
            $user_message .= "## فیلدهای سفارشی شناسایی شده از تحقیق:\n";
            foreach ($detected_fields as $key => $value) {
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                $user_message .= "- {$key}: {$value}\n";
            }
            $user_message .= "\n";
        }
        
        $user_message .= "## دستورالعمل مهم:\n";
        $user_message .= "لطفاً بر اساس پرامپت و داده‌های بالا، محتوای کامل ۱۸ بخشی را به همراه خروجی JSON تولید کن.\n\n";
        $user_message .= "**نکات کلیدی:**\n";
        $user_message .= "1. تمام فیلدهای سفارشی (customFields) در JSON خروجی باید پر شوند\n";
        $user_message .= "2. از داده‌های تحقیق برای استخراج تمام مشخصات فنی استفاده کن\n";
        $user_message .= "3. فیلدهای اجباری شامل: brand, model, batteryCapacity, outputPower, tankCapacity, chargingType, displayType, dimensions, weight, materials\n";
        $user_message .= "4. اگر اطلاعاتی در داده‌های تحقیق موجود نیست، از دانش عمومی خود استفاده کن\n";
        $user_message .= "5. مطمئن شو که JSON خروجی معتبر و کامل باشد\n";
        
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
            $error_message = $response->get_error_message();
            $error_code = $response->get_error_code();
            throw new Exception("خطای اتصال ({$error_code}): {$error_message}");
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body_raw = wp_remote_retrieve_body($response);
        
        // Check for empty response body
        if (empty($body_raw)) {
            throw new Exception("پاسخ خالی از سرور دریافت شد (HTTP {$status_code})");
        }
        
        $body = json_decode($body_raw, true);
        
        // Check for JSON parsing errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('خطا در تجزیه پاسخ JSON: ' . json_last_error_msg());
        }
        
        if ($status_code !== 200) {
            $error_msg = 'خطای نامشخص';
            
            // Try different error message formats
            if (isset($body['error']['message'])) {
                $error_msg = $body['error']['message'];
            } elseif (isset($body['error'])) {
                $error_msg = is_string($body['error']) ? $body['error'] : json_encode($body['error']);
            } elseif (isset($body['message'])) {
                $error_msg = $body['message'];
            }
            
            throw new Exception("خطای API ({$status_code}): {$error_msg}");
        }
        
        // Extract content from various response formats
        $content = '';
        
        // Priority order for content extraction
        if (isset($body['choices'][0]['message']['content'])) {
            $content = $body['choices'][0]['message']['content'];
        } elseif (isset($body['response'])) {
            $content = $body['response'];
        } elseif (isset($body['message']['content'])) {
            $content = $body['message']['content'];
        } elseif (isset($body['text'])) {
            $content = $body['text'];
        } elseif (isset($body['content'])) {
            $content = is_array($body['content']) ? 
                (isset($body['content'][0]['text']) ? $body['content'][0]['text'] : json_encode($body['content'])) : 
                $body['content'];
        } elseif (isset($body['data']['content'])) {
            $content = $body['data']['content'];
        }
        
        if (empty($content)) {
            // Log the response structure for debugging
            error_log('Blackbox API response structure: ' . print_r($body, true));
            throw new Exception('محتوای خالی از API دریافت شد. ساختار پاسخ نامعتبر است.');
        }
        
        return $content;
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        try {
            if (empty($this->api_key)) {
                return [
                    'success' => false,
                    'message' => '❌ کلید API تنظیم نشده است'
                ];
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
                    'model' => $this->model,
                    'max_tokens' => 50
                ])
            ]);
            
            if (is_wp_error($response)) {
                return [
                    'success' => false,
                    'message' => '❌ ' . $response->get_error_message()
                ];
            }
            
            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($code === 200) {
                // Try to parse response to ensure it's valid
                $data = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return [
                        'success' => true,
                        'message' => '✅ اتصال به Blackbox API برقرار است'
                    ];
                }
            }
            
            // Parse error message from response
            $data = json_decode($body, true);
            $error_msg = "خطای HTTP {$code}";
            
            if (isset($data['error']['message'])) {
                $error_msg .= ': ' . $data['error']['message'];
            } elseif (isset($data['message'])) {
                $error_msg .= ': ' . $data['message'];
            }
            
            return [
                'success' => false,
                'message' => '❌ ' . $error_msg
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => '❌ ' . $e->getMessage()
            ];
        }
    }
}
