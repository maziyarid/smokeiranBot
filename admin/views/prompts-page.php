<?php
if (!defined('ABSPATH')) exit;

$prompts = [
    'research' => [
        'label' => '🔍 پرامپت تحقیق (Tavily)',
        'description' => 'برای جمع‌آوری اطلاعات محصول از منابع اینترنتی',
        'value' => get_option('sir_research_prompt', '')
    ],
    'content' => [
        'label' => '📦 پرامپت محتوای محصول (Claude)',
        'description' => 'برای تولید محتوای ۱۸ بخشی محصول',
        'value' => get_option('sir_content_prompt', '')
    ],
    'post' => [
        'label' => '📝 پرامپت پست بلاگ (Claude)',
        'description' => 'برای تولید مقالات و پست‌های بلاگ',
        'value' => get_option('sir_post_prompt', '')
    ],
    'update' => [
        'label' => '🔄 پرامپت به‌روزرسانی (Claude)',
        'description' => 'برای به‌روزرسانی محتوای موجود',
        'value' => get_option('sir_update_prompt', '')
    ]
];
?>
<div class="wrap sir-wrap" dir="rtl">
    <h1 class="sir-title">📋 مدیریت پرامپت‌ها</h1>
    <p class="sir-subtitle">ویرایش و سفارشی‌سازی پرامپت‌های هوش مصنوعی</p>
    
    <div class="sir-prompts-container">
        <form id="sir-prompts-form">
            <?php wp_nonce_field('sir_ajax_nonce', 'sir_nonce'); ?>
            
            <div class="sir-prompts-tabs">
                <button type="button" class="sir-tab-btn active" data-tab="research">🔍 تحقیق</button>
                <button type="button" class="sir-tab-btn" data-tab="content">📦 محصول</button>
                <button type="button" class="sir-tab-btn" data-tab="post">📝 پست</button>
                <button type="button" class="sir-tab-btn" data-tab="update">🔄 به‌روزرسانی</button>
            </div>
            
            <?php foreach ($prompts as $key => $prompt): ?>
            <div class="sir-prompt-tab <?php echo $key === 'research' ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($key); ?>">
                <div class="sir-prompt-header">
                    <h3><?php echo esc_html($prompt['label']); ?></h3>
                    <p><?php echo esc_html($prompt['description']); ?></p>
                </div>
                
                <div class="sir-form-row">
                    <textarea id="prompt_<?php echo esc_attr($key); ?>" 
                              name="prompt_<?php echo esc_attr($key); ?>" 
                              rows="25" 
                              class="sir-prompt-editor"
                              dir="auto"><?php echo esc_textarea($prompt['value']); ?></textarea>
                </div>
                
                <div class="sir-prompt-actions">
                    <button type="button" class="button sir-reset-prompt" data-prompt="<?php echo esc_attr($key); ?>">
                        🔄 بازگردانی به پیش‌فرض
                    </button>
                    <span class="sir-char-count">
                        تعداد کاراکتر: <span id="count_<?php echo esc_attr($key); ?>">0</span>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="sir-form-actions">
                <button type="submit" id="sir-save-prompts-btn" class="button button-primary button-hero">
                    💾 ذخیره همه پرامپت‌ها
                </button>
            </div>
        </form>
    </div>
    
    <div class="sir-prompts-help">
        <h3>📖 راهنمای متغیرها</h3>
        <table class="sir-help-table">
            <thead>
                <tr>
                    <th>متغیر</th>
                    <th>توضیح</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>{product_name}</code></td>
                    <td>نام محصول وارد شده</td>
                </tr>
                <tr>
                    <td><code>{keywords}</code></td>
                    <td>کلیدواژه‌های هدف</td>
                </tr>
                <tr>
                    <td><code>{research_data}</code></td>
                    <td>داده‌های تحقیق از Tavily</td>
                </tr>
                <tr>
                    <td><code>{current_content}</code></td>
                    <td>محتوای فعلی (برای به‌روزرسانی)</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
