<?php
/**
 * Content Parser for Smokeiran
 */

if (!defined('ABSPATH')) exit;

class SIR_Content_Parser {
    
    /**
     * Parse generated content
     */
    public function parse($raw_content) {
        if (empty($raw_content)) {
            throw new Exception('محتوای خالی برای تجزیه دریافت شد.');
        }
        
        $parsed = [
            // SEO Fields
            'h1_title' => '',
            'slug' => '',
            'meta_title' => '',
            'meta_description' => '',
            
            // Content Fields
            'short_description' => '',
            'full_content' => '',
            
            // Sections
            'introduction' => '',
            'problems_solutions' => '',
            'key_features' => '',
            'technical_specs' => '',
            'usage_guide' => '',
            'maintenance' => '',
            'comparison' => '',
            'variants' => '',
            'pros_cons' => '',
            'brand_story' => '',
            'warranty' => '',
            
            // Structured Data
            'faq' => [],
            'alt_texts' => [],
            'social_captions' => [],
            'related_products' => '',
            
            // Custom Fields
            'custom_fields' => [],
            
            // JSON Data
            'json_data' => null
        ];
        
        // Extract JSON block
        $parsed['json_data'] = $this->extract_json($raw_content);
        
        // Populate from JSON if available
        if ($parsed['json_data']) {
            $this->populate_from_json($parsed);
        }
        
        // Extract sections from content
        $this->extract_sections($raw_content, $parsed);
        
        // Build full content only if not already set from JSON
        if (empty($parsed['full_content'])) {
            $parsed['full_content'] = $this->build_full_content($raw_content);
        }
        
        // Extract SEO meta if not in JSON
        $this->extract_seo_meta($raw_content, $parsed);
        
        // Parse FAQ if not array
        if (empty($parsed['faq']) || !is_array($parsed['faq'])) {
            $parsed['faq'] = $this->extract_faq($raw_content);
        }
        
        // Extract custom fields if empty (fallback when no JSON)
        if (empty($parsed['custom_fields'])) {
            $parsed['custom_fields'] = SIR_Custom_Fields::generate_from_research($raw_content);
        }
        
        return $parsed;
    }
    
