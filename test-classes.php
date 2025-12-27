<?php
/**
 * Test file to check if VTC Inventory classes are loading
 * 
 * Upload this to your WordPress root and visit it to check class loading
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>VTC Inventory Class Loading Test</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .box{border:1px solid #ccc;padding:15px;margin:10px 0;background:#f9f9f9;}</style>";

// Test each class file
$classes = array(
    'VTC_Inventory_Post_Types' => 'includes/class-post-types.php',
    'VTC_Inventory_User_Roles' => 'includes/class-user-roles.php',
    'VTC_Inventory_Manager' => 'includes/class-inventory-manager.php',
    'VTC_Data_Migration' => 'includes/class-data-migration.php',
    'VTC_Inventory_Admin_Pages' => 'includes/class-admin-pages.php',
    'VTC_Inventory_PDF_Generator' => 'includes/class-pdf-generator.php'
);

echo "<div class='box'>";
echo "<h2>Class Loading Test</h2>";

foreach ($classes as $class_name => $file_path) {
    $full_path = WP_PLUGIN_DIR . '/vtc-inventory/' . $file_path;
    
    echo "<h3>$class_name</h3>";
    
    // Check if file exists
    if (file_exists($full_path)) {
        echo "<p class='success'>✅ File exists: $file_path</p>";
        
        // Try to include the file
        try {
            include_once $full_path;
            echo "<p class='success'>✅ File included successfully</p>";
            
            // Check if class exists
            if (class_exists($class_name)) {
                echo "<p class='success'>✅ Class $class_name exists</p>";
            } else {
                echo "<p class='error'>❌ Class $class_name does NOT exist after including file</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error including file: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='error'>❌ File NOT found: $full_path</p>";
    }
    echo "<hr>";
}

echo "</div>";

// Test plugin initialization
echo "<div class='box'>";
echo "<h2>Plugin Initialization Test</h2>";

// Check if the init function exists
if (function_exists('vtc_inventory_init')) {
    echo "<p class='success'>✅ vtc_inventory_init function exists</p>";
    
    // Try to call it
    try {
        vtc_inventory_init();
        echo "<p class='success'>✅ vtc_inventory_init executed successfully</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error calling vtc_inventory_init: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='error'>❌ vtc_inventory_init function does NOT exist</p>";
}

echo "</div>";

// Test shortcode registration after init
echo "<div class='box'>";
echo "<h2>Shortcode Registration Test</h2>";

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

// Test custom post type
echo "<div class='box'>";
echo "<h2>Custom Post Type Test</h2>";

if (post_type_exists('vtc_inventory_item')) {
    echo "<p class='success'>✅ Custom post type 'vtc_inventory_item' exists</p>";
} else {
    echo "<p class='error'>❌ Custom post type 'vtc_inventory_item' does NOT exist</p>";
}

echo "</div>";

// Show all shortcodes
echo "<div class='box'>";
echo "<h2>All Registered Shortcodes</h2>";
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
?>
