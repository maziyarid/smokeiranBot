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
        
        // Clean JSON blocks from content first
        $cleaned_content = $this->remove_json_block($raw_content);
        
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
        $this->extract_sections($cleaned_content, $parsed);
        
        // Build full content
        $parsed['full_content'] = $this->build_full_content($cleaned_content);
        
        // Generate fallback HTML if full_content is empty or very short
        if (empty($parsed['full_content']) || strlen($parsed['full_content']) < 100) {
            $parsed['full_content'] = $this->generate_fallback_html($raw_content, $parsed);
        }
        
        // Extract SEO meta if not in JSON
        $this->extract_seo_meta($cleaned_content, $parsed);
        
        // Auto-extract title from HTML if missing
        if (empty($parsed['h1_title'])) {
            $parsed['h1_title'] = $this->extract_title_from_html($parsed['full_content']);
        }
        
        // Auto-generate slug if missing
        if (empty($parsed['slug']) && !empty($parsed['h1_title'])) {
            $parsed['slug'] = $this->generate_slug($parsed['h1_title']);
        }
        
        // Parse FAQ if not array
        if (empty($parsed['faq']) || !is_array($parsed['faq'])) {
            $parsed['faq'] = $this->extract_faq($cleaned_content);
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
     * Build full content (excluding JSON and meta sections)
     */
    private function build_full_content($content) {
        // Remove JSON block
        $content = preg_replace('/```json[\s\S]*?```/m', '', $content);
        
        // Try to extract section 4 onwards (HTML content section)
        // Match from بخش ۴ to the end, but stop at بخش following sections that aren't part of HTML
        if (preg_match('/##?\s*بخش\s*[۴4][:\s].*?(?:کد HTML|محتوای اصلی|HTML).*?\n+([\s\S]+?)(?=\n##?\s*بخش\s*[۵۶۷۸۹5-9](?:[:\s]|$)|$)/u', $content, $match)) {
            $html_content = trim($match[1]);
            
            // Clean up malformed HTML - fix broken style attributes
            $html_content = preg_replace('/;direction:\s*rtl;[^"]*?">/u', '; direction: rtl; text-align: right;">', $html_content);
            $html_content = preg_replace('/(?<!")style="[^"]*$/m', '', $html_content); // Remove incomplete style attributes
            
            return $html_content;
        }
        
        // Fallback: Extract everything after section 4 marker
        if (preg_match('/##?\s*بخش\s*[۴4].*?\n+([\s\S]+)/u', $content, $match)) {
            return trim($match[1]);
        }
        
        // Last fallback: Remove first 3 meta sections
        $content = preg_replace('/^##?\s*بخش\s*[۱۲۳123].*?\n+.*?(?=\n##?\s*بخش\s*[۴4]|\z)/ums', '', $content);
        
        return trim($content);
    }
    
    /**
     * Extract SEO meta from content
     */
    private function extract_seo_meta($content, &$parsed) {
        // H1 Title - try multiple patterns
        if (empty($parsed['h1_title'])) {
            // Pattern 1: بخش ۱: عنوان محصول (H1) - capture title on same or next line
            if (preg_match('/##?\s*بخش\s*[۱1][:\s].*?(?:عنوان|H1).*?\n+([^\n]+?)(?:\n|$)/u', $content, $match)) {
                $title = trim($match[1]);
                // Remove "---" separators if present
                $title = preg_replace('/^-+\s*/', '', $title);
                $title = preg_replace('/\s*-+$/', '', $title);
                if (!empty($title) && $title !== '---') {
                    $parsed['h1_title'] = $title;
                }
            }
            // Pattern 2: After "عنوان محصول (H1)" label
            if (empty($parsed['h1_title']) && preg_match('/عنوان محصول\s*\(H1\)\s*\n+([^\n]+)/u', $content, $match)) {
                $parsed['h1_title'] = trim($match[1]);
            }
            // Pattern 3: Simple markdown heading at start
            if (empty($parsed['h1_title']) && preg_match('/^#\s+([^\n]+)$/m', $content, $match)) {
                $parsed['h1_title'] = trim($match[1]);
            }
        }
        
        // Slug - try multiple patterns
        if (empty($parsed['slug'])) {
            // Pattern 1: بخش ۲: پیوند یکتا
            if (preg_match('/##?\s*بخش\s*[۲2][:\s].*?(?:پیوند|Slug).*?\n+([a-z0-9\-]+)(?:\n|$)/ui', $content, $match)) {
                $slug = strtolower(trim($match[1]));
                // Remove "---" if captured
                $slug = preg_replace('/^-+/', '', $slug);
                $slug = preg_replace('/-+$/', '', $slug);
                if (!empty($slug) && $slug !== '---' && strlen($slug) > 3) {
                    $parsed['slug'] = $slug;
                }
            }
            // Pattern 2: After label
            if (empty($parsed['slug']) && preg_match('/(?:پیوند یکتا|Slug)\s*\n+([a-z0-9\-]+)/ui', $content, $match)) {
                $parsed['slug'] = strtolower(trim($match[1]));
            }
        }
        
        // Short Description - try multiple patterns with strict boundaries
        if (empty($parsed['short_description'])) {
            // Pattern 1: بخش ۳: توضیح کوتاه - stop BEFORE بخش ۴ or ---
            if (preg_match('/##?\s*بخش\s*[۳3][:\s].*?(?:توضیح|Short).*?\n+(.+?)(?=\n---\s*\n##?\s*بخش|\n##?\s*بخش\s*[۴4]|\z)/us', $content, $match)) {
                $short_desc = trim($match[1]);
                // Remove separator lines
                $short_desc = preg_replace('/^-+\s*/', '', $short_desc);
                $short_desc = preg_replace('/\s*-+$/', '', $short_desc);
                // Limit to reasonable length (max 300 chars for short description)
                if (strlen($short_desc) > 300) {
                    $short_desc = mb_substr($short_desc, 0, 297) . '...';
                }
                if (!empty($short_desc) && $short_desc !== '---') {
                    $parsed['short_description'] = $short_desc;
                }
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
     * Remove JSON block from content
     */
    private function remove_json_block($content) {
        // Remove JSON code blocks
        $content = preg_replace('/```json[\s\S]*?```/m', '', $content);
        
        // Remove trailing JSON objects
        $content = $this->remove_trailing_json_object($content);
        
        return $content;
    }
    
    /**
     * Remove trailing JSON object
     */
    private function remove_trailing_json_object($content) {
        // Pattern to match a trailing JSON object
        $pattern = '/\{[\s\S]*"(?:product|seo|content|customFields)"[\s\S]*\}\s*$/m';
        $content = preg_replace($pattern, '', $content);
        
        return trim($content);
    }
    
    /**
     * Generate fallback HTML from content
     * 3-tier fallback system:
     * 1. Structured data → Beautiful HTML
     * 2. Plain text extraction → Formatted paragraphs
     * 3. Warning message → Styled notice
     */
    private function generate_fallback_html($raw_content, $parsed) {
        // Tier 1: Try to generate from structured data
        if (!empty($parsed['json_data'])) {
            $html = $this->generate_html_from_json($parsed['json_data']);
            if (!empty($html)) {
                return $html;
            }
        }
        
        // Tier 2: Extract and format plain text
        $text_content = $this->extract_plain_text($raw_content);
        if (!empty($text_content) && strlen($text_content) > 100) {
            return $this->format_plain_text_to_html($text_content);
        }
        
        // Tier 3: Warning message
        return $this->generate_warning_html();
    }
    
    /**
     * Generate HTML from JSON data
     */
    private function generate_html_from_json($json_data) {
        $html = '';
        
        // Extract product name
        $product_name = $json_data['product']['name'] ?? 'محصول';
        
        // Start with hero section
        $html .= '<div class="sir-product-content" style="font-family: \'IRANSans\', Tahoma, Arial, sans-serif; direction: rtl; text-align: right; line-height: 2;">';
        
        // Add introduction if available
        if (!empty($json_data['content']['introduction'])) {
            $html .= '<div style="margin-bottom: 25px;">';
            $html .= '<h2>معرفی محصول</h2>';
            $html .= '<p>' . nl2br(htmlspecialchars($json_data['content']['introduction'])) . '</p>';
            $html .= '</div>';
        }
        
        // Add features if available
        if (!empty($json_data['content']['features']) && is_array($json_data['content']['features'])) {
            $html .= '<div style="margin-bottom: 25px;">';
            $html .= '<h2>ویژگی‌های کلیدی</h2>';
            $html .= '<ul>';
            foreach ($json_data['content']['features'] as $feature) {
                $html .= '<li>' . htmlspecialchars($feature) . '</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return strlen($html) > 200 ? $html : '';
    }
    
    /**
     * Extract plain text from raw content
     */
    private function extract_plain_text($content) {
        // Remove JSON blocks
        $content = preg_replace('/```json[\s\S]*?```/m', '', $content);
        
        // Remove markdown headers but keep content
        $content = preg_replace('/^#{1,6}\s+/m', '', $content);
        
        // Remove special markers
        $content = preg_replace('/^---+$/m', '', $content);
        
        return trim($content);
    }
    
    /**
     * Format plain text to HTML
     */
    private function format_plain_text_to_html($text) {
        // Split into paragraphs
        $paragraphs = explode("\n\n", $text);
        
        $html = '<div class="sir-product-content" style="font-family: \'IRANSans\', Tahoma, Arial, sans-serif; direction: rtl; text-align: right; line-height: 2;">';
        
        foreach ($paragraphs as $para) {
            $para = trim($para);
            if (!empty($para)) {
                $html .= '<p>' . nl2br(htmlspecialchars($para)) . '</p>';
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate warning HTML
     */
    private function generate_warning_html() {
        return '<div style="background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 20px; margin: 20px 0; direction: rtl; text-align: right;">' .
               '<p style="margin: 0; color: #856404;"><strong>⚠️ هشدار:</strong> محتوای تولید شده به درستی تجزیه نشد. لطفاً محتوا را به صورت دستی بررسی و ویرایش کنید.</p>' .
               '</div>';
    }
    
    /**
     * Extract title from HTML content
     */
    private function extract_title_from_html($html) {
        // Try h1 tags
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $html, $match)) {
            return strip_tags($match[1]);
        }
        
        // Try first heading
        if (preg_match('/<h[2-6][^>]*>(.*?)<\/h[2-6]>/i', $html, $match)) {
            return strip_tags($match[1]);
        }
        
        return '';
    }
    
    /**
     * Generate slug from title
     */
    private function generate_slug($title) {
        // Remove special characters and convert to lowercase
        $slug = preg_replace('/[^a-z0-9\s-]/i', '', $title);
        $slug = preg_replace('/\s+/', '-', trim($slug));
        $slug = strtolower($slug);
        
        // Limit length
        if (strlen($slug) > 200) {
            $slug = substr($slug, 0, 200);
        }
        
        return $slug;
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
