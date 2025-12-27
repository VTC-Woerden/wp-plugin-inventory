<?php
/**
 * PDF Generator Class
 * 
 * Handles PDF generation for inventory items with QR codes
 */

if (!defined('ABSPATH')) {
    exit;
}

class VTC_Inventory_PDF_Generator {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_vtc_generate_pdf', array($this, 'ajax_generate_pdf'));
        add_action('wp_ajax_nopriv_vtc_generate_pdf', array($this, 'ajax_generate_pdf'));
    }
    
    /**
     * AJAX handler for PDF generation
     */
    public function ajax_generate_pdf() {
        if (!wp_verify_nonce($_POST['nonce'], 'vtc_inventory_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'vtc-inventory')));
        }
        
        if (!VTC_Inventory_User_Roles::can_manage_inventory()) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'vtc-inventory')));
        }
        
        $selected_items = json_decode(stripslashes($_POST['selected_items']), true);
        $layout = sanitize_text_field($_POST['layout'] ?? 'grid');
        $location = sanitize_text_field($_POST['location'] ?? '');
        
        if (empty($selected_items)) {
            wp_send_json_error(array('message' => __('No items selected', 'vtc-inventory')));
        }
        
        $this->generate_pdf($selected_items, $layout, $location);
    }
    
    /**
     * Generate PDF with QR codes
     */
    public function generate_pdf($selected_items, $layout = 'grid', $location = '') {
        // Set content type to HTML for printing
        header('Content-Type: text/html; charset=utf-8');
        
        $is_large_layout = ($layout === 'large');
        $items_per_page = $is_large_layout ? 6 : 36; // 2x3=6 for large, 6x6=36 for grid
        
        ?>
        <!DOCTYPE html>
        <html lang="nl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>
                <?php if (!empty($location)): ?>
                    QR Codes - Locatie <?php echo esc_html($location); ?> - <?php echo count($selected_items); ?> items
                <?php else: ?>
                    QR Codes - <?php echo count($selected_items); ?> items
                <?php endif; ?>
                <?php echo $is_large_layout ? ' (Grote QR Codes)' : ''; ?>
            </title>
            
            <!-- QR Code Library -->
            <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
            
            <style>
                @media print {
                    body { 
                        margin: 0; 
                        padding: 10px;
                    }
                    .no-print { display: none; }
                    .page-break { page-break-before: always; }
                    
                    /* Ensure grid works properly in print */
                    .qr-grid {
                        display: grid !important;
                        <?php if ($is_large_layout): ?>
                        grid-template-columns: repeat(2, 1fr) !important;
                        <?php else: ?>
                        grid-template-columns: repeat(6, 1fr) !important;
                        <?php endif; ?>
                        gap: 8px !important;
                        page-break-inside: avoid;
                    }
                    
                    .qr-item {
                        page-break-inside: avoid;
                        break-inside: avoid;
                    }
                    
                    /* Optimize for print density */
                    .qr-code {
                        <?php if ($is_large_layout): ?>
                        width: 150px !important;
                        height: 150px !important;
                        <?php else: ?>
                        width: 70px !important;
                        height: 70px !important;
                        <?php endif; ?>
                        margin: 0 auto 5px auto !important;
                    }
                    
                    .item-name {
                        <?php if ($is_large_layout): ?>
                        font-size: 16px !important;
                        <?php else: ?>
                        font-size: 10px !important;
                        <?php endif; ?>
                        margin-bottom: 3px !important;
                    }
                    
                    .item-quantity {
                        <?php if ($is_large_layout): ?>
                        font-size: 12px !important;
                        <?php else: ?>
                        font-size: 9px !important;
                        <?php endif; ?>
                    }
                }
                
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    background: white;
                }
                
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 15px;
                }
                
                .header h1 {
                    margin: 0 0 10px 0;
                    color: #333;
                    font-size: 24px;
                }
                
                .header p {
                    margin: 0;
                    color: #666;
                    font-size: 14px;
                }
                
                .qr-grid {
                    display: grid;
                    <?php if ($is_large_layout): ?>
                    grid-template-columns: repeat(2, 1fr);
                    <?php else: ?>
                    grid-template-columns: repeat(6, 1fr);
                    <?php endif; ?>
                    gap: 10px;
                    margin-bottom: 30px;
                }
                
                .qr-item {
                    text-align: center;
                    border: 1px solid #ddd;
                    padding: 8px;
                    border-radius: 6px;
                    background: #f9f9f9;
                }
                
                .qr-code {
                    <?php if ($is_large_layout): ?>
                    width: 160px;
                    height: 160px;
                    <?php else: ?>
                    width: 78px;
                    height: 78px;
                    <?php endif; ?>
                    margin: 0 auto 7px auto;
                    background: white;
                    border: 1px solid #ccc;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .qr-code img {
                    width: 100%;
                    height: 100%;
                    object-fit: contain;
                }
                
                .item-name {
                    font-weight: bold;
                    margin-bottom: 4px;
                    <?php if ($is_large_layout): ?>
                    font-size: 18px;
                    <?php else: ?>
                    font-size: 11px;
                    <?php endif; ?>
                    color: #333;
                }
                
                .item-quantity {
                    <?php if ($is_large_layout): ?>
                    font-size: 14px;
                    <?php else: ?>
                    font-size: 9px;
                    <?php endif; ?>
                    color: #666;
                }
                
                .print-button {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #007bff;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 16px;
                }
                
                .print-button:hover {
                    background: #0056b3;
                }
                
                @media (max-width: 768px) {
                    .qr-grid {
                        <?php if ($is_large_layout): ?>
                        grid-template-columns: repeat(1, 1fr);
                        <?php else: ?>
                        grid-template-columns: repeat(2, 1fr);
                        <?php endif; ?>
                        gap: 15px;
                    }
                    
                    .qr-code {
                        <?php if ($is_large_layout): ?>
                        width: 200px;
                        height: 200px;
                        <?php else: ?>
                        width: 100px;
                        height: 100px;
                        <?php endif; ?>
                    }
                }
            </style>
        </head>
        <body>
            <button class="print-button no-print" onclick="window.print()">
                <i class="fas fa-print"></i> Print PDF
            </button>
            
            <div class="header">
                <h1>
                    <?php if (!empty($location)): ?>
                        QR Codes voor Materialen op Locatie <?php echo esc_html($location); ?>
                    <?php else: ?>
                        QR Codes voor Materialen
                    <?php endif; ?>
                    <?php echo $is_large_layout ? ' (Grote QR Codes)' : ''; ?>
                </h1>
                <?php if (!empty($location)): ?>
                    <p><strong>Locatie:</strong> <?php echo esc_html($location); ?></p>
                <?php endif; ?>
                <p>VTC Woerden - <?php echo date('d-m-Y H:i'); ?></p>
                <p>Totaal: <?php echo count($selected_items); ?> items</p>
                <p><strong>Layout:</strong> <?php echo $is_large_layout ? '2x3 Grote QR Codes' : '6x6 Standaard Grid'; ?></p>
            </div>
            
            <div class="qr-grid">
                <?php foreach ($selected_items as $index => $item): ?>
                    <?php 
                    $qr_url = get_option('vtc_inventory_qr_base_url', 'https://vtcwoerden.nl/materiaal/?object=') . urlencode($item['name']);
                    ?>
                    <div class="qr-item">
                        <div class="qr-code" id="qr-<?php echo $item['id']; ?>">
                            <!-- QR code will be generated here by JavaScript -->
                        </div>
                        <div class="item-name"><?php echo esc_html($item['name']); ?></div>
                        <?php if (!$is_large_layout): ?>
                        <div class="item-quantity">(<?php echo $item['quantity']; ?> keer)</div>
                        <?php endif; ?>
                    </div>
                    
                    <?php 
                    // Page break logic
                    if (($index + 1) % $items_per_page === 0 && $index + 1 < count($selected_items)): 
                    ?>
                        <div class="page-break"></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <script>
                // Generate QR codes using the same method as index.php
                document.addEventListener('DOMContentLoaded', function() {
                    const qrContainers = document.querySelectorAll('[id^="qr-"]');
                    console.log('Found QR containers:', qrContainers.length);
                    
                    qrContainers.forEach(container => {
                        const itemId = container.id.replace('qr-', '');
                        const itemName = container.closest('.qr-item').querySelector('.item-name').textContent;
                        const qrUrl = '<?php echo get_option('vtc_inventory_qr_base_url', 'https://vtcwoerden.nl/materiaal/?object='); ?>' + encodeURIComponent(itemName);
                        
                        console.log('Generating QR for:', itemName, 'URL:', qrUrl);
                        
                        // Use qrcode-generator library
                        if (typeof qrcode !== 'undefined') {
                            try {
                                const qr = qrcode(0, 'M');
                                qr.addData(qrUrl);
                                qr.make();
                                <?php if ($is_large_layout): ?>
                                const qrImage = qr.createImgTag(8, 2); // Larger QR code for large layout
                                <?php else: ?>
                                const qrImage = qr.createImgTag(5, 2); // Standard size for grid layout
                                <?php endif; ?>
                                container.innerHTML = qrImage;
                                
                                // Ensure proper sizing
                                const img = container.querySelector('img');
                                if (img) {
                                    <?php if ($is_large_layout): ?>
                                    img.style.width = '160px';
                                    img.style.height = '160px';
                                    <?php else: ?>
                                    img.style.width = '78px';
                                    img.style.height = '78px';
                                    <?php endif; ?>
                                    console.log('QR code generated successfully for:', itemName);
                                }
                            } catch (e) {
                                console.error('QR generation failed for:', itemName, e);
                                // Fallback to Google Charts API if library fails
                                <?php if ($is_large_layout): ?>
                                const fallbackUrl = `https://chart.googleapis.com/chart?cht=qr&chs=160x160&chl=${encodeURIComponent(qrUrl)}&choe=UTF-8`;
                                container.innerHTML = `<img src="${fallbackUrl}" alt="QR Code voor ${itemName}" style="width: 160px; height: 160px;">`;
                                <?php else: ?>
                                const fallbackUrl = `https://chart.googleapis.com/chart?cht=qr&chs=78x78&chl=${encodeURIComponent(qrUrl)}&choe=UTF-8`;
                                container.innerHTML = `<img src="${fallbackUrl}" alt="QR Code voor ${itemName}" style="width: 78px; height: 78px;">`;
                                <?php endif; ?>
                            }
                        } else {
                            // Fallback to Google Charts API if library not loaded
                            <?php if ($is_large_layout): ?>
                            const fallbackUrl = `https://chart.googleapis.com/chart?cht=qr&chs=160x160&chl=${encodeURIComponent(qrUrl)}&choe=UTF-8`;
                            container.innerHTML = `<img src="${fallbackUrl}" alt="QR Code voor ${itemName}" style="width: 160px; height: 160px;">`;
                            <?php else: ?>
                            const fallbackUrl = `https://chart.googleapis.com/chart?cht=qr&chs=78x78&chl=${encodeURIComponent(qrUrl)}&choe=UTF-8`;
                            container.innerHTML = `<img src="${fallbackUrl}" alt="QR Code voor ${itemName}" style="width: 78px; height: 78px;">`;
                            <?php endif; ?>
                        }
                    });
                });
                
                // Auto-print when page loads (optional)
                // window.onload = function() { window.print(); }
            </script>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Generate PDF for specific items
     */
    public function generate_pdf_for_items($item_ids, $layout = 'grid', $location = '') {
        $items = array();
        
        foreach ($item_ids as $item_id) {
            $item = get_post($item_id);
            if ($item && $item->post_type === 'vtc_inventory_item') {
                $items[] = array(
                    'id' => $item->ID,
                    'name' => $item->post_title,
                    'quantity' => get_post_meta($item->ID, '_vtc_quantity', true) ?: 1
                );
            }
        }
        
        $this->generate_pdf($items, $layout, $location);
    }
    
    /**
     * Generate PDF for all items
     */
    public function generate_pdf_for_all($layout = 'grid', $location = '') {
        $args = array(
            'post_type' => 'vtc_inventory_item',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        if (!empty($location)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'inventory_location',
                    'field' => 'slug',
                    'terms' => $location
                )
            );
        }
        
        $items_query = new WP_Query($args);
        $items = array();
        
        foreach ($items_query->posts as $item) {
            $items[] = array(
                'id' => $item->ID,
                'name' => $item->post_title,
                'quantity' => get_post_meta($item->ID, '_vtc_quantity', true) ?: 1
            );
        }
        
        $this->generate_pdf($items, $layout, $location);
    }
    
    /**
     * Get PDF generation options
     */
    public static function get_pdf_options() {
        return array(
            'layouts' => array(
                'grid' => __('Standard Grid (6x6)', 'vtc-inventory'),
                'large' => __('Large QR Codes (2x3)', 'vtc-inventory')
            ),
            'locations' => get_terms(array(
                'taxonomy' => 'inventory_location',
                'hide_empty' => false,
                'fields' => 'id=>name'
            ))
        );
    }
}
