<?php
/**
 * Main Inventory Manager Class
 * 
 * Handles the frontend display and AJAX operations for the inventory system
 */

if (!defined('ABSPATH')) {
    exit;
}

class VTC_Inventory_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_vtc_inventory_action', array($this, 'handle_ajax'));
        add_action('wp_ajax_nopriv_vtc_inventory_action', array($this, 'handle_ajax'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_head', array($this, 'add_inline_styles'));
        add_action('template_redirect', array($this, 'handle_export_requests'));
        
        // Register shortcodes immediately
        $this->create_shortcode();
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only load on pages with inventory shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'vtc_inventory')) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array(), '5.3.0', true);
            wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', array(), '5.3.0');
            wp_enqueue_script('qrcode', 'https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js', array(), '1.5.3', true);
            wp_enqueue_script('qrcode-generator', 'https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js', array(), '1.4.4', true);
            wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
            
            wp_enqueue_script('vtc-inventory', VTC_INVENTORY_PLUGIN_URL . 'assets/js/inventory.js', array('jquery'), VTC_INVENTORY_VERSION, true);
            wp_enqueue_style('vtc-inventory', VTC_INVENTORY_PLUGIN_URL . 'assets/css/inventory.css', array(), VTC_INVENTORY_VERSION);
            
            // Localize script for AJAX
            wp_localize_script('vtc-inventory', 'vtc_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vtc_inventory_nonce'),
                'is_logged_in' => is_user_logged_in(),
                'can_manage' => VTC_Inventory_User_Roles::can_manage_inventory(),
                'can_view' => VTC_Inventory_User_Roles::can_view_inventory(),
                'user_permissions' => VTC_Inventory_User_Roles::get_user_permissions(),
                'login_url' => wp_login_url(get_permalink()),
                'logout_url' => wp_logout_url(get_permalink()),
                'strings' => array(
                    'loading' => __('Loading...', 'vtc-inventory'),
                    'error' => __('An error occurred', 'vtc-inventory'),
                    'success' => __('Success', 'vtc-inventory'),
                    'confirm_delete' => __('Are you sure you want to delete this item?', 'vtc-inventory'),
                    'login_required' => __('Please log in to perform this action', 'vtc-inventory'),
                )
            ));
        }
    }
    
    /**
     * Add inline styles
     */
    public function add_inline_styles() {
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'vtc_inventory')) {
            ?>
            <style>
                .vtc-inventory-container {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                }
                .vtc-inventory-container .card {
                    transition: transform 0.2s ease, box-shadow 0.2s ease;
                }
                .vtc-inventory-container .card:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
                }
                .vtc-inventory-container .qr-code {
                    width: 120px;
                    height: 120px;
                    margin: 0 auto;
                }
                .vtc-inventory-container .condition-badge {
                    font-size: 0.75rem;
                    padding: 0.25rem 0.75rem;
                    border-radius: 1rem;
                    font-weight: 500;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .condition-zeer_goed { background-color: #d1e7dd; color: #0f5132; }
                .condition-goed { background-color: #d1ecf1; color: #0c5460; }
                .condition-redelijk { background-color: #fff3cd; color: #856404; }
                .condition-slecht { background-color: #f8d7da; color: #721c24; }
                .condition-zeer_slecht { background-color: #f5c6cb; color: #721c24; }
            </style>
            <?php
        }
    }
    
    /**
     * Create shortcode
     */
    public function create_shortcode() {
        add_shortcode('vtc_inventory', array($this, 'render_inventory_page'));
        
        // Debug: Check if shortcode is registered
        if (current_user_can('manage_options')) {
            add_action('wp_footer', array($this, 'debug_shortcode_registration'));
        }
        
        // Add a simple test shortcode for debugging
        add_shortcode('vtc_test', array($this, 'test_shortcode'));
    }
    
    /**
     * Simple test shortcode
     */
    public function test_shortcode($atts) {
        return '<div style="background: #f0f0f0; padding: 20px; border: 2px solid #007cba; margin: 10px 0;"><strong>VTC Test Shortcode Working!</strong><br>Plugin is loaded and shortcodes are functioning.</div>';
    }
    
    /**
     * Debug shortcode registration
     */
    public function debug_shortcode_registration() {
        global $shortcode_tags;
        if (isset($shortcode_tags['vtc_inventory'])) {
            echo '<!-- VTC Inventory shortcode is registered -->';
        } else {
            echo '<!-- VTC Inventory shortcode is NOT registered -->';
        }
    }
    
    /**
     * Render inventory page
     */
    public function render_inventory_page($atts) {
        // Debug: Log shortcode execution
        if (current_user_can('manage_options')) {
            error_log('VTC Inventory: Shortcode executed');
        }
        
        // Basic error checking
        if (!function_exists('get_post_meta')) {
            return '<div style="color: red; padding: 10px; border: 1px solid red;">Error: WordPress functions not available</div>';
        }
        
        $atts = shortcode_atts(array(
            'show_search' => 'true',
            'show_filters' => 'true',
            'show_stats' => 'true',
            'items_per_page' => -1,
            'layout' => 'grid'
        ), $atts);
        
        // Get inventory items using WordPress query
        $args = array(
            'post_type' => 'vtc_inventory_item',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['items_per_page']),
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_vtc_quantity',
                    'value' => 0,
                    'compare' => '>'
                )
            )
        );
        
        $items_query = new WP_Query($args);
        $items = $items_query->posts;
        
        // Get taxonomies
        $owners = get_terms(array('taxonomy' => 'inventory_owner', 'hide_empty' => false));
        $conditions = get_terms(array('taxonomy' => 'inventory_condition', 'hide_empty' => false));
        $locations = get_terms(array('taxonomy' => 'inventory_location', 'hide_empty' => false));
        
        // Check permissions
        $can_manage = VTC_Inventory_User_Roles::can_manage_inventory();
        $can_view = VTC_Inventory_User_Roles::can_view_inventory();
        
        if (!$can_view) {
            return '<div class="alert alert-warning">' . __('You do not have permission to view the inventory.', 'vtc-inventory') . '</div>';
        }
        
        // Debug: Log item count
        if (current_user_can('manage_options')) {
            error_log('VTC Inventory: Found ' . count($items) . ' items');
        }
        
        // Start output buffering
        ob_start();
        
        // Make variables available to template
        $template_vars = array(
            'items' => $items,
            'owners' => $owners,
            'conditions' => $conditions,
            'locations' => $locations,
            'atts' => $atts,
            'can_manage' => $can_manage,
            'can_view' => $can_view,
            'is_logged_in' => is_user_logged_in()
        );
        
        // Extract variables for template
        extract($template_vars);
        
        include VTC_INVENTORY_PLUGIN_DIR . 'templates/inventory-page.php';
        return ob_get_clean();
    }
    
    /**
     * Handle AJAX requests
     */
    public function handle_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'vtc_inventory_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'vtc-inventory')));
        }
        
        $action = sanitize_text_field($_POST['action_type'] ?? '');
        
        switch ($action) {
            case 'get_item_data':
                $this->get_item_data();
                break;
            case 'update_item':
                $this->update_item();
                break;
            case 'delete_item':
                $this->delete_item();
                break;
            case 'bulk_export':
                $this->bulk_export();
                break;
            case 'search_items':
                $this->search_items();
                break;
            case 'get_modal_content':
                $this->get_modal_content();
                break;
            default:
                wp_send_json_error(array('message' => __('Invalid action', 'vtc-inventory')));
        }
        
        wp_die();
    }
    
    /**
     * Get item data
     */
    private function get_item_data() {
        $item_id = intval($_POST['item_id']);
        $item = get_post($item_id);
        
        if (!$item || $item->post_type !== 'vtc_inventory_item') {
            wp_send_json_error(array('message' => __('Item not found', 'vtc-inventory')));
        }
        
        $data = array(
            'id' => $item->ID,
            'name' => $item->post_title,
            'description' => $item->post_content,
            'quantity' => get_post_meta($item->ID, '_vtc_quantity', true) ?: 1,
            'comments' => get_post_meta($item->ID, '_vtc_comments', true),
            'qr_code_url' => get_post_meta($item->ID, '_vtc_qr_code_url', true),
            'owner' => wp_get_post_terms($item->ID, 'inventory_owner', array('fields' => 'names')),
            'condition' => wp_get_post_terms($item->ID, 'inventory_condition', array('fields' => 'names')),
            'location' => wp_get_post_terms($item->ID, 'inventory_location', array('fields' => 'names')),
            'photos' => $this->get_item_photos($item->ID),
            'featured_image' => get_the_post_thumbnail_url($item->ID, 'medium')
        );
        
        wp_send_json_success($data);
    }
    
    /**
     * Update item
     */
    private function update_item() {
        if (!VTC_Inventory_User_Roles::can_manage_inventory()) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'vtc-inventory')));
        }
        
        $item_id = intval($_POST['item_id']);
        $item_data = array(
            'ID' => $item_id,
            'post_title' => sanitize_text_field($_POST['name']),
            'post_content' => sanitize_textarea_field($_POST['description'])
        );
        
        $result = wp_update_post($item_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        // Update meta fields
        update_post_meta($item_id, '_vtc_quantity', intval($_POST['quantity']));
        update_post_meta($item_id, '_vtc_comments', sanitize_textarea_field($_POST['comments']));
        
        // Update taxonomies
        if (!empty($_POST['owner'])) {
            wp_set_post_terms($item_id, array(sanitize_text_field($_POST['owner'])), 'inventory_owner');
        }
        if (!empty($_POST['condition'])) {
            wp_set_post_terms($item_id, array(sanitize_text_field($_POST['condition'])), 'inventory_condition');
        }
        if (!empty($_POST['location'])) {
            wp_set_post_terms($item_id, array(sanitize_text_field($_POST['location'])), 'inventory_location');
        }
        
        // Handle photo uploads
        if (!empty($_FILES['photos'])) {
            $this->handle_photo_uploads($item_id);
        }
        
        wp_send_json_success(array('message' => __('Item updated successfully', 'vtc-inventory')));
    }
    
    /**
     * Delete item
     */
    private function delete_item() {
        if (!VTC_Inventory_User_Roles::can_manage_inventory()) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'vtc-inventory')));
        }
        
        $item_id = intval($_POST['item_id']);
        $result = wp_delete_post($item_id, true);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to delete item', 'vtc-inventory')));
        }
        
        wp_send_json_success(array('message' => __('Item deleted successfully', 'vtc-inventory')));
    }
    
    /**
     * Search items
     */
    private function search_items() {
        $search_term = sanitize_text_field($_POST['search_term'] ?? '');
        $location = sanitize_text_field($_POST['location'] ?? '');
        $owner = sanitize_text_field($_POST['owner'] ?? '');
        $condition = sanitize_text_field($_POST['condition'] ?? '');
        
        $args = array(
            'post_type' => 'vtc_inventory_item',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        // Add search term
        if (!empty($search_term)) {
            $args['s'] = $search_term;
        }
        
        // Add taxonomy filters
        $tax_query = array();
        
        if (!empty($location)) {
            $tax_query[] = array(
                'taxonomy' => 'inventory_location',
                'field' => 'slug',
                'terms' => $location
            );
        }
        
        if (!empty($owner)) {
            $tax_query[] = array(
                'taxonomy' => 'inventory_owner',
                'field' => 'slug',
                'terms' => $owner
            );
        }
        
        if (!empty($condition)) {
            $tax_query[] = array(
                'taxonomy' => 'inventory_condition',
                'field' => 'slug',
                'terms' => $condition
            );
        }
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        $items_query = new WP_Query($args);
        $items = array();
        
        foreach ($items_query->posts as $item) {
            $items[] = array(
                'id' => $item->ID,
                'name' => $item->post_title,
                'quantity' => get_post_meta($item->ID, '_vtc_quantity', true) ?: 1,
                'owner' => wp_get_post_terms($item->ID, 'inventory_owner', array('fields' => 'names')),
                'condition' => wp_get_post_terms($item->ID, 'inventory_condition', array('fields' => 'names')),
                'location' => wp_get_post_terms($item->ID, 'inventory_location', array('fields' => 'names')),
                'featured_image' => get_the_post_thumbnail_url($item->ID, 'medium'),
                'qr_code_url' => get_post_meta($item->ID, '_vtc_qr_code_url', true)
            );
        }
        
        wp_send_json_success($items);
    }
    
    /**
     * Get modal content
     */
    private function get_modal_content() {
        $modal_type = sanitize_text_field($_POST['modal_type'] ?? '');
        $item_id = intval($_POST['item_id'] ?? 0);
        
        if ($modal_type === 'edit' && $item_id > 0) {
            $item = get_post($item_id);
            if (!$item || $item->post_type !== 'vtc_inventory_item') {
                wp_send_json_error(array('message' => __('Item not found', 'vtc-inventory')));
            }
            
            $item_data = array(
                'id' => $item->ID,
                'name' => $item->post_title,
                'description' => $item->post_content,
                'quantity' => get_post_meta($item->ID, '_vtc_quantity', true) ?: 1,
                'comments' => get_post_meta($item->ID, '_vtc_comments', true),
                'owner' => wp_get_post_terms($item->ID, 'inventory_owner', array('fields' => 'names')),
                'condition' => wp_get_post_terms($item->ID, 'inventory_condition', array('fields' => 'names')),
                'location' => wp_get_post_terms($item->ID, 'inventory_location', array('fields' => 'names')),
                'photos' => $this->get_item_photos($item->ID)
            );
            
            wp_send_json_success($item_data);
        } else {
            // Return empty form for new item
            wp_send_json_success(array());
        }
    }
    
    /**
     * Get item photos
     */
    private function get_item_photos($item_id) {
        $photos = array();
        $attachments = get_attached_media('image', $item_id);
        
        foreach ($attachments as $attachment) {
            $photos[] = array(
                'id' => $attachment->ID,
                'url' => wp_get_attachment_url($attachment->ID),
                'thumbnail' => wp_get_attachment_thumb_url($attachment->ID),
                'title' => $attachment->post_title
            );
        }
        
        return $photos;
    }
    
    /**
     * Handle photo uploads
     */
    private function handle_photo_uploads($item_id) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $uploaded_files = array();
        
        foreach ($_FILES['photos']['name'] as $key => $value) {
            if ($_FILES['photos']['name'][$key]) {
                $file = array(
                    'name' => $_FILES['photos']['name'][$key],
                    'type' => $_FILES['photos']['type'][$key],
                    'tmp_name' => $_FILES['photos']['tmp_name'][$key],
                    'error' => $_FILES['photos']['error'][$key],
                    'size' => $_FILES['photos']['size'][$key]
                );
                
                $upload_overrides = array('test_form' => false);
                $movefile = wp_handle_upload($file, $upload_overrides);
                
                if ($movefile && !isset($movefile['error'])) {
                    $attachment = array(
                        'post_mime_type' => $movefile['type'],
                        'post_title' => sanitize_file_name($file['name']),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    );
                    
                    $attachment_id = wp_insert_attachment($attachment, $movefile['file'], $item_id);
                    
                    if (!is_wp_error($attachment_id)) {
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        $attachment_data = wp_generate_attachment_metadata($attachment_id, $movefile['file']);
                        wp_update_attachment_metadata($attachment_id, $attachment_data);
                        
                        $uploaded_files[] = $attachment_id;
                    }
                }
            }
        }
        
        return $uploaded_files;
    }
    
    /**
     * Handle export requests
     */
    public function handle_export_requests() {
        if (get_query_var('vtc_export') && get_query_var('vtc_format')) {
            $format = get_query_var('vtc_format');
            
            if ($format === 'csv') {
                $this->export_csv();
            } elseif ($format === 'pdf') {
                $this->export_pdf();
            }
        }
    }
    
    /**
     * Export CSV
     */
    private function export_csv() {
        if (!VTC_Inventory_User_Roles::can_manage_inventory()) {
            wp_die(__('Insufficient permissions', 'vtc-inventory'));
        }
        
        $args = array(
            'post_type' => 'vtc_inventory_item',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        $items_query = new WP_Query($args);
        $csv_data = array();
        
        // CSV headers
        $csv_data[] = array('ID', 'Name', 'Quantity', 'Owner', 'Condition', 'Location', 'Comments', 'Date Created');
        
        foreach ($items_query->posts as $item) {
            $owner_terms = wp_get_post_terms($item->ID, 'inventory_owner', array('fields' => 'names'));
            $condition_terms = wp_get_post_terms($item->ID, 'inventory_condition', array('fields' => 'names'));
            $location_terms = wp_get_post_terms($item->ID, 'inventory_location', array('fields' => 'names'));
            
            $csv_data[] = array(
                $item->ID,
                $item->post_title,
                get_post_meta($item->ID, '_vtc_quantity', true) ?: 1,
                implode(', ', $owner_terms),
                implode(', ', $condition_terms),
                implode(', ', $location_terms),
                get_post_meta($item->ID, '_vtc_comments', true),
                $item->post_date
            );
        }
        
        // Generate CSV
        $filename = 'vtc_inventory_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        foreach ($csv_data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
    
    /**
     * Export PDF
     */
    private function export_pdf() {
        if (!VTC_Inventory_User_Roles::can_manage_inventory()) {
            wp_die(__('Insufficient permissions', 'vtc-inventory'));
        }
        
        // This will be handled by the PDF generator class
        $pdf_generator = new VTC_Inventory_PDF_Generator();
        $pdf_generator->generate_pdf();
    }
}
