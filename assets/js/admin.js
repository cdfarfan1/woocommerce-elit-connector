/**
 * NewBytes WooCommerce Connector - Admin JavaScript
 * Interactive functionality for the admin interface
 */

(function($) {
    'use strict';
    
    // Main admin object
    const NBAdmin = {
        
        // Initialize the admin interface
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initTooltips();
            this.checkSyncStatus();
            this.startStatusPolling();
        },
        
        // Bind event handlers
        bindEvents: function() {
            // Sync products button
            $(document).on('click', '.nb-btn-sync', this.syncProducts.bind(this));
            
            // Test connection button
            $(document).on('click', '.nb-btn-test-connection', this.testConnection.bind(this));
            
            // Clear cache button
            $(document).on('click', '.nb-btn-clear-cache', this.clearCache.bind(this));
            
            // Clear logs button
            $(document).on('click', '.nb-btn-clear-logs', this.clearLogs.bind(this));
            
            // Refresh logs button
            $(document).on('click', '.nb-btn-refresh-logs', this.refreshLogs.bind(this));
            
            // Log type filter
            $(document).on('change', '#nb-log-type-filter', this.filterLogs.bind(this));
            
            // Show log details
            $(document).on('click', '.nb-show-details', this.showLogDetails.bind(this));
            
            // Modal close
            $(document).on('click', '.nb-modal-close, .nb-modal', this.closeModal.bind(this));
            
            // Prevent modal content click from closing modal
            $(document).on('click', '.nb-modal-content', function(e) {
                e.stopPropagation();
            });
            
            // Tab navigation
            $(document).on('click', '.nb-tab-link', this.switchTab.bind(this));
            
            // Auto-refresh stats
            setInterval(this.refreshStats.bind(this), 30000); // Every 30 seconds
        },
        
        // Initialize tabs functionality
        initTabs: function() {
            $('.nb-tab-link').first().addClass('active');
            $('.nb-tab-panel').first().addClass('active');
        },
        
        // Initialize tooltips
        initTooltips: function() {
            // Add tooltips to buttons and icons
            $('[data-tooltip]').each(function() {
                $(this).attr('title', $(this).data('tooltip'));
            });
        },
        
        // Switch between tabs
        switchTab: function(e) {
            e.preventDefault();
            
            const $link = $(e.currentTarget);
            const targetTab = $link.attr('href');
            
            // Update active tab link
            $('.nb-tab-link').removeClass('active');
            $link.addClass('active');
            
            // Update active tab panel
            $('.nb-tab-panel').removeClass('active');
            $(targetTab).addClass('active');
        },
        
        // Sync products
        syncProducts: function(e) {
            e.preventDefault();
            
            if (!confirm(nbAdmin.strings.confirmSync)) {
                return;
            }
            
            const $button = $(e.currentTarget);
            const $progress = $('.nb-sync-progress');
            const $progressFill = $('.nb-progress-fill');
            const $progressText = $('.nb-progress-text');
            
            // Disable button and show progress
            $button.prop('disabled', true).addClass('nb-loading');
            $progress.show();
            $progressText.text(nbAdmin.strings.syncInProgress);
            
            // Start progress animation
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 10;
                if (progress > 90) progress = 90;
                $progressFill.css('width', progress + '%');
            }, 500);
            
            // Make AJAX request
            $.ajax({
                url: nbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'nb_sync_products',
                    nonce: nbAdmin.nonce
                },
                success: (response) => {
                    clearInterval(progressInterval);
                    $progressFill.css('width', '100%');
                    
                    if (response.success) {
                        $progressText.text(nbAdmin.strings.syncCompleted);
                        this.showNotification('success', response.message || nbAdmin.strings.syncCompleted);
                        this.refreshStats();
                        this.updateSyncStatus('completed');
                    } else {
                        $progressText.text(nbAdmin.strings.syncFailed);
                        this.showNotification('error', response.message || nbAdmin.strings.syncFailed);
                        this.updateSyncStatus('error');
                    }
                    
                    // Hide progress after delay
                    setTimeout(() => {
                        $progress.hide();
                        $button.prop('disabled', false).removeClass('nb-loading');
                        $progressFill.css('width', '0%');
                    }, 2000);
                },
                error: (xhr, status, error) => {
                    clearInterval(progressInterval);
                    $progressText.text(nbAdmin.strings.syncFailed);
                    this.showNotification('error', 'Error: ' + error);
                    this.updateSyncStatus('error');
                    
                    setTimeout(() => {
                        $progress.hide();
                        $button.prop('disabled', false).removeClass('nb-loading');
                        $progressFill.css('width', '0%');
                    }, 2000);
                }
            });
        },
        
        // Test API connection
        testConnection: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const originalText = $button.text();
            
            $button.prop('disabled', true).addClass('nb-loading')
                   .html('<span class="dashicons dashicons-update"></span> ' + nbAdmin.strings.connectionTesting);
            
            $.ajax({
                url: nbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'nb_test_connection',
                    nonce: nbAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('success', response.message || nbAdmin.strings.connectionSuccess);
                    } else {
                        this.showNotification('error', response.message || nbAdmin.strings.connectionFailed);
                    }
                },
                error: (xhr, status, error) => {
                    this.showNotification('error', nbAdmin.strings.connectionFailed + ': ' + error);
                },
                complete: () => {
                    $button.prop('disabled', false).removeClass('nb-loading').html(originalText);
                }
            });
        },
        
        // Clear cache
        clearCache: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const originalText = $button.text();
            
            $button.prop('disabled', true).addClass('nb-loading')
                   .html('<span class="dashicons dashicons-update"></span> Limpiando...');
            
            // Simulate cache clearing (you can implement actual AJAX call)
            setTimeout(() => {
                this.showNotification('success', 'Caché limpiado correctamente');
                $button.prop('disabled', false).removeClass('nb-loading').html(originalText);
            }, 1000);
        },
        
        // Clear logs
        clearLogs: function(e) {
            e.preventDefault();
            
            if (!confirm(nbAdmin.strings.confirmClearLogs)) {
                return;
            }
            
            const $button = $(e.currentTarget);
            const originalText = $button.text();
            
            $button.prop('disabled', true).addClass('nb-loading')
                   .html('<span class="dashicons dashicons-update"></span> Limpiando...');
            
            $.ajax({
                url: nbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'nb_clear_logs',
                    nonce: nbAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('success', response.message);
                        this.refreshLogs();
                    } else {
                        this.showNotification('error', response.message || 'Error al limpiar logs');
                    }
                },
                error: (xhr, status, error) => {
                    this.showNotification('error', 'Error: ' + error);
                },
                complete: () => {
                    $button.prop('disabled', false).removeClass('nb-loading').html(originalText);
                }
            });
        },
        
        // Refresh logs
        refreshLogs: function() {
            const logType = $('#nb-log-type-filter').val() || 'all';
            window.location.href = window.location.pathname + '?page=newbytes-logs&log_type=' + logType;
        },
        
        // Filter logs
        filterLogs: function(e) {
            const logType = $(e.currentTarget).val();
            window.location.href = window.location.pathname + '?page=newbytes-logs&log_type=' + logType;
        },
        
        // Show log details in modal
        showLogDetails: function(e) {
            e.preventDefault();
            
            const details = $(e.currentTarget).data('details');
            let formattedDetails;
            
            try {
                // Try to parse as JSON for better formatting
                const parsed = JSON.parse(details);
                formattedDetails = JSON.stringify(parsed, null, 2);
            } catch (error) {
                formattedDetails = details;
            }
            
            $('#nb-log-details-content').text(formattedDetails);
            $('#nb-log-details-modal').show();
        },
        
        // Close modal
        closeModal: function(e) {
            if (e.target === e.currentTarget) {
                $('.nb-modal').hide();
            }
        },
        
        // Check sync status
        checkSyncStatus: function() {
            $.ajax({
                url: nbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'nb_get_sync_status',
                    nonce: nbAdmin.nonce
                },
                success: (response) => {
                    if (response.status) {
                        this.updateSyncStatus(response.status);
                    }
                }
            });
        },
        
        // Update sync status display
        updateSyncStatus: function(status) {
            const $indicator = $('.nb-status-indicator');
            const $text = $('.nb-status-text');
            const $button = $('.nb-btn-sync');
            
            // Remove all status classes
            $indicator.removeClass('nb-status-idle nb-status-running nb-status-completed nb-status-error');
            
            // Add new status class
            $indicator.addClass('nb-status-' + status);
            
            // Update text and button state
            switch (status) {
                case 'running':
                    $text.text('Sincronización en progreso');
                    $button.prop('disabled', true);
                    break;
                case 'completed':
                    $text.text('Última sincronización completada');
                    $button.prop('disabled', false);
                    break;
                case 'error':
                    $text.text('Error en la última sincronización');
                    $button.prop('disabled', false);
                    break;
                default:
                    $text.text('Listo para sincronizar');
                    $button.prop('disabled', false);
            }
        },
        
        // Start status polling
        startStatusPolling: function() {
            // Poll sync status every 10 seconds
            setInterval(() => {
                this.checkSyncStatus();
            }, 10000);
        },
        
        // Refresh statistics
        refreshStats: function() {
            $.ajax({
                url: nbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'nb_get_stats',
                    nonce: nbAdmin.nonce
                },
                success: (response) => {
                    if (response.total_products !== undefined) {
                        $('.nb-card-primary h3').text(this.formatNumber(response.total_products));
                    }
                    if (response.nb_products !== undefined) {
                        $('.nb-card-success h3').text(this.formatNumber(response.nb_products));
                    }
                    if (response.database_size !== undefined) {
                        $('.nb-card-info h3').text(response.database_size);
                    }
                }
            });
        },
        
        // Format number with thousands separator
        formatNumber: function(num) {
            return new Intl.NumberFormat().format(num);
        },
        
        // Show notification
        showNotification: function(type, message, duration = 5000) {
            // Remove existing notifications
            $('.nb-notification').remove();
            
            // Create notification element
            const $notification = $('<div class="nb-notification ' + type + '">');
            $notification.html('<p>' + message + '</p>');
            
            // Add to page
            $('body').append($notification);
            
            // Auto-remove after duration
            setTimeout(() => {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, duration);
            
            // Allow manual close
            $notification.on('click', function() {
                $(this).fadeOut(300, function() {
                    $(this).remove();
                });
            });
        },
        
        // Utility: Debounce function
        debounce: function(func, wait, immediate) {
            let timeout;
            return function executedFunction() {
                const context = this;
                const args = arguments;
                const later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },
        
        // Utility: Throttle function
        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        }
    };
    
    // Enhanced form validation
    const NBFormValidator = {
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Real-time validation
            $('.nb-settings-form input, .nb-settings-form select').on('blur', this.validateField.bind(this));
            $('.nb-settings-form').on('submit', this.validateForm.bind(this));
        },
        
        validateField: function(e) {
            const $field = $(e.currentTarget);
            const value = $field.val();
            const type = $field.attr('type') || $field.prop('tagName').toLowerCase();
            
            this.clearFieldError($field);
            
            // Validate based on field type
            switch (type) {
                case 'url':
                    if (value && !this.isValidUrl(value)) {
                        this.showFieldError($field, 'Por favor, ingresa una URL válida');
                        return false;
                    }
                    break;
                    
                case 'email':
                    if (value && !this.isValidEmail(value)) {
                        this.showFieldError($field, 'Por favor, ingresa un email válido');
                        return false;
                    }
                    break;
                    
                case 'number':
                    const min = parseInt($field.attr('min'));
                    const max = parseInt($field.attr('max'));
                    const numValue = parseInt(value);
                    
                    if (value && (isNaN(numValue) || (min && numValue < min) || (max && numValue > max))) {
                        this.showFieldError($field, `Por favor, ingresa un número entre ${min} y ${max}`);
                        return false;
                    }
                    break;
            }
            
            // Required field validation
            if ($field.prop('required') && !value.trim()) {
                this.showFieldError($field, 'Este campo es requerido');
                return false;
            }
            
            return true;
        },
        
        validateForm: function(e) {
            let isValid = true;
            const $form = $(e.currentTarget);
            
            $form.find('input, select').each((index, field) => {
                if (!this.validateField({ currentTarget: field })) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                NBAdmin.showNotification('error', 'Por favor, corrige los errores en el formulario');
            }
            
            return isValid;
        },
        
        showFieldError: function($field, message) {
            $field.addClass('error');
            
            const $error = $('<div class="field-error">' + message + '</div>');
            $field.after($error);
        },
        
        clearFieldError: function($field) {
            $field.removeClass('error');
            $field.next('.field-error').remove();
        },
        
        isValidUrl: function(url) {
            try {
                new URL(url);
                return true;
            } catch {
                return false;
            }
        },
        
        isValidEmail: function(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    };
    
    // Enhanced table functionality
    const NBTableEnhancer = {
        
        init: function() {
            this.enhanceTables();
        },
        
        enhanceTables: function() {
            // Add sorting to log tables
            $('.nb-logs-container table th').each(function(index) {
                if (index < 4) { // Only first 4 columns are sortable
                    $(this).addClass('sortable').css('cursor', 'pointer');
                    $(this).on('click', NBTableEnhancer.sortTable.bind(NBTableEnhancer, index));
                }
            });
            
            // Add row highlighting
            $('.nb-logs-container table tbody tr').hover(
                function() { $(this).addClass('highlight'); },
                function() { $(this).removeClass('highlight'); }
            );
        },
        
        sortTable: function(columnIndex) {
            const $table = $('.nb-logs-container table');
            const $tbody = $table.find('tbody');
            const $rows = $tbody.find('tr').toArray();
            
            const isAscending = !$table.data('sort-asc-' + columnIndex);
            $table.data('sort-asc-' + columnIndex, isAscending);
            
            $rows.sort((a, b) => {
                const aText = $(a).find('td').eq(columnIndex).text().trim();
                const bText = $(b).find('td').eq(columnIndex).text().trim();
                
                // Handle dates
                if (columnIndex === 0) {
                    const aDate = new Date(aText);
                    const bDate = new Date(bText);
                    return isAscending ? aDate - bDate : bDate - aDate;
                }
                
                // Handle text
                return isAscending ? 
                    aText.localeCompare(bText) : 
                    bText.localeCompare(aText);
            });
            
            $tbody.empty().append($rows);
            
            // Update sort indicators
            $table.find('th').removeClass('sort-asc sort-desc');
            $table.find('th').eq(columnIndex).addClass(isAscending ? 'sort-asc' : 'sort-desc');
        }
    };
    
    // Keyboard shortcuts
    const NBKeyboardShortcuts = {
        
        init: function() {
            $(document).on('keydown', this.handleKeydown.bind(this));
        },
        
        handleKeydown: function(e) {
            // Only handle shortcuts when not in input fields
            if ($(e.target).is('input, textarea, select')) {
                return;
            }
            
            // Ctrl/Cmd + S: Save settings (if on settings page)
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                $('.nb-settings-form input[type="submit"]').click();
            }
            
            // Ctrl/Cmd + R: Refresh logs (if on logs page)
            if ((e.ctrlKey || e.metaKey) && e.key === 'r' && $('.nb-btn-refresh-logs').length) {
                e.preventDefault();
                $('.nb-btn-refresh-logs').click();
            }
            
            // Escape: Close modals
            if (e.key === 'Escape') {
                $('.nb-modal').hide();
            }
        }
    };
    
    // Initialize everything when document is ready
    $(document).ready(function() {
        NBAdmin.init();
        NBFormValidator.init();
        NBTableEnhancer.init();
        NBKeyboardShortcuts.init();
        
        // Add some CSS for enhanced functionality
        $('<style>').text(`
            .field-error {
                color: #dc3232;
                font-size: 12px;
                margin-top: 4px;
            }
            
            input.error, select.error {
                border-color: #dc3232 !important;
                box-shadow: 0 0 0 1px #dc3232 !important;
            }
            
            .sortable::after {
                content: '↕';
                margin-left: 5px;
                opacity: 0.5;
            }
            
            .sort-asc::after {
                content: '↑';
                opacity: 1;
            }
            
            .sort-desc::after {
                content: '↓';
                opacity: 1;
            }
            
            .highlight {
                background-color: #f6f7f7 !important;
            }
        `).appendTo('head');
    });
    
})(jQuery);