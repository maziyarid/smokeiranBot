<?php
if (!defined('ABSPATH')) exit;

$settings = [
    'blackbox_api_key' => get_option('sir_blackbox_api_key', ''),
    'tavily_api_key' => get_option('sir_tavily_api_key', ''),
    'claude_model' => get_option('sir_claude_model', 'blackboxai/x-ai/grok-code-fast-1:free'),
    'auto_publish' => get_option('sir_auto_publish', 'draft'),
    'enable_logging' => get_option('sir_enable_logging', 'yes'),
];
?>
<div class="wrap sir-wrap" dir="rtl">
    <h1 class="sir-title">⚙️ تنظیمات ربات اسموک‌ایران</h1>
    
    <div class="sir-settings-container">
        <form id="sir-settings-form">
            <?php wp_nonce_field('sir_ajax_nonce', 'sir_nonce'); ?>
            
            <!-- API Settings -->
            <div class="sir-settings-section">
                <h2>🔌 تنظیمات API</h2>
                
                <div class="sir-form-row">
                    <label for="blackbox_api_key">کلید API بلک‌باکس (Blackbox)</label>
                    <div class="sir-input-group">
                        <input type="password" 
                               id="blackbox_api_key" 
                               name="blackbox_api_key" 
                               value="<?php echo esc_attr($settings['blackbox_api_key']); ?>"
                               placeholder="کلید API خود را وارد کنید">
                        <button type="button" class="button sir-toggle-password">👁️</button>
                        <button type="button" class="button sir-test-api" data-api="blackbox">تست اتصال</button>
                    </div>
                    <span class="sir-help">
                        از <a href="https://www.blackbox.ai/api" target="_blank">blackbox.ai/api</a> دریافت کنید
                    </span>
                    <div class="sir-api-status" id="blackbox-status"></div>
                </div>
                
                <div class="sir-form-row">
                    <label for="tavily_api_key">کلید API تاویلی (Tavily)</label>
                    <div class="sir-input-group">
                        <input type="password" 
                               id="tavily_api_key" 
                               name="tavily_api_key" 
                               value="<?php echo esc_attr($settings['tavily_api_key']); ?>"
                               placeholder="کلید API خود را وارد کنید">
                        <button type="button" class="button sir-toggle-password">👁️</button>
                        <button type="button" class="button sir-test-api" data-api="tavily">تست اتصال</button>
                    </div>
                    <span class="sir-help">
                        از <a href="https://tavily.com" target="_blank">tavily.com</a> دریافت کنید - برای تحقیق محصول
                    </span>
                    <div class="sir-api-status" id="tavily-status"></div>
                </div>
            </div>
            
            <!-- Model Settings -->
            <div class="sir-settings-section">
                <h2>🤖 تنظیمات مدل</h2>
                
                <div class="sir-form-row">
                    <label for="claude_model">مدل هوش مصنوعی</label>
                    <select id="claude_model" name="claude_model">
                        <optgroup label="🆓 مدل‌های رایگان (پیشنهادی)">
                            <option value="blackboxai/x-ai/grok-code-fast-1:free" <?php selected($settings['claude_model'], 'blackboxai/x-ai/grok-code-fast-1:free'); ?>>
                                xAI Grok Code Fast 1 (رایگان - پیشنهادی)
                            </option>
                            <option value="blackboxai/agentica-org/deepcoder-14b-preview:free" <?php selected($settings['claude_model'], 'blackboxai/agentica-org/deepcoder-14b-preview:free'); ?>>
                                Agentica Deepcoder 14B (رایگان)
                            </option>
                        </optgroup>
                        <optgroup label="🔝 مدل‌های پیشرفته (Premium)">
                            <option value="blackboxai/anthropic/claude-opus-4" <?php selected($settings['claude_model'], 'blackboxai/anthropic/claude-opus-4'); ?>>
                                Claude Opus 4 (بهترین کیفیت)
                            </option>
                            <option value="blackboxai/anthropic/claude-sonnet-4" <?php selected($settings['claude_model'], 'blackboxai/anthropic/claude-sonnet-4'); ?>>
                                Claude Sonnet 4
                            </option>
                            <option value="blackboxai/anthropic/claude-3-5-sonnet" <?php selected($settings['claude_model'], 'blackboxai/anthropic/claude-3-5-sonnet'); ?>>
                                Claude 3.5 Sonnet
                            </option>
                            <option value="blackboxai/openai/gpt-4o" <?php selected($settings['claude_model'], 'blackboxai/openai/gpt-4o'); ?>>
                                ChatGPT-4o (OpenAI)
                            </option>
                            <option value="blackboxai/openai/gpt-4-turbo" <?php selected($settings['claude_model'], 'blackboxai/openai/gpt-4-turbo'); ?>>
                                ChatGPT-4 Turbo (OpenAI)
                            </option>
                            <option value="blackboxai/google/gemini-pro-1.5" <?php selected($settings['claude_model'], 'blackboxai/google/gemini-pro-1.5'); ?>>
                                Gemini Pro 1.5 (Google)
                            </option>
                            <option value="blackboxai/google/gemini-ultra" <?php selected($settings['claude_model'], 'blackboxai/google/gemini-ultra'); ?>>
                                Gemini Ultra (Google)
                            </option>
                        </optgroup>
                        <optgroup label="💰 مدل‌های اقتصادی">
                            <option value="blackboxai/amazon/nova-micro-v1" <?php selected($settings['claude_model'], 'blackboxai/amazon/nova-micro-v1'); ?>>
                                Amazon Nova Micro ($0.04 in / $0.14 out)
                            </option>
                            <option value="blackboxai/amazon/nova-lite-v1" <?php selected($settings['claude_model'], 'blackboxai/amazon/nova-lite-v1'); ?>>
                                Amazon Nova Lite ($0.06 in / $0.24 out)
                            </option>
                            <option value="blackboxai/openai/gpt-4o-mini" <?php selected($settings['claude_model'], 'blackboxai/openai/gpt-4o-mini'); ?>>
                                ChatGPT-4o Mini ($0.15 in / $0.60 out)
                            </option>
                            <option value="blackboxai/ai21/jamba-1.6-mini" <?php selected($settings['claude_model'], 'blackboxai/ai21/jamba-1.6-mini'); ?>>
                                AI21 Jamba Mini ($0.20 in / $0.40 out)
                            </option>
                            <option value="blackboxai/anthropic/claude-3-haiku" <?php selected($settings['claude_model'], 'blackboxai/anthropic/claude-3-haiku'); ?>>
                                Claude 3 Haiku ($0.25 in / $1.25 out)
                            </option>
                            <option value="blackboxai/google/gemini-flash-1.5" <?php selected($settings['claude_model'], 'blackboxai/google/gemini-flash-1.5'); ?>>
                                Gemini Flash 1.5 ($0.10 in / $0.30 out)
                            </option>
                        </optgroup>
                        <optgroup label="⚙️ مدل‌های تخصصی">
                            <option value="blackboxai/aion-labs/aion-1.0-mini" <?php selected($settings['claude_model'], 'blackboxai/aion-labs/aion-1.0-mini'); ?>>
                                AionLabs Aion Mini ($0.70 in / $1.40 out)
                            </option>
                            <option value="blackboxai/amazon/nova-pro-v1" <?php selected($settings['claude_model'], 'blackboxai/amazon/nova-pro-v1'); ?>>
                                Amazon Nova Pro ($0.80 in / $3.20 out)
                            </option>
                            <option value="blackboxai/ai21/jamba-1.6-large" <?php selected($settings['claude_model'], 'blackboxai/ai21/jamba-1.6-large'); ?>>
                                AI21 Jamba Large ($2.00 in / $8.00 out)
                            </option>
                            <option value="blackboxai/01-ai/yi-large" <?php selected($settings['claude_model'], 'blackboxai/01-ai/yi-large'); ?>>
                                01.AI Yi Large ($3.00 in / $3.00 out)
                            </option>
                            <option value="blackboxai/aion-labs/aion-1.0" <?php selected($settings['claude_model'], 'blackboxai/aion-labs/aion-1.0'); ?>>
                                AionLabs Aion 1.0 ($4.00 in / $8.00 out)
                            </option>
                        </optgroup>
                        <optgroup label="🤝 عوامل همکار (Background Agents - رایگان)">
                            <option value="BLACKBOX" <?php selected($settings['claude_model'], 'BLACKBOX'); ?>>
                                BLACKBOX Agent (رایگان - همکار)
                            </option>
                            <option value="Claude Code" <?php selected($settings['claude_model'], 'Claude Code'); ?>>
                                Claude Code Agent (رایگان - همکار)
                            </option>
                            <option value="Codex" <?php selected($settings['claude_model'], 'Codex'); ?>>
                                Codex Agent (رایگان - همکار)
                            </option>
                            <option value="Gemini" <?php selected($settings['claude_model'], 'Gemini'); ?>>
                                Gemini Agent (رایگان - همکار)
                            </option>
                        </optgroup>
                    </select>
                    <span class="sir-help">
                        مدل پیش‌فرض برای تولید محتوا - مدل‌های رایگان برای شروع پیشنهاد می‌شوند
                        <br>قیمت‌ها به ازای هر میلیون توکن محاسبه می‌شوند
                        <br><strong>عوامل همکار</strong> برای پردازش پس‌زمینه و بهبود کیفیت استفاده می‌شوند (رایگان)
                    </span>
                </div>
                
                <div class="sir-form-row">
                    <label>
                        <input type="checkbox" 
                               name="enable_multi_agent" 
                               value="yes" 
                               <?php checked(get_option('sir_enable_multi_agent', 'no'), 'yes'); ?>>
                        فعال‌سازی سیستم چند-عامله (Multi-Agent Orchestration)
                    </label>
                    <span class="sir-help">
                        با فعال‌سازی این گزینه، سیستم از چندین عامل هوش مصنوعی به صورت موازی برای بهبود کیفیت استفاده می‌کند
                        <br>عوامل همکار: BLACKBOX، Claude Code، Codex، Gemini (همگی رایگان)
                    </span>
                </div>
            </div>
            
            <!-- Content Settings -->
            <div class="sir-settings-section">
                <h2>📝 تنظیمات محتوا</h2>
                
                <div class="sir-form-row">
                    <label for="auto_publish">وضعیت پیش‌فرض انتشار</label>
                    <select id="auto_publish" name="auto_publish">
                        <option value="draft" <?php selected($settings['auto_publish'], 'draft'); ?>>
                            پیش‌نویس (پیشنهادی)
                        </option>
                        <option value="publish" <?php selected($settings['auto_publish'], 'publish'); ?>>
                            انتشار فوری
                        </option>
                        <option value="pending" <?php selected($settings['auto_publish'], 'pending'); ?>>
                            در انتظار بررسی
                        </option>
                    </select>
                </div>
                
                <div class="sir-form-row">
                    <label>
                        <input type="checkbox" 
                               name="enable_logging" 
                               value="yes" 
                               <?php checked($settings['enable_logging'], 'yes'); ?>>
                        فعال‌سازی ثبت گزارش عملیات
                    </label>
                    <span class="sir-help">گزارش تمام عملیات‌های تولید و به‌روزرسانی محتوا</span>
                </div>
            </div>
            
            <!-- Design Settings -->
            <div class="sir-settings-section">
                <h2>🎨 تنظیمات طراحی</h2>
                
                <div class="sir-form-row">
                    <label for="primary_color">رنگ اصلی سایت</label>
                    <div class="sir-input-group">
                        <input type="color" 
                               id="primary_color" 
                               name="primary_color" 
                               value="<?php echo esc_attr(get_option('sir_primary_color', '#29853a')); ?>"
                               class="sir-color-picker">
                        <input type="text" 
                               id="primary_color_hex" 
                               value="<?php echo esc_attr(get_option('sir_primary_color', '#29853a')); ?>"
                               class="sir-color-input"
                               pattern="^#[0-9A-Fa-f]{6}$"
                               placeholder="#29853a">
                    </div>
                    <span class="sir-help">
                        این رنگ در جداول و المان‌های HTML تولید شده توسط شورت‌کدهای FSP استفاده می‌شود.
                        در صورت خالی بودن، از رنگ پیش‌فرض (#29853a) یا رنگ قالب استفاده می‌شود.
                    </span>
                </div>
                
                <div class="sir-form-row">
                    <label>
                        <input type="checkbox" 
                               name="use_theme_color" 
                               value="yes" 
                               <?php checked(get_option('sir_use_theme_color', 'no'), 'yes'); ?>>
                        استفاده خودکار از رنگ اصلی قالب (در صورت وجود)
                    </label>
                    <span class="sir-help">اگر فعال باشد، ابتدا رنگ اصلی قالب با get_theme_mod() دریافت می‌شود</span>
                </div>
            </div>
            
            <!-- Custom Fields Mapping -->
            <div class="sir-settings-section">
                <h2>🗂️ نقشه‌برداری فیلدهای سفارشی</h2>
                <p class="sir-section-desc">تطبیق فیلدهای تولید شده با فیلدهای سفارشی ووکامرس/ACF</p>
                
                <table class="sir-fields-table">
                    <thead>
                        <tr>
                            <th>فیلد خروجی</th>
                            <th>Meta Key در وردپرس</th>
                            <th>فعال</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $field_mappings = get_option('sir_field_mappings', SIR_Custom_Fields::get_product_fields_mapping());
                        foreach ($field_mappings as $key => $field):
                        ?>
                        <tr>
                            <td><?php echo esc_html($field['label']); ?></td>
                            <td>
                                <input type="text" 
                                       name="field_mapping[<?php echo esc_attr($key); ?>][meta_key]" 
                                       value="<?php echo esc_attr($field['meta_key']); ?>"
                                       class="sir-field-input">
                            </td>
                            <td>
                                <input type="checkbox" 
                                       name="field_mapping[<?php echo esc_attr($key); ?>][enabled]" 
                                       value="yes"
                                       <?php checked(isset($field['enabled']) ? $field['enabled'] : true, true); ?>>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="sir-form-actions">
                <button type="submit" id="sir-save-settings-btn" class="button button-primary button-hero">
                    💾 ذخیره تنظیمات
                </button>
            </div>
        </form>
    </div>
</div>
