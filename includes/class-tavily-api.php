<?php
/**
 * Tavily API Handler for Product Research
 */

if (!defined('ABSPATH')) exit;

class SIR_Tavily_API {
    
    private $api_key;
    private $base_url = 'https://api.tavily.com/search';
    private $timeout = 60;
    
    public function __construct() {
        $this->api_key = trim(get_option('sir_tavily_api_key', ''));
    }
    
    /**
     * Research a product
     */
    public function research_product($product_name, $keywords = '') {
        if (empty($this->api_key)) {
            throw new Exception('کلید API تاویلی تنظیم نشده است.');
        }
        
        $results = [];
        
        // Search 1: Basic specs and features
        $results['specs'] = $this->search(
            "{$product_name} specifications features review",
            ['eleafworld.com', 'voopoo.com', 'vaporesso.com', 'uwell.com', 'geekvape.com']
        );
        
        // Search 2: Technical details
        $results['technical'] = $this->search(
            "{$product_name} coil battery chipset wattage",
            []
        );
        
        // Search 3: Brand and usage
        $brand = $this->extract_brand($product_name);
        $results['brand'] = $this->search(
            "{$brand} vape brand history {$product_name} manual",
            []
        );
        
        // Search 4: Comparisons
        $results['comparison'] = $this->search(
            "{$product_name} vs comparison review best",
            []
        );
        
        // Compile results
        return $this->compile_research($product_name, $results, $keywords);
    }
    
    /**
     * Research for blog post
     */
    public function research_topic($topic, $keywords = '') {
        if (empty($this->api_key)) {
            throw new Exception('کلید API تاویلی تنظیم نشده است.');
        }
        
        $results = [];
        
        // Main topic search
        $results['main'] = $this->search($topic . ' guide tutorial', []);
        
        // Related searches
        $results['related'] = $this->search($topic . ' tips tricks best practices', []);
        
        // FAQ search
        $results['faq'] = $this->search($topic . ' FAQ questions answers', []);
        
        return $this->compile_topic_research($topic, $results, $keywords);
    }
    
    /**
     * Perform Tavily search
     */
    private function search($query, $include_domains = []) {
        $body = [
            'query' => $query,
            'search_depth' => 'advanced',
            'include_answer' => true,
            'include_raw_content' => false,
            'max_results' => 8,
        ];
        
        if (!empty($include_domains)) {
            $body['include_domains'] = $include_domains;
        }
        
        $response = wp_remote_post($this->base_url, [
            'timeout' => $this->timeout,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'body' => json_encode($body)
        ]);
        
        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message(), 'answer' => '', 'sources' => []];
        }
        
        $result = json_decode(wp_remote_retrieve_body($response), true);
        
        return [
            'answer' => $result['answer'] ?? '',
            'sources' => array_map(function($r) {
                return [
                    'title' => $r['title'] ?? '',
                    'url' => $r['url'] ?? '',
                    'content' => $r['content'] ?? ''
                ];
            }, $result['results'] ?? [])
        ];
    }
    
    /**
     * Extract brand name from product name
     */
    private function extract_brand($product_name) {
        $brands = [
            'VOOPOO', 'Vaporesso', 'UWELL', 'GeekVape', 'SMOK', 
            'Aspire', 'Innokin', 'Lost Vape', 'Eleaf', 'Joyetech',
            'Vapefly', 'Vandy Vape', 'Hellvape', 'Wotofo', 'OXVA'
        ];
        
        foreach ($brands as $brand) {
            if (stripos($product_name, $brand) !== false) {
                return $brand;
            }
        }
        
        return explode(' ', $product_name)[0];
    }
    
    /**
     * Compile product research into Persian format
     */
    private function compile_research($product_name, $results, $keywords) {
        $output = "# گزارش تحقیق محصول: {$product_name}\n\n";
        $output .= "---\n\n";
        
        // Title section
        $output .= "## عنوان:\n";
        $output .= "{$product_name}\n\n";
        
        // Keywords
        if (!empty($keywords)) {
            $output .= "## کلیدواژه‌های هدف:\n";
            $output .= "{$keywords}\n\n";
        }
        
        // Specs section
        $output .= "## مشخصات فنی:\n";
        if (!empty($results['specs']['answer'])) {
            $output .= $results['specs']['answer'] . "\n\n";
        }
        $output .= "### منابع:\n";
        foreach ($results['specs']['sources'] as $idx => $source) {
            $num = $idx + 1;
            $output .= "**[منبع {$num}]** {$source['title']}\n";
            $output .= $source['content'] . "\n";
            $output .= "لینک: {$source['url']}\n\n";
        }
        
        // Technical section
        $output .= "## جزئیات فنی:\n";
        if (!empty($results['technical']['answer'])) {
            $output .= $results['technical']['answer'] . "\n\n";
        }
        foreach ($results['technical']['sources'] as $source) {
            $output .= "- {$source['content']}\n";
        }
        $output .= "\n";
        
        // Brand section
        $output .= "## داستان برند:\n";
        if (!empty($results['brand']['answer'])) {
            $output .= $results['brand']['answer'] . "\n\n";
        }
        foreach ($results['brand']['sources'] as $source) {
            if (stripos($source['content'], 'brand') !== false || 
                stripos($source['content'], 'history') !== false ||
                stripos($source['content'], 'founded') !== false) {
                $output .= $source['content'] . "\n\n";
            }
        }
        
        // Comparison section
        $output .= "## مقایسه با رقبا:\n";
        if (!empty($results['comparison']['answer'])) {
            $output .= $results['comparison']['answer'] . "\n\n";
        }
        foreach ($results['comparison']['sources'] as $source) {
            $output .= "- {$source['content']}\n";
        }
        
        $output .= "\n---\n";
        $output .= "تاریخ تحقیق: " . current_time('Y-m-d H:i:s') . "\n";
        
        return $output;
    }
    
    /**
     * Compile topic research
     */
    private function compile_topic_research($topic, $results, $keywords) {
        $output = "# گزارش تحقیق موضوع: {$topic}\n\n";
        
        if (!empty($keywords)) {
            $output .= "## کلیدواژه‌ها:\n{$keywords}\n\n";
        }
        
        $output .= "## خلاصه:\n";
        $output .= ($results['main']['answer'] ?? '') . "\n\n";
        
        $output .= "## اطلاعات تکمیلی:\n";
        $output .= ($results['related']['answer'] ?? '') . "\n\n";
        
        $output .= "## سوالات متداول:\n";
        $output .= ($results['faq']['answer'] ?? '') . "\n\n";
        
        $output .= "## منابع:\n";
        foreach ($results['main']['sources'] as $source) {
            $output .= "- [{$source['title']}]({$source['url']})\n";
        }
        
        return $output;
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        try {
            $response = wp_remote_post($this->base_url, [
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key,
                ],
                'body' => json_encode([
                    'query' => 'vape device test',
                    'max_results' => 1
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
                    'message' => '✅ اتصال به Tavily API برقرار است'
                ];
            }
            
            $body = json_decode(wp_remote_retrieve_body($response), true);
            return [
                'success' => false,
                'message' => $body['error'] ?? "خطای HTTP {$code}"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
