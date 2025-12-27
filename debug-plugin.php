<?php
/**
 * VTC Inventory Plugin Debug File
 * 
 * Upload this file to your WordPress root directory and visit it in your browser
 * to debug the plugin installation and shortcode registration.
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>VTC Inventory Plugin Debug</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .box{border:1px solid #ccc;padding:15px;margin:10px 0;background:#f9f9f9;}</style>";

// 1. Check if WordPress is loaded
echo "<div class='box'>";
echo "<h2>1. WordPress Status</h2>";
if (function_exists('wp_get_current_user')) {
    echo "<p class='success'>✅ WordPress is loaded</p>";
} else {
    echo "<p class='error'>❌ WordPress is not loaded</p>";
    exit;
}
echo "</div>";

// 2. Check if plugin file exists
echo "<div class='box'>";
echo "<h2>2. Plugin File Check</h2>";
$plugin_file = WP_PLUGIN_DIR . '/vtc-inventory/vtc-inventory.php';
if (file_exists($plugin_file)) {
    echo "<p class='success'>✅ Plugin file exists: $plugin_file</p>";
} else {
    echo "<p class='error'>❌ Plugin file NOT found: $plugin_file</p>";
    echo "<p>Make sure you copied the vtc-inventory folder to /wp-content/plugins/</p>";
}
echo "</div>";

// 3. Check if plugin is active
echo "<div class='box'>";
echo "<h2>3. Plugin Activation Status</h2>";
if (is_plugin_active('vtc-inventory/vtc-inventory.php')) {
    echo "<p class='success'>✅ Plugin is active</p>";
} else {
    echo "<p class='error'>❌ Plugin is NOT active</p>";
    echo "<p>Go to WordPress Admin > Plugins and activate 'VTC Inventory Management'</p>";
}
echo "</div>";

// 4. Check if classes are loaded
echo "<div class='box'>";
echo "<h2>4. Class Loading Check</h2>";
if (class_exists('VTC_Inventory_Manager')) {
    echo "<p class='success'>✅ VTC_Inventory_Manager class exists</p>";
} else {
    echo "<p class='error'>❌ VTC_Inventory_Manager class NOT found</p>";
}

if (class_exists('VTC_Inventory_User_Roles')) {
    echo "<p class='success'>✅ VTC_Inventory_User_Roles class exists</p>";
} else {
    echo "<p class='error'>❌ VTC_Inventory_User_Roles class NOT found</p>";
}

if (class_exists('VTC_Inventory_Post_Types')) {
    echo "<p class='success'>✅ VTC_Inventory_Post_Types class exists</p>";
} else {
    echo "<p class='error'>❌ VTC_Inventory_Post_Types class NOT found</p>";
}
echo "</div>";

// 5. Check shortcode registration
echo "<div class='box'>";
echo "<h2>5. Shortcode Registration Check</h2>";
global $shortcode_tags;

if (isset($shortcode_tags['vtc_inventory'])) {
    echo "<p class='success'>✅ vtc_inventory shortcode is registered</p>";
} else {
    echo "<p class='error'>❌ vtc_inventory shortcode is NOT registered</p>";
}

if (isset($shortcode_tags['vtc_test'])) {
    echo "<p class='success'>✅ vtc_test shortcode is registered</p>";
} else {
    echo "<p class='error'>❌ vtc_test shortcode is NOT registered</p>";
}
echo "</div>";

// 6. Test shortcode execution
echo "<div class='box'>";
echo "<h2>6. Shortcode Execution Test</h2>";
echo "<h3>Test Shortcode [vtc_test]:</h3>";
echo do_shortcode('[vtc_test]');

echo "<h3>Main Shortcode [vtc_inventory]:</h3>";
$inventory_output = do_shortcode('[vtc_inventory]');
if ($inventory_output === '[vtc_inventory]') {
    echo "<p class='error'>❌ Shortcode is not being processed - shows as literal text</p>";
} else {
    echo "<p class='success'>✅ Shortcode is being processed</p>";
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
    echo $inventory_output;
    echo "</div>";
}
echo "</div>";

// 7. Check for PHP errors
echo "<div class='box'>";
echo "<h2>7. PHP Error Check</h2>";
$error = error_get_last();
if ($error) {
    echo "<p class='error'>❌ PHP Error found:</p>";
    echo "<p><strong>Message:</strong> " . $error['message'] . "</p>";
    echo "<p><strong>File:</strong> " . $error['file'] . "</p>";
    echo "<p><strong>Line:</strong> " . $error['line'] . "</p>";
} else {
    echo "<p class='success'>✅ No PHP errors detected</p>";
}
echo "</div>";

// 8. Check custom post type
echo "<div class='box'>";
echo "<h2>8. Custom Post Type Check</h2>";
if (post_type_exists('vtc_inventory_item')) {
    echo "<p class='success'>✅ Custom post type 'vtc_inventory_item' exists</p>";
} else {
    echo "<p class='error'>❌ Custom post type 'vtc_inventory_item' does NOT exist</p>";
}
echo "</div>";

// 9. List all shortcodes
echo "<div class='box'>";
echo "<h2>9. All Registered Shortcodes</h2>";
if (empty($shortcode_tags)) {
    echo "<p class='error'>❌ No shortcodes are registered!</p>";
} else {
    echo "<p class='info'>Found " . count($shortcode_tags) . " registered shortcodes:</p>";
    echo "<ul>";
    foreach ($shortcode_tags as $tag => $callback) {
        $callback_info = is_array($callback) ? get_class($callback[0]) . '::' . $callback[1] : $callback;
        echo "<li><strong>$tag</strong> - $callback_info</li>";
    }
    echo "</ul>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>If the plugin is not active, go to WordPress Admin > Plugins and activate it</li>";
echo "<li>If classes are not loaded, check for PHP errors in your error log</li>";
echo "<li>If shortcodes are not registered, the plugin initialization might be failing</li>";
echo "<li>Try deactivating and reactivating the plugin</li>";
echo "<li>Check your WordPress error log for any PHP errors</li>";
echo "</ol>";
echo "</div>";
?>
