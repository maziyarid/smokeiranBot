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
        
        // Preserve FSP shortcodes in content
        $raw_content = $this->preserve_fsp_shortcodes($raw_content);
        
        $parsed = [
            // SEO Fields
            'h1_title' => '',
            'slug' => '',
            'meta_title' => '',
            'meta_description' => '',
            
            // Content Fields
            'short_description' => '',
            'full_content' => '',
            
            // Sections (expanded to 22 sections)
            'introduction' => '',
            'trust_badges' => '',
            'problems_solutions' => '',
            'key_features' => '',
            'technical_specs' => '',
            'usage_guide' => '',
            'video' => '',
            'maintenance' => '',
            'comparison' => '',
            'variants' => '',
            'pros_cons' => '',
            'testimonials' => '',
            'brand_story' => '',
            'warranty' => '',
            'faq' => '',
            'cta' => '',
            'alt_texts' => '',
            'internal_links' => '',
            'social_content' => '',
            
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
        
        // Build full content
        $parsed['full_content'] = $this->build_full_content($raw_content);
        
        // Extract SEO meta if not in JSON
        $this->extract_seo_meta($raw_content, $parsed);
        
        // Parse FAQ if not array
        if (empty($parsed['faq']) || !is_array($parsed['faq'])) {
            $parsed['faq'] = $this->extract_faq($raw_content);
        }
        
        return $parsed;
    }
    
    /**
     * Preserve FSP shortcodes in content
     */
    private function preserve_fsp_shortcodes($content) {
        // FSP shortcodes should remain intact in the content
        // We don't need to escape them as they'll be processed by WordPress
        
        // List of all FSP shortcodes to preserve
        $fsp_shortcodes = [
            'fsp_info',
            'fsp_features', 'fsp_feature',
            'fsp_highlight',
            'fsp_accordion', 'fsp_accordion_item',
            'fsp_columns', 'fsp_column',
            'fsp_cta',
            'fsp_button',
            'fsp_badge',
            'fsp_gallery',
            'fsp_video',
            'fsp_specs', 'fsp_spec',
            'fsp_faq', 'fsp_faq_item',
            'fsp_comparison', 'fsp_compare_row',
            'fsp_testimonial',
            'fsp_countdown',
            'fsp_tabs', 'fsp_tab',
            'fsp_trust', 'fsp_trust_item'
        ];
        
        // The content should already contain proper FSP shortcodes
        // Just ensure they're not HTML-escaped if they were
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $content;
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
            // New 22-section structure
            'introduction' => '/## (?:معرفی محصول|بخش ۴).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'trust_badges' => '/## (?:نشان‌های اعتماد|بخش ۳).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'problems_solutions' => '/## (?:مشکلات کاربر|بخش ۶).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'key_features' => '/## (?:ویژگی‌های کلیدی|بخش ۷).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'technical_specs' => '/## (?:مشخصات فنی|بخش ۸).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'usage_guide' => '/## (?:نحوه استفاده|بخش ۹).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'video' => '/## (?:ویدیو|بخش ۱۰).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'maintenance' => '/## (?:نکات نگهداری|بخش ۱۱).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'comparison' => '/## (?:مقایسه با رقبا|بخش ۱۲).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'variants' => '/## (?:طعم‌ها|رنگ‌ها|مدل‌ها|بخش ۱۳).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'pros_cons' => '/## (?:نقاط قوت و ضعف|بخش ۱۴).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'testimonials' => '/## (?:نظرات|بخش ۱۵).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'brand_story' => '/## (?:داستان برند|بخش ۱۶).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'warranty' => '/## (?:گارانتی|بخش ۱۷).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'faq' => '/## (?:سوالات متداول|بخش ۱۸).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'cta' => '/## (?:فراخوان|بخش ۱۹).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'alt_texts' => '/## (?:متن جایگزین|بخش ۲۰).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'internal_links' => '/## (?:لینک‌سازی|بخش ۲۱).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
            'social_content' => '/## (?:کپشن|بخش ۲۲).*?\n([\s\S]*?)(?=\n## |\n---|\z)/u',
        ];
        
        foreach ($section_patterns as $key => $pattern) {
            if (preg_match($pattern, $content, $match)) {
                $parsed[$key] = trim($match[1]);
            }
        }
    }
    
    /**
     * Build full content (excluding JSON)
     */
    private function build_full_content($content) {
        // Remove JSON block
        $content = preg_replace('/```json[\s\S]*?```/m', '', $content);
        
        // Remove ALL "بخش X:" markers (AI-generated section markers)
        // This removes lines like "بخش ۱:", "בخش ۲:", "بخش ۱۰:", etc.
        $content = preg_replace('/^[\s]*بخش\s*[\d۰-۹]+\s*[:：].*/mu', '', $content);
        $content = preg_replace('/^[\s]*###\s*بخش\s*[\d۰-۹]+\s*[:：].*/mu', '', $content);
        $content = preg_replace('/^[\s]*##\s*بخش\s*[\d۰-۹]+\s*[:：].*/mu', '', $content);
        
        // Remove "متادیتای SEO" section header
        $content = preg_replace('/^[\s]*##\s*(?:بخش\s*[\d۰-۹]+\s*[:：]\s*)?متادیتای\s*SEO.*/mu', '', $content);
        
        // Remove standalone metadata lines (not in proper HTML format)
        $content = preg_replace('/^[\s]*[-–—]\s*(?:عنوان صفحه|پیوند یکتا|متا تایتل|متا دسکریپشن).*$/mu', '', $content);
        
        // Convert markdown headers to HTML while preserving FSP shortcodes
        // H2 headers
        $content = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $content);
        
        // H3 headers
        $content = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $content);
        
        // H4 headers  
        $content = preg_replace('/^#### (.+)$/m', '<h4>$1</h4>', $content);
        
        // Convert markdown bold to HTML (but not inside shortcodes)
        $content = preg_replace('/\*\*([^*\[]+)\*\*/u', '<strong>$1</strong>', $content);
        
        // Convert markdown lists to HTML
        $content = $this->convert_markdown_lists($content);
        
        // Clean up excessive newlines while preserving shortcodes
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        // Wrap paragraphs (but not shortcodes or HTML tags)
        $content = $this->wrap_paragraphs($content);
        
        return trim($content);
    }
    
    /**
     * Convert markdown lists to HTML
     */
    private function convert_markdown_lists($content) {
        // Unordered lists
        $content = preg_replace_callback(
            '/((?:^[\*\-] .+\n)+)/m',
            function($matches) {
                $items = preg_split('/\n/', trim($matches[1]));
                $html = "<ul>\n";
                foreach ($items as $item) {
                    if (preg_match('/^[\*\-] (.+)$/', $item, $m)) {
                        $html .= "<li>" . trim($m[1]) . "</li>\n";
                    }
                }
                $html .= "</ul>\n";
                return $html;
            },
            $content
        );
        
        // Ordered lists
        $content = preg_replace_callback(
            '/((?:^\d+\. .+\n)+)/m',
            function($matches) {
                $items = preg_split('/\n/', trim($matches[1]));
                $html = "<ol>\n";
                foreach ($items as $item) {
                    if (preg_match('/^\d+\. (.+)$/', $item, $m)) {
                        $html .= "<li>" . trim($m[1]) . "</li>\n";
                    }
                }
                $html .= "</ol>\n";
                return $html;
            },
            $content
        );
        
        return $content;
    }
    
    /**
     * Wrap paragraphs in <p> tags
     */
    private function wrap_paragraphs($content) {
        // Split by double newlines
        $blocks = preg_split('/\n\n+/', $content);
        $output = [];
        
        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) continue;
            
            // Don't wrap if it's already HTML, a shortcode, or a heading
            if (preg_match('/^<[^>]+>/', $block) || 
                preg_match('/^\[fsp_/', $block) ||
                preg_match('/^<h[1-6]>/', $block) ||
                preg_match('/^<table/', $block)) {
                $output[] = $block;
            } else {
                // Wrap as paragraph
                $output[] = '<p>' . $block . '</p>';
            }
        }
        
        return implode("\n\n", $output);
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
        
        // Pattern 1: FSP FAQ shortcode format
        if (preg_match_all('/\[fsp_faq_item question="(.+?)"\]([\s\S]*?)\[\/fsp_faq_item\]/u', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $faqs[] = [
                    'question' => trim($match[1]),
                    'answer' => trim(strip_tags($match[2]))
                ];
            }
            return $faqs;
        }
        
        // Pattern 2: Q: / A: format
        preg_match_all('/Q:\s*(.+?)\s*\nA:\s*(.+?)(?=\nQ:|\n\n##|\z)/us', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $faqs[] = [
                'question' => trim($match[1]),
                'answer' => trim($match[2])
            ];
        }
        
        // Pattern 3: **سوال** / پاسخ format
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
