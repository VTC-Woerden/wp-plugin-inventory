<?php
/**
 * Data Migration Class
 * 
 * Handles migration from the original JSON-based system to WordPress database
 */

if (!defined('ABSPATH')) {
    exit;
}

class VTC_Data_Migration {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_migration_page'));
        add_action('wp_ajax_vtc_migrate_data', array($this, 'ajax_migrate_data'));
        add_action('wp_ajax_vtc_rollback_migration', array($this, 'ajax_rollback_migration'));
        add_action('wp_ajax_vtc_sweep_inventory', array($this, 'ajax_sweep_inventory'));
        add_action('wp_ajax_vtc_preview_sweep', array($this, 'ajax_preview_sweep'));
    }
    
    /**
     * Add migration page to admin menu
     */
    public function add_migration_page() {
        add_management_page(
            __('VTC Inventory Migration', 'vtc-inventory'),
            __('VTC Inventory Migration', 'vtc-inventory'),
            'manage_options',
            'vtc-inventory-migration',
            array($this, 'migration_page')
        );
    }
    
    /**
     * Migration page content
     */
    public function migration_page() {
        if (isset($_POST['migrate_data']) && wp_verify_nonce($_POST['_wpnonce'], 'vtc_migration_nonce')) {
            $this->migrate_from_json();
        }
        
        if (isset($_POST['rollback_migration']) && wp_verify_nonce($_POST['_wpnonce'], 'vtc_rollback_nonce')) {
            $this->rollback_migration();
        }
        
        if (isset($_POST['sweep_inventory']) && wp_verify_nonce($_POST['_wpnonce'], 'vtc_sweep_nonce')) {
            $this->sweep_inventory();
        }
        
        $migration_status = get_option('vtc_migration_status', 'not_started');
        $migrated_count = get_option('vtc_migrated_items_count', 0);
        $json_file_exists = file_exists($this->get_json_file_path());
        
        ?>
        <div class="wrap">
            <h1><?php _e('VTC Inventory Data Migration', 'vtc-inventory'); ?></h1>
            
            <div class="notice notice-info">
                <p><?php _e('This tool will migrate data from the old JSON format to WordPress database.', 'vtc-inventory'); ?></p>
            </div>
            
            <div class="card" style="max-width: 800px;">
                <h2><?php _e('Migration Status', 'vtc-inventory'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('JSON File Status', 'vtc-inventory'); ?></th>
                        <td>
                            <?php if ($json_file_exists): ?>
                                <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                <?php _e('JSON file found', 'vtc-inventory'); ?>
                            <?php else: ?>
                                <span class="dashicons dashicons-warning" style="color: orange;"></span>
                                <?php _e('JSON file not found', 'vtc-inventory'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Migration Status', 'vtc-inventory'); ?></th>
                        <td>
                            <?php
                            switch ($migration_status) {
                                case 'completed':
                                    echo '<span class="dashicons dashicons-yes-alt" style="color: green;"></span> ';
                                    _e('Migration completed', 'vtc-inventory');
                                    break;
                                case 'in_progress':
                                    echo '<span class="dashicons dashicons-update" style="color: blue;"></span> ';
                                    _e('Migration in progress', 'vtc-inventory');
                                    break;
                                case 'failed':
                                    echo '<span class="dashicons dashicons-warning" style="color: red;"></span> ';
                                    _e('Migration failed', 'vtc-inventory');
                                    break;
                                default:
                                    echo '<span class="dashicons dashicons-minus" style="color: gray;"></span> ';
                                    _e('Not started', 'vtc-inventory');
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Items Migrated', 'vtc-inventory'); ?></th>
                        <td><?php echo $migrated_count; ?></td>
                    </tr>
                </table>
            </div>
            
            <?php if ($json_file_exists && $migration_status !== 'completed'): ?>
                <div class="card" style="max-width: 800px; margin-top: 20px;">
                    <h2><?php _e('Start Migration', 'vtc-inventory'); ?></h2>
                    <p><?php _e('Click the button below to start migrating data from JSON to WordPress database.', 'vtc-inventory'); ?></p>
                    
                    <form method="post" id="migration-form">
                        <?php wp_nonce_field('vtc_migration_nonce'); ?>
                        <input type="submit" name="migrate_data" class="button button-primary" value="<?php _e('Start Migration', 'vtc-inventory'); ?>" />
                    </form>
                </div>
            <?php endif; ?>
            
            <?php if ($migration_status === 'completed'): ?>
                <div class="card" style="max-width: 800px; margin-top: 20px;">
                    <h2><?php _e('Migration Completed', 'vtc-inventory'); ?></h2>
                    <p><?php printf(__('Successfully migrated %d items to WordPress database.', 'vtc-inventory'), $migrated_count); ?></p>
                    
                    <div class="notice notice-warning">
                        <p><strong><?php _e('Important:', 'vtc-inventory'); ?></strong> <?php _e('After successful migration, you can safely delete the original JSON file and photos from the old system.', 'vtc-inventory'); ?></p>
                    </div>
                    
                    <form method="post" id="rollback-form" style="margin-top: 20px;">
                        <?php wp_nonce_field('vtc_rollback_nonce'); ?>
                        <input type="submit" name="rollback_migration" class="button button-secondary" 
                               value="<?php _e('Rollback Migration (Delete Migrated Items)', 'vtc-inventory'); ?>" 
                               onclick="return confirm('<?php _e('Are you sure you want to rollback the migration? This will delete all migrated items.', 'vtc-inventory'); ?>');" />
                    </form>
                </div>
            <?php endif; ?>
            
            <?php
            // Get total inventory count
            $total_inventory_count = wp_count_posts('vtc_inventory_item')->publish;
            if ($total_inventory_count > 0): ?>
                <div class="card" style="max-width: 800px; margin-top: 20px;">
                    <h2><?php _e('Inventory Management', 'vtc-inventory'); ?></h2>
                    <p><?php printf(__('Current inventory contains %d items.', 'vtc-inventory'), $total_inventory_count); ?></p>
                    
                    <div class="notice notice-error">
                        <p><strong><?php _e('Danger Zone:', 'vtc-inventory'); ?></strong> <?php _e('The sweep operation will permanently delete ALL inventory items. This action cannot be undone!', 'vtc-inventory'); ?></p>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <button type="button" id="preview-sweep" class="button button-secondary" style="margin-right: 10px;">
                            <?php _e('Preview What Will Be Deleted', 'vtc-inventory'); ?>
                        </button>
                        
                        <form method="post" id="sweep-form" style="display: inline;">
                            <?php wp_nonce_field('vtc_sweep_nonce'); ?>
                            <input type="submit" name="sweep_inventory" class="button button-secondary" 
                                   value="<?php _e('Sweep All Inventory Items', 'vtc-inventory'); ?>" 
                                   style="background-color: #dc3232; color: white; border-color: #dc3232;"
                                   onclick="return confirm('<?php _e('WARNING: This will permanently delete ALL inventory items! Are you absolutely sure?', 'vtc-inventory'); ?>');" />
                        </form>
                    </div>
                    
                    <div id="sweep-preview" style="margin-top: 15px; display: none;">
                        <h4><?php _e('Items that will be deleted:', 'vtc-inventory'); ?></h4>
                        <div id="sweep-preview-content"></div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php _e('Migration Details', 'vtc-inventory'); ?></h2>
                <ul>
                    <li><?php _e('Items will be created as custom post types', 'vtc-inventory'); ?></li>
                    <li><?php _e('Photos will be uploaded to WordPress media library', 'vtc-inventory'); ?></li>
                    <li><?php _e('Taxonomies will be created for owners, conditions, and locations', 'vtc-inventory'); ?></li>
                    <li><?php _e('QR codes will be auto-generated for each item', 'vtc-inventory'); ?></li>
                    <li><?php _e('Original data will be preserved in JSON file', 'vtc-inventory'); ?></li>
                </ul>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#migration-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $button = $form.find('input[type="submit"]');
                
                $button.prop('disabled', true).val('<?php _e('Migrating...', 'vtc-inventory'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vtc_migrate_data',
                        nonce: '<?php echo wp_create_nonce('vtc_migration_ajax'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Migration failed: ' + response.data.message);
                            $button.prop('disabled', false).val('<?php _e('Start Migration', 'vtc-inventory'); ?>');
                        }
                    },
                    error: function() {
                        alert('Migration failed: Network error');
                        $button.prop('disabled', false).val('<?php _e('Start Migration', 'vtc-inventory'); ?>');
                    }
                });
            });
            
            $('#preview-sweep').on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var $preview = $('#sweep-preview');
                var $content = $('#sweep-preview-content');
                
                $button.prop('disabled', true).text('<?php _e('Loading...', 'vtc-inventory'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vtc_preview_sweep',
                        nonce: '<?php echo wp_create_nonce('vtc_preview_ajax'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var html = '<p><strong>' + response.data.message + '</strong></p>';
                            
                            if (response.data.items.length > 0) {
                                html += '<table class="wp-list-table widefat fixed striped">';
                                html += '<thead><tr><th>ID</th><th>Name</th><th>Owner</th><th>Condition</th><th>Location</th><th>Status</th><th>Type</th></tr></thead>';
                                html += '<tbody>';
                                
                                response.data.items.forEach(function(item) {
                                    html += '<tr>';
                                    html += '<td>' + item.id + '</td>';
                                    html += '<td>' + item.title + '</td>';
                                    html += '<td>' + item.owner + '</td>';
                                    html += '<td>' + item.condition + '</td>';
                                    html += '<td>' + item.location + '</td>';
                                    html += '<td>' + item.status + '</td>';
                                    html += '<td>' + (item.is_migrated ? 'Migrated' : 'Manual') + '</td>';
                                    html += '</tr>';
                                });
                                
                                html += '</tbody></table>';
                            } else {
                                html += '<p>No items found.</p>';
                            }
                            
                            $content.html(html);
                            $preview.show();
                        } else {
                            alert('Preview failed: ' + response.data.message);
                        }
                        
                        $button.prop('disabled', false).text('<?php _e('Preview What Will Be Deleted', 'vtc-inventory'); ?>');
                    },
                    error: function() {
                        alert('Preview failed: Network error');
                        $button.prop('disabled', false).text('<?php _e('Preview What Will Be Deleted', 'vtc-inventory'); ?>');
                    }
                });
            });
            
            $('#sweep-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $button = $form.find('input[type="submit"]');
                
                $button.prop('disabled', true).val('<?php _e('Sweeping...', 'vtc-inventory'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vtc_sweep_inventory',
                        nonce: '<?php echo wp_create_nonce('vtc_sweep_ajax'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Sweep completed: ' + response.data.message);
                            location.reload();
                        } else {
                            alert('Sweep failed: ' + response.data.message);
                            $button.prop('disabled', false).val('<?php _e('Sweep All Inventory Items', 'vtc-inventory'); ?>');
                        }
                    },
                    error: function() {
                        alert('Sweep failed: Network error');
                        $button.prop('disabled', false).val('<?php _e('Sweep All Inventory Items', 'vtc-inventory'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for migration
     */
    public function ajax_migrate_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'vtc_migration_ajax')) {
            wp_send_json_error(array('message' => __('Security check failed', 'vtc-inventory')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'vtc-inventory')));
        }
        
        $result = $this->migrate_from_json();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX handler for rollback
     */
    public function ajax_rollback_migration() {
        if (!wp_verify_nonce($_POST['nonce'], 'vtc_rollback_ajax')) {
            wp_send_json_error(array('message' => __('Security check failed', 'vtc-inventory')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'vtc-inventory')));
        }
        
        $result = $this->rollback_migration();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX handler for sweep
     */
    public function ajax_sweep_inventory() {
        if (!wp_verify_nonce($_POST['nonce'], 'vtc_sweep_ajax')) {
            wp_send_json_error(array('message' => __('Security check failed', 'vtc-inventory')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'vtc-inventory')));
        }
        
        $result = $this->sweep_inventory();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX handler for sweep preview
     */
    public function ajax_preview_sweep() {
        if (!wp_verify_nonce($_POST['nonce'], 'vtc_preview_ajax')) {
            wp_send_json_error(array('message' => __('Security check failed', 'vtc-inventory')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'vtc-inventory')));
        }
        
        $result = $this->preview_sweep();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Migrate data from JSON file
     */
    public function migrate_from_json() {
        $json_file = $this->get_json_file_path();
        
        if (!file_exists($json_file)) {
            return array(
                'success' => false,
                'message' => __('JSON data file not found', 'vtc-inventory')
            );
        }
        
        $json_content = file_get_contents($json_file);
        $data = json_decode($json_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'message' => sprintf(__('Invalid JSON format: %s', 'vtc-inventory'), json_last_error_msg())
            );
        }
        
        if (!$data || !is_array($data)) {
            return array(
                'success' => false,
                'message' => __('JSON data is not an array. Expected format: [{"name": "Item Name", "quantity": 1, ...}, ...]', 'vtc-inventory')
            );
        }
        
        // Validate that each item has required fields
        foreach ($data as $index => $item) {
            if (!is_array($item)) {
                return array(
                    'success' => false,
                    'message' => sprintf(__('Item at index %d is not an object', 'vtc-inventory'), $index)
                );
            }
            
            if (empty($item['name'])) {
                return array(
                    'success' => false,
                    'message' => sprintf(__('Item at index %d is missing required field "name"', 'vtc-inventory'), $index)
                );
            }
        }
        
        // Set migration status
        update_option('vtc_migration_status', 'in_progress');
        
        $migrated = 0;
        $errors = 0;
        $error_messages = array();
        
        foreach ($data as $item) {
            try {
                // Create post
                $post_data = array(
                    'post_title' => sanitize_text_field($item['name']),
                    'post_content' => sanitize_textarea_field($item['comments'] ?? ''),
                    'post_type' => 'vtc_inventory_item',
                    'post_status' => 'publish',
                    'post_date' => $this->convert_timestamp($item['timestamp'] ?? current_time('mysql'))
                );
                
                $post_id = wp_insert_post($post_data);
                
                if (is_wp_error($post_id)) {
                    $errors++;
                    $error_messages[] = sprintf(__('Failed to create post for item: %s', 'vtc-inventory'), $item['name']);
                    continue;
                }
                
                // Add meta fields
                update_post_meta($post_id, '_vtc_quantity', intval($item['quantity'] ?? 1));
                update_post_meta($post_id, '_vtc_comments', sanitize_textarea_field($item['comments'] ?? ''));
                update_post_meta($post_id, '_vtc_original_id', intval($item['id'] ?? 0));
                update_post_meta($post_id, '_vtc_migrated_from_json', '1');
                
                // Generate QR code URL
                $qr_url = 'https://vtcwoerden.nl/materiaal/?object=' . urlencode($item['name']);
                update_post_meta($post_id, '_vtc_qr_code_url', $qr_url);
                
                // Add taxonomies
                if (!empty($item['owner'])) {
                    $owner_term = $this->normalize_owner($item['owner']);
                    $this->ensure_term_exists($owner_term, 'inventory_owner');
                    wp_set_post_terms($post_id, array($owner_term), 'inventory_owner');
                }
                
                if (!empty($item['condition'])) {
                    $condition_term = $this->normalize_condition($item['condition']);
                    $this->ensure_term_exists($condition_term, 'inventory_condition');
                    wp_set_post_terms($post_id, array($condition_term), 'inventory_condition');
                }
                
                if (!empty($item['location'])) {
                    $this->ensure_term_exists($item['location'], 'inventory_location');
                    wp_set_post_terms($post_id, array($item['location']), 'inventory_location');
                }
                
                // Handle photos
                if (!empty($item['photos']) && is_array($item['photos'])) {
                    $this->migrate_photos($post_id, $item['photos']);
                }
                
                $migrated++;
                
            } catch (Exception $e) {
                $errors++;
                $error_messages[] = sprintf(__('Error migrating item %s: %s', 'vtc-inventory'), $item['name'], $e->getMessage());
            }
        }
        
        // Update migration status
        if ($errors === 0) {
            update_option('vtc_migration_status', 'completed');
        } else {
            update_option('vtc_migration_status', 'failed');
        }
        
        update_option('vtc_migrated_items_count', $migrated);
        
        return array(
            'success' => $errors === 0,
            'migrated' => $migrated,
            'errors' => $errors,
            'error_messages' => $error_messages,
            'message' => sprintf(__('Migration completed: %d items migrated, %d errors', 'vtc-inventory'), $migrated, $errors)
        );
    }
    
    /**
     * Rollback migration
     */
    public function rollback_migration() {
        if (!current_user_can('manage_options')) {
            return array(
                'success' => false,
                'message' => __('Insufficient permissions', 'vtc-inventory')
            );
        }
        
        // Get all migrated items
        $args = array(
            'post_type' => 'vtc_inventory_item',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_vtc_migrated_from_json',
                    'value' => '1',
                    'compare' => '='
                )
            )
        );
        
        $items_query = new WP_Query($args);
        $deleted = 0;
        
        foreach ($items_query->posts as $item) {
            if (wp_delete_post($item->ID, true)) {
                $deleted++;
            }
        }
        
        // Reset migration status
        delete_option('vtc_migration_status');
        delete_option('vtc_migrated_items_count');
        
        return array(
            'success' => true,
            'deleted' => $deleted,
            'message' => sprintf(__('Rollback completed: %d items deleted', 'vtc-inventory'), $deleted)
        );
    }
    
    /**
     * Preview what will be deleted by sweep
     */
    public function preview_sweep() {
        if (!current_user_can('manage_options')) {
            return array(
                'success' => false,
                'message' => __('Insufficient permissions', 'vtc-inventory')
            );
        }
        
        // Get ALL inventory items (not just migrated ones)
        $args = array(
            'post_type' => 'vtc_inventory_item',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        $items_query = new WP_Query($args);
        $items = array();
        
        foreach ($items_query->posts as $item) {
            $owner_terms = wp_get_post_terms($item->ID, 'inventory_owner');
            $condition_terms = wp_get_post_terms($item->ID, 'inventory_condition');
            $location_terms = wp_get_post_terms($item->ID, 'inventory_location');
            
            $items[] = array(
                'id' => $item->ID,
                'title' => $item->post_title,
                'status' => $item->post_status,
                'owner' => !empty($owner_terms) ? $owner_terms[0]->name : 'N/A',
                'condition' => !empty($condition_terms) ? $condition_terms[0]->name : 'N/A',
                'location' => !empty($location_terms) ? $location_terms[0]->name : 'N/A',
                'is_migrated' => get_post_meta($item->ID, '_vtc_migrated_from_json', true) === '1'
            );
        }
        
        return array(
            'success' => true,
            'items' => $items,
            'count' => count($items),
            'message' => sprintf(__('Found %d inventory items that will be deleted', 'vtc-inventory'), count($items))
        );
    }
    
    /**
     * Sweep all inventory items
     */
    public function sweep_inventory() {
        if (!current_user_can('manage_options')) {
            return array(
                'success' => false,
                'message' => __('Insufficient permissions', 'vtc-inventory')
            );
        }
        
        // Get ALL inventory items (not just migrated ones)
        $args = array(
            'post_type' => 'vtc_inventory_item',
            'post_status' => 'any',
            'posts_per_page' => -1
        );
        
        $items_query = new WP_Query($args);
        $deleted = 0;
        $errors = 0;
        
        foreach ($items_query->posts as $item) {
            if (wp_delete_post($item->ID, true)) {
                $deleted++;
            } else {
                $errors++;
            }
        }
        
        // Reset all migration-related options
        delete_option('vtc_migration_status');
        delete_option('vtc_migrated_items_count');
        
        // Clean up any orphaned taxonomy terms
        $this->cleanup_orphaned_terms();
        
        return array(
            'success' => $errors === 0,
            'deleted' => $deleted,
            'errors' => $errors,
            'message' => sprintf(__('Sweep completed: %d items deleted, %d errors', 'vtc-inventory'), $deleted, $errors)
        );
    }
    
    /**
     * Get JSON file path
     */
    private function get_json_file_path() {
        return VTC_INVENTORY_PLUGIN_DIR . 'data/data.json';
    }
    
    /**
     * Ensure term exists in taxonomy
     */
    private function ensure_term_exists($term_name, $taxonomy) {
        if (!term_exists($term_name, $taxonomy)) {
            wp_insert_term($term_name, $taxonomy);
        }
    }
    
    /**
     * Convert timestamp to MySQL format
     */
    private function convert_timestamp($timestamp) {
        if (empty($timestamp)) {
            return current_time('mysql');
        }
        
        // Try to parse ISO 8601 format with timezone
        $date = DateTime::createFromFormat('c', $timestamp);
        if ($date) {
            return $date->format('Y-m-d H:i:s');
        }
        
        // Try to parse with explicit format for your timestamps
        $date = DateTime::createFromFormat('Y-m-d\TH:i:sP', $timestamp);
        if ($date) {
            return $date->format('Y-m-d H:i:s');
        }
        
        // Try to parse as standard ISO format
        $date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $timestamp);
        if ($date) {
            return $date->format('Y-m-d H:i:s');
        }
        
        // Fallback to current time
        return current_time('mysql');
    }
    
    /**
     * Migrate photos from old system
     */
    private function migrate_photos($post_id, $photos) {
        $source_dir = VTC_INVENTORY_PLUGIN_DIR . 'data/uploads/';
        $uploaded_count = 0;
        
        foreach ($photos as $photo) {
            $source_path = $source_dir . $photo;
            
            if (!file_exists($source_path)) {
                continue;
            }
            
            $filename = basename($photo);
            $file_array = array(
                'name' => $filename,
                'tmp_name' => $source_path,
                'type' => wp_check_filetype($filename)['type'],
                'error' => 0,
                'size' => filesize($source_path)
            );
            
            $attachment_id = media_handle_sideload($file_array, $post_id);
            
            if (!is_wp_error($attachment_id)) {
                // Set as featured image if this is the first photo
                if ($uploaded_count === 0) {
                    set_post_thumbnail($post_id, $attachment_id);
                }
                $uploaded_count++;
            }
        }
        
        return $uploaded_count;
    }
    
    /**
     * Get migration statistics
     */
    public static function get_migration_stats() {
        return array(
            'status' => get_option('vtc_migration_status', 'not_started'),
            'migrated_count' => get_option('vtc_migrated_items_count', 0),
            'json_file_exists' => file_exists(VTC_INVENTORY_PLUGIN_DIR . 'data/data.json'),
            'total_items' => wp_count_posts('vtc_inventory_item')->publish
        );
    }
    
    /**
     * Check if migration is needed
     */
    public static function is_migration_needed() {
        $migration_status = get_option('vtc_migration_status', 'not_started');
        $json_file_exists = file_exists(VTC_INVENTORY_PLUGIN_DIR . 'data/data.json');
        
        return $json_file_exists && $migration_status !== 'completed';
    }
    
    /**
     * Normalize owner values from JSON to display format
     */
    private function normalize_owner($owner) {
        $owner_map = array(
            'vtc' => 'VTC Woerden',
            'gemeente' => 'Gemeente'
        );
        
        return isset($owner_map[$owner]) ? $owner_map[$owner] : ucfirst($owner);
    }
    
    /**
     * Normalize condition values from JSON to display format
     */
    private function normalize_condition($condition) {
        $condition_map = array(
            'zeer_goed' => 'Zeer goed',
            'goed' => 'Goed',
            'redelijk' => 'Redelijk',
            'slecht' => 'Slecht',
            'zeer_slecht' => 'Zeer slecht'
        );
        
        return isset($condition_map[$condition]) ? $condition_map[$condition] : ucfirst(str_replace('_', ' ', $condition));
    }
    
    /**
     * Clean up orphaned taxonomy terms (only those not used by any posts)
     */
    private function cleanup_orphaned_terms() {
        $taxonomies = array('inventory_owner', 'inventory_condition', 'inventory_location');
        
        foreach ($taxonomies as $taxonomy) {
            // Only get terms that are truly empty (no posts using them)
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => true  // Only get terms with no posts
            ));
            
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    // Double-check that the term is really empty
                    $posts_with_term = get_posts(array(
                        'post_type' => 'any',
                        'tax_query' => array(
                            array(
                                'taxonomy' => $taxonomy,
                                'field' => 'term_id',
                                'terms' => $term->term_id
                            )
                        ),
                        'posts_per_page' => 1
                    ));
                    
                    // Only delete if truly no posts use this term
                    if (empty($posts_with_term)) {
                        wp_delete_term($term->term_id, $taxonomy);
                    }
                }
            }
        }
    }
}
