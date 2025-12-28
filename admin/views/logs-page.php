<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'sir_logs';

// Pagination
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Get total count
$total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
$total_pages = ceil($total_items / $per_page);

// Get logs
$logs = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
    $per_page,
    $offset
));

// Statistics
$stats = [
    'total' => $total_items,
    'products' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE action_type LIKE '%product%'"),
    'posts' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE action_type LIKE '%post%'"),
    'updates' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE action_type LIKE '%update%'"),
    'success' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'success'"),
    'failed' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'failed'"),
];
?>
<div class="wrap sir-wrap" dir="rtl">
    <h1 class="sir-title">📊 گزارش‌های ربات اسموک‌ایران</h1>
    
    <!-- Statistics Cards -->
    <div class="sir-stats-grid">
        <div class="sir-stat-card">
            <span class="sir-stat-icon">📊</span>
            <div class="sir-stat-content">
                <span class="sir-stat-number"><?php echo number_format_i18n($stats['total']); ?></span>
                <span class="sir-stat-label">کل عملیات</span>
            </div>
        </div>
        <div class="sir-stat-card">
            <span class="sir-stat-icon">📦</span>
            <div class="sir-stat-content">
                <span class="sir-stat-number"><?php echo number_format_i18n($stats['products']); ?></span>
                <span class="sir-stat-label">محصولات</span>
            </div>
        </div>
        <div class="sir-stat-card">
            <span class="sir-stat-icon">📝</span>
            <div class="sir-stat-content">
                <span class="sir-stat-number"><?php echo number_format_i18n($stats['posts']); ?></span>
                <span class="sir-stat-label">پست‌ها</span>
            </div>
        </div>
        <div class="sir-stat-card">
            <span class="sir-stat-icon">🔄</span>
            <div class="sir-stat-content">
                <span class="sir-stat-number"><?php echo number_format_i18n($stats['updates']); ?></span>
                <span class="sir-stat-label">به‌روزرسانی‌ها</span>
            </div>
        </div>
        <div class="sir-stat-card sir-stat-success">
            <span class="sir-stat-icon">✅</span>
            <div class="sir-stat-content">
                <span class="sir-stat-number"><?php echo number_format_i18n($stats['success']); ?></span>
                <span class="sir-stat-label">موفق</span>
            </div>
        </div>
        <div class="sir-stat-card sir-stat-failed">
            <span class="sir-stat-icon">❌</span>
            <div class="sir-stat-content">
                <span class="sir-stat-number"><?php echo number_format_i18n($stats['failed']); ?></span>
                <span class="sir-stat-label">ناموفق</span>
            </div>
        </div>
    </div>
    
    <!-- Logs Table -->
    <div class="sir-logs-container">
        <div class="sir-logs-header">
            <h2>📋 لیست عملیات</h2>
            <div class="sir-logs-actions">
                <button type="button" id="sir-clear-logs" class="button">
                    🗑️ پاک کردن همه
                </button>
                <button type="button" id="sir-export-logs" class="button">
                    📥 خروجی CSV
                </button>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped sir-logs-table">
            <thead>
                <tr>
                    <th class="column-id">شناسه</th>
                    <th class="column-action">نوع عملیات</th>
                    <th class="column-name">نام</th>
                    <th class="column-status">وضعیت</th>
                    <th class="column-message">پیام</th>
                    <th class="column-date">تاریخ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" class="sir-no-logs">هنوز هیچ گزارشی ثبت نشده است.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo esc_html($log->id); ?></td>
                    <td>
                        <span class="sir-action-badge sir-action-<?php echo esc_attr($log->action_type); ?>">
                            <?php 
                            $action_labels = [
                                'create_product' => '📦 ایجاد محصول',
                                'update_product' => '🔄 به‌روزرسانی محصول',
                                'create_post' => '📝 ایجاد پست',
                                'update_post' => '🔄 به‌روزرسانی پست',
                            ];
                            echo isset($action_labels[$log->action_type]) 
                                ? $action_labels[$log->action_type] 
                                : esc_html($log->action_type);
                            ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($log->product_name); ?></td>
                    <td>
                        <span class="sir-status-badge sir-status-<?php echo esc_attr($log->status); ?>">
                            <?php echo $log->status === 'success' ? '✅ موفق' : '❌ ناموفق'; ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($log->message); ?></td>
                    <td>
                        <?php 
                        $date = new DateTime($log->created_at);
                        echo $date->format('Y/m/d H:i:s');
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="sir-pagination">
            <?php
            echo paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo; قبلی',
                'next_text' => 'بعدی &raquo;',
                'total' => $total_pages,
                'current' => $current_page
            ]);
            ?>
        </div>
        <?php endif; ?>
    </div>
</div>
