<?php
/**
 * Uninstall ExoClass Calendar Plugin
 * 
 * This file is executed when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('exoclass_calendar_options');

// Clean up any other plugin data if needed
// For example, if you created custom post types or database tables

// Note: We don't delete the upload directory as it might contain user data
// that they might want to keep 