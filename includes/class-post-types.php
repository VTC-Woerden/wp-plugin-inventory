<?php
/**
 * Post Types and Taxonomies Class
 * 
 * Handles the registration of custom post types and taxonomies
 * for the VTC Inventory system
 */

if (!defined('ABSPATH')) {
    exit;
}

class VTC_Inventory_Post_Types {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_fields'));
        add_action('rest_api_init', array($this, 'add_rest_fields'));
        add_filter('manage_vtc_inventory_item_posts_columns', array($this, 'add_admin_columns'));
        add_action('manage_vtc_inventory_item_posts_custom_column', array($this, 'populate_admin_columns'), 10, 2);
        add_filter('manage_edit-vtc_inventory_item_sortable_columns', array($this, 'make_columns_sortable'));
        
        // Register post types and taxonomies immediately
        $this->register_post_types();
        $this->register_taxonomies();
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        $labels = array(
            'name'                  => _x('Inventory Items', 'Post type general name', 'vtc-inventory'),
            'singular_name'         => _x('Inventory Item', 'Post type singular name', 'vtc-inventory'),
            'menu_name'             => _x('Inventory', 'Admin Menu text', 'vtc-inventory'),
            'name_admin_bar'        => _x('Inventory Item', 'Add New on Toolbar', 'vtc-inventory'),
            'add_new'               => __('Add New', 'vtc-inventory'),
            'add_new_item'          => __('Add New Inventory Item', 'vtc-inventory'),
            'new_item'              => __('New Inventory Item', 'vtc-inventory'),
            'edit_item'             => __('Edit Inventory Item', 'vtc-inventory'),
            'view_item'             => __('View Inventory Item', 'vtc-inventory'),
            'all_items'             => __('All Items', 'vtc-inventory'),
            'search_items'          => __('Search Items', 'vtc-inventory'),
            'parent_item_colon'     => __('Parent Items:', 'vtc-inventory'),
            'not_found'             => __('No items found.', 'vtc-inventory'),
            'not_found_in_trash'    => __('No items found in Trash.', 'vtc-inventory'),
            'featured_image'        => _x('Item Photo', 'Overrides the "Featured Image" phrase', 'vtc-inventory'),
            'set_featured_image'    => _x('Set item photo', 'Overrides the "Set featured image" phrase', 'vtc-inventory'),
            'remove_featured_image' => _x('Remove item photo', 'Overrides the "Remove featured image" phrase', 'vtc-inventory'),
            'use_featured_image'    => _x('Use as item photo', 'Overrides the "Use as featured image" phrase', 'vtc-inventory'),
            'archives'              => _x('Inventory archives', 'The post type archive label', 'vtc-inventory'),
            'insert_into_item'      => _x('Insert into item', 'Overrides the "Insert into post"/"Insert into page" phrase', 'vtc-inventory'),
            'uploaded_to_this_item' => _x('Uploaded to this item', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase', 'vtc-inventory'),
            'filter_items_list'     => _x('Filter items list', 'Screen reader text for the filter links', 'vtc-inventory'),
            'items_list_navigation' => _x('Items list navigation', 'Screen reader text for the pagination', 'vtc-inventory'),
            'items_list'            => _x('Items list', 'Screen reader text for the items list', 'vtc-inventory'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
            'show_in_rest'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'inventory-item'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 25,
            'menu_icon'          => 'dashicons-boxes',
            'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields', 'revisions'),
            'capabilities'       => array(
                'edit_post'          => 'manage_vtc_inventory',
                'read_post'          => 'read_vtc_inventory',
                'delete_post'        => 'manage_vtc_inventory',
                'edit_posts'         => 'manage_vtc_inventory',
                'edit_others_posts'  => 'manage_vtc_inventory',
                'publish_posts'      => 'manage_vtc_inventory',
                'read_private_posts' => 'manage_vtc_inventory',
                'delete_posts'       => 'manage_vtc_inventory',
            ),
        );

        register_post_type('vtc_inventory_item', $args);
    }
    
    /**
     * Register custom taxonomies
     */
    public function register_taxonomies() {
        // Owner taxonomy (Gemeente vs VTC)
        $owner_labels = array(
            'name'              => _x('Owners', 'taxonomy general name', 'vtc-inventory'),
            'singular_name'     => _x('Owner', 'taxonomy singular name', 'vtc-inventory'),
            'search_items'      => __('Search Owners', 'vtc-inventory'),
            'all_items'         => __('All Owners', 'vtc-inventory'),
            'parent_item'       => __('Parent Owner', 'vtc-inventory'),
            'parent_item_colon' => __('Parent Owner:', 'vtc-inventory'),
            'edit_item'         => __('Edit Owner', 'vtc-inventory'),
            'update_item'       => __('Update Owner', 'vtc-inventory'),
            'add_new_item'      => __('Add New Owner', 'vtc-inventory'),
            'new_item_name'     => __('New Owner Name', 'vtc-inventory'),
            'menu_name'         => __('Owners', 'vtc-inventory'),
        );

        register_taxonomy('inventory_owner', array('vtc_inventory_item'), array(
            'hierarchical'      => true,
            'labels'            => $owner_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'owner'),
            'capabilities'      => array(
                'manage_terms' => 'manage_vtc_inventory',
                'edit_terms'   => 'manage_vtc_inventory',
                'delete_terms' => 'manage_vtc_inventory',
                'assign_terms' => 'manage_vtc_inventory',
            ),
        ));

