# VTC Inventory Plugin Installation Guide

This guide will walk you through the complete installation and setup process for the VTC Inventory Management WordPress Plugin.

## Prerequisites

### WordPress Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- At least 50MB free disk space
- Memory limit: 256MB or higher

### Server Requirements
- Apache or Nginx web server
- mod_rewrite enabled
- GD extension for image processing
- cURL extension for external API calls

## Installation Methods

### Method 1: FTP Upload (Recommended)

1. **Download the Plugin**
   - Download the complete `vtc-inventory` folder
   - Ensure all files and subdirectories are included

2. **Upload to WordPress**
   - Connect to your WordPress site via FTP/SFTP
   - Navigate to `/wp-content/plugins/` directory
   - Upload the entire `vtc-inventory` folder
   - Set proper file permissions (755 for directories, 644 for files)

3. **Activate the Plugin**
   - Log in to WordPress admin dashboard
   - Go to Plugins > Installed Plugins
   - Find "VTC Inventory Management"
   - Click "Activate"

### Method 2: WordPress Admin Upload

1. **Prepare Plugin File**
   - Create a ZIP file of the `vtc-inventory` folder
   - Name it `vtc-inventory.zip`

2. **Upload via Admin**
   - Go to Plugins > Add New
   - Click "Upload Plugin"
   - Choose the ZIP file
   - Click "Install Now"
   - Click "Activate Plugin"

## Initial Configuration

### Step 1: Verify Installation
After activation, you should see:
- New "VTC Inventory" menu in WordPress admin
- New "Inventory" submenu under Posts
- New taxonomies: "Owners", "Conditions", "Locations"

### Step 2: Set File Permissions
Ensure proper permissions are set:
```bash
# Set directory permissions
chmod 755 /wp-content/plugins/vtc-inventory/
chmod 755 /wp-content/plugins/vtc-inventory/data/
chmod 755 /wp-content/plugins/vtc-inventory/data/uploads/

# Set file permissions
find /wp-content/plugins/vtc-inventory/ -type f -exec chmod 644 {} \;
```

### Step 3: Configure Settings
1. Go to **VTC Inventory > Settings**
2. Configure the following:
   - **QR Code Base URL**: URL for QR code links (default: https://vtcwoerden.nl/materiaal/?object=)
   - **Auto-generate QR Codes**: Enable automatic QR code generation
   - **Public Access**: Allow public viewing (enabled by default)

### Step 4: Access Control
**No additional setup required!** The system works as follows:
- **Public Visitors**: Can view inventory without any login
- **Logged-in Users**: Can manage inventory (add, edit, delete items)
- **All WordPress Roles**: Subscriber and above automatically get management access

## Data Migration (If Applicable)

If you're migrating from the original JSON-based system:

### Step 1: Prepare Data Files
1. Place your original `data.json` file in:
   ```
   /wp-content/plugins/vtc-inventory/data/data.json
   ```
2. Copy all photos from the original system to:
   ```
   /wp-content/plugins/vtc-inventory/data/uploads/
   ```

### Step 2: Run Migration
1. Go to **Tools > VTC Inventory Migration**
2. Review the migration status
3. Click **"Start Migration"**
4. Wait for completion
5. Verify migrated data

### Step 3: Verify Migration
1. Check that all items appear in **VTC Inventory > All Items**
2. Verify photos are visible
3. Test QR code generation
4. Confirm all data is correct

## Frontend Setup

### Step 1: Create Inventory Page
1. Go to **Pages > Add New**
2. Create a new page (e.g., "Inventory")
3. Add the shortcode: `[vtc_inventory]`
4. Publish the page

### Step 2: Configure Shortcode Options
You can customize the display with shortcode parameters:
```
[vtc_inventory show_search="true" show_filters="true" show_stats="true" items_per_page="20"]
```

### Step 3: Test Frontend
1. Visit the inventory page
2. Test search and filtering
3. Try adding a new item (if logged in)
4. Test QR code generation
5. Verify PDF export functionality

## Advanced Configuration

### Custom Taxonomies
The plugin creates three taxonomies. You can add terms through:
- **VTC Inventory > Owners**
- **VTC Inventory > Conditions** 
- **VTC Inventory > Locations**

### Custom Post Type Settings
The inventory items are stored as a custom post type. You can modify:
- Permalinks in **Settings > Permalinks**
- Display options in the post type settings
- REST API endpoints

### Database Optimization
For large inventories, consider:
1. **Database Cleanup**: Remove old revisions
2. **Image Optimization**: Compress uploaded photos
3. **Caching**: Enable WordPress caching plugins
4. **CDN**: Use a CDN for faster image loading

## Security Configuration

### Step 1: Secure Upload Directory
Add `.htaccess` to the uploads directory:
```apache
# Deny direct access to PHP files
<Files "*.php">
    Order Deny,Allow
    Deny from all
</Files>
```

### Step 2: Configure User Access
1. **Limit Admin Access**: Only trusted users should have admin access
2. **Regular PIN Changes**: Change the PIN code periodically
3. **Monitor Failed Logins**: Check Tools > VTC Inventory Migration for failed attempts

### Step 3: Backup Strategy
1. **Database Backups**: Regular WordPress database backups
2. **File Backups**: Include the plugin directory in backups
3. **Media Backups**: Ensure photos are included in backups

## Troubleshooting

### Common Installation Issues

#### Plugin Not Activating
- Check PHP version compatibility
- Verify file permissions
- Check for plugin conflicts
- Review WordPress error logs

#### Missing Menu Items
- Clear any caching plugins
- Check user capabilities
- Verify plugin activation
- Refresh admin dashboard

#### File Upload Issues
- Check upload directory permissions
- Verify PHP upload limits
- Check WordPress media settings
- Review server error logs

#### QR Code Problems
- Verify internet connectivity
- Check JavaScript console for errors
- Ensure QR code libraries are loading
- Test with different browsers

### Debug Mode
Enable WordPress debug mode for detailed error information:
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Performance Issues
If the plugin is slow:
1. **Optimize Images**: Compress uploaded photos
2. **Enable Caching**: Use WordPress caching plugins
3. **Database Cleanup**: Remove unnecessary data
4. **Server Resources**: Check server performance

## Post-Installation Checklist

- [ ] Plugin activated successfully
- [ ] File permissions set correctly
- [ ] Settings configured
- [ ] User roles assigned
- [ ] Data migrated (if applicable)
- [ ] Frontend page created
- [ ] Search and filtering working
- [ ] QR codes generating
- [ ] PDF export functional
- [ ] Admin dashboard accessible
- [ ] Security measures in place
- [ ] Backup strategy implemented

## Support and Maintenance

### Regular Maintenance
- **Weekly**: Check for failed login attempts
- **Monthly**: Review inventory data accuracy
- **Quarterly**: Update PIN codes and user access
- **Annually**: Full system backup and review

### Updates
- Keep WordPress core updated
- Update the plugin when new versions are released
- Test updates on staging environment first
- Backup before any updates

### Monitoring
- Monitor plugin performance
- Check error logs regularly
- Review user access patterns
- Monitor storage usage

## Getting Help

If you encounter issues:
1. Check this installation guide
2. Review the main README.md file
3. Check WordPress error logs
4. Contact technical support

---

**Note**: This plugin is specifically designed for VTC Woerden's inventory management needs. Custom modifications may be required for other organizations.
