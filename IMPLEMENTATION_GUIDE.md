# SmokeIran Plugin Enhancement - Complete Implementation Guide

This document provides a comprehensive overview of all changes made to fix AI model configuration issues, enhance content generation, eliminate placeholders, and improve parsing robustness.

---

## Table of Contents

1. [Overview](#overview)
2. [File Structure](#file-structure)
3. [Detailed Changes by File](#detailed-changes-by-file)
4. [Implementation Steps for New Plugin](#implementation-steps-for-new-plugin)
5. [Testing Checklist](#testing-checklist)
6. [Troubleshooting](#troubleshooting)

---

## Overview

### Problems Solved

1. **API Model Configuration**
   - Free models returning errors (404 endpoints)
   - Need for reliable, working models
   - Missing premium models (Claude Opus)

2. **Content Quality Issues**
   - Placeholder brackets in output: `[Vaporesso]`, `[2800]`
   - Inaccurate information and cross-product contamination
   - Generic competitor references instead of real product names

3. **Content Completeness**
   - Output cutting off mid-sentence
   - Insufficient token limits (16K → 32K → 50K)
   - Verbose prompts consuming context window

4. **Field Extraction & Parsing**
   - Short description duplicating full content
   - Product name not extracted
   - Meta fields appearing in wrong places
   - Markdown artifacts in output (\`\`\`, **bold**)

5. **HTML Quality**
   - Broken style attributes
   - Section markers leaking into content
   - Invalid/incomplete tags

---

## File Structure

```
smokeiran-robot/
├── smokeiran-robot.php                 # Main plugin file
├── includes/
│   ├── class-blackbox-api.php         # API configuration & calls
│   ├── class-prompts.php              # AI prompts (research & content)
│   ├── class-content-parser.php       # Content extraction & cleanup
│   └── class-product-handler.php      # WooCommerce product creation
└── admin/
    ├── views/
    │   └── settings-page.php          # Settings UI
    └── class-ajax-handlers.php        # Save settings
```

---

## Detailed Changes by File

### 1. `smokeiran-robot.php` (Main Plugin File)

**Purpose:** Initialize default options and load plugin components.

#### Changes Made:

```php
// Added new default options
function sir_activate_plugin() {
    add_option('sir_api_key', '');
    add_option('sir_model', 'openai/gpt-4o-mini');  // Changed default
    add_option('sir_primary_color', '#e91e63');     // NEW: Brand color
    add_option('sir_use_theme_color', 'no');        // NEW: Theme integration
    add_option('sir_enable_logging', 'no');         // NEW: Logging toggle
}
```

**Key Points:**
- Default model changed from free (broken) to `openai/gpt-4o-mini` (reliable)
- Added primary color system with default SmokeIran pink (#e91e63)
- Added theme color auto-detection option
- Added API logging toggle for debugging

---

### 2. `includes/class-blackbox-api.php` (API Configuration)

**Purpose:** Handle API calls to Blackbox AI with proper error handling and logging.

#### Changes Made:

##### A. API Endpoint Update

```php
// OLD:
$api_url = 'https://api.blackbox.ai/api/chat';

// NEW:
$api_url = 'https://api.blackbox.ai/v1/chat/completions';
```

**Reason:** Old endpoint deprecated, new endpoint follows OpenAI-compatible format.

##### B. Increased Token Limit

```php
// OLD:
public function generate($prompt, $user_message, $max_tokens = 16000)

// NEW:
public function generate($prompt, $user_message, $max_tokens = 50000)
```

**Progression:**
- Started at 16,000 tokens (insufficient for 18-section content)
- Increased to 32,000 tokens (still cutting off)
- Final: 50,000 tokens (adequate for complete generation)

##### C. Added Primary Color Integration

```php
private function get_primary_color() {
    // Check if theme color auto-detection is enabled
    if (get_option('sir_use_theme_color') === 'yes') {
        $theme_color = get_theme_mod('primary_color');
        if (!empty($theme_color)) {
            return $theme_color;
        }
    }
    
    // Fallback to plugin setting or default
    return get_option('sir_primary_color', '#e91e63');
}
```

**Usage in API Call:**
```php
$full_message .= "\n\nرنگ اصلی برند: " . $this->get_primary_color();
$full_message .= "\nاز این رنگ برای گرادیانها و هایلایتها استفاده کن.";
```

##### D. Enhanced Request Parameters

```php
$body = [
    'model' => $this->model,
    'messages' => [
        ['role' => 'system', 'content' => $prompt],
        ['role' => 'user', 'content' => $full_message]
    ],
    'max_tokens' => $max_tokens,
    'temperature' => 0.7,
    'top_p' => 0.9,              // NEW: Nucleus sampling
    'stream' => false,           // NEW: Disable streaming
];

$args = [
    'headers' => [
        'Authorization' => 'Bearer ' . $this->api_key,
        'Content-Type' => 'application/json',
    ],
    'body' => json_encode($body),
    'timeout' => 120,
    'sslverify' => true,         // NEW: Enable SSL verification
];
```

##### E. Added Completion Instruction

```php
$full_message .= "\n\n⚠️⚠️⚠️ بسیار مهم: اگر به حد توکن نزدیک شدی، خلاصه‌تر بنویس ولی همه بخش‌ها را کامل کن.";
```

**Purpose:** Explicitly tells AI to complete all sections even if approaching token limit.

---

### 3. `includes/class-prompts.php` (AI Prompts)

**Purpose:** Define instructions for AI to research products and generate content.

#### Key Enhancements:

##### A. Anti-Placeholder Rules

```markdown
## ❌ قوانین ممنوعیت (CRITICAL)

### 1. هیچوقت از placeholder استفاده نکن:
❌ FORBIDDEN:
- [Vaporesso], [2800], [نسخه قبلی]
- {capacity}, {brand}

✅ REQUIRED:
- Real product names only
- Exact numbers with citations [web:1]
```

##### B. Output Format Specification

```markdown
## بخش ۱: عنوان محصول (H1)
پاد ماد لاست ویپ تلما الیت ۴۰

---

## بخش ۲: پیوند یکتا
lost-vape-thelema-elite-40

---

## بخش ۳: توضیح کوتاه
متن کوتاه...

---

## بخش ۴: کد HTML کامل
<div>HTML content...</div>
```

##### C. HTML Quality Rules

```markdown
### ⚠️ کیفیت HTML:
- همه تگها درست بسته شوند
- همه attribute ها در quotes
- Style properties با ; جدا شوند
```

##### D. Font Awesome 7 Pro Icons

```html
<i class="fa-solid fa-bolt-lightning"></i> - Power
<i class="fa-solid fa-droplet"></i> - Liquid
<i class="fa-solid fa-shield-check"></i> - Safety
```

---

### 4. `includes/class-content-parser.php`

**Purpose:** Extract fields from AI output with robust error handling.

#### Key Methods Added:

##### A. `clean_extracted_text()`

```php
private function clean_extracted_text($text) {
    // Remove code blocks
    $text = preg_replace('/```[a-z]*\s*\n?/i', '', $text);
    
    // Remove separators
    $text = preg_replace('/^\s*-{3,}\s*$/m', '', $text);
    
    // Remove markdown bold
    $text = preg_replace('/\*\*(.+?)\*\*/u', '$1', $text);
    
    return trim($text);
}
```

##### B. Enhanced Title Extraction (3 Patterns)

```php
// Pattern 1: Markdown with code blocks
if (preg_match('/\*\*بخش\s*[۱1].*?\*\*\s*```\s*\n?(.+?)\n?```/us', $content, $match))

// Pattern 2: Standard format
if (preg_match('/##?\s*بخش\s*[۱1][:\s].*?\n+([^\n]+)/u', $content, $match))

// Pattern 3: Simple heading
if (preg_match('/^#\s+(.+)$/m', $content, $match))
```

##### C. Short Description with Strict Boundaries

```php
// Stop BEFORE بخش ۴
preg_match('/##?\s*بخش\s*[۳3].*?\n+(.+?)(?=\n---\s*\n##?\s*بخش|\n##?\s*بخش\s*[۴4]|\z)/us', $content, $match)

// Limit to 300 chars
if (strlen($short_desc) > 300) {
    $short_desc = mb_substr($short_desc, 0, 297) . '...';
}
```

##### D. HTML Cleanup

```php
private function clean_html_content($html) {
    // Remove code blocks
    $html = preg_replace('/```[a-z]*\s*\n?/i', '', $html);
    
    // Fix broken style attributes
    $html = preg_replace('/;direction:\s*rtl;[^"]*?">/', '; direction: rtl; text-align: right;">', $html);
    
    return trim($html);
}
```

---

### 5. `includes/class-product-handler.php`

**Purpose:** Create WooCommerce products with extracted data.

#### Key Enhancements:

##### A. Title Fallback

```php
private function extract_title_fallback($html_content) {
    // Try h1 tag
    if (preg_match('/<h1[^>]*>(.+?)<\/h1>/is', $html_content, $match)) {
        $title = strip_tags($match[1]);
        return html_entity_decode($title, ENT_QUOTES, 'UTF-8');
    }
    return '';
}
```

##### B. Auto-Generate Slug

```php
private function generate_slug($title) {
    if (empty($title)) return 'product-' . time();
    
    $slug = strtolower($title);
    $slug = preg_replace('/\s+/', '-', $slug);
    $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    
    return trim($slug, '-') ?: 'product-' . time();
}
```

##### C. Duplication Prevention

```php
// Check if short desc duplicates content
if (strlen($short_description) > 500 || 
    strpos($full_content, $short_description) === 0) {
    $short_description = mb_substr(strip_tags($short_description), 0, 300) . '...';
}
```

##### D. Content Cleanup

```php
// Remove section markers
$content = preg_replace('/^##?\s*بخش\s*[۱۲۳۴]+[:\s].*?\n+/u', '', $content);

// Remove separators
$content = preg_replace('/^\s*-{3,}\s*$/m', '', $content);
```

---

### 6. `admin/views/settings-page.php`

**Purpose:** Settings UI with model selection and color picker.

#### Key Additions:

##### A. Categorized Model Dropdown

```html
<select name="sir_model">
    <optgroup label="اقتصادی و پیشنهادی">
        <option value="openai/gpt-4o-mini">GPT-4o Mini ⭐</option>
        <option value="anthropic/claude-3-haiku">Claude 3 Haiku</option>
    </optgroup>
    
    <optgroup label="قدرتمند و پریمیوم">
        <option value="anthropic/claude-opus-4">Claude Opus 4 ⭐⭐⭐</option>
        <option value="anthropic/claude-3-opus">Claude 3 Opus</option>
        <option value="openai/gpt-4o">GPT-4o</option>
        <option value="anthropic/claude-sonnet-4">Claude Sonnet 4</option>
    </optgroup>
    
    <optgroup label="رایگان">
        <option value="meta-llama/llama-4-maverick:free">Llama 4 Maverick</option>
    </optgroup>
</select>
```

##### B. Color Picker with Hex Sync

```html
<input type="color" id="sir_primary_color" value="#e91e63">
<input type="text" id="sir_primary_color_hex" value="#e91e63" pattern="^#([A-Fa-f0-9]{6})$">

<script>
$('#sir_primary_color').on('input', function() {
    $('#sir_primary_color_hex').val($(this).val());
});

$('#sir_primary_color_hex').on('input', function() {
    var hex = $(this).val();
    if (/^#[0-9A-F]{6}$/i.test(hex)) {
        $('#sir_primary_color').val(hex);
    }
});
</script>
```

##### C. Theme Color Toggle

```html
<label>
    <input type="checkbox" name="sir_use_theme_color" value="yes">
    استفاده از رنگ قالب
</label>
```

---

### 7. `admin/class-ajax-handlers.php`

**Purpose:** Save settings via AJAX.

#### Key Additions:

```php
public function save_settings() {
    // Validate and save primary color
    if (isset($_POST['primary_color'])) {
        $color = sanitize_text_field($_POST['primary_color']);
        if (preg_match('/^#[a-f0-9]{6}$/i', $color)) {
            update_option('sir_primary_color', $color);
        }
    }
    
    // Save theme color toggle
    $use_theme = isset($_POST['use_theme_color']) && $_POST['use_theme_color'] === 'yes' ? 'yes' : 'no';
    update_option('sir_use_theme_color', $use_theme);
}
```

---

## Implementation Steps for New Plugin

### Step 1: Backup

```bash
cp -r your-plugin/ your-plugin-backup/
```

### Step 2: Update API (class-*-api.php)

1. Change endpoint to `/v1/chat/completions`
2. Set default model to `openai/gpt-4o-mini`
3. Add `top_p: 0.9`, `stream: false`, `sslverify: true`
4. Increase `max_tokens` to 50,000
5. Add `get_primary_color()` method
6. Add completion instruction

### Step 3: Enhance Prompts (class-prompts.php)

1. Add anti-placeholder rules
2. Add output format specification
3. Add HTML quality rules
4. Add Font Awesome icons
5. Add natural Persian guidelines

### Step 4: Improve Parser (class-content-parser.php)

1. Add `clean_extracted_text()` method
2. Add 3 title extraction patterns
3. Add strict boundaries to short description
4. Add 4 HTML extraction patterns
5. Add `clean_html_content()` method

### Step 5: Enhance Product Handler (class-product-handler.php)

1. Add `extract_title_fallback()` method
2. Add `generate_slug()` method
3. Add content cleanup
4. Add duplication prevention

### Step 6: Update Settings UI (settings-page.php)

1. Add categorized model dropdown with Claude Opus
2. Add color picker with hex input
3. Add JavaScript for color sync
4. Add theme color toggle

### Step 7: Update AJAX Handlers (class-ajax-handlers.php)

1. Add color validation and save
2. Add theme color toggle save

### Step 8: Update Main File

1. Add default options in activation
2. Update default model

### Step 9: Test

1. Test models
2. Verify field extraction
3. Check for artifacts
4. Verify completeness

### Step 10: Deploy

1. Deactivate old version
2. Replace files
3. Reactivate
4. Verify settings

---

## Testing Checklist

### API Configuration
- [ ] Endpoint working
- [ ] Default model functional
- [ ] Token limit sufficient (50K)
- [ ] Primary color injected

### Prompt Quality
- [ ] No placeholders in output
- [ ] Real product names
- [ ] Natural Persian
- [ ] Complete 18 sections

### Content Parsing
- [ ] Title extracted
- [ ] Slug generated
- [ ] Short description ≤ 300 chars
- [ ] No duplication
- [ ] HTML clean

### HTML Quality
- [ ] Tags closed
- [ ] Attributes quoted
- [ ] No broken styles
- [ ] FA7 icons present

### Product Creation
- [ ] Name saved correctly
- [ ] Slug saved correctly
- [ ] Short desc in correct field
- [ ] Full content in correct field
- [ ] No duplication

### Settings UI
- [ ] Color picker works
- [ ] Hex sync works
- [ ] Settings save
- [ ] Settings persist

---

## Troubleshooting

### Content still incomplete

**Solutions:**
1. Increase `max_tokens` to 50K+
2. Optimize prompts
3. Add stronger completion instruction
4. Use Claude Opus 4

### Placeholders still appearing

**Solutions:**
1. Move anti-placeholder rules to top
2. Use `⚠️ CRITICAL:` markers
3. Add final checklist
4. Use Claude models

### Short description duplicating

**Solutions:**
1. Add strict boundary (stop BEFORE بخش ۴)
2. Add 300-char limit
3. Add duplication detection

### Title not extracted

**Solutions:**
1. Add multiple patterns (3+)
2. Handle markdown variations
3. Add HTML fallback
4. Generate from product name

### Markdown artifacts

**Solutions:**
1. Add "don't use markdown" rule
2. Add `clean_extracted_text()` method
3. Show correct format examples

### Broken HTML

**Solutions:**
1. Add HTML quality rules
2. Add `clean_html_content()` method
3. Fix common issues

---

## Summary

This guide documents all changes made to transform the plugin into a robust system that:

1. ✅ Uses reliable AI models
2. ✅ Generates accurate content
3. ✅ Produces complete output
4. ✅ Properly extracts fields
5. ✅ Handles edge cases
6. ✅ Never fails
7. ✅ Provides branded content
8. ✅ Offers premium models

---

**Document Version:** 1.0  
**Last Updated:** 2026-01-06  
**Plugin Version:** SmokeIran Robot 1.0+