        // Condition taxonomy
        $condition_labels = array(
            'name'              => _x('Conditions', 'taxonomy general name', 'vtc-inventory'),
            'singular_name'     => _x('Condition', 'taxonomy singular name', 'vtc-inventory'),
            'search_items'      => __('Search Conditions', 'vtc-inventory'),
            'all_items'         => __('All Conditions', 'vtc-inventory'),
            'parent_item'       => __('Parent Condition', 'vtc-inventory'),
            'parent_item_colon' => __('Parent Condition:', 'vtc-inventory'),
            'edit_item'         => __('Edit Condition', 'vtc-inventory'),
            'update_item'       => __('Update Condition', 'vtc-inventory'),
            'add_new_item'      => __('Add New Condition', 'vtc-inventory'),
            'new_item_name'     => __('New Condition Name', 'vtc-inventory'),
            'menu_name'         => __('Conditions', 'vtc-inventory'),
        );

        register_taxonomy('inventory_condition', array('vtc_inventory_item'), array(
            'hierarchical'      => true,
            'labels'            => $condition_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'condition'),
        ));

        // Location taxonomy
        $location_labels = array(
            'name'              => _x('Locations', 'taxonomy general name', 'vtc-inventory'),
            'singular_name'     => _x('Location', 'taxonomy singular name', 'vtc-inventory'),
            'search_items'      => __('Search Locations', 'vtc-inventory'),
            'all_items'         => __('All Locations', 'vtc-inventory'),
            'parent_item'       => __('Parent Location', 'vtc-inventory'),
            'parent_item_colon' => __('Parent Location:', 'vtc-inventory'),
            'edit_item'         => __('Edit Location', 'vtc-inventory'),
            'update_item'       => __('Update Location', 'vtc-inventory'),
            'add_new_item'      => __('Add New Location', 'vtc-inventory'),
            'new_item_name'     => __('New Location Name', 'vtc-inventory'),
            'menu_name'         => __('Locations', 'vtc-inventory'),
        );

        register_taxonomy('inventory_location', array('vtc_inventory_item'), array(
            'hierarchical'      => true,
            'labels'            => $location_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'location'),
        ));
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'vtc_inventory_details',
            __('Inventory Details', 'vtc-inventory'),
            array($this, 'inventory_details_meta_box'),
            'vtc_inventory_item',
            'normal',
            'high'
        );
        
        add_meta_box(
            'vtc_inventory_qr_code',
            __('QR Code', 'vtc-inventory'),
            array($this, 'qr_code_meta_box'),
            'vtc_inventory_item',
            'side',
            'default'
        );
    }
    
    /**
     * Inventory details meta box
     */
    public function inventory_details_meta_box($post) {
        wp_nonce_field('vtc_inventory_meta_box', 'vtc_inventory_meta_box_nonce');
        
        $quantity = get_post_meta($post->ID, '_vtc_quantity', true);
        $comments = get_post_meta($post->ID, '_vtc_comments', true);
        $qr_code_url = get_post_meta($post->ID, '_vtc_qr_code_url', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="vtc_quantity"><?php _e('Quantity', 'vtc-inventory'); ?></label></th>
                <td>
                    <input type="number" id="vtc_quantity" name="vtc_quantity" value="<?php echo esc_attr($quantity); ?>" min="1" class="regular-text" />
                    <p class="description"><?php _e('Number of items available', 'vtc-inventory'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="vtc_comments"><?php _e('Comments', 'vtc-inventory'); ?></label></th>
                <td>
                    <textarea id="vtc_comments" name="vtc_comments" rows="3" cols="50" class="large-text"><?php echo esc_textarea($comments); ?></textarea>
                    <p class="description"><?php _e('Additional notes about this item', 'vtc-inventory'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="vtc_qr_code_url"><?php _e('QR Code URL', 'vtc-inventory'); ?></label></th>
                <td>
                    <input type="url" id="vtc_qr_code_url" name="vtc_qr_code_url" value="<?php echo esc_attr($qr_code_url); ?>" class="regular-text" readonly />
                    <p class="description"><?php _e('Auto-generated based on item name', 'vtc-inventory'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * QR Code meta box
     */
    public function qr_code_meta_box($post) {
        $qr_code_url = get_post_meta($post->ID, '_vtc_qr_code_url', true);
        
        if ($qr_code_url) {
            ?>
            <div id="qr-code-preview" style="text-align: center; margin: 10px 0;">
                <div id="qr-code-<?php echo $post->ID; ?>" style="width: 150px; height: 150px; margin: 0 auto;"></div>
                <p><small><?php _e('QR Code for this item', 'vtc-inventory'); ?></small></p>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof qrcode !== 'undefined') {
                    try {
                        const qr = qrcode(0, 'M');
                        qr.addData('<?php echo esc_js($qr_code_url); ?>');
                        qr.make();
                        const qrImage = qr.createImgTag(4, 2);
                        document.getElementById('qr-code-<?php echo $post->ID; ?>').innerHTML = qrImage;
                    } catch(e) {
                        console.error('QR Code generation failed:', e);
                    }
                }
            });
            </script>
            <?php
        }
    }
    
    /**
     * Save meta fields
     */
    public function save_meta_fields($post_id) {
        // Check if our nonce is set
        if (!isset($_POST['vtc_inventory_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['vtc_inventory_meta_box_nonce'], 'vtc_inventory_meta_box')) {
            return;
        }
        
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save quantity
        if (isset($_POST['vtc_quantity'])) {
            update_post_meta($post_id, '_vtc_quantity', intval($_POST['vtc_quantity']));
        }
        
        // Save comments
        if (isset($_POST['vtc_comments'])) {
            update_post_meta($post_id, '_vtc_comments', sanitize_textarea_field($_POST['vtc_comments']));
        }
        
        // Auto-generate QR code URL
        $post = get_post($post_id);
        if ($post && $post->post_type === 'vtc_inventory_item') {
            $qr_url = 'https://vtcwoerden.nl/materiaal/?object=' . urlencode($post->post_title);
            update_post_meta($post_id, '_vtc_qr_code_url', $qr_url);
        }
    }
    
    /**
     * Add REST API fields
     */
    public function add_rest_fields() {
        register_rest_field('vtc_inventory_item', 'inventory_data', array(
            'get_callback' => array($this, 'get_inventory_rest_data'),
            'update_callback' => array($this, 'update_inventory_rest_data'),
            'schema' => array(
                'description' => __('Inventory item data', 'vtc-inventory'),
                'type' => 'object',
                'context' => array('view', 'edit'),
            ),
        ));
    }
    
    /**
     * Get inventory data for REST API
     */
    public function get_inventory_rest_data($object) {
        return array(
            'quantity' => get_post_meta($object['id'], '_vtc_quantity', true),
            'comments' => get_post_meta($object['id'], '_vtc_comments', true),
            'qr_code_url' => get_post_meta($object['id'], '_vtc_qr_code_url', true),
            'owner' => wp_get_post_terms($object['id'], 'inventory_owner', array('fields' => 'names')),
            'condition' => wp_get_post_terms($object['id'], 'inventory_condition', array('fields' => 'names')),
            'location' => wp_get_post_terms($object['id'], 'inventory_location', array('fields' => 'names')),
        );
    }
    
    /**
     * Update inventory data via REST API
     */
    public function update_inventory_rest_data($value, $object) {
        if (isset($value['quantity'])) {
            update_post_meta($object->ID, '_vtc_quantity', intval($value['quantity']));
        }
        if (isset($value['comments'])) {
            update_post_meta($object->ID, '_vtc_comments', sanitize_textarea_field($value['comments']));
        }
        if (isset($value['qr_code_url'])) {
            update_post_meta($object->ID, '_vtc_qr_code_url', esc_url_raw($value['qr_code_url']));
        }
    }
    
    /**
     * Add admin columns
     */
    public function add_admin_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['quantity'] = __('Quantity', 'vtc-inventory');
        $new_columns['owner'] = __('Owner', 'vtc-inventory');
        $new_columns['condition'] = __('Condition', 'vtc-inventory');
        $new_columns['location'] = __('Location', 'vtc-inventory');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Populate admin columns
     */
    public function populate_admin_columns($column, $post_id) {
        switch ($column) {
            case 'quantity':
                echo get_post_meta($post_id, '_vtc_quantity', true) ?: '1';
                break;
            case 'owner':
                $terms = get_the_terms($post_id, 'inventory_owner');
                if ($terms && !is_wp_error($terms)) {
                    echo esc_html(implode(', ', wp_list_pluck($terms, 'name')));
                }
                break;
            case 'condition':
                $terms = get_the_terms($post_id, 'inventory_condition');
                if ($terms && !is_wp_error($terms)) {
                    echo esc_html(implode(', ', wp_list_pluck($terms, 'name')));
                }
                break;
            case 'location':
                $terms = get_the_terms($post_id, 'inventory_location');
                if ($terms && !is_wp_error($terms)) {
                    echo esc_html(implode(', ', wp_list_pluck($terms, 'name')));
                }
                break;
        }
    }
    
    /**
     * Make columns sortable
     */
    public function make_columns_sortable($columns) {
        $columns['quantity'] = 'quantity';
        $columns['owner'] = 'owner';
        $columns['condition'] = 'condition';
        $columns['location'] = 'location';
        return $columns;
    }
}
