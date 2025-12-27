<?php
/**
 * Plugin Name: VTC Inventory Management
 * Plugin URI: https://vtcwoerden.nl
 * Description: Material inventory system for VTC Woerden using WordPress database and native features
 * Version: 1.0.0
 * Author: VTC Woerden
 * License: GPL v2 or later
 * Text Domain: vtc-inventory
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VTC_INVENTORY_VERSION', '1.0.0');
define('VTC_INVENTORY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VTC_INVENTORY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VTC_INVENTORY_PLUGIN_FILE', __FILE__);

// Include required files with error checking
$required_files = array(
    'includes/class-post-types.php',
    'includes/class-user-roles.php', 
    'includes/class-inventory-manager.php',
    'includes/class-data-migration.php',
    'includes/class-admin-pages.php',
    'includes/class-pdf-generator.php'
);

foreach ($required_files as $file) {
    $file_path = VTC_INVENTORY_PLUGIN_DIR . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    } else {
        error_log("VTC Inventory: Required file not found: $file_path");
    }
}

/**
 * Initialize plugin components
 */
function vtc_inventory_init() {
    // Initialize all components
    if (class_exists('VTC_Inventory_Post_Types')) {
        new VTC_Inventory_Post_Types();
    }
    if (class_exists('VTC_Inventory_User_Roles')) {
        new VTC_Inventory_User_Roles();
    }
    if (class_exists('VTC_Inventory_Manager')) {
        new VTC_Inventory_Manager();
    }
    if (class_exists('VTC_Data_Migration')) {
        new VTC_Data_Migration();
    }
    if (class_exists('VTC_Inventory_Admin_Pages')) {
        new VTC_Inventory_Admin_Pages();
    }
    if (class_exists('VTC_Inventory_PDF_Generator')) {
        new VTC_Inventory_PDF_Generator();
    }
}

/**
 * Add custom rewrite rules
 */
function vtc_inventory_add_rewrite_rules() {
    add_rewrite_rule(
        '^inventory/export/([^/]+)/?$',
        'index.php?vtc_export=1&vtc_format=$matches[1]',
        'top'
    );
}

/**
 * Add custom query vars
 */
function vtc_inventory_add_query_vars($vars) {
    $vars[] = 'vtc_export';
    $vars[] = 'vtc_format';
    return $vars;
}

/**
 * Plugin activation
 */
function vtc_inventory_activate() {
    // Create default terms
    vtc_inventory_create_default_terms();
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Set default options
    add_option('vtc_inventory_version', VTC_INVENTORY_VERSION);
}

/**
 * Plugin deactivation
 */
function vtc_inventory_deactivate() {
    flush_rewrite_rules();
}

/**
 * Create default taxonomy terms
 */
function vtc_inventory_create_default_terms() {
    // Create default owners
    if (!term_exists('Gemeente', 'inventory_owner')) {
        wp_insert_term('Gemeente', 'inventory_owner');
    }
    if (!term_exists('VTC Woerden', 'inventory_owner')) {
        wp_insert_term('VTC Woerden', 'inventory_owner');
    }
    
    // Create default conditions
    $conditions = array('Zeer goed', 'Goed', 'Redelijk', 'Slecht', 'Zeer slecht');
    foreach ($conditions as $condition) {
        if (!term_exists($condition, 'inventory_condition')) {
            wp_insert_term($condition, 'inventory_condition');
        }
    }
    
    // Create default locations
    $locations = array(
        'Trainerskast 1', 'Trainerskast 2', 'Trainerskast 3',
        'Materiaalruimte', 'Beheerdersruimte', 'Zaal', 'Lokaal',
        'Kantine', 'Keuken', 'Vide', 'Ballenkar'
    );
    foreach ($locations as $location) {
        if (!term_exists($location, 'inventory_location')) {
            wp_insert_term($location, 'inventory_location');
        }
    }
}

// Hook into WordPress
add_action('init', 'vtc_inventory_init');
add_action('init', 'vtc_inventory_add_rewrite_rules');
add_filter('query_vars', 'vtc_inventory_add_query_vars');
add_action('plugins_loaded', 'vtc_inventory_load_textdomain');
register_activation_hook(__FILE__, 'vtc_inventory_activate');
register_deactivation_hook(__FILE__, 'vtc_inventory_deactivate');

/**
 * Load plugin textdomain
 */
function vtc_inventory_load_textdomain() {
    load_plugin_textdomain('vtc-inventory', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
