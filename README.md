# VTC Inventory Management WordPress Plugin

A comprehensive inventory management system for VTC Woerden, built as a WordPress plugin using native WordPress features.

## Features

### Core Functionality
- **Material Tracking**: Complete inventory management with detailed item information
- **QR Code Integration**: Auto-generated QR codes for each item linking to external reporting system
- **Photo Management**: Upload and manage multiple photos per item using WordPress media library
- **Search & Filtering**: Advanced search and filtering by location, owner, condition, and text
- **User Management**: Role-based access control with PIN-based quick login
- **PDF Generation**: Generate printable PDFs with QR codes in multiple layouts
- **Data Export**: CSV export functionality for reporting and backup

### WordPress Integration
- **Custom Post Types**: Items stored as custom post types for full WordPress integration
- **Taxonomies**: Organized by owners, conditions, and locations using WordPress taxonomies
- **Media Library**: Photos stored in WordPress media library
- **User Roles**: Custom roles and capabilities for inventory management
- **REST API**: Full REST API support for mobile/frontend access
- **Admin Interface**: Comprehensive admin dashboard with statistics and reports

## Installation

### Method 1: Manual Installation
1. Download the plugin files
2. Upload the `vtc-inventory` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Tools > VTC Inventory Migration to migrate existing data (if applicable)

### Method 2: FTP Upload
1. Upload the entire `vtc-inventory` folder to your WordPress `/wp-content/plugins/` directory
2. Activate the plugin through the WordPress admin panel
3. Configure settings in VTC Inventory > Settings

## Configuration

### Initial Setup
1. **Activate Plugin**: Go to Plugins and activate "VTC Inventory Management"
2. **Configure Settings**: Go to VTC Inventory > Settings to configure:
   - QR code base URL
   - Auto-generation settings
   - Public access permissions

### Access Control
- **Public Visitors**: Can view inventory without login
- **Logged-in Users**: Can manage inventory (add, edit, delete items)
- **All WordPress Roles**: Subscriber and above can manage inventory

### Data Migration
If migrating from the original JSON-based system:
1. Place the original `data.json` file in the plugin's `data/` folder
2. Go to Tools > VTC Inventory Migration
3. Click "Start Migration" to convert data to WordPress format
4. Verify the migration was successful

## Usage

### Frontend Display
Add the inventory to any page or post using the shortcode:
```
[vtc_inventory]
```

Shortcode parameters:
- `show_search="true/false"` - Show/hide search functionality
- `show_filters="true/false"` - Show/hide filter options
- `show_stats="true/false"` - Show/hide statistics cards
- `items_per_page="number"` - Number of items per page (-1 for all)
- `layout="grid"` - Display layout

### Managing Items

#### Adding New Items
1. Log in with PIN code or WordPress credentials
2. Click "Nieuw Materiaal" button
3. Fill in item details:
   - Name (required)
   - Quantity (required)
   - Owner (Gemeente or VTC Woerden)
   - Condition (Zeer goed, Goed, Redelijk, Slecht, Zeer slecht)
   - Location (select from existing or add new)
   - Description and comments
   - Photos (multiple files supported)
4. Click "Opslaan"

#### Editing Items
1. Click the "Bewerken" button on any item card
2. Modify the information as needed
3. Click "Bijwerken" to save changes

#### Deleting Items
1. Click "Bewerken" on the item
2. Click "Verwijderen" button
3. Confirm deletion

### Search and Filtering
- **Text Search**: Search by item name, description, or comments
- **Location Filter**: Filter by storage location
- **Owner Filter**: Filter by Gemeente or VTC Woerden
- **Condition Filter**: Filter by item condition
- **Clear Filters**: Reset all filters to show all items

### PDF Generation
1. Select items using checkboxes
2. Choose layout:
   - **Standard Grid**: 6x6 layout with small QR codes
   - **Large QR Codes**: 2x3 layout with larger QR codes
3. Click "PDF genereren" to generate and print

### Reports and Export
- **CSV Export**: Export all data or filtered results
- **PDF Reports**: Generate comprehensive reports
- **Statistics**: View dashboard with key metrics
- **Admin Tools**: Various maintenance and management tools

