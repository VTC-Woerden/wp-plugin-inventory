<?php
/**
 * User Roles and Capabilities Class
 * 
 * Handles custom user roles and capabilities for the VTC Inventory system
 */

if (!defined('ABSPATH')) {
    exit;
}

class VTC_Inventory_User_Roles {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add capabilities and roles immediately
        $this->add_custom_capabilities();
        $this->add_inventory_manager_role();
        $this->add_inventory_viewer_role();
    }
    
    /**
     * Add custom capabilities to existing roles
     */
    public function add_custom_capabilities() {
        // Add capabilities to administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_vtc_inventory');
            $admin_role->add_cap('read_vtc_inventory');
            $admin_role->add_cap('edit_vtc_inventory');
            $admin_role->add_cap('delete_vtc_inventory');
            $admin_role->add_cap('export_vtc_inventory');
        }
        
        // Add capabilities to editor
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('read_vtc_inventory');
            $editor_role->add_cap('edit_vtc_inventory');
        }
        
        // Add capabilities to author
        $author_role = get_role('author');
        if ($author_role) {
            $author_role->add_cap('read_vtc_inventory');
            $author_role->add_cap('edit_vtc_inventory');
        }
        
        // Add capabilities to contributor
        $contributor_role = get_role('contributor');
        if ($contributor_role) {
            $contributor_role->add_cap('read_vtc_inventory');
            $contributor_role->add_cap('edit_vtc_inventory');
        }
        
        // Add capabilities to subscriber
        $subscriber_role = get_role('subscriber');
        if ($subscriber_role) {
            $subscriber_role->add_cap('read_vtc_inventory');
            $subscriber_role->add_cap('edit_vtc_inventory');
        }
    }
    
    /**
     * Add VTC Inventory Manager role
     */
    public function add_inventory_manager_role() {
        $capabilities = array(
            'read' => true,
            'manage_vtc_inventory' => true,
            'read_vtc_inventory' => true,
            'edit_vtc_inventory' => true,
            'delete_vtc_inventory' => true,
            'export_vtc_inventory' => true,
            'upload_files' => true,
            'edit_posts' => true,
            'edit_published_posts' => true,
            'publish_posts' => true,
            'delete_posts' => true,
            'delete_published_posts' => true,
            'read_private_posts' => true,
            'edit_private_posts' => true,
            'delete_private_posts' => true,
        );
        
        add_role('vtc_inventory_manager', __('VTC Inventory Manager', 'vtc-inventory'), $capabilities);
    }
    
    /**
     * Add VTC Inventory Viewer role
     */
    public function add_inventory_viewer_role() {
        $capabilities = array(
            'read' => true,
            'read_vtc_inventory' => true,
        );
        
        add_role('vtc_inventory_viewer', __('VTC Inventory Viewer', 'vtc-inventory'), $capabilities);
    }
    
    
    /**
     * Check if user can manage inventory
     */
    public static function can_manage_inventory() {
        return is_user_logged_in() && current_user_can('edit_vtc_inventory');
    }
    
    /**
     * Check if user can view inventory
     */
    public static function can_view_inventory() {
        // All visitors can view inventory
        return true;
    }
    
    /**
     * Get user's inventory permissions
     */
    public static function get_user_permissions() {
        return array(
            'can_manage' => self::can_manage_inventory(),
            'can_view' => self::can_view_inventory(),
            'can_edit' => is_user_logged_in() && current_user_can('edit_vtc_inventory'),
            'can_delete' => is_user_logged_in() && current_user_can('delete_vtc_inventory'),
            'can_export' => is_user_logged_in() && current_user_can('export_vtc_inventory'),
            'is_logged_in' => is_user_logged_in(),
            'user_id' => get_current_user_id(),
            'user_roles' => is_user_logged_in() ? wp_get_current_user()->roles : array(),
        );
    }
}
