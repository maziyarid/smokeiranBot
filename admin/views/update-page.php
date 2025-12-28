<?php
if (!defined('ABSPATH')) exit;

// Get existing products and posts
$products = [];
$posts_list = [];

if (class_exists('WooCommerce')) {
    $args = [
        'post_type' => 'product',
        'posts_per_page' => 100,
        'orderby' => 'date',
        'order' => 'DESC'
    ];
    $products = get_posts($args);
}

$posts_list = get_posts([
    'post_type' => 'post',
    'posts_per_page' => 100,
    'orderby' => 'date',
    'order' => 'DESC'
]);
?>
<div class="wrap sir-wrap" dir="rtl">
    <h1 class="sir-title">🔄 به‌روزرسانی محتوای موجود</h1>
    <p class="sir-subtitle">به‌روزرسانی و بهبود محصولات و پست‌های موجود</p>
    
    <div class="sir-container">
        <div class="sir-main-form">
            <form id="sir-update-form">
                <?php wp_nonce_field('sir_ajax_nonce', 'sir_nonce'); ?>
                
                <div class="sir-form-section">
                    <h3>📝 انتخاب محتوا</h3>
                    
                    <div class="sir-form-row">
                        <label>نوع محتوا</label>
                        <div class="sir-radio-group">
                            <label>
                                <input type="radio" name="update_type" value="product" checked>
                                <span>📦 محصول</span>
                            </label>
                            <label>
                                <input type="radio" name="update_type" value="post">
                                <span>📝 پست</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="sir-form-row" id="product-select-row">
                        <label for="product_id">انتخاب محصول</label>
                        <select id="product_id" name="product_id">
                            <option value="">-- انتخاب کنید --</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo esc_attr($product->ID); ?>">
                                    <?php echo esc_html($product->post_title); ?> (ID: <?php echo $product->ID; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="sir-form-row" id="post-select-row" style="display:none;">
                        <label for="post_id">انتخاب پست</label>
                        <select id="post_id" name="post_id">
                            <option value="">-- انتخاب کنید --</option>
                            <?php foreach ($posts_list as $post): ?>
                                <option value="<?php echo esc_attr($post->ID); ?>">
                                    <?php echo esc_html($post->post_title); ?> (ID: <?php echo $post->ID; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="sir-form-section">
                    <h3>🔧 دستورالعمل به‌روزرسانی</h3>
                    
                    <div class="sir-form-row">
                        <label for="update_instructions">چه بخش‌هایی به‌روزرسانی شوند؟</label>
                        <textarea id="update_instructions" name="update_instructions" rows="4"
                                  placeholder="مثال:
- به‌روزرسانی مشخصات فنی
- اضافه کردن سوالات متداول جدید
- بهبود سئو
- اضافه کردن مقایسه با محصولات جدید"></textarea>
                    </div>
                    
                    <div class="sir-form-row">
                        <label>
                            <input type="checkbox" name="refresh_research" value="yes">
                            تحقیق مجدد از منابع جدید
                        </label>
                    </div>
                </div>
                
                <div class="sir-form-actions">
                    <button type="button" id="sir-load-content-btn" class="button button-secondary">
                        📄 نمایش محتوای فعلی
                    </button>
                    <button type="submit" id="sir-update-btn" class="button button-primary button-hero">
                        🔄 به‌روزرسانی محتوا
                    </button>
                </div>
            </form>
            
            <div id="sir-current-content" class="sir-current-content" style="display:none;">
                <h4>محتوای فعلی:</h4>
                <div id="sir-current-content-display"></div>
            </div>
        </div>
    </div>
    
    <!-- Progress Modal -->
    <div id="sir-update-progress-modal" class="sir-modal" style="display:none;">
        <div class="sir-modal-content">
            <h3>⏳ در حال به‌روزرسانی...</h3>
            <div class="sir-progress-bar">
                <div class="sir-progress-fill"></div>
            </div>
        </div>
    </div>
    
    <!-- Result Modal -->
    <div id="sir-update-result-modal" class="sir-modal" style="display:none;">
        <div class="sir-modal-content">
            <button class="sir-modal-close">&times;</button>
            <div id="sir-update-result-content"></div>
        </div>
    </div>
</div>
