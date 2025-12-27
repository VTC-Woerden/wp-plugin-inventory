/**
 * VTC Inventory Frontend JavaScript
 * 
 * Handles all frontend functionality for the inventory system
 */

(function($) {
    'use strict';

    // Main inventory object
    const VTCInventory = {
        
        // Configuration
        config: {
            ajaxUrl: vtc_ajax.ajax_url,
            nonce: vtc_ajax.nonce,
            isLoggedIn: vtc_ajax.is_logged_in,
            canManage: vtc_ajax.can_manage,
            canView: vtc_ajax.can_view,
            userPermissions: vtc_ajax.user_permissions,
            loginUrl: vtc_ajax.login_url,
            logoutUrl: vtc_ajax.logout_url,
            strings: vtc_ajax.strings
        },
        
        // Initialize
        init: function() {
            this.bindEvents();
            this.initializeQRCodes();
            this.initializeFilters();
            this.initializeLazyLoading();
        },
        
        // Bind event listeners
        bindEvents: function() {
            // Search functionality
            $('#searchInput').on('keyup', this.debounce(this.handleSearch, 300));
            $('#searchBtn').on('click', this.handleSearch);
            
            // Filter functionality
            $('#locationFilter, #ownerFilter, #conditionFilter').on('change', this.handleFilter);
            $('#clearFilters').on('click', this.clearFilters);
            
            // Item management
            $(document).on('click', '.edit-item-btn', this.handleEditItem);
            $(document).on('click', '#addNewItemBtn', this.handleAddItem);
            $(document).on('click', '.item-photo', this.handlePhotoClick);
            
            // PDF generation
            $('#selectAllBtn').on('click', this.selectAllItems);
            $('#deselectAllBtn').on('click', this.deselectAllItems);
            $('#generatePdfBtn').on('click', this.generatePDF);
            $('.item-checkbox').on('change', this.updatePDFButton);
            
            // Modal events
            $('#itemModal').on('hidden.bs.modal', this.resetModal);
            
            // Form submission
            $(document).on('submit', '#itemModal form', this.handleFormSubmit);
            
            // Location select handling
            $(document).on('change', '#location-select', this.handleLocationSelect);
        },
        
        // Handle search
        handleSearch: function() {
            const searchTerm = $('#searchInput').val().toLowerCase().trim();
            VTCInventory.filterItems();
        },
        
        // Handle filter changes
        handleFilter: function() {
            VTCInventory.filterItems();
        },
        
        // Filter items based on search and filters
        filterItems: function() {
            const searchTerm = $('#searchInput').val().toLowerCase().trim();
            const locationFilter = $('#locationFilter').val();
            const ownerFilter = $('#ownerFilter').val();
            const conditionFilter = $('#conditionFilter').val();
            
            const items = $('.item-item');
            let visibleCount = 0;
            
            items.each(function() {
                const $item = $(this);
                const name = $item.data('name') || '';
                const location = $item.data('location') || '';
                const owner = $item.data('owner') || '';
                const condition = $item.data('condition') || '';
                
                // Search in name and other text content
                const itemText = $item.text().toLowerCase();
                const matchesSearch = !searchTerm || itemText.includes(searchTerm);
                const matchesLocation = !locationFilter || location.includes(locationFilter);
                const matchesOwner = !ownerFilter || owner.includes(ownerFilter);
                const matchesCondition = !conditionFilter || condition.includes(conditionFilter);
                
                if (matchesSearch && matchesLocation && matchesOwner && matchesCondition) {
                    $item.show();
                    visibleCount++;
                } else {
                    $item.hide();
                    // Uncheck hidden items
                    $item.find('.item-checkbox').prop('checked', false);
                }
            });
            
            // Show/hide no results message
            if (visibleCount === 0) {
                $('#noResults').show();
                $('#itemsContainer').hide();
            } else {
                $('#noResults').hide();
                $('#itemsContainer').show();
            }
            
            // Update visible count
            $('#visibleCount').text(visibleCount);
            VTCInventory.updatePDFButton();
        },
        
        // Clear all filters
        clearFilters: function() {
            $('#searchInput').val('');
            $('#locationFilter').val('');
            $('#ownerFilter').val('');
            $('#conditionFilter').val('');
            
            // Clear all selections
            $('.item-checkbox').prop('checked', false);
            
            VTCInventory.filterItems();
        },
        
        // Handle edit item
        handleEditItem: function(e) {
            e.preventDefault();
            const itemId = $(this).data('item-id');
            VTCInventory.loadEditModal(itemId);
        },
        
        // Handle add item
        handleAddItem: function(e) {
            e.preventDefault();
            VTCInventory.loadAddModal();
        },
        
        // Load edit modal
        loadEditModal: function(itemId) {
            this.showModal('Materiaal Bewerken');
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vtc_inventory_action',
                    action_type: 'get_modal_content',
                    modal_type: 'edit',
                    item_id: itemId,
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VTCInventory.populateModal(response.data);
                    } else {
                        VTCInventory.showError(response.data.message);
                    }
                },
                error: function() {
                    VTCInventory.showError('Er is een fout opgetreden bij het laden van het item.');
                }
            });
        },
        
        // Load add modal
        loadAddModal: function() {
            this.showModal('Nieuw Materiaal Toevoegen');
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vtc_inventory_action',
                    action_type: 'get_modal_content',
                    modal_type: 'add',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VTCInventory.populateModal(response.data);
                    } else {
                        VTCInventory.showError(response.data.message);
                    }
                },
                error: function() {
                    VTCInventory.showError('Er is een fout opgetreden bij het laden van het formulier.');
                }
            });
        },
        
        // Show modal
        showModal: function(title) {
            $('#modalTitle').text(title);
            $('#modalBody').html('<div class="text-center py-4"><div class="vtc-loading"></div><p class="mt-2">Laden...</p></div>');
            $('#itemModal').modal('show');
        },
        
        // Populate modal with form
        populateModal: function(data) {
            let formHtml = this.generateFormHtml(data);
            $('#modalBody').html(formHtml);
            this.initializeFormHandlers();
        },
        
        // Generate form HTML
        generateFormHtml: function(data) {
            const isEdit = data && data.id;
            const itemId = isEdit ? data.id : '';
            const name = isEdit ? data.name : '';
            const description = isEdit ? data.description : '';
            const quantity = isEdit ? data.quantity : 1;
            const comments = isEdit ? data.comments : '';
            const owner = isEdit && data.owner ? data.owner[0] : '';
            const condition = isEdit && data.condition ? data.condition[0] : '';
            const location = isEdit && data.location ? data.location[0] : '';
            
            return `
                <form method="post" enctype="multipart/form-data" id="itemForm">
                    ${isEdit ? `<input type="hidden" name="item_id" value="${itemId}">` : ''}
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Naam *</label>
                            <input name="name" class="form-control" required value="${name}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Aantal *</label>
                            <input type="number" name="quantity" class="form-control" required value="${quantity}" min="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Eigenaar</label>
                            <select name="owner" class="form-select">
                                <option value="">-- Kies eigenaar --</option>
                                <option value="gemeente" ${owner === 'Gemeente' ? 'selected' : ''}>Gemeente</option>
                                <option value="vtc-woerden" ${owner === 'VTC Woerden' ? 'selected' : ''}>VTC Woerden</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Conditie</label>
                            <select name="condition" class="form-select">
                                <option value="">-- Kies conditie --</option>
                                <option value="zeer-goed" ${condition === 'Zeer goed' ? 'selected' : ''}>Zeer goed</option>
                                <option value="goed" ${condition === 'Goed' ? 'selected' : ''}>Goed</option>
                                <option value="redelijk" ${condition === 'Redelijk' ? 'selected' : ''}>Redelijk</option>
                                <option value="slecht" ${condition === 'Slecht' ? 'selected' : ''}>Slecht</option>
                                <option value="zeer-slecht" ${condition === 'Zeer slecht' ? 'selected' : ''}>Zeer slecht</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Locatie</label>
                            <select name="location" id="location-select" class="form-select">
                                <option value="">-- Kies locatie --</option>
                                <option value="trainerskast-1" ${location === 'Trainerskast 1' ? 'selected' : ''}>Trainerskast 1</option>
                                <option value="trainerskast-2" ${location === 'Trainerskast 2' ? 'selected' : ''}>Trainerskast 2</option>
                                <option value="trainerskast-3" ${location === 'Trainerskast 3' ? 'selected' : ''}>Trainerskast 3</option>
                                <option value="materiaalruimte" ${location === 'Materiaalruimte' ? 'selected' : ''}>Materiaalruimte</option>
                                <option value="beheerdersruimte" ${location === 'Beheerdersruimte' ? 'selected' : ''}>Beheerdersruimte</option>
                                <option value="zaal" ${location === 'Zaal' ? 'selected' : ''}>Zaal</option>
                                <option value="lokaal" ${location === 'Lokaal' ? 'selected' : ''}>Lokaal</option>
                                <option value="kantine" ${location === 'Kantine' ? 'selected' : ''}>Kantine</option>
                                <option value="keuken" ${location === 'Keuken' ? 'selected' : ''}>Keuken</option>
                                <option value="vide" ${location === 'Vide' ? 'selected' : ''}>Vide</option>
                                <option value="ballenkar" ${location === 'Ballenkar' ? 'selected' : ''}>Ballenkar</option>
                                <option value="other">Nieuwe locatie...</option>
                            </select>
                            <input type="text" name="location_new" id="location-new" class="form-control mt-2" 
                                   placeholder="Nieuwe locatie" style="display:none;">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Beschrijving</label>
                            <textarea name="description" class="form-control" rows="3">${description}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Opmerkingen</label>
                            <textarea name="comments" class="form-control" rows="3">${comments}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Foto('s)</label>
                            <input type="file" name="photos[]" class="form-control" accept="image/*" multiple>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        ${isEdit ? `<button type="button" class="btn btn-danger me-auto" id="deleteItemBtn" data-item-id="${itemId}"><i class="fas fa-trash me-1"></i> Verwijderen</button>` : ''}
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            ${isEdit ? 'Bijwerken' : 'Opslaan'}
                        </button>
                    </div>
                </form>
            `;
        },
        
        // Initialize form handlers
        initializeFormHandlers: function() {
            // Location select handling
            $('#location-select').on('change', function() {
                if ($(this).val() === 'other') {
                    $('#location-new').show().prop('required', true);
                } else {
                    $('#location-new').hide().prop('required', false);
                }
            });
            
            // Delete button
            $('#deleteItemBtn').on('click', function() {
                if (confirm('Weet je het zeker dat je dit item wilt verwijderen?')) {
                    const itemId = $(this).data('item-id');
                    VTCInventory.deleteItem(itemId);
                }
            });
        },
        
        // Handle form submission
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.html();
            
            // Show loading state
            $submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i> Opslaan...').prop('disabled', true);
            
            const formData = new FormData(this);
            formData.append('action', 'vtc_inventory_action');
            formData.append('action_type', 'update_item');
            formData.append('nonce', VTCInventory.config.nonce);
            
            $.ajax({
                url: VTCInventory.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $('#itemModal').modal('hide');
                        VTCInventory.showSuccess(response.data.message);
                        location.reload(); // Refresh to show changes
                    } else {
                        VTCInventory.showError(response.data.message);
                    }
                },
                error: function() {
                    VTCInventory.showError('Er is een fout opgetreden bij het opslaan.');
                },
                complete: function() {
                    $submitBtn.html(originalText).prop('disabled', false);
                }
            });
        },
        
        // Delete item
        deleteItem: function(itemId) {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vtc_inventory_action',
                    action_type: 'delete_item',
                    item_id: itemId,
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#itemModal').modal('hide');
                        VTCInventory.showSuccess(response.data.message);
                        location.reload();
                    } else {
                        VTCInventory.showError(response.data.message);
                    }
                },
                error: function() {
                    VTCInventory.showError('Er is een fout opgetreden bij het verwijderen.');
                }
            });
        },
        
        // Handle photo click
        handlePhotoClick: function(e) {
            e.preventDefault();
            const itemId = $(this).data('item-id');
            VTCInventory.showPhotoModal(itemId);
        },
        
        // Show photo modal
        showPhotoModal: function(itemId) {
            // This would load photos for the specific item
            $('#photoModal').modal('show');
        },
        
        
        // PDF generation
        selectAllItems: function() {
            $('.item-item:visible .item-checkbox').prop('checked', true);
            VTCInventory.updatePDFButton();
        },
        
        deselectAllItems: function() {
            $('.item-checkbox').prop('checked', false);
            VTCInventory.updatePDFButton();
        },
        
        updatePDFButton: function() {
            const checkedBoxes = $('.item-checkbox:checked');
            const selectedCount = checkedBoxes.length;
            const $pdfBtn = $('#generatePdfBtn');
            
            $pdfBtn.prop('disabled', selectedCount === 0);
            $pdfBtn.html(`<i class="fas fa-file-pdf me-1"></i> PDF genereren (${selectedCount})`);
            $('#selectedCount').text(selectedCount);
        },
        
        generatePDF: function() {
            const checkedBoxes = $('.item-checkbox:checked');
            if (checkedBoxes.length === 0) return;
            
            const selectedItems = [];
            checkedBoxes.each(function() {
                selectedItems.push({
                    id: $(this).data('item-id'),
                    name: $(this).data('item-name'),
                    quantity: $(this).data('item-quantity')
                });
            });
            
            const layout = $('#largeQrLayout').is(':checked') ? 'large' : 'grid';
            const location = $('#locationFilter').val();
            
            // Create form and submit
            const form = $('<form>', {
                method: 'POST',
                action: VTCInventory.config.ajaxUrl,
                target: '_blank'
            });
            
            form.append($('<input>', { type: 'hidden', name: 'action', value: 'vtc_generate_pdf' }));
            form.append($('<input>', { type: 'hidden', name: 'nonce', value: VTCInventory.config.nonce }));
            form.append($('<input>', { type: 'hidden', name: 'selected_items', value: JSON.stringify(selectedItems) }));
            form.append($('<input>', { type: 'hidden', name: 'layout', value: layout }));
            form.append($('<input>', { type: 'hidden', name: 'location', value: location }));
            
            $('body').append(form);
            form.submit();
            form.remove();
        },
        
        // Initialize QR codes
        initializeQRCodes: function() {
            $('[id^="qr-"]').each(function() {
                const $container = $(this);
                const itemId = $container.attr('id').replace('qr-', '');
                const itemName = $container.closest('.item-item').find('.card-title').text();
                const qrUrl = `https://vtcwoerden.nl/materiaal/?object=${encodeURIComponent(itemName)}`;
                
                VTCInventory.generateQRCode($container, qrUrl);
            });
        },
        
        // Generate QR code
        generateQRCode: function($container, url) {
            if (typeof qrcode !== 'undefined') {
                try {
                    const qr = qrcode(0, 'M');
                    qr.addData(url);
                    qr.make();
                    const qrImage = qr.createImgTag(4, 2);
                    $container.html(qrImage);
                } catch (e) {
                    console.error('QR generation failed:', e);
                    VTCInventory.generateQRCodeFallback($container, url);
                }
            } else {
                VTCInventory.generateQRCodeFallback($container, url);
            }
        },
        
        // QR code fallback
        generateQRCodeFallback: function($container, url) {
            const qrImageUrl = `https://chart.googleapis.com/chart?cht=qr&chs=120x120&chl=${encodeURIComponent(url)}&choe=UTF-8`;
            $container.html(`<img src="${qrImageUrl}" alt="QR Code" style="width: 120px; height: 120px;">`);
        },
        
        // Initialize lazy loading
        initializeLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.classList.add('loaded');
                                observer.unobserve(img);
                            }
                        }
                    });
                });
                
                $('img[data-src]').each(function() {
                    imageObserver.observe(this);
                });
            }
        },
        
        // Initialize filters
        initializeFilters: function() {
            this.filterItems();
        },
        
        // Reset modal
        resetModal: function() {
            $('#modalBody').empty();
        },
        
        
        // Handle location select
        handleLocationSelect: function() {
            const $select = $(this);
            const $newInput = $('#location-new');
            
            if ($select.val() === 'other') {
                $newInput.show().prop('required', true);
            } else {
                $newInput.hide().prop('required', false);
            }
        },
        
        // Utility functions
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        showSuccess: function(message) {
            this.showAlert(message, 'success');
        },
        
        showError: function(message) {
            this.showAlert(message, 'danger');
        },
        
        showAlert: function(message, type) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            $('.container').first().prepend(alertHtml);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        VTCInventory.init();
    });
    
    // Make VTCInventory available globally
    window.VTCInventory = VTCInventory;
    
})(jQuery);
