<?php
/**
 * Admin Pages Class
 * 
 * Handles admin interface pages and settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class VTC_Inventory_Admin_Pages {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_vtc_reset_login_attempts', array($this, 'ajax_reset_login_attempts'));
        add_action('wp_ajax_vtc_export_inventory', array($this, 'ajax_export_inventory'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main inventory menu
        add_menu_page(
            __('VTC Inventory', 'vtc-inventory'),
            __('VTC Inventory', 'vtc-inventory'),
            'manage_vtc_inventory',
            'vtc-inventory',
            array($this, 'inventory_dashboard'),
            'dashicons-boxes',
            25
        );
        
        // Dashboard submenu
        add_submenu_page(
            'vtc-inventory',
            __('Dashboard', 'vtc-inventory'),
            __('Dashboard', 'vtc-inventory'),
            'manage_vtc_inventory',
            'vtc-inventory',
            array($this, 'inventory_dashboard')
        );
        
        // Settings submenu
        add_submenu_page(
            'vtc-inventory',
            __('Settings', 'vtc-inventory'),
            __('Settings', 'vtc-inventory'),
            'manage_options',
            'vtc-inventory-settings',
            array($this, 'settings_page')
        );
        
        // Reports submenu
        add_submenu_page(
            'vtc-inventory',
            __('Reports', 'vtc-inventory'),
            __('Reports', 'vtc-inventory'),
            'manage_vtc_inventory',
            'vtc-inventory-reports',
            array($this, 'reports_page')
        );
        
        // Tools submenu
        add_submenu_page(
            'vtc-inventory',
            __('Tools', 'vtc-inventory'),
            __('Tools', 'vtc-inventory'),
            'manage_options',
            'vtc-inventory-tools',
            array($this, 'tools_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('vtc_inventory_settings', 'vtc_inventory_qr_base_url');
        register_setting('vtc_inventory_settings', 'vtc_inventory_auto_qr');
        register_setting('vtc_inventory_settings', 'vtc_inventory_public_access');
        
        add_settings_section(
            'vtc_inventory_general',
            __('General Settings', 'vtc-inventory'),
            array($this, 'general_settings_section_callback'),
            'vtc_inventory_settings'
        );
        
        
        add_settings_field(
            'vtc_inventory_qr_base_url',
            __('QR Code Base URL', 'vtc-inventory'),
            array($this, 'qr_base_url_field_callback'),
            'vtc_inventory_settings',
            'vtc_inventory_general'
        );
        
        add_settings_field(
            'vtc_inventory_auto_qr',
            __('Auto-generate QR Codes', 'vtc-inventory'),
            array($this, 'auto_qr_field_callback'),
            'vtc_inventory_settings',
            'vtc_inventory_general'
        );
        
        add_settings_field(
            'vtc_inventory_public_access',
            __('Public Access', 'vtc-inventory'),
            array($this, 'public_access_field_callback'),
            'vtc_inventory_settings',
            'vtc_inventory_general'
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'vtc-inventory') !== false) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
            wp_enqueue_script('vtc-inventory-admin', VTC_INVENTORY_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), VTC_INVENTORY_VERSION, true);
            wp_enqueue_style('vtc-inventory-admin', VTC_INVENTORY_PLUGIN_URL . 'assets/css/admin.css', array(), VTC_INVENTORY_VERSION);
            
            wp_localize_script('vtc-inventory-admin', 'vtc_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vtc_admin_nonce')
            ));
        }
    }
    
    /**
     * Inventory dashboard
     */
    public function inventory_dashboard() {
        $stats = $this->get_dashboard_stats();
        $recent_items = $this->get_recent_items();
        $low_stock_items = $this->get_low_stock_items();
        
        ?>
        <div class="wrap">
            <h1><?php _e('VTC Inventory Dashboard', 'vtc-inventory'); ?></h1>
            
            <!-- Statistics Cards -->
            <div class="vtc-stats-grid">
                <div class="vtc-stat-card">
                    <div class="vtc-stat-icon">
                        <span class="dashicons dashicons-boxes"></span>
                    </div>
                    <div class="vtc-stat-content">
                        <h3><?php echo $stats['total_items']; ?></h3>
                        <p><?php _e('Total Items', 'vtc-inventory'); ?></p>
                    </div>
                </div>
                
                <div class="vtc-stat-card">
                    <div class="vtc-stat-icon">
                        <span class="dashicons dashicons-location"></span>
                    </div>
                    <div class="vtc-stat-content">
                        <h3><?php echo $stats['total_locations']; ?></h3>
                        <p><?php _e('Locations', 'vtc-inventory'); ?></p>
                    </div>
                </div>
                
                <div class="vtc-stat-card">
                    <div class="vtc-stat-icon">
                        <span class="dashicons dashicons-admin-users"></span>
                    </div>
                    <div class="vtc-stat-content">
                        <h3><?php echo $stats['total_owners']; ?></h3>
                        <p><?php _e('Owners', 'vtc-inventory'); ?></p>
                    </div>
                </div>
                
                <div class="vtc-stat-card">
                    <div class="vtc-stat-icon">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <div class="vtc-stat-content">
                        <h3><?php echo $stats['low_stock_items']; ?></h3>
                        <p><?php _e('Low Stock Items', 'vtc-inventory'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="vtc-charts-row">
                <div class="vtc-chart-container">
                    <h3><?php _e('Items by Owner', 'vtc-inventory'); ?></h3>
                    <canvas id="ownerChart"></canvas>
                </div>
                
                <div class="vtc-chart-container">
                    <h3><?php _e('Items by Condition', 'vtc-inventory'); ?></h3>
                    <canvas id="conditionChart"></canvas>
                </div>
            </div>
            
            <!-- Recent Items and Low Stock -->
            <div class="vtc-content-row">
                <div class="vtc-content-column">
                    <h3><?php _e('Recent Items', 'vtc-inventory'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Name', 'vtc-inventory'); ?></th>
                                <th><?php _e('Quantity', 'vtc-inventory'); ?></th>
                                <th><?php _e('Location', 'vtc-inventory'); ?></th>
                                <th><?php _e('Date', 'vtc-inventory'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_items as $item): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($item->ID); ?>">
                                            <?php echo esc_html($item->post_title); ?>
                                        </a>
                                    </td>
                                    <td><?php echo get_post_meta($item->ID, '_vtc_quantity', true) ?: 1; ?></td>
                                    <td>
                                        <?php
                                        $location_terms = wp_get_post_terms($item->ID, 'inventory_location', array('fields' => 'names'));
                                        echo esc_html(implode(', ', $location_terms));
                                        ?>
                                    </td>
                                    <td><?php echo get_the_date('Y-m-d', $item->ID); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="vtc-content-column">
                    <h3><?php _e('Low Stock Items', 'vtc-inventory'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Name', 'vtc-inventory'); ?></th>
                                <th><?php _e('Quantity', 'vtc-inventory'); ?></th>
                                <th><?php _e('Location', 'vtc-inventory'); ?></th>
                                <th><?php _e('Action', 'vtc-inventory'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($low_stock_items as $item): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($item->ID); ?>">
                                            <?php echo esc_html($item->post_title); ?>
                                        </a>
                                    </td>
                                    <td><?php echo get_post_meta($item->ID, '_vtc_quantity', true) ?: 1; ?></td>
                                    <td>
                                        <?php
                                        $location_terms = wp_get_post_terms($item->ID, 'inventory_location', array('fields' => 'names'));
                                        echo esc_html(implode(', ', $location_terms));
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($item->ID); ?>" class="button button-small">
                                            <?php _e('Edit', 'vtc-inventory'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Owner Chart
            const ownerCtx = document.getElementById('ownerChart').getContext('2d');
            new Chart(ownerCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_keys($stats['owner_breakdown'])); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($stats['owner_breakdown'])); ?>,
                        backgroundColor: ['#0073aa', '#00a32a', '#d63638', '#f0b849']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            
            // Condition Chart
            const conditionCtx = document.getElementById('conditionChart').getContext('2d');
            new Chart(conditionCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_keys($stats['condition_breakdown'])); ?>,
                    datasets: [{
                        label: '<?php _e('Items', 'vtc-inventory'); ?>',
                        data: <?php echo json_encode(array_values($stats['condition_breakdown'])); ?>,
                        backgroundColor: ['#d1e7dd', '#d1ecf1', '#fff3cd', '#f8d7da', '#f5c6cb']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('VTC Inventory Settings', 'vtc-inventory'); ?></h1>
            
            <form method="post" action="">
                <?php settings_fields('vtc_inventory_settings'); ?>
                <?php do_settings_sections('vtc_inventory_settings'); ?>
                
                <table class="form-table">
                    <?php $this->general_settings_section_callback(); ?>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php _e('Access Information', 'vtc-inventory'); ?></h2>
                <p><?php _e('All visitors can view the inventory. Logged-in WordPress users can manage inventory items.', 'vtc-inventory'); ?></p>
                <p><strong><?php _e('Current Access:', 'vtc-inventory'); ?></strong> 
                    <?php if (is_user_logged_in()): ?>
                        <?php _e('Logged in - Full management access', 'vtc-inventory'); ?>
                    <?php else: ?>
                        <?php _e('Not logged in - View only access', 'vtc-inventory'); ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Reports page
     */
    public function reports_page() {
        $date_from = sanitize_text_field($_GET['date_from'] ?? date('Y-m-01'));
        $date_to = sanitize_text_field($_GET['date_to'] ?? date('Y-m-d'));
        $location = sanitize_text_field($_GET['location'] ?? '');
        $owner = sanitize_text_field($_GET['owner'] ?? '');
        
        $report_data = $this->generate_report($date_from, $date_to, $location, $owner);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Inventory Reports', 'vtc-inventory'); ?></h1>
            
            <!-- Filters -->
            <div class="card" style="max-width: 800px;">
                <h2><?php _e('Report Filters', 'vtc-inventory'); ?></h2>
                <form method="get" action="">
                    <input type="hidden" name="page" value="vtc-inventory-reports">
                    
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Date From', 'vtc-inventory'); ?></th>
                            <td><input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><?php _e('Date To', 'vtc-inventory'); ?></th>
                            <td><input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><?php _e('Location', 'vtc-inventory'); ?></th>
                            <td>
                                <select name="location" class="regular-text">
                                    <option value=""><?php _e('All Locations', 'vtc-inventory'); ?></option>
                                    <?php
                                    $locations = get_terms(array('taxonomy' => 'inventory_location', 'hide_empty' => false));
                                    foreach ($locations as $loc) {
                                        echo '<option value="' . esc_attr($loc->slug) . '"' . selected($location, $loc->slug, false) . '>' . esc_html($loc->name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Owner', 'vtc-inventory'); ?></th>
                            <td>
                                <select name="owner" class="regular-text">
                                    <option value=""><?php _e('All Owners', 'vtc-inventory'); ?></option>
                                    <?php
                                    $owners = get_terms(array('taxonomy' => 'inventory_owner', 'hide_empty' => false));
                                    foreach ($owners as $own) {
                                        echo '<option value="' . esc_attr($own->slug) . '"' . selected($owner, $own->slug, false) . '>' . esc_html($own->name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Generate Report', 'vtc-inventory')); ?>
                </form>
            </div>
            
            <!-- Report Results -->
            <div class="card" style="max-width: 1200px; margin-top: 20px;">
                <h2><?php _e('Report Results', 'vtc-inventory'); ?></h2>
                
                <div class="vtc-report-actions">
                    <button type="button" class="button" id="export-csv"><?php _e('Export CSV', 'vtc-inventory'); ?></button>
                    <button type="button" class="button" id="export-pdf"><?php _e('Export PDF', 'vtc-inventory'); ?></button>
                    <button type="button" class="button" id="print-report"><?php _e('Print Report', 'vtc-inventory'); ?></button>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'vtc-inventory'); ?></th>
                            <th><?php _e('Quantity', 'vtc-inventory'); ?></th>
                            <th><?php _e('Owner', 'vtc-inventory'); ?></th>
                            <th><?php _e('Condition', 'vtc-inventory'); ?></th>
                            <th><?php _e('Location', 'vtc-inventory'); ?></th>
                            <th><?php _e('Date Added', 'vtc-inventory'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data['items'] as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item->post_title); ?></td>
                                <td><?php echo get_post_meta($item->ID, '_vtc_quantity', true) ?: 1; ?></td>
                                <td>
                                    <?php
                                    $owner_terms = wp_get_post_terms($item->ID, 'inventory_owner', array('fields' => 'names'));
                                    echo esc_html(implode(', ', $owner_terms));
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $condition_terms = wp_get_post_terms($item->ID, 'inventory_condition', array('fields' => 'names'));
                                    echo esc_html(implode(', ', $condition_terms));
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $location_terms = wp_get_post_terms($item->ID, 'inventory_location', array('fields' => 'names'));
                                    echo esc_html(implode(', ', $location_terms));
                                    ?>
                                </td>
                                <td><?php echo get_the_date('Y-m-d', $item->ID); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="vtc-report-summary">
                    <h3><?php _e('Summary', 'vtc-inventory'); ?></h3>
                    <p><strong><?php _e('Total Items:', 'vtc-inventory'); ?></strong> <?php echo $report_data['total_items']; ?></p>
                    <p><strong><?php _e('Total Quantity:', 'vtc-inventory'); ?></strong> <?php echo $report_data['total_quantity']; ?></p>
                    <p><strong><?php _e('Report Period:', 'vtc-inventory'); ?></strong> <?php echo $date_from; ?> - <?php echo $date_to; ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Tools page
     */
    public function tools_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('VTC Inventory Tools', 'vtc-inventory'); ?></h1>
            
            <div class="card" style="max-width: 800px;">
                <h2><?php _e('Data Management', 'vtc-inventory'); ?></h2>
                <p><?php _e('Tools for managing inventory data and system maintenance.', 'vtc-inventory'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('Export All Data', 'vtc-inventory'); ?></th>
                        <td>
                            <button type="button" class="button" id="export-all-csv"><?php _e('Export CSV', 'vtc-inventory'); ?></button>
                            <button type="button" class="button" id="export-all-pdf"><?php _e('Export PDF', 'vtc-inventory'); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Reset Login Attempts', 'vtc-inventory'); ?></th>
                        <td>
                            <button type="button" class="button" id="reset-login-attempts"><?php _e('Reset Failed Attempts', 'vtc-inventory'); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Regenerate QR Codes', 'vtc-inventory'); ?></th>
                        <td>
                            <button type="button" class="button" id="regenerate-qr-codes"><?php _e('Regenerate All QR Codes', 'vtc-inventory'); ?></button>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php _e('System Information', 'vtc-inventory'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Plugin Version', 'vtc-inventory'); ?></th>
                        <td><?php echo VTC_INVENTORY_VERSION; ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('WordPress Version', 'vtc-inventory'); ?></th>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Total Items', 'vtc-inventory'); ?></th>
                        <td><?php echo wp_count_posts('vtc_inventory_item')->publish; ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Database Size', 'vtc-inventory'); ?></th>
                        <td><?php echo $this->get_database_size(); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        $total_items = wp_count_posts('vtc_inventory_item')->publish;
        $total_locations = wp_count_terms('inventory_location');
        $total_owners = wp_count_terms('inventory_owner');
        
        // Get low stock items (quantity <= 2)
        $low_stock_query = new WP_Query(array(
            'post_type' => 'vtc_inventory_item',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_vtc_quantity',
                    'value' => 2,
                    'compare' => '<='
                )
            )
        ));
        
        $low_stock_items = $low_stock_query->found_posts;
        
        // Get owner breakdown
        $owner_breakdown = array();
        $owners = get_terms(array('taxonomy' => 'inventory_owner', 'hide_empty' => false));
        foreach ($owners as $owner) {
            $count = wp_count_posts('vtc_inventory_item', array(
                'tax_query' => array(
                    array(
                        'taxonomy' => 'inventory_owner',
                        'field' => 'term_id',
                        'terms' => $owner->term_id
                    )
                )
            ));
            $owner_breakdown[$owner->name] = $count->publish;
        }
        
        // Get condition breakdown
        $condition_breakdown = array();
        $conditions = get_terms(array('taxonomy' => 'inventory_condition', 'hide_empty' => false));
        foreach ($conditions as $condition) {
            $count = wp_count_posts('vtc_inventory_item', array(
                'tax_query' => array(
                    array(
                        'taxonomy' => 'inventory_condition',
                        'field' => 'term_id',
                        'terms' => $condition->term_id
                    )
                )
            ));
            $condition_breakdown[$condition->name] = $count->publish;
        }
        
        return array(
            'total_items' => $total_items,
            'total_locations' => $total_locations,
            'total_owners' => $total_owners,
            'low_stock_items' => $low_stock_items,
            'owner_breakdown' => $owner_breakdown,
            'condition_breakdown' => $condition_breakdown
        );
    }
    
    /**
     * Get recent items
     */
    private function get_recent_items($limit = 10) {
        $query = new WP_Query(array(
            'post_type' => 'vtc_inventory_item',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        return $query->posts;
    }
    
    /**
     * Get low stock items
     */
    private function get_low_stock_items($limit = 10) {
        $query = new WP_Query(array(
            'post_type' => 'vtc_inventory_item',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_query' => array(
                array(
                    'key' => '_vtc_quantity',
                    'value' => 2,
                    'compare' => '<='
                )
            ),
            'orderby' => 'meta_value_num',
            'meta_key' => '_vtc_quantity',
            'order' => 'ASC'
        ));
        
        return $query->posts;
    }
    
    /**
     * Generate report data
     */
    private function generate_report($date_from, $date_to, $location, $owner) {
        $args = array(
            'post_type' => 'vtc_inventory_item',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'date_query' => array(
                array(
                    'after' => $date_from,
                    'before' => $date_to,
                    'inclusive' => true
                )
            )
        );
        
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
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        $query = new WP_Query($args);
        $items = $query->posts;
        
        $total_quantity = 0;
        foreach ($items as $item) {
            $total_quantity += get_post_meta($item->ID, '_vtc_quantity', true) ?: 1;
        }
        
        return array(
            'items' => $items,
            'total_items' => count($items),
            'total_quantity' => $total_quantity
        );
    }
    
    /**
     * Get database size
     */
    private function get_database_size() {
        global $wpdb;
        
        $result = $wpdb->get_var("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'DB Size in MB'
            FROM information_schema.tables
            WHERE table_schema = '" . DB_NAME . "'
        ");
        
        return $result ? $result . ' MB' : 'Unknown';
    }
    
    /**
     * Settings field callbacks
     */
    public function general_settings_section_callback() {
        echo '<p>' . __('Configure general settings for the VTC Inventory system.', 'vtc-inventory') . '</p>';
    }
    
    
    public function qr_base_url_field_callback() {
        $value = get_option('vtc_inventory_qr_base_url', 'https://vtcwoerden.nl/materiaal/?object=');
        echo '<input type="url" name="vtc_inventory_qr_base_url" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Base URL for QR codes. The item name will be appended to this URL.', 'vtc-inventory') . '</p>';
    }
    
    public function auto_qr_field_callback() {
        $value = get_option('vtc_inventory_auto_qr', 1);
        echo '<input type="checkbox" name="vtc_inventory_auto_qr" value="1" ' . checked($value, 1, false) . ' />';
        echo '<p class="description">' . __('Automatically generate QR codes when items are created or updated.', 'vtc-inventory') . '</p>';
    }
    
    public function public_access_field_callback() {
        $value = get_option('vtc_inventory_public_access', 0);
        echo '<input type="checkbox" name="vtc_inventory_public_access" value="1" ' . checked($value, 1, false) . ' />';
        echo '<p class="description">' . __('Allow public access to view inventory (read-only).', 'vtc-inventory') . '</p>';
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        
        if (isset($_POST['vtc_inventory_qr_base_url'])) {
            update_option('vtc_inventory_qr_base_url', esc_url_raw($_POST['vtc_inventory_qr_base_url']));
        }
        
        update_option('vtc_inventory_auto_qr', isset($_POST['vtc_inventory_auto_qr']) ? 1 : 0);
        update_option('vtc_inventory_public_access', isset($_POST['vtc_inventory_public_access']) ? 1 : 0);
        
        echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'vtc-inventory') . '</p></div>';
    }
    
    /**
     * AJAX handlers
     */
    public function ajax_reset_login_attempts() {
        if (!wp_verify_nonce($_POST['nonce'], 'vtc_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'vtc-inventory')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'vtc-inventory')));
        }
        
        VTC_Inventory_User_Roles::reset_login_attempts();
        wp_send_json_success(array('message' => __('Login attempts reset successfully', 'vtc-inventory')));
    }
    
    public function ajax_export_inventory() {
        if (!wp_verify_nonce($_POST['nonce'], 'vtc_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'vtc-inventory')));
        }
        
        if (!current_user_can('export_vtc_inventory')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'vtc-inventory')));
        }
        
        $format = sanitize_text_field($_POST['format']);
        $url = home_url('/inventory/export/' . $format . '/');
        
        wp_send_json_success(array('url' => $url));
    }
}