## File Structure

```
vtc-inventory/
├── vtc-inventory.php              # Main plugin file
├── includes/                      # PHP classes
│   ├── class-post-types.php      # Custom post types and taxonomies
│   ├── class-user-roles.php      # User roles and capabilities
│   ├── class-inventory-manager.php # Main functionality
│   ├── class-data-migration.php  # Data migration from JSON
│   ├── class-admin-pages.php     # Admin interface
│   └── class-pdf-generator.php   # PDF generation
├── templates/                     # HTML templates
│   └── inventory-page.php        # Frontend display template
├── assets/                        # CSS and JavaScript
│   ├── css/
│   │   ├── inventory.css         # Frontend styles
│   │   └── admin.css             # Admin styles
│   └── js/
│       ├── inventory.js          # Frontend JavaScript
│       └── admin.js              # Admin JavaScript
├── data/                          # Data storage
│   └── uploads/                   # Photo uploads
└── README.md                      # This file
```

## API Endpoints

### REST API
The plugin provides REST API endpoints for all inventory operations:

- `GET /wp-json/wp/v2/vtc_inventory_item` - List all items
- `GET /wp-json/wp/v2/vtc_inventory_item/{id}` - Get specific item
- `POST /wp-json/wp/v2/vtc_inventory_item` - Create new item
- `PUT /wp-json/wp/v2/vtc_inventory_item/{id}` - Update item
- `DELETE /wp-json/wp/v2/vtc_inventory_item/{id}` - Delete item

### AJAX Endpoints
- `vtc_inventory_action` - Main inventory operations
- `vtc_pin_login` - PIN-based authentication
- `vtc_generate_pdf` - PDF generation
- `vtc_export_inventory` - Data export

## Customization

### Styling
- Frontend styles: `assets/css/inventory.css`
- Admin styles: `assets/css/admin.css`
- Custom CSS can be added through WordPress Customizer

### Hooks and Filters
The plugin provides various hooks for customization:

```php
// Modify inventory query
add_filter('vtc_inventory_query_args', function($args) {
    // Customize query arguments
    return $args;
});

// Modify item data before display
add_filter('vtc_inventory_item_data', function($data, $item) {
    // Customize item data
    return $data;
}, 10, 2);
```

### Custom Fields
Additional custom fields can be added by extending the meta box:

```php
add_action('add_meta_boxes', function() {
    // Add custom meta box
});
```

## Security

### Authentication
- PIN-based quick access for inventory management
- WordPress user roles for full access control
- Failed login attempt tracking and blocking
- Nonce verification for all forms and AJAX requests

### Data Protection
- Input sanitization and output escaping
- Capability checks for all operations
- Secure file upload handling
- SQL injection prevention through WordPress APIs

## Troubleshooting

### Common Issues

#### QR Codes Not Generating
- Check if QR code libraries are loading properly
- Verify internet connection for fallback Google Charts API
- Check browser console for JavaScript errors

#### Photos Not Uploading
- Verify WordPress media library permissions
- Check file size limits in WordPress settings
- Ensure proper file types (images only)

#### Migration Issues
- Verify JSON file format and location
- Check file permissions on data directory
- Review error messages in migration tool

#### Permission Errors
- Ensure users have proper roles assigned
- Check WordPress capability settings
- Verify plugin activation

### Debug Mode
Enable WordPress debug mode to see detailed error messages:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support

### Documentation
- Plugin documentation: Available in WordPress admin under VTC Inventory
- Code comments: Extensive inline documentation
- API documentation: REST API endpoints documented

### Maintenance
- Regular WordPress updates recommended
- Plugin updates for new features and security fixes
- Database optimization through WordPress tools

## Changelog

### Version 1.0.0
- Initial release
- Complete inventory management system
- WordPress native integration
- QR code generation
- PDF export functionality
- Data migration from JSON system
- Admin dashboard and reporting
- User role management
- REST API support

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed for VTC Woerden - A sports club material inventory management system.

---

For technical support or feature requests, please contact the development team.
