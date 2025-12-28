<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap sir-wrap" dir="rtl">
    <h1 class="sir-title">🤖 ربات اسموک‌ایران - تولید محصول جدید</h1>
    <p class="sir-subtitle">تولید محتوای سئو شده ۱۸ بخشی برای محصولات ویپ با استفاده از هوش مصنوعی</p>
    
    <div class="sir-container">
        <div class="sir-main-form">
            <form id="sir-product-form">
                <?php wp_nonce_field('sir_ajax_nonce', 'sir_nonce'); ?>
                
                <div class="sir-form-section">
                    <h3>📦 اطلاعات محصول</h3>
                    
                    <div class="sir-form-row">
                        <label for="product_name">نام محصول <span class="required">*</span></label>
                        <input type="text" id="product_name" name="product_name" 
                               placeholder="مثال: VOOPOO ARGUS G3" required>
                        <span class="sir-help">نام کامل محصول به انگلیسی یا فارسی</span>
                    </div>
                    
                    <div class="sir-form-row">
                        <label for="keywords">کلیدواژه‌های هدف</label>
                        <textarea id="keywords" name="keywords" rows="4"
                                  placeholder="هر کلیدواژه در یک خط:
پاد ووپو آرگاس
voopoo argus g3 kit
پادسیستم ۳۰ وات"></textarea>
                        <span class="sir-help">کلیدواژه‌های اصلی و فرعی برای سئو</span>
                    </div>
                </div>
                
                <div class="sir-form-section">
                    <h3>⚙️ تنظیمات تولید</h3>
                    
                    <div class="sir-form-row">
                        <label>روش تحقیق</label>
                        <div class="sir-radio-group">
                            <label>
                                <input type="radio" name="research_method" value="auto" checked>
                                <span>🔍 تحقیق خودکار (Tavily)</span>
                            </label>
                            <label>
                                <input type="radio" name="research_method" value="manual">
                                <span>📋 ورود دستی داده‌ها</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="sir-form-row sir-manual-research" style="display:none;">
                        <label for="manual_research">داده‌های تحقیق</label>
                        <textarea id="manual_research" name="manual_research" rows="10"
                                  placeholder="اطلاعات محصول را از منابع مختلف اینجا وارد کنید..."></textarea>
                    </div>
                    
                    <div class="sir-form-row">
                        <label>وضعیت انتشار</label>
                        <div class="sir-radio-group">
                            <label>
                                <input type="radio" name="publish_status" value="draft" checked>
                                <span>📝 پیش‌نویس</span>
                            </label>
                            <label>
                                <input type="radio" name="publish_status" value="publish">
                                <span>🚀 انتشار فوری</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="sir-form-actions">
                    <button type="submit" id="sir-generate-btn" class="button button-primary button-hero">
                        <span class="dashicons dashicons-admin-generic"></span>
                        تولید محتوای محصول
                    </button>
                </div>
            </form>
        </div>
        
        <div class="sir-sidebar">
            <div class="sir-info-box">
                <h4>📋 خروجی شامل:</h4>
                <ul>
                    <li>✅ متادیتای سئو کامل</li>
                    <li>✅ توضیح کوتاه محصول</li>
                    <li>✅ معرفی ۲۰۰+ کلمه‌ای</li>
                    <li>✅ مشخصات فنی کامل</li>
                    <li>✅ راهنمای استفاده</li>
                    <li>✅ مقایسه با ۳ رقیب</li>
                    <li>✅ ۸+ سوال متداول</li>
                    <li>✅ Alt Text تصاویر</li>
                    <li>✅ کپشن شبکه‌های اجتماعی</li>
                    <li>✅ فیلدهای سفارشی</li>
                    <li>✅ خروجی JSON</li>
                </ul>
            </div>
            
            <div class="sir-api-status">
                <h4>🔌 وضعیت API</h4>
                <div id="sir-api-status-content">
                    <p>در حال بررسی...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Progress Modal -->
    <div id="sir-progress-modal" class="sir-modal" style="display:none;">
        <div class="sir-modal-content">
            <h3>⏳ در حال تولید محتوا...</h3>
            <div class="sir-progress-steps">
                <div class="sir-step" data-step="research">
                    <span class="sir-step-icon">⏳</span>
                    <span class="sir-step-text">مرحله ۱: تحقیق محصول</span>
                </div>
                <div class="sir-step" data-step="content">
                    <span class="sir-step-icon">⏳</span>
                    <span class="sir-step-text">مرحله ۲: تولید محتوای ۱۸ بخشی</span>
                </div>
                <div class="sir-step" data-step="publish">
                    <span class="sir-step-icon">⏳</span>
                    <span class="sir-step-text">مرحله ۳: ایجاد محصول در ووکامرس</span>
                </div>
            </div>
            <div class="sir-progress-bar">
                <div class="sir-progress-fill"></div>
            </div>
            <p class="sir-progress-message">لطفاً صبر کنید. این فرآیند ممکن است ۲-۳ دقیقه طول بکشد.</p>
        </div>
    </div>
    
    <!-- Result Modal -->
    <div id="sir-result-modal" class="sir-modal" style="display:none;">
        <div class="sir-modal-content">
            <button class="sir-modal-close">&times;</button>
            <div id="sir-result-content"></div>
        </div>
    </div>
</div>
