<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap sir-wrap" dir="rtl">
    <h1 class="sir-title">📝 ربات اسموک‌ایران - تولید پست بلاگ</h1>
    <p class="sir-subtitle">تولید مقالات آموزشی و بررسی محصولات با هوش مصنوعی</p>
    
    <div class="sir-container">
        <div class="sir-main-form">
            <form id="sir-post-form">
                <?php wp_nonce_field('sir_ajax_nonce', 'sir_nonce'); ?>
                
                <div class="sir-form-section">
                    <h3>📰 اطلاعات پست</h3>
                    
                    <div class="sir-form-row">
                        <label for="post_topic">موضوع پست <span class="required">*</span></label>
                        <input type="text" id="post_topic" name="post_topic" 
                               placeholder="مثال: راهنمای انتخاب پاد مناسب برای مبتدیان" required>
                    </div>
                    
                    <div class="sir-form-row">
                        <label for="post_keywords">کلیدواژه‌های هدف</label>
                        <textarea id="post_keywords" name="post_keywords" rows="3"
                                  placeholder="انتخاب پاد
بهترین پاد برای مبتدیان
راهنمای خرید ویپ"></textarea>
                    </div>
                    
                    <div class="sir-form-row">
                        <label for="post_type">نوع پست</label>
                        <select id="post_type" name="post_type">
                            <option value="guide">📚 راهنما و آموزش</option>
                            <option value="review">⭐ بررسی محصول</option>
                            <option value="comparison">🔄 مقایسه محصولات</option>
                            <option value="news">📰 اخبار و تازه‌ها</option>
                            <option value="tips">💡 نکات و ترفندها</option>
                        </select>
                    </div>
                </div>
                
                <div class="sir-form-section">
                    <h3>⚙️ تنظیمات</h3>
                    
                    <div class="sir-form-row">
                        <label>روش تحقیق</label>
                        <div class="sir-radio-group">
                            <label>
                                <input type="radio" name="post_research_method" value="auto" checked>
                                <span>🔍 تحقیق خودکار</span>
                            </label>
                            <label>
                                <input type="radio" name="post_research_method" value="manual">
                                <span>📋 ورود دستی</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="sir-form-row sir-post-manual-research" style="display:none;">
                        <label for="post_manual_research">داده‌های تحقیق</label>
                        <textarea id="post_manual_research" name="post_manual_research" rows="8"></textarea>
                    </div>
                    
                    <div class="sir-form-row">
                        <label>وضعیت انتشار</label>
                        <div class="sir-radio-group">
                            <label>
                                <input type="radio" name="post_publish_status" value="draft" checked>
                                <span>📝 پیش‌نویس</span>
                            </label>
                            <label>
                                <input type="radio" name="post_publish_status" value="publish">
                                <span>🚀 انتشار فوری</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="sir-form-actions">
                    <button type="submit" id="sir-generate-post-btn" class="button button-primary button-hero">
                        <span class="dashicons dashicons-edit"></span>
                        تولید محتوای پست
                    </button>
                </div>
            </form>
        </div>
        
        <div class="sir-sidebar">
            <div class="sir-info-box">
                <h4>📋 خروجی پست شامل:</h4>
                <ul>
                    <li>✅ عنوان جذاب</li>
                    <li>✅ متادیتای سئو</li>
                    <li>✅ مقدمه جذاب</li>
                    <li>✅ بدنه ۱۰۰۰+ کلمه</li>
                    <li>✅ سوالات متداول</li>
                    <li>✅ جمع‌بندی</li>
                    <li>✅ دسته‌بندی و تگ</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Progress Modal -->
    <div id="sir-post-progress-modal" class="sir-modal" style="display:none;">
        <div class="sir-modal-content">
            <h3>⏳ در حال تولید پست...</h3>
            <div class="sir-progress-bar">
                <div class="sir-progress-fill"></div>
            </div>
            <p class="sir-progress-message">لطفاً صبر کنید...</p>
        </div>
    </div>
    
    <!-- Result Modal -->
    <div id="sir-post-result-modal" class="sir-modal" style="display:none;">
        <div class="sir-modal-content">
            <button class="sir-modal-close">&times;</button>
            <div id="sir-post-result-content"></div>
        </div>
    </div>
</div>
