<?php
/**
 * Inventory Page Template
 * 
 * Main template for displaying the inventory system
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables are passed from the shortcode function
?>

<div class="vtc-inventory-container">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-boxes me-2"></i>
                VTC Inventarisatie
            </a>
            
            <!-- Hamburger button for mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Collapsible navigation content -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <?php if ($can_manage): ?>
                        <button type="button" class="btn btn-success btn-sm me-2 mb-2 mb-lg-0" id="addNewItemBtn">
                            <i class="fas fa-plus me-1"></i> Nieuw Materiaal
                        </button>
                        <a href="<?php echo home_url('/inventory/export/csv/'); ?>" class="btn btn-outline-light btn-sm me-2 mb-2 mb-lg-0" title="Export CSV">
                            <i class="fas fa-download me-1"></i> CSV Export
                        </a>
                        <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-outline-light btn-sm me-2 mb-2 mb-lg-0">
                            <i class="fas fa-sign-out-alt me-1"></i> Uitloggen
                        </a>
                    <?php elseif ($is_logged_in): ?>
                        <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-outline-light btn-sm me-2 mb-2 mb-lg-0">
                            <i class="fas fa-sign-out-alt me-1"></i> Uitloggen
                        </a>
                    <?php else: ?>
                        <a href="<?php echo wp_login_url(get_permalink()); ?>" class="btn btn-outline-light btn-sm me-2 mb-2 mb-lg-0">
                            <i class="fas fa-sign-in-alt me-1"></i> Inloggen
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Statistics Row -->
        <?php if ($atts['show_stats'] === 'true'): ?>
        <div class="row mb-4 stats-row">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($items); ?></div>
                    <div>Totaal Items</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, var(--success-color), #146c43);">
                    <div class="stats-number">
                        <?php
                        $gemeente_count = 0;
                        foreach ($items as $item) {
                            $owner_terms = wp_get_post_terms($item->ID, 'inventory_owner', array('fields' => 'names'));
                            if (in_array('Gemeente', $owner_terms)) {
                                $gemeente_count++;
                            }
                        }
                        echo $gemeente_count;
                        ?>
                    </div>
                    <div>Gemeente</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, var(--warning-color), #e0a800);">
                    <div class="stats-number">
                        <?php
                        $vtc_count = 0;
                        foreach ($items as $item) {
                            $owner_terms = wp_get_post_terms($item->ID, 'inventory_owner', array('fields' => 'names'));
                            if (in_array('VTC Woerden', $owner_terms)) {
                                $vtc_count++;
                            }
                        }
                        echo $vtc_count;
                        ?>
                    </div>
                    <div>VTC Woerden</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, var(--secondary-color), #5a6268);">
                    <div class="stats-number"><?php echo count($locations); ?></div>
                    <div>Locaties</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Search and Filters -->
        <?php if ($atts['show_search'] === 'true' || $atts['show_filters'] === 'true'): ?>
        <div class="search-container">
            <div class="row g-3">
                <?php if ($atts['show_search'] === 'true'): ?>
                <div class="col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" id="searchInput" class="form-control search-input border-start-0" 
                               placeholder="Zoek naar materialen...">
                        <button type="button" id="searchBtn" class="btn btn-primary border-start-0">
                            <i class="fas fa-search me-1"></i> Zoeken
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($atts['show_filters'] === 'true'): ?>
                <div class="col-lg-2">
                    <select id="locationFilter" class="form-select">
                        <option value="">Alle locaties</option>
                        <?php foreach($locations as $loc): ?>
                            <option value="<?php echo esc_attr($loc->slug); ?>"><?php echo esc_html($loc->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2">
                    <select id="ownerFilter" class="form-select">
                        <option value="">Alle eigenaren</option>
                        <?php foreach($owners as $owner): ?>
                            <option value="<?php echo esc_attr($owner->slug); ?>"><?php echo esc_html($owner->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2">
                    <select id="conditionFilter" class="form-select">
                        <option value="">Alle condities</option>
                        <?php foreach($conditions as $condition): ?>
                            <option value="<?php echo esc_attr($condition->slug); ?>"><?php echo esc_html($condition->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-1">
                    <button type="button" id="clearFilters" class="btn btn-outline-secondary w-100" title="Filters wissen">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Items Grid -->
        <div id="itemsContainer" class="row g-4">
            <?php if (empty($items)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-boxes fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Geen materialen gevonden</h4>
                        <p class="text-muted">Er zijn nog geen materialen toegevoegd aan de inventaris.</p>
                        <?php if ($can_manage): ?>
                            <button type="button" class="btn btn-primary" id="addNewItemBtn">
                                <i class="fas fa-plus me-1"></i> Eerste materiaal toevoegen
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
            <?php foreach($items as $item): ?>
                <?php
                $owner_terms = wp_get_post_terms($item->ID, 'inventory_owner', array('fields' => 'names'));
                $condition_terms = wp_get_post_terms($item->ID, 'inventory_condition', array('fields' => 'names'));
                $location_terms = wp_get_post_terms($item->ID, 'inventory_location', array('fields' => 'names'));
                $quantity = get_post_meta($item->ID, '_vtc_quantity', true) ?: 1;
                $comments = get_post_meta($item->ID, '_vtc_comments', true);
                $qr_code_url = get_post_meta($item->ID, '_vtc_qr_code_url', true);
                $featured_image = get_the_post_thumbnail_url($item->ID, 'medium');
                ?>
                <div class="col-lg-4 col-md-6 col-sm-12 item-item" 
                     data-name="<?php echo esc_attr(strtolower($item->post_title)); ?>"
                     data-location="<?php echo esc_attr(implode(',', $location_terms)); ?>"
                     data-owner="<?php echo esc_attr(implode(',', $owner_terms)); ?>"
                     data-condition="<?php echo esc_attr(implode(',', $condition_terms)); ?>">
                    <div class="card item-card h-100">
                        <?php if ($can_manage): ?>
                        <div class="card-checkbox">
                            <input type="checkbox" class="item-checkbox" id="item-<?php echo $item->ID; ?>" 
                                   data-item-id="<?php echo $item->ID; ?>" 
                                   data-item-name="<?php echo esc_attr($item->post_title); ?>"
                                   data-item-quantity="<?php echo $quantity; ?>">
                            <label for="item-<?php echo $item->ID; ?>" class="checkbox-label">
                                <i class="fas fa-check"></i>
                            </label>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <div class="card-content">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0 text-primary fw-bold" style="margin-left: 35px;">
                                        <?php echo esc_html($item->post_title); ?>
                                    </h5>
                                    <span class="badge owner-badge">
                                        <?php echo esc_html(implode(', ', $owner_terms)); ?>
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <span class="badge condition-badge condition-<?php echo esc_attr(strtolower(str_replace(' ', '_', implode('_', $condition_terms)))); ?>">
                                        <?php echo esc_html(implode(', ', $condition_terms)); ?>
                                    </span>
                                </div>
                               
                                <div class="row text-muted small mb-3">
                                    <div class="col-6">
                                        <i class="fas fa-hashtag me-1 text-primary"></i>
                                        <strong><?php echo $quantity; ?></strong> keer
                                    </div>
                                    <div class="col-6">
                                        <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                                        <strong><?php echo esc_html(implode(', ', $location_terms)); ?></strong>
                                    </div>
                                </div>
                                
                                <?php if (!empty($comments) && $can_manage): ?>
                                    <div class="mb-3 p-2 bg-light rounded">
                                        <p class="card-text small text-muted mb-0">
                                            <i class="fas fa-comment me-1 text-primary"></i>
                                            <?php echo esc_html($comments); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                               
                                <!-- QR Code and Photo -->
                                <div class="d-flex align-items-center justify-content-center gap-3 mb-3" style="margin-top: auto;">
                                    <!-- Photo Container -->
                                    <div class="photo-container">
                                        <div class="photo-placeholder">
                                            <?php if ($featured_image): ?>
                                                <img src="<?php echo esc_url($featured_image); ?>" 
                                                     class="item-photo" 
                                                     alt="<?php echo esc_attr($item->post_title); ?>"
                                                     data-bs-toggle="modal" 
                                                     data-bs-target="#photoModal" 
                                                     data-item-id="<?php echo $item->ID; ?>">
                                            <?php else: ?>
                                                <div class="no-photo">
                                                    <i class="fas fa-camera text-muted"></i>
                                                    <small class="text-muted d-block">Geen foto</small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted d-block mt-1">Afbeelding</small>
                                    </div>
                                    
                                    <!-- QR Code -->
                                    <div class="qr-code-container">
                                        <a href="<?php echo esc_url($qr_code_url); ?>" 
                                           target="_blank" 
                                           title="Klik om details te bekijken"
                                           class="qr-code-link">
                                            <div id="qr-<?php echo $item->ID; ?>"></div>
                                            <small class="text-muted d-block mt-1">Maak een melding</small>
                                        </a>
                                    </div>
                                </div>
                            </div>
                           
                            <div class="card-actions">
                                <?php if ($can_manage): ?>
                                    <button type="button" class="btn btn-primary btn-modern btn-sm w-100 edit-item-btn" 
                                            data-item-id="<?php echo $item->ID; ?>">
                                        <i class="fas fa-edit me-1"></i> Bewerken
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- No Results Message -->
        <div id="noResults" class="text-center py-5" style="display: none;">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">Geen resultaten gevonden</h4>
            <p class="text-muted">Probeer andere zoektermen of filters</p>
        </div>
        
        <!-- PDF Generation Controls (only for logged in users) -->
        <?php if ($can_manage): ?>
        <div class="pdf-controls-container" style="margin-top: 2rem;">
            <div class="row g-3">
                <div class="col-lg-12">
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" id="selectAllBtn" class="btn btn-outline-primary">
                            <i class="fas fa-check-square me-1"></i> Alles selecteren (<span id="visibleCount">0</span>)
                        </button>
                        <button type="button" id="deselectAllBtn" class="btn btn-outline-secondary">
                            <i class="fas fa-square me-1"></i> Deselecteren
                        </button>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="d-flex gap-3 justify-content-center align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="largeQrLayout" name="largeQrLayout">
                            <label class="form-check-label" for="largeQrLayout">
                                <i class="fas fa-expand-arrows-alt me-1"></i> Grote QR Codes (2x3 layout)
                            </label>
                            <div class="form-text text-muted small">
                                <i class="fas fa-info-circle me-1"></i> 
                                Standaard: 6x6 grid met kleine QR codes. Grote QR codes: 2x3 layout met alleen namen.
                            </div>
                        </div>
                        <button type="button" id="generatePdfBtn" class="btn btn-success" disabled>
                            <i class="fas fa-file-pdf me-1"></i> PDF genereren (<span id="selectedCount">0</span>)
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add/Edit Item Modal -->
    <div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Materiaal Bewerken</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Modal content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Photo Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <div id="carouselPhotos" class="carousel slide" data-bs-ride="false">
                        <div class="carousel-inner">
                            <!-- Photos will be loaded here -->
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselPhotos" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                            <span class="visually-hidden">Vorige</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselPhotos" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                            <span class="visually-hidden">Volgende</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
