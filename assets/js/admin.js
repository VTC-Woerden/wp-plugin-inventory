/**
 * VTC Inventory Admin JavaScript
 * 
 * Handles admin interface functionality
 */

(function($) {
    'use strict';

    // Admin object
    const VTCAdmin = {
        
        // Initialize
        init: function() {
            this.bindEvents();
            this.initializeCharts();
        },
        
        // Bind event listeners
        bindEvents: function() {
            // Reset login attempts
            $('#reset-attempts').on('click', this.resetLoginAttempts);
            
            // Export functions
            $('#export-csv').on('click', this.exportCSV);
            $('#export-pdf').on('click', this.exportPDF);
            $('#print-report').on('click', this.printReport);
            
            // Tools page
            $('#export-all-csv').on('click', this.exportAllCSV);
            $('#export-all-pdf').on('click', this.exportAllPDF);
            $('#reset-login-attempts').on('click', this.resetLoginAttempts);
            $('#regenerate-qr-codes').on('click', this.regenerateQRCodes);
            
            // Migration
            $('#migration-form').on('submit', this.handleMigration);
        },
        
        // Reset login attempts
        resetLoginAttempts: function(e) {
            e.preventDefault();
            
            if (!confirm('Weet je zeker dat je de mislukte inlogpogingen wilt resetten?')) {
                return;
            }
            
            $.ajax({
                url: vtc_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vtc_reset_login_attempts',
                    nonce: vtc_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VTCAdmin.showNotice(response.data.message, 'success');
                        $('#failed-attempts').text('0');
                    } else {
                        VTCAdmin.showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    VTCAdmin.showNotice('Er is een fout opgetreden.', 'error');
                }
            });
        },
        
        // Export CSV
        exportCSV: function(e) {
            e.preventDefault();
            window.open(vtc_admin_ajax.ajax_url + '?action=vtc_export_inventory&format=csv&nonce=' + vtc_admin_ajax.nonce, '_blank');
        },
        
        // Export PDF
        exportPDF: function(e) {
            e.preventDefault();
            window.open(vtc_admin_ajax.ajax_url + '?action=vtc_export_inventory&format=pdf&nonce=' + vtc_admin_ajax.nonce, '_blank');
        },
        
        // Print report
        printReport: function(e) {
            e.preventDefault();
            window.print();
        },
        
        // Export all CSV
        exportAllCSV: function(e) {
            e.preventDefault();
            window.open(vtc_admin_ajax.ajax_url + '?action=vtc_export_inventory&format=csv&all=1&nonce=' + vtc_admin_ajax.nonce, '_blank');
        },
        
        // Export all PDF
        exportAllPDF: function(e) {
            e.preventDefault();
            window.open(vtc_admin_ajax.ajax_url + '?action=vtc_export_inventory&format=pdf&all=1&nonce=' + vtc_admin_ajax.nonce, '_blank');
        },
        
        // Regenerate QR codes
        regenerateQRCodes: function(e) {
            e.preventDefault();
            
            if (!confirm('Weet je zeker dat je alle QR codes wilt regenereren? Dit kan even duren.')) {
                return;
            }
            
            const $btn = $(this);
            const originalText = $btn.text();
            
            $btn.text('QR codes regenereren...').prop('disabled', true);
            
            $.ajax({
                url: vtc_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vtc_regenerate_qr_codes',
                    nonce: vtc_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VTCAdmin.showNotice(response.data.message, 'success');
                    } else {
                        VTCAdmin.showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    VTCAdmin.showNotice('Er is een fout opgetreden bij het regenereren van QR codes.', 'error');
                },
                complete: function() {
                    $btn.text(originalText).prop('disabled', false);
                }
            });
        },
        
        // Handle migration
        handleMigration: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('input[type="submit"]');
            const originalText = $submitBtn.val();
            
            $submitBtn.val('Migreren...').prop('disabled', true);
            
            $.ajax({
                url: vtc_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vtc_migrate_data',
                    nonce: vtc_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VTCAdmin.showNotice(response.data.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        VTCAdmin.showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    VTCAdmin.showNotice('Er is een fout opgetreden bij de migratie.', 'error');
                },
                complete: function() {
                    $submitBtn.val(originalText).prop('disabled', false);
                }
            });
        },
        
        // Initialize charts
        initializeCharts: function() {
            // This would initialize any charts on the admin pages
            if (typeof Chart !== 'undefined') {
                // Chart initialization code would go here
            }
        },
        
        // Show notice
        showNotice: function(message, type) {
            const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            const noticeHtml = `
                <div class="notice ${noticeClass} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `;
            
            $('.wrap h1').after(noticeHtml);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $('.notice').fadeOut();
            }, 5000);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        VTCAdmin.init();
    });
    
    // Make VTCAdmin available globally
    window.VTCAdmin = VTCAdmin;
    
})(jQuery);