    /**
     * Extract JSON from content
     */
    private function extract_json($content) {
        if (preg_match('/```json\s*([\s\S]*?)\s*```/m', $content, $match)) {
            $json_str = trim($match[1]);
            $data = json_decode($json_str, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }
        return null;
    }
    
    /**
     * Populate fields from JSON data
     */
    private function populate_from_json(&$parsed) {
        $json = $parsed['json_data'];
        
        // SEO fields
        if (isset($json['seo'])) {
            $parsed['h1_title'] = $json['seo']['title'] ?? '';
            $parsed['slug'] = $json['seo']['slug'] ?? '';
            $parsed['meta_title'] = $json['seo']['metaTitle'] ?? '';
            $parsed['meta_description'] = $json['seo']['metaDescription'] ?? '';
        }
        
        // Content fields
        if (isset($json['content'])) {
            $parsed['short_description'] = $json['content']['shortDescription'] ?? '';
            $parsed['faq'] = $json['content']['faq'] ?? [];
            
            // Build full content from JSON sections (excluding technical specs that go in custom fields)
            $content_parts = [];
            
            if (!empty($json['content']['introduction'])) {
                $content_parts[] = "## معرفی محصول\n\n" . $json['content']['introduction'];
            }
            
            if (!empty($json['content']['features'])) {
                $content_parts[] = "## ویژگی‌های کلیدی\n\n" . (is_array($json['content']['features']) ? implode("\n\n", $json['content']['features']) : $json['content']['features']);
            }
            
            if (!empty($json['content']['usage'])) {
                $content_parts[] = "## نحوه استفاده\n\n" . (is_array($json['content']['usage']) ? implode("\n\n", $json['content']['usage']) : $json['content']['usage']);
            }
            
            if (!empty($json['content']['maintenance'])) {
                $content_parts[] = "## نکات نگهداری\n\n" . $json['content']['maintenance'];
            }
            
            if (!empty($json['content']['comparison'])) {
                $content_parts[] = "## مقایسه با رقبا\n\n" . (is_array($json['content']['comparison']) ? json_encode($json['content']['comparison'], JSON_UNESCAPED_UNICODE) : $json['content']['comparison']);
            }
            
            if (!empty($json['content']['prosAndCons'])) {
                $pros_cons = $json['content']['prosAndCons'];
                $pc_text = "## نقاط قوت و ضعف\n\n";
                if (is_array($pros_cons)) {
                    if (!empty($pros_cons['pros'])) {
                        $pc_text .= "### نقاط قوت:\n" . (is_array($pros_cons['pros']) ? implode("\n", array_map(fn($p) => "- $p", $pros_cons['pros'])) : $pros_cons['pros']) . "\n\n";
                    }
                    if (!empty($pros_cons['cons'])) {
                        $pc_text .= "### نقاط ضعف:\n" . (is_array($pros_cons['cons']) ? implode("\n", array_map(fn($c) => "- $c", $pros_cons['cons'])) : $pros_cons['cons']);
                    }
                } else {
                    $pc_text .= $pros_cons;
                }
                $content_parts[] = $pc_text;
            }
            
            if (!empty($json['content']['brandStory'])) {
                $content_parts[] = "## داستان برند\n\n" . $json['content']['brandStory'];
            }
            
            if (!empty($json['content']['warranty'])) {
                $content_parts[] = "## گارانتی و خدمات\n\n" . $json['content']['warranty'];
            }
            
            // Only set full_content from JSON if we have content parts
            if (!empty($content_parts)) {
                $parsed['full_content'] = implode("\n\n---\n\n", $content_parts);
            }
        }
        
        // Images/Alt texts
        if (isset($json['images'])) {
            $parsed['alt_texts'] = $json['images'];
        }
        
        // Social captions
        if (isset($json['social'])) {
            $parsed['social_captions'] = $json['social'];
        }
        
        // Custom fields
        if (isset($json['customFields'])) {
            $parsed['custom_fields'] = $json['customFields'];
        }
    }
    
    /**
     * Extract sections by headers
     */
    private function extract_sections($content, &$parsed) {
        $section_patterns = [
            'introduction' => '/## (?:معرفی محصول|بخش ۳).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'problems_solutions' => '/## (?:مشکلات کاربر|بخش ۴).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'key_features' => '/## (?:ویژگی‌های کلیدی|بخش ۵).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'technical_specs' => '/## (?:مشخصات فنی|بخش ۶).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'usage_guide' => '/## (?:نحوه استفاده|بخش ۷).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'maintenance' => '/## (?:نکات نگهداری|بخش ۸).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'comparison' => '/## (?:مقایسه با رقبا|بخش ۹).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'variants' => '/## (?:طعم‌ها|رنگ‌ها|مدل‌ها|بخش ۱۰).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'pros_cons' => '/## (?:نقاط قوت و ضعف|بخش ۱۱).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'brand_story' => '/## (?:داستان برند|بخش ۱۲).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'warranty' => '/## (?:گارانتی|بخش ۱۳).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
        ];
        
        foreach ($section_patterns as $key => $pattern) {
            if (preg_match($pattern, $content, $match)) {
                $parsed[$key] = trim($match[1]);
            }
        }
    }
    
    /**
     * Build full content (excluding JSON and technical specs that go to custom fields)
     */
    private function build_full_content($content) {
        // Remove JSON block
        $content = preg_replace('/```json[\s\S]*?```/m', '', $content);
        
        // Remove SEO metadata section (should not be in product description)
        // Pattern: ## متادیتای SEO ... (including the separator)
        $content = preg_replace('/##\s*متادیتای\s*SEO.*?(?:\n---+\n|\n(?=##)|\z)/ms', '', $content);
        $content = preg_replace('/###\s*بخش\s*۱:.*?(?:\n---+\n|\n(?=##)|\z)/ms', '', $content);
        
        // Remove short description section (goes to separate field)
        // Pattern: ## توضیح کوتاه محصول ... (including the separator)
        $content = preg_replace('/##\s*توضیح\s*کوتاه\s*محصول.*?(?:\n---+\n|\n(?=##)|\z)/ms', '', $content);
        $content = preg_replace('/###\s*بخش\s*۲:.*?(?:\n---+\n|\n(?=##)|\z)/ms', '', $content);
        
        // Remove technical specifications section (بخش ۶) as it goes into custom fields
        $content = preg_replace('/###\s*بخش\s*۶:.*?(?:\n---+\n|\n(?=##)|\z)/ms', '', $content);
        $content = preg_replace('/##\s*مشخصات\s*فنی.*?(?:\n---+\n|\n(?=##)|\z)/ms', '', $content);
        
        // Remove FAQ section (goes to separate meta field)
        $content = preg_replace('/###\s*بخش\s*۱۴:.*?(?:\n---+\n|\n(?=##)|\z)/ms', '', $content);
        $content = preg_replace('/##\s*سوالات\s*متداول.*?(?:\n---+\n|\n(?=##)|\z)/ms', '', $content);
        
        // Remove alt text table section (بخش ۱۵) as it's for images
        $content = preg_replace('/###\s*بخش\s*۱۵:.*?(?:\n---+\n|\n(?=##)|\z)/ms', '', $content);
        
        // Remove internal linking section (بخش ۱۶)
        $content = preg_replace('/###\s*بخش\s*۱۶:.*?(?:\n---+\n|\n(?=##)|\z)/ms', '', $content);
        
        // Remove social media captions section (بخش ۱۷)
        $content = preg_replace('/###\s*بخش\s*۱۷:.*?(?:\n---+\n|\n(?=##)|\z)/ms', '', $content);
        
        // Remove JSON output section (بخش ۱۸)
        $content = preg_replace('/###\s*بخش\s*۱۸:.*?(?:\n---+\n|\n(?=##)|\z)/ms', '', $content);
        
        // Clean up any remaining standalone dashes
        $content = preg_replace('/^\s*---+\s*$/m', '', $content);
        
        // Clean up multiple newlines
        $content = preg_replace('/\n{3,}/m', "\n\n", $content);
        
        return trim($content);
    }
    
    /**
     * Extract SEO meta from content
     */
    private function extract_seo_meta($content, &$parsed) {
        // H1 Title
        if (empty($parsed['h1_title'])) {
            if (preg_match('/عنوان صفحه \(H1\):\s*(.+)/u', $content, $match)) {
                $parsed['h1_title'] = trim($match[1]);
            } elseif (preg_match('/^# (.+)$/m', $content, $match)) {
                $parsed['h1_title'] = trim($match[1]);
            }
        }
        
        // Slug
        if (empty($parsed['slug'])) {
            if (preg_match('/پیوند یکتا.*?:\s*([a-z0-9\-]+)/ui', $content, $match)) {
                $parsed['slug'] = strtolower(trim($match[1]));
            }
        }
        
        // Meta Title
        if (empty($parsed['meta_title'])) {
            if (preg_match('/متا تایتل:\s*(.+)/u', $content, $match)) {
                $parsed['meta_title'] = trim($match[1]);
            }
        }
        
        // Meta Description
        if (empty($parsed['meta_description'])) {
            if (preg_match('/متا دسکریپشن:\s*(.+)/u', $content, $match)) {
                $parsed['meta_description'] = trim($match[1]);
            }
        }
        
        // Short Description
        if (empty($parsed['short_description'])) {
            if (preg_match('/### بخش ۲:.*?\n\n(.+?)(?=\n\n|###)/us', $content, $match)) {
                $parsed['short_description'] = trim($match[1]);
            }
        }
    }
    
    /**
     * Extract FAQ from content
     */
    private function extract_faq($content) {
        $faqs = [];
        
        // Pattern 1: Q: / A: format
        preg_match_all('/Q:\s*(.+?)\s*\nA:\s*(.+?)(?=\nQ:|\n\n##|\z)/us', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $faqs[] = [
                'question' => trim($match[1]),
                'answer' => trim($match[2])
            ];
        }
        
        // Pattern 2: **سوال** / پاسخ format
        if (empty($faqs)) {
            preg_match_all('/\*\*(.+?)\*\*\s*\n(.+?)(?=\*\*|\n\n##|\z)/us', $content, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                if (mb_strpos($match[1], '؟') !== false || mb_strpos($match[1], '?') !== false) {
                    $faqs[] = [
                        'question' => trim($match[1]),
                        'answer' => trim($match[2])
                    ];
                }
            }
        }
        
        return $faqs;
    }
    
    /**
     * Generate FAQ Schema
     */
    public function generate_faq_schema($faqs) {
        if (empty($faqs)) return '';
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => []
        ];
        
        foreach ($faqs as $faq) {
            $schema['mainEntity'][] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer']
                ]
            ];
        }
        
        return '<script type="application/ld+json">' . 
               json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . 
               '</script>';
    }
    
    /**
     * Parse update response
     */
    public function parse_update_response($content) {
        $result = [
            'updated_content' => '',
            'changes' => [],
            'suggestions' => []
        ];
        
        // Extract JSON if present
        $json = $this->extract_json($content);
        
        if ($json) {
            $result['updated_content'] = $json['updatedContent'] ?? '';
            $result['changes'] = $json['changes'] ?? [];
            $result['suggestions'] = $json['suggestions'] ?? [];
        } else {
            $result['updated_content'] = $content;
        }
        
        return $result;
    }
}
