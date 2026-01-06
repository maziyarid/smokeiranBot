# SmokeIran Plugin - Changes Summary

Quick reference of all changes made across 7 commits.

## Commits Overview

1. **7887e05** - Initial: API config, color system, enhanced prompts
2. **0bf6a59** - Fix placeholders with anti-placeholder rules
3. **db6d5a9** - Increase tokens to 32K, optimize prompts
4. **74b2f19** - Increase tokens to 50K, fix parser patterns
5. **1e04776** - Fix duplication, improve title extraction, clean HTML
6. **ae43295** - Add Claude Opus, fix markdown artifacts
7. **Current** - Documentation

---

## Files Modified (7 files)

### 1. `smokeiran-robot.php`
- Added default options: `sir_primary_color`, `sir_use_theme_color`, `sir_enable_logging`
- Changed default model to `openai/gpt-4o-mini`

### 2. `includes/class-blackbox-api.php`
- ✅ Endpoint: `https://api.blackbox.ai/v1/chat/completions`
- ✅ Default model: `openai/gpt-4o-mini`
- ✅ max_tokens: 16K → 32K → 50K
- ✅ Added: `top_p: 0.9`, `stream: false`, `sslverify: true`
- ✅ Added: `get_primary_color()` method
- ✅ Added: Logging system (request/response/error)
- ✅ Added: Completion instruction

### 3. `includes/class-prompts.php`
- ✅ Research Prompt:
  - Anti-placeholder rules (❌ `[...]`, ✅ real names)
  - Box contents accuracy (exact model/resistance)
  - Competitor naming (real product names)
  - Final 8-point checklist
- ✅ Content Prompt:
  - Output format specification
  - HTML quality rules
  - Font Awesome 7 Pro icons
  - Primary color integration
  - Natural Persian guidelines
  - 18-section structure

### 4. `includes/class-content-parser.php`
- ✅ Added: `clean_extracted_text()` - removes artifacts
- ✅ Title extraction: 3 patterns (markdown/standard/simple)
- ✅ Short description: strict boundaries, 300-char limit
- ✅ HTML extraction: 4 patterns (handles markdown/standard)
- ✅ Added: `clean_html_content()` - fixes broken attributes
- ✅ 3-tier fallback system

### 5. `includes/class-product-handler.php`
- ✅ Added: Parsing diagnostics logging
- ✅ Added: `extract_title_fallback()` - extracts from HTML
- ✅ Added: `generate_slug()` - auto-generates from title
- ✅ Content cleanup: removes section markers and separators
- ✅ Duplication prevention: checks short desc vs full content
- ✅ Never fails: always creates product with fallbacks

### 6. `admin/views/settings-page.php`
- ✅ Model dropdown:
  - Affordable: GPT-4o Mini ⭐, Claude Haiku, Gemini Flash
  - Premium: Claude Opus 4 ⭐⭐⭐, Claude 3 Opus, GPT-4o, Claude Sonnet 4
  - Free: Llama 4 Maverick
  - Legacy: Old models (may not work)
- ✅ Color picker: visual + hex input with sync
- ✅ Theme color toggle: auto-detect from WordPress theme
- ✅ Help text: Tavily alternative (Blackbox search)

### 7. `admin/class-ajax-handlers.php`
- ✅ Save primary_color with hex validation
- ✅ Save use_theme_color toggle
- ✅ Save enable_logging toggle

---

## Key Features Added

### 1. Anti-Placeholder System
```
❌ Forbidden: [Vaporesso], [2800], [نسخه قبلی]
✅ Required: Real names, exact numbers, citations [web:X]
```

### 2. Complete Output Generation
- Token limit: 50,000
- Completion instruction added
- Prompts optimized (25KB → 20KB)

### 3. Robust Field Extraction
- 3 title patterns + HTML fallback
- Short description with strict boundaries (≤300 chars)
- 4 HTML extraction patterns
- Markdown artifact removal

### 4. HTML Quality Enforcement
```html
✅ Valid: <div style="direction: rtl; color: #333;">
❌ Invalid: ...sans-serif;direction: rtl;...>"
```

### 5. Primary Color System
- Color picker with hex sync
- Theme color auto-detection
- Injected into AI prompts for branded content

### 6. Premium Models
- Claude Opus 4 (best quality)
- Claude 3 Opus
- Claude Sonnet 4
- GPT-4o

---

## Quick Migration Guide

1. **Backup:** `cp -r old-plugin/ old-plugin-backup/`
2. **API:** Update endpoint, model, tokens, add color
3. **Prompts:** Add anti-placeholder rules, format spec
4. **Parser:** Add cleanup methods, multiple patterns
5. **Handler:** Add fallbacks, duplication prevention
6. **Settings:** Add color picker, model dropdown
7. **Test:** Verify extraction, no duplication, complete output
8. **Deploy:** Deactivate, replace, reactivate

---

## Testing Quick Checks

```
✅ No placeholders: [Vaporesso] → VOOPOO DRAG 5
✅ No markdown: **بخش ۱:** → Clean text
✅ Complete output: All 18 sections generated
✅ Correct fields: Short desc ≠ Full content
✅ Valid HTML: All tags closed, attributes quoted
✅ Title extracted: Product name saved
✅ Slug generated: lost-vape-thelema-elite-40
```

---

## Common Issues & Fixes

| Issue | Fix |
|-------|-----|
| Incomplete output | Increase max_tokens to 50K |
| Placeholders | Add anti-placeholder rules to prompts |
| Duplication | Add strict boundaries in parser |
| No title | Add multiple patterns + HTML fallback |
| Markdown artifacts | Add `clean_extracted_text()` method |
| Broken HTML | Add `clean_html_content()` method |

---

## Net Changes

- **Files modified:** 7
- **Lines added:** ~1,200
- **Lines removed:** ~1,500
- **Focus:** Quality, accuracy, robustness

---

**Quick Links:**
- Full Implementation Guide: `IMPLEMENTATION_GUIDE.md`
- Troubleshooting: See Implementation Guide Section 6
- Testing Checklist: See Implementation Guide Section 5

**Last Updated:** 2026-01-06
