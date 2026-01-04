/**
 * SmokeIran Robot Admin JavaScript
 */

(function($) {
    'use strict';
    
    // ============================================
    // Initialize
    // ============================================
    
    $(document).ready(function() {
        SIR.init();
    });
    
    var SIR = {
        
        init: function() {
            this.bindEvents();
            this.checkApiStatus();
            this.initPromptTabs();
            this.initCharCount();
            this.initColorPicker();
        },
        
        // ============================================
        // Event Bindings
        // ============================================
        
        bindEvents: function() {
            // Product form
            $('#sir-product-form').on('submit', this.handleProductSubmit);
            
            // Post form
            $('#sir-post-form').on('submit', this.handlePostSubmit);
            
            // Update form
            $('#sir-update-form').on('submit', this.handleUpdateSubmit);
            $('#sir-load-content-btn').on('click', this.handleLoadContent);
            $('input[name="update_type"]').on('change', this.toggleUpdateSelects);
            
            // Settings form
            $('#sir-settings-form').on('submit', this.handleSettingsSave);
            
            // Prompts form
            $('#sir-prompts-form').on('submit', this.handlePromptsSave);
            $('.sir-reset-prompt').on('click', this.handlePromptReset);
            
            // API tests
            $('.sir-test-api').on('click', this.handleApiTest);
            
            // Toggle password visibility
            $('.sir-toggle-password').on('click', this.togglePassword);
            
            // Research method toggle
            $('input[name="research_method"]').on('change', this.toggleResearchInput);
            $('input[name="post_research_method"]').on('change', this.togglePostResearchInput);
            
            // Modal close
            $('.sir-modal-close').on('click', this.closeModal);
            
            // Logs actions
            $('#sir-clear-logs').on('click', this.handleClearLogs);
            $('#sir-export-logs').on('click', this.handleExportLogs);
            
            // Queue actions
            this.initQueuePage();
        },
        
        // ============================================
        // Product Generation
        // ============================================
        
        handleProductSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $btn = $('#sir-generate-btn');
            
            if (!$('#product_name').val().trim()) {
                SIR.showNotice('error', 'لطفاً نام محصول را وارد کنید');
                return;
            }
            
            // Show progress modal
            SIR.showProgressModal();
            $btn.prop('disabled', true);
            
            // Update step 1
            SIR.updateProgressStep('research', 'loading');
            
            $.ajax({
                url: sir_ajax.url,
                type: 'POST',
                data: {
                    action: 'sir_generate_product',
                    nonce: sir_ajax.nonce,
                    product_name: $('#product_name').val(),
                    keywords: $('#keywords').val(),
                    research_method: $('input[name="research_method"]:checked').val(),
                    manual_research: $('#manual_research').val(),
                    publish_status: $('input[name="publish_status"]:checked').val()
                },
                timeout: 300000,
                success: function(response) {
                    SIR.updateProgressStep('research', 'done');
                    SIR.updateProgressStep('content', 'done');
                    SIR.updateProgressStep('publish', 'done');
                    
                    setTimeout(function() {
                        SIR.hideProgressModal();
                        
                        if (response.success) {
                            SIR.showResultModal(response.data);
                            $form[0].reset();
                        } else {
                            SIR.showNotice('error', response.data.message);
                        }
                    }, 500);
                },
                error: function(xhr, status, error) {
                    SIR.hideProgressModal();
                    SIR.showNotice('error', 'خطا در ارتباط با سرور: ' + error);
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },
        
        // ============================================
        // Post Generation
        // ============================================
        
        handlePostSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $btn = $('#sir-generate-post-btn');
            
            if (!$('#post_topic').val().trim()) {
                SIR.showNotice('error', 'لطفاً موضوع پست را وارد کنید');
                return;
            }
            
            SIR.showModal('#sir-post-progress-modal');
            $btn.prop('disabled', true);
            
            $.ajax({
                url: sir_ajax.url,
                type: 'POST',
                data: {
                    action: 'sir_generate_post',
                    nonce: sir_ajax.nonce,
                    topic: $('#post_topic').val(),
                    keywords: $('#post_keywords').val(),
                    post_type: $('#post_type').val(),
                    research_method: $('input[name="post_research_method"]:checked').val(),
                    manual_research: $('#post_manual_research').val(),
                    publish_status: $('input[name="post_publish_status"]:checked').val()
                },
                timeout: 300000,
                success: function(response) {
                    SIR.hideModal('#sir-post-progress-modal');
                    
                    if (response.success) {
                        SIR.showPostResultModal(response.data);
                        $form[0].reset();
                    } else {
                        SIR.showNotice('error', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    SIR.hideModal('#sir-post-progress-modal');
                    SIR.showNotice('error', 'خطا: ' + error);
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },
        
        // ============================================
        // Update Content
        // ============================================
        
        handleUpdateSubmit: function(e) {
            e.preventDefault();
            
            var updateType = $('input[name="update_type"]:checked').val();
            var itemId = updateType === 'product' 
                ? $('#product_id').val() 
                : $('#post_id').val();
            
            if (!itemId) {
                SIR.showNotice('error', 'لطفاً یک مورد انتخاب کنید');
                return;
            }
            
            var $btn = $('#sir-update-btn');
            SIR.showModal('#sir-update-progress-modal');
            $btn.prop('disabled', true);
            
            $.ajax({
                url: sir_ajax.url,
                type: 'POST',
                data: {
                    action: 'sir_update_content',
                    nonce: sir_ajax.nonce,
                    update_type: updateType,
                    item_id: itemId,
                    instructions: $('#update_instructions').val(),
                    refresh_research: $('input[name="refresh_research"]:checked').val() || 'no'
                },
                timeout: 300000,
                success: function(response) {
                    SIR.hideModal('#sir-update-progress-modal');
                    
                    if (response.success) {
                        SIR.showUpdateResultModal(response.data);
                    } else {
                        SIR.showNotice('error', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    SIR.hideModal('#sir-update-progress-modal');
                    SIR.showNotice('error', 'خطا: ' + error);
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },
        
        handleLoadContent: function() {
            var updateType = $('input[name="update_type"]:checked').val();
            var itemId = updateType === 'product' 
                ? $('#product_id').val() 
                : $('#post_id').val();
            
            if (!itemId) {
                SIR.showNotice('error', 'لطفاً یک مورد انتخاب کنید');
                return;
            }
            
            $.ajax({
                url: sir_ajax.url,
                type: 'POST',
                data: {
                    action: 'sir_load_content',
                    nonce: sir_ajax.nonce,
                    type: updateType,
                    item_id: itemId
                },
                success: function(response) {
                    if (response.success) {
                        var content = response.data.raw_content || 
                                      response.data.description || 
                                      response.data.content || '';
                        
                        $('#sir-current-content-display').html(
                            '<pre style="white-space: pre-wrap; direction: rtl;">' + 
                            SIR.escapeHtml(content.substring(0, 2000)) + 
                            (content.length > 2000 ? '...' : '') +
                            '</pre>'
                        );
                        $('#sir-current-content').show();
                    } else {
                        SIR.showNotice('error', response.data.message);
                    }
                }
            });
        },
        
        toggleUpdateSelects: function() {
            var type = $(this).val();
            if (type === 'product') {
                $('#product-select-row').show();
                $('#post-select-row').hide();
            } else {
                $('#product-select-row').hide();
                $('#post-select-row').show();
            }
            $('#sir-current-content').hide();
        },
        
        // ============================================
        // Settings
        // ============================================
        
        handleSettingsSave: function(e) {
            e.preventDefault();
            
            var $btn = $('#sir-save-settings-btn');
            $btn.prop('disabled', true).text('در حال ذخیره...');
            
            $.ajax({
                url: sir_ajax.url,
                type: 'POST',
                data: $(this).serialize() + '&action=sir_save_settings&nonce=' + sir_ajax.nonce,
                success: function(response) {
                    if (response.success) {
                        SIR.showNotice('success', response.data.message);
                    } else {
                        SIR.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    SIR.showNotice('error', 'خطا در ذخیره تنظیمات');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('💾 ذخیره تنظیمات');
                }
            });
        },
        
        handlePromptsSave: function(e) {
            e.preventDefault();
            
            var $btn = $('#sir-save-prompts-btn');
            $btn.prop('disabled', true).text('در حال ذخیره...');
            
            $.ajax({
                url: sir_ajax.url,
                type: 'POST',
                data: $(this).serialize() + '&action=sir_save_prompts&nonce=' + sir_ajax.nonce,
                success: function(response) {
                    if (response.success) {
                        SIR.showNotice('success', response.data.message);
                    } else {
                        SIR.showNotice('error', response.data.message);
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).text('💾 ذخیره همه پرامپت‌ها');
                }
            });
        },
        
        handlePromptReset: function() {
            var promptType = $(this).data('prompt');
            
            if (!confirm('آیا مطمئن هستید که می‌خواهید این پرامپت را به پیش‌فرض بازگردانید؟')) {
                return;
            }
            
            $.ajax({
                url: sir_ajax.url,
                type: 'POST',
                data: {
                    action: 'sir_reset_prompt',
                    nonce: sir_ajax.nonce,
                    prompt_type: promptType
                },
                success: function(response) {
                    if (response.success) {
                        $('#prompt_' + promptType).val(response.data.content);
                        SIR.showNotice('success', response.data.message);
                        SIR.updateCharCount(promptType);
                    } else {
                        SIR.showNotice('error', response.data.message);
                    }
                }
            });
        },
        
        // ============================================
        // API Testing
        // ============================================
        
        handleApiTest: function() {
            var $btn = $(this);
            var apiType = $btn.data('api');
            var $status = $('#' + apiType + '-status');
            
            $btn.prop('disabled', true).text('در حال تست...');
            $status.html('<span class="sir-testing">⏳ در حال بررسی...</span>');
            
            $.ajax({
                url: sir_ajax.url,
                type: 'POST',
                data: {
                    action: 'sir_test_api',
                    nonce: sir_ajax.nonce,
                    api_type: apiType
                },
                success: function(response) {
                    if (response.success) {
                        $status.html('<span class="sir-success">' + response.message + '</span>');
                    } else {
                        $status.html('<span class="sir-error">❌ ' + response.message + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span class="sir-error">❌ خطا در اتصال</span>');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('تست اتصال');
                }
            });
        },
        
        checkApiStatus: function() {
            var $container = $('#sir-api-status-content');
            if (!$container.length) return;
            
            $.ajax({
                url: sir_ajax.url,
                type: 'POST',
                data: {
                    action: 'sir_check_api_status',
                    nonce: sir_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var html = '';
                        
                        // Blackbox status
                        if (response.data.blackbox.success) {
                            html += '<p class="sir-api-ok">✅ Blackbox API: متصل</p>';
                        } else {
                            html += '<p class="sir-api-error">❌ Blackbox API: ' + response.data.blackbox.message + '</p>';
                        }
                        
                        // Tavily status
                        if (response.data.tavily.success) {
                            html += '<p class="sir-api-ok">✅ Tavily API: متصل</p>';
                        } else {
                            html += '<p class="sir-api-error">❌ Tavily API: ' + response.data.tavily.message + '</p>';
                        }
                        
                        $container.html(html);
                    }
                }
            });
        },
        
        // ============================================
        // Logs
        // ============================================
        
        handleClearLogs: function() {
            if (!confirm('آیا مطمئن هستید که می‌خواهید همه گزارش‌ها را پاک کنید؟')) {
                return;
            }
            
            $.ajax({
                url: sir_ajax.url,
                type: 'POST',
                data: {
                    action: 'sir_clear_logs',
                    nonce: sir_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        SIR.showNotice('error', response.data.message);
                    }
                }
            });
        },
        
        handleExportLogs: function() {
            $.ajax({
                url: sir_ajax.url,
                type: 'POST',
                data: {
                    action: 'sir_export_logs',
                    nonce: sir_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Properly decode base64 with UTF-8 support for Persian
                        var csvData = SIR.base64ToUtf8(response.data.csv);
                        // Add BOM for Excel UTF-8 compatibility
                        var bom = '\uFEFF';
                        var blob = new Blob([bom + csvData], {type: 'text/csv;charset=utf-8;'});
                        var link = document.createElement('a');
                        link.href = URL.createObjectURL(blob);
                        link.download = response.data.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        SIR.showNotice('error', response.data.message);
                    }
                }
            });
        },
        
        base64ToUtf8: function(base64) {
            try {
                var binaryString = atob(base64);
                var bytes = new Uint8Array(binaryString.length);
                for (var i = 0; i < binaryString.length; i++) {
                    bytes[i] = binaryString.charCodeAt(i);
                }
                return new TextDecoder('utf-8').decode(bytes);
            } catch (e) {
                console.error('Base64 decode error:', e);
                return atob(base64);
            }
        },
        
        // ============================================
        // UI Helpers
        // ============================================
        
        togglePassword: function() {
            var $input = $(this).siblings('input');
            var type = $input.attr('type') === 'password' ? 'text' : 'password';
            $input.attr('type', type);
            $(this).text(type === 'password' ? '👁️' : '🙈');
        },
        
        toggleResearchInput: function() {
            var method = $(this).val();
            if (method === 'manual') {
                $('.sir-manual-research').show();
            } else {
                $('.sir-manual-research').hide();
            }
        },
        
        togglePostResearchInput: function() {
            var method = $(this).val();
            if (method === 'manual') {
                $('.sir-post-manual-research').show();
            } else {
                $('.sir-post-manual-research').hide();
            }
        },
        
        initPromptTabs: function() {
            $('.sir-tab-btn').on('click', function() {
                var tab = $(this).data('tab');
                
                $('.sir-tab-btn').removeClass('active');
                $(this).addClass('active');
                
                $('.sir-prompt-tab').removeClass('active');
                $('.sir-prompt-tab[data-tab="' + tab + '"]').addClass('active');
            });
        },
        
        initCharCount: function() {
            $('.sir-prompt-editor').on('input', function() {
                var id = $(this).attr('id').replace('prompt_', '');
                SIR.updateCharCount(id);
            });
            
            // Initial count
            $('.sir-prompt-editor').each(function() {
                var id = $(this).attr('id').replace('prompt_', '');
                SIR.updateCharCount(id);
            });
        },
        
        updateCharCount: function(id) {
            var count = $('#prompt_' + id).val().length;
            $('#count_' + id).text(count.toLocaleString('fa-IR'));
        },
        
        initColorPicker: function() {
            // Sync color picker with text input
            $('#primary_color').on('input', function() {
                $('#primary_color_hex').val($(this).val().toUpperCase());
            });
            
            $('#primary_color_hex').on('input', function() {
                var color = $(this).val();
                if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
                    $('#primary_color').val(color);
                }
            });
        },
        
        // ============================================
        // Modals
        // ============================================
        
        showProgressModal: function() {
            $('#sir-progress-modal').fadeIn(200);
            $('.sir-step').each(function() {
                $(this).find('.sir-step-icon').text('⏳');
            });
            $('.sir-progress-fill').css('width', '0%');
        },
        
        hideProgressModal: function() {
            $('#sir-progress-modal').fadeOut(200);
        },
        
        updateProgressStep: function(step, status) {
            var $step = $('.sir-step[data-step="' + step + '"]');
            var icon = status === 'loading' ? '🔄' : (status === 'done' ? '✅' : '❌');
            $step.find('.sir-step-icon').text(icon);
            
            // Update progress bar
            var steps = ['research', 'content', 'publish'];
            var currentIndex = steps.indexOf(step);
            var progress = ((currentIndex + 1) / steps.length) * 100;
            $('.sir-progress-fill').css('width', progress + '%');
        },
        
        showResultModal: function(data) {
            var html = '<div class="sir-result-success">';
            html += '<h2>' + data.message + '</h2>';
            html += '<div class="sir-result-details">';
            html += '<p><strong>عنوان:</strong> ' + SIR.escapeHtml(data.title) + '</p>';
            html += '<p><strong>شناسه محصول:</strong> ' + data.product_id + '</p>';
            html += '<p><strong>تعداد FAQ:</strong> ' + data.faq_count + '</p>';
            html += '<p><strong>فیلدهای سفارشی:</strong> ' + data.custom_fields_count + '</p>';
            html += '</div>';
            html += '<div class="sir-result-actions">';
            html += '<a href="' + data.edit_link + '" class="button button-primary" target="_blank">✏️ ویرایش محصول</a>';
            html += '<a href="' + data.view_link + '" class="button" target="_blank">👁️ مشاهده محصول</a>';
            html += '</div>';
            html += '</div>';
            
            $('#sir-result-content').html(html);
            $('#sir-result-modal').fadeIn(200);
        },
        
        showPostResultModal: function(data) {
            var html = '<div class="sir-result-success">';
            html += '<h2>' + data.message + '</h2>';
            html += '<p><strong>عنوان:</strong> ' + SIR.escapeHtml(data.title) + '</p>';
            html += '<div class="sir-result-actions">';
            html += '<a href="' + data.edit_link + '" class="button button-primary" target="_blank">✏️ ویرایش پست</a>';
            html += '<a href="' + data.view_link + '" class="button" target="_blank">👁️ مشاهده پست</a>';
            html += '</div>';
            html += '</div>';
            
            $('#sir-post-result-content').html(html);
            $('#sir-post-result-modal').fadeIn(200);
        },
        
        showUpdateResultModal: function(data) {
            var html = '<div class="sir-result-success">';
            html += '<h2>' + data.message + '</h2>';
            html += '<div class="sir-result-actions">';
            html += '<a href="' + data.edit_link + '" class="button button-primary" target="_blank">✏️ مشاهده و ویرایش</a>';
            html += '</div>';
            html += '</div>';
            
            $('#sir-update-result-content').html(html);
            $('#sir-update-result-modal').fadeIn(200);
        },
        
        showModal: function(selector) {
            $(selector).fadeIn(200);
        },
        
        hideModal: function(selector) {
            $(selector).fadeOut(200);
        },
        
        closeModal: function() {
            $(this).closest('.sir-modal').fadeOut(200);
        },
        
        // ============================================
        // Utilities
        // ============================================
        
        showNotice: function(type, message) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible sir-notice"><p>' + message + '</p></div>');
            $('.sir-wrap h1').after($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        escapeHtml: function(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        // ============================================
        // Queue Management
        // ============================================
        
        initQueuePage: function() {
            if ($('#sir-queue-table').length === 0) return;
            
            // Tab switching
            $('.sir-tab-btn').on('click', function() {
                var tab = $(this).data('tab');
                $('.sir-tab-btn').removeClass('active');
                $(this).addClass('active');
                $('.sir-tab-content').removeClass('active');
                $('#tab-' + tab).addClass('active');
            });
            
            // Single add form
            $('#sir-add-single-form').on('submit', SIR.handleAddSingleToQueue);
            
            // Bulk add form
            $('#sir-add-bulk-form').on('submit', SIR.handleBulkAddToQueue);
            
            // CSV upload form
            $('#sir-upload-csv-form').on('submit', SIR.handleCsvUpload);
            
            // Process queue button
            $('.sir-process-queue-btn').on('click', SIR.handleProcessQueue);
            
            // Refresh queue
            $('#sir-refresh-queue').on('click', SIR.refreshQueueTable);
            
            // Clear completed
            $('#sir-clear-completed').on('click', SIR.handleClearCompleted);
            
            // Delete queue item
            $(document).on('click', '.sir-delete-btn', SIR.handleDeleteQueueItem);
            
            // Retry queue item
            $(document).on('click', '.sir-retry-btn', SIR.handleRetryQueueItem);
            
            // Show error
            $(document).on('click', '.sir-show-error-btn', SIR.handleShowError);
            
            // Filter status
            $('#sir-filter-status').on('change', SIR.filterQueueItems);
        },
        
        handleAddSingleToQueue: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            
            $btn.prop('disabled', true).text('در حال افزودن...');
            
            $.ajax({
                url: sir_ajax.url,
                type: 'POST',
                data: $form.serialize() + '&action=sir_add_to_queue&nonce=' + sir_ajax.nonce,
                success: function(response) {
                    if (response.success) {
                        SIR.showNotice('success', response.data.message);
                        $form[0].reset();
                        SIR.refreshQueueTable();
                    } else {
                        SIR.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    SIR.showNotice('error', 'خطا در افزودن به صف');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('افزودن به صف');
                }
            });
        },
        
        handleBulkAddToQueue: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            
            $btn.prop('disabled', true).text('در حال افزودن...');
            
            $.ajax({
                url: sir_ajax.url,
                type: 'POST',
                data: $form.serialize() + '&action=sir_bulk_add_queue&nonce=' + sir_ajax.nonce,
                success: function(response) {
                    if (response.success) {
                        SIR.showNotice('success', response.data.message);
                        $form[0].reset();
                        SIR.refreshQueueTable();
                    } else {
                        SIR.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    SIR.showNotice('error', 'خطا در افزودن گروهی به صف');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('افزودن گروهی به صف');
                }
            });
        },
        
        handleCsvUpload: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var formData = new FormData(this);
            formData.append('action', 'sir_import_csv');
            formData.append('nonce', sir_ajax.nonce);
            
            $btn.prop('disabled', true).text('در حال آپلود...');
            
            $.ajax({
                url: sir_ajax.url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        SIR.showNotice('success', response.data.message);
                        $form[0].reset();
                        
                        // Show preview
                        if (response.data.items) {
                            var html = '<table class="wp-list-table widefat">';
                            html += '<tr><th>عنوان</th><th>کلیدواژه‌ها</th><th>نوع</th><th>اولویت</th></tr>';
                            response.data.items.slice(0, 10).forEach(function(item) {
                                html += '<tr>';
                                html += '<td>' + SIR.escapeHtml(item.title) + '</td>';
                                html += '<td>' + SIR.escapeHtml(item.keywords) + '</td>';
                                html += '<td>' + SIR.escapeHtml(item.item_type) + '</td>';
                                html += '<td>' + item.priority + '</td>';
                                html += '</tr>';
                            });
                            if (response.data.items.length > 10) {
                                html += '<tr><td colspan="4">... و ' + (response.data.items.length - 10) + ' مورد دیگر</td></tr>';
                            }
                            html += '</table>';
                            $('#csv-preview-content').html(html);
                            $('#csv-preview').show();
                        }
                        
                        SIR.refreshQueueTable();
                    } else {
                        SIR.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    SIR.showNotice('error', 'خطا در آپلود CSV');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('آپلود و افزودن به صف');
                }
            });
        },
        
        handleProcessQueue: function(e) {
            e.preventDefault();
            
            if (!confirm('آیا می‌خواهید پردازش صف را شروع کنید؟\n\nاین عملیات ممکن است چند دقیقه طول بکشد.')) {
                return;
            }
            
            // Show progress modal
            $('#sir-queue-progress-modal').fadeIn(200);
            $('#sir-progress-status').html('<p>در حال پردازش...</p>');
            $('#sir-progress-log').html('');
            
            var processedCount = 0;
            var totalToProcess = 5; // Process 5 items at a time
            
            SIR.processQueueBatch(totalToProcess, function(success, data) {
                if (success) {
                    $('#sir-progress-status').html(
                        '<p>✅ پردازش تکمیل شد</p>' +
                        '<p>موفق: ' + data.success_count + '</p>' +
                        '<p>ناموفق: ' + data.failed_count + '</p>'
                    );
                    
                    // Show results
                    var logHtml = '';
                    data.results.forEach(function(result) {
                        if (result.success) {
                            logHtml += '<div style="color:green;">✅ ' + result.title + '</div>';
                        } else {
                            logHtml += '<div style="color:red;">❌ خطا: ' + result.error + '</div>';
                        }
                    });
                    $('#sir-progress-log').html(logHtml);
                    
                    $('.sir-progress-fill').css('width', '100%');
                    
                    // Refresh table after 2 seconds
                    setTimeout(function() {
                        $('#sir-queue-progress-modal').fadeOut(200);
                        SIR.refreshQueueTable();
                    }, 2000);
                } else {
                    $('#sir-progress-status').html('<p style="color:red;">❌ خطا در پردازش</p>');
                    setTimeout(function() {
                        $('#sir-queue-progress-modal').fadeOut(200);
                    }, 3000);
                }
            });
        },
        
        processQueueBatch: function(count, callback) {
            $.ajax({
                url: sir_ajax.url,
                type: 'POST',
                data: {
                    action: 'sir_process_queue',
                    nonce: sir_ajax.nonce,
                    count: count
                },
                timeout: 600000, // 10 minutes timeout
                success: function(response) {
                    if (response.success) {
                        callback(true, response.data);
                    } else {
                        callback(false, response.data);
                    }
                },
                error: function() {
                    callback(false, {message: 'خطا در ارتباط با سرور'});
                }
            });
        },
        
        refreshQueueTable: function() {
            $.ajax({
                url: sir_ajax.url,
                type: 'POST',
                data: {
                    action: 'sir_get_queue_status',
                    nonce: sir_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update stats
                        var stats = response.data.stats;
                        $('.sir-stat-card:eq(0) .sir-stat-value').text(stats.total);
                        $('.sir-stat-card:eq(1) .sir-stat-value').text(stats.pending);
                        $('.sir-stat-card:eq(2) .sir-stat-value').text(stats.processing);
                        $('.sir-stat-card:eq(3) .sir-stat-value').text(stats.completed);
                        $('.sir-stat-card:eq(4) .sir-stat-value').text(stats.failed);
                        
                        // Update table
                        var items = response.data.items;
                        var tbody = $('#sir-queue-table tbody');
                        tbody.empty();
                        
                        if (items.length === 0) {
                            tbody.append('<tr><td colspan="9" style="text-align:center;padding:40px;">📭 صف خالی است</td></tr>');
                        } else {
                            items.forEach(function(item) {
                                var row = SIR.buildQueueTableRow(item);
                                tbody.append(row);
                            });
                        }
                    }
                }
            });
        },
        
        buildQueueTableRow: function(item) {
            var statusLabels = {
                'pending': '⏳ در انتظار',
                'processing': '⚙️ در حال پردازش',
                'completed': '✅ تکمیل شده',
                'failed': '❌ ناموفق'
            };
            
            var row = '<tr data-id="' + item.id + '" data-status="' + item.status + '">';
            row += '<td>' + item.id + '</td>';
            row += '<td><strong>' + SIR.escapeHtml(item.title) + '</strong></td>';
            row += '<td><span class="sir-badge sir-badge-' + item.item_type + '">';
            row += item.item_type === 'product' ? '📦 محصول' : '📝 پست';
            row += '</span></td>';
            row += '<td><small>' + SIR.escapeHtml(item.keywords.substring(0, 50)) + (item.keywords.length > 50 ? '...' : '') + '</small></td>';
            row += '<td><span class="sir-status-badge sir-status-' + item.status + '">' + statusLabels[item.status] + '</span></td>';
            row += '<td>' + item.priority + '</td>';
            row += '<td>' + item.created_at + '</td>';
            row += '<td>' + (item.processed_at || '-') + '</td>';
            row += '<td class="sir-queue-actions">';
            
            if (item.status === 'failed') {
                row += '<button class="button button-small sir-retry-btn" data-id="' + item.id + '" title="تلاش مجدد">🔄</button>';
            }
            
            if (item.status === 'completed' && item.result_id) {
                var editLink = item.item_type === 'product' 
                    ? '/wp-admin/post.php?post=' + item.result_id + '&action=edit'
                    : '/wp-admin/post.php?post=' + item.result_id + '&action=edit';
                row += '<a href="' + editLink + '" class="button button-small" target="_blank" title="مشاهده نتیجه">👁️</a>';
            }
            
            row += '<button class="button button-small sir-delete-btn" data-id="' + item.id + '" title="حذف">🗑️</button>';
            
            if (item.status === 'failed' && item.error_message) {
                row += '<button class="button button-small sir-show-error-btn" data-error="' + SIR.escapeHtml(item.error_message) + '" title="مشاهده خطا">⚠️</button>';
            }
            
            row += '</td>';
            row += '</tr>';
            
            return row;
        },
        
        handleDeleteQueueItem: function() {
            var itemId = $(this).data('id');
            
            if (!confirm('آیا مطمئن هستید که می‌خواهید این مورد را حذف کنید؟')) {
                return;
            }
            
            $.ajax({
                url: sir_ajax.url,
                type: 'POST',
                data: {
                    action: 'sir_delete_queue_item',
                    nonce: sir_ajax.nonce,
                    item_id: itemId
                },
                success: function(response) {
                    if (response.success) {
                        SIR.showNotice('success', response.data.message);
                        $('tr[data-id="' + itemId + '"]').fadeOut(function() {
                            $(this).remove();
                        });
                        SIR.refreshQueueTable();
                    } else {
                        SIR.showNotice('error', response.data.message);
                    }
                }
            });
        },
        
        handleRetryQueueItem: function() {
            var itemId = $(this).data('id');
            
            $.ajax({
                url: sir_ajax.url,
                type: 'POST',
                data: {
                    action: 'sir_retry_queue_item',
                    nonce: sir_ajax.nonce,
                    item_id: itemId
                },
                success: function(response) {
                    if (response.success) {
                        SIR.showNotice('success', response.data.message);
                        SIR.refreshQueueTable();
                    } else {
                        SIR.showNotice('error', response.data.message);
                    }
                }
            });
        },
        
        handleClearCompleted: function() {
            if (!confirm('آیا مطمئن هستید که می‌خواهید تمام موارد تکمیل شده را پاک کنید؟')) {
                return;
            }
            
            $.ajax({
                url: sir_ajax.url,
                type: 'POST',
                data: {
                    action: 'sir_clear_completed',
                    nonce: sir_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SIR.showNotice('success', response.data.message);
                        SIR.refreshQueueTable();
                    } else {
                        SIR.showNotice('error', response.data.message);
                    }
                }
            });
        },
        
        handleShowError: function() {
            var error = $(this).data('error');
            alert('خطا:\n\n' + error);
        },
        
        filterQueueItems: function() {
            var status = $(this).val();
            
            if (status === '') {
                $('#sir-queue-table tbody tr').show();
            } else {
                $('#sir-queue-table tbody tr').each(function() {
                    var rowStatus = $(this).data('status');
                    if (rowStatus === status) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        }
    };
    
    // Make SIR globally available
    window.SIR = SIR;
    
})(jQuery);
