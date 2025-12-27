<?php
/**
 * Test file to verify VTC Inventory shortcode is working
 * 
 * This file can be used to test if the shortcode is properly registered and functioning.
 * Place this file in your WordPress root directory and visit it in your browser.
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Check if shortcode is registered
global $shortcode_tags;

echo "<h1>VTC Inventory Shortcode Test</h1>";

if (isset($shortcode_tags['vtc_inventory'])) {
    echo "<p style='color: green;'>✅ Shortcode 'vtc_inventory' is registered!</p>";
    
    // Test shortcode execution
    echo "<h2>Shortcode Output Test:</h2>";
    echo "<div style='border: 1px solid #ccc; padding: 20px; margin: 20px 0;'>";
    echo do_shortcode('[vtc_inventory]');
    echo "</div>";
    
} else {
    echo "<p style='color: red;'>❌ Shortcode 'vtc_inventory' is NOT registered!</p>";
    echo "<p>Possible issues:</p>";
    echo "<ul>";
    echo "<li>Plugin is not activated</li>";
    echo "<li>Plugin files are not in the correct location</li>";
    echo "<li>There's a PHP error preventing the plugin from loading</li>";
    echo "</ul>";
}

// Check if plugin is active
if (is_plugin_active('vtc-inventory/vtc-inventory.php')) {
    echo "<p style='color: green;'>✅ Plugin is active!</p>";
} else {
    echo "<p style='color: red;'>❌ Plugin is NOT active!</p>";
}

// Check if custom post type exists
if (post_type_exists('vtc_inventory_item')) {
    echo "<p style='color: green;'>✅ Custom post type 'vtc_inventory_item' exists!</p>";
} else {
    echo "<p style='color: red;'>❌ Custom post type 'vtc_inventory_item' does NOT exist!</p>";
}

// Check if taxonomies exist
$taxonomies = array('inventory_owner', 'inventory_condition', 'inventory_location');
foreach ($taxonomies as $taxonomy) {
    if (taxonomy_exists($taxonomy)) {
        echo "<p style='color: green;'>✅ Taxonomy '$taxonomy' exists!</p>";
    } else {
        echo "<p style='color: red;'>❌ Taxonomy '$taxonomy' does NOT exist!</p>";
    }
}

// List all registered shortcodes
echo "<h2>All Registered Shortcodes:</h2>";
echo "<ul>";
foreach ($shortcode_tags as $tag => $callback) {
    echo "<li><strong>$tag</strong> - " . (is_array($callback) ? get_class($callback[0]) . '::' . $callback[1] : $callback) . "</li>";
}
echo "</ul>";

// Check for PHP errors
if (function_exists('error_get_last')) {
    $error = error_get_last();
    if ($error) {
        echo "<h2>Last PHP Error:</h2>";
        echo "<p style='color: red;'>" . $error['message'] . " in " . $error['file'] . " on line " . $error['line'] . "</p>";
    }
}
?>
