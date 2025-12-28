<?php
if (!defined('ABSPATH')) exit;

$settings = [
    'blackbox_api_key' => get_option('sir_blackbox_api_key', ''),
    'tavily_api_key' => get_option('sir_tavily_api_key', ''),
    'claude_model' => get_option('sir_claude_model', 'claude-sonnet-4-20250514'),
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
                    <label for="claude_model">مدل Claude</label>
                    <select id="claude_model" name="claude_model">
                        <option value="claude-sonnet-4-20250514" <?php selected($settings['claude_model'], 'claude-sonnet-4-20250514'); ?>>
                            Claude Sonnet 4 (پیشنهادی - بهترین نسبت کیفیت/قیمت)
                        </option>
                        <option value="claude-3-5-sonnet-20241022" <?php selected($settings['claude_model'], 'claude-3-5-sonnet-20241022'); ?>>
                            Claude 3.5 Sonnet
                        </option>
                        <option value="gpt-4o" <?php selected($settings['claude_model'], 'gpt-4o'); ?>>
                            GPT-4o (OpenAI)
                        </option>
                        <option value="gpt-4o-mini" <?php selected($settings['claude_model'], 'gpt-4o-mini'); ?>>
                            GPT-4o Mini (اقتصادی)
                        </option>
                    </select>
                    <span class="sir-help">مدل پیش‌فرض برای تولید محتوا</span>
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
