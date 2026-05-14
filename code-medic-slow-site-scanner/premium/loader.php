<?php
/**
 * Premium Module Loader
 * 
 * This file loads premium-specific features when present.
 * Premium features are ADDITIONAL functionality, not locked free features.
 * The free version works 100% without this folder present.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load premium telemetry module
if ( file_exists( dirname( __FILE__ ) . '/telemetry.php' ) ) {
    require_once dirname( __FILE__ ) . '/telemetry.php';
}

// Load premium UI with page selection dropdown
if ( file_exists( dirname( __FILE__ ) . '/../admin/ui-premium.php' ) ) {
    require_once dirname( __FILE__ ) . '/../admin/ui-premium.php';
    
    // Override admin menu to use premium UI
    remove_action( 'admin_menu', 'codemedsss_admin_menu' );
    add_action( 'admin_menu', 'codemedsss_admin_menu' );
}

// Add other premium features here as needed
// Examples: advanced reporting, export capabilities, etc.
