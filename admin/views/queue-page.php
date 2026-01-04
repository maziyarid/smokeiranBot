<?php
/**
 * Queue Management Page
 */

if (!defined('ABSPATH')) exit;

// Initialize queue manager
$queue_manager = new SIR_Queue_Manager();
$stats = $queue_manager->get_stats();
$items = $queue_manager->get_items(null, 100);
?>

<div class="wrap">
    <h1>
        📋 مدیریت صف تولید محتوا
        <a href="#" class="page-title-action sir-process-queue-btn">▶️ پردازش صف</a>
    </h1>
    
    <!-- Statistics -->
    <div class="sir-queue-stats">
        <div class="sir-stat-card">
            <div class="sir-stat-label">کل موارد</div>
            <div class="sir-stat-value"><?php echo $stats['total']; ?></div>
        </div>
        <div class="sir-stat-card pending">
            <div class="sir-stat-label">در انتظار</div>
            <div class="sir-stat-value"><?php echo $stats['pending']; ?></div>
        </div>
        <div class="sir-stat-card processing">
            <div class="sir-stat-label">در حال پردازش</div>
            <div class="sir-stat-value"><?php echo $stats['processing']; ?></div>
        </div>
        <div class="sir-stat-card completed">
            <div class="sir-stat-label">تکمیل شده</div>
            <div class="sir-stat-value"><?php echo $stats['completed']; ?></div>
        </div>
        <div class="sir-stat-card failed">
            <div class="sir-stat-label">ناموفق</div>
            <div class="sir-stat-value"><?php echo $stats['failed']; ?></div>
        </div>
    </div>
    
    <!-- Add to Queue Section -->
    <div class="sir-card">
        <h2>➕ افزودن به صف</h2>
        
        <div class="sir-tabs">
            <button class="sir-tab-btn active" data-tab="single">افزودن تکی</button>
            <button class="sir-tab-btn" data-tab="bulk">افزودن گروهی (متن)</button>
            <button class="sir-tab-btn" data-tab="csv">آپلود CSV</button>
        </div>
        
        <!-- Single Add Tab -->
        <div class="sir-tab-content active" id="tab-single">
            <form id="sir-add-single-form">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label>عنوان/نام محصول</label></th>
                        <td>
                            <input type="text" name="title" class="regular-text" required 
                                   placeholder="مثال: VOOPOO DRAG 5">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>کلیدواژه‌ها</label></th>
                        <td>
                            <textarea name="keywords" rows="3" class="large-text" 
                                      placeholder="مثال: ویپ ووپو، درگ 5، vape"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>نوع محتوا</label></th>
                        <td>
                            <select name="item_type">
                                <option value="product">محصول</option>
                                <option value="post">پست بلاگ</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>اولویت</label></th>
                        <td>
                            <input type="number" name="priority" value="0" min="0" max="100" 
                                   class="small-text">
                            <p class="description">عدد بالاتر = اولویت بیشتر (0-100)</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">افزودن به صف</button>
                </p>
            </form>
        </div>
        
        <!-- Bulk Add Tab -->
        <div class="sir-tab-content" id="tab-bulk">
            <form id="sir-add-bulk-form">
                <p class="description">
                    هر خط یک مورد: <code>عنوان | کلیدواژه1، کلیدواژه2، کلیدواژه3</code><br>
                    مثال:<br>
                    <code>VOOPOO DRAG 5 | ویپ ووپو، درگ 5، vape</code><br>
                    <code>راهنمای انتخاب ویپ | راهنمای خرید ویپ، بهترین ویپ</code>
                </p>
                <textarea name="bulk_text" rows="10" class="large-text code" 
                          placeholder="عنوان 1 | کلیدواژه‌ها
عنوان 2 | کلیدواژه‌ها
عنوان 3 | کلیدواژه‌ها"></textarea>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label>نوع محتوا برای همه</label></th>
                        <td>
                            <select name="bulk_type">
                                <option value="product">محصول</option>
                                <option value="post">پست بلاگ</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>اولویت برای همه</label></th>
                        <td>
                            <input type="number" name="bulk_priority" value="0" min="0" max="100" 
                                   class="small-text">
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">افزودن گروهی به صف</button>
                </p>
            </form>
        </div>
        
        <!-- CSV Upload Tab -->
        <div class="sir-tab-content" id="tab-csv">
            <form id="sir-upload-csv-form" enctype="multipart/form-data">
                <p class="description">
                    فرمت CSV: <code>عنوان, کلیدواژه‌ها, نوع (product/post), اولویت</code><br>
                    <strong>ردیف اول فایل باید هدر باشد.</strong>
                </p>
                
                <input type="file" name="csv_file" accept=".csv" required>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">آپلود و افزودن به صف</button>
                </p>
            </form>
            
            <div id="csv-preview" style="display:none;">
                <h3>پیش‌نمایش موارد پردازش شده:</h3>
                <div id="csv-preview-content"></div>
            </div>
        </div>
    </div>
    
    <!-- Queue Items Table -->
    <div class="sir-card">
        <div class="sir-card-header">
            <h2>📋 موارد صف</h2>
            <div class="sir-card-actions">
                <button class="button" id="sir-refresh-queue">🔄 بروزرسانی</button>
                <button class="button" id="sir-clear-completed">🗑️ پاک کردن تکمیل شده‌ها</button>
            </div>
        </div>
        
        <div class="sir-table-filters">
            <label>
                <select id="sir-filter-status">
                    <option value="">همه موارد</option>
                    <option value="pending">در انتظار</option>
                    <option value="processing">در حال پردازش</option>
                    <option value="completed">تکمیل شده</option>
                    <option value="failed">ناموفق</option>
                </select>
            </label>
        </div>
        
        <table class="wp-list-table widefat fixed striped" id="sir-queue-table">
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>عنوان</th>
                    <th>نوع</th>
                    <th>کلیدواژه‌ها</th>
                    <th>وضعیت</th>
                    <th>اولویت</th>
                    <th>تاریخ ایجاد</th>
                    <th>تاریخ پردازش</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center;padding:40px;">
                            📭 صف خالی است
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr data-id="<?php echo esc_attr($item['id']); ?>" 
                            data-status="<?php echo esc_attr($item['status']); ?>">
                            <td><?php echo esc_html($item['id']); ?></td>
                            <td><strong><?php echo esc_html($item['title']); ?></strong></td>
                            <td>
                                <span class="sir-badge sir-badge-<?php echo esc_attr($item['item_type']); ?>">
                                    <?php echo $item['item_type'] === 'product' ? '📦 محصول' : '📝 پست'; ?>
                                </span>
                            </td>
                            <td>
                                <small><?php echo esc_html(mb_substr($item['keywords'], 0, 50)); ?><?php echo mb_strlen($item['keywords']) > 50 ? '...' : ''; ?></small>
                            </td>
                            <td>
                                <?php 
                                $status_labels = [
                                    'pending' => '⏳ در انتظار',
                                    'processing' => '⚙️ در حال پردازش',
                                    'completed' => '✅ تکمیل شده',
                                    'failed' => '❌ ناموفق'
                                ];
                                $status = $item['status'];
                                ?>
                                <span class="sir-status-badge sir-status-<?php echo esc_attr($status); ?>">
                                    <?php echo $status_labels[$status] ?? $status; ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($item['priority']); ?></td>
                            <td><?php echo esc_html($item['created_at']); ?></td>
                            <td><?php echo $item['processed_at'] ? esc_html($item['processed_at']) : '-'; ?></td>
                            <td class="sir-queue-actions">
                                <?php if ($status === 'failed'): ?>
                                    <button class="button button-small sir-retry-btn" 
                                            data-id="<?php echo esc_attr($item['id']); ?>" 
                                            title="تلاش مجدد">
                                        🔄
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($status === 'completed' && $item['result_id']): ?>
                                    <?php 
                                    $edit_link = $item['item_type'] === 'product' 
                                        ? admin_url('post.php?post=' . $item['result_id'] . '&action=edit')
                                        : admin_url('post.php?post=' . $item['result_id'] . '&action=edit');
                                    ?>
                                    <a href="<?php echo esc_url($edit_link); ?>" 
                                       class="button button-small" 
                                       target="_blank" 
                                       title="مشاهده نتیجه">
                                        👁️
                                    </a>
                                <?php endif; ?>
                                
                                <button class="button button-small sir-delete-btn" 
                                        data-id="<?php echo esc_attr($item['id']); ?>" 
                                        title="حذف">
                                    🗑️
                                </button>
                                
                                <?php if ($status === 'failed' && $item['error_message']): ?>
                                    <button class="button button-small sir-show-error-btn" 
                                            data-error="<?php echo esc_attr($item['error_message']); ?>" 
                                            title="مشاهده خطا">
                                        ⚠️
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Processing Progress Modal -->
    <div id="sir-queue-progress-modal" class="sir-modal" style="display:none;">
        <div class="sir-modal-content">
            <h2>⚙️ در حال پردازش صف...</h2>
            <div class="sir-progress-bar">
                <div class="sir-progress-fill" style="width:0%"></div>
            </div>
            <div id="sir-progress-status"></div>
            <div id="sir-progress-log"></div>
        </div>
    </div>
</div>

<style>
.sir-queue-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.sir-stat-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 15px;
    text-align: center;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.sir-stat-card.pending { border-left: 4px solid #f0ad4e; }
.sir-stat-card.processing { border-left: 4px solid #5bc0de; }
.sir-stat-card.completed { border-left: 4px solid #5cb85c; }
.sir-stat-card.failed { border-left: 4px solid #d9534f; }

.sir-stat-label {
    font-size: 12px;
    color: #646970;
    margin-bottom: 5px;
}

.sir-stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #1d2327;
}

.sir-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
    margin: 20px 0;
    padding: 20px;
}

.sir-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.sir-card-header h2 {
    margin: 0;
}

.sir-tabs {
    border-bottom: 1px solid #c3c4c7;
    margin-bottom: 20px;
}

.sir-tab-btn {
    background: none;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    font-size: 14px;
}

.sir-tab-btn.active {
    border-bottom-color: #2271b1;
    color: #2271b1;
    font-weight: 600;
}

.sir-tab-content {
    display: none;
}

.sir-tab-content.active {
    display: block;
}

.sir-status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.sir-status-pending {
    background: #fff3cd;
    color: #856404;
}

.sir-status-processing {
    background: #d1ecf1;
    color: #0c5460;
}

.sir-status-completed {
    background: #d4edda;
    color: #155724;
}

.sir-status-failed {
    background: #f8d7da;
    color: #721c24;
}

.sir-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
}

.sir-badge-product {
    background: #e7f3ff;
    color: #004085;
}

.sir-badge-post {
    background: #f0e6ff;
    color: #4a148c;
}

.sir-queue-actions {
    white-space: nowrap;
}

.sir-queue-actions button,
.sir-queue-actions a {
    margin-right: 5px;
}

.sir-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
}

.sir-modal-content {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.sir-progress-bar {
    width: 100%;
    height: 30px;
    background: #e0e0e0;
    border-radius: 15px;
    overflow: hidden;
    margin: 20px 0;
}

.sir-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #2271b1, #50a5e6);
    transition: width 0.3s ease;
}

#sir-progress-log {
    max-height: 300px;
    overflow-y: auto;
    background: #f5f5f5;
    padding: 10px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 12px;
    margin-top: 15px;
}

.sir-table-filters {
    margin-bottom: 15px;
}
</style>
