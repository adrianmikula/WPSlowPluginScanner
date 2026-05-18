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

// Hook into scan URL filter to provide custom URLs from premium module
add_filter( 'codemedsss_scan_url', 'codemedsss_premium_scan_url', 10, 1 );

function codemedsss_premium_scan_url( $default_url ) {
    // Check if a custom URL was posted via AJAX
    if ( isset( $_POST['url'] ) && ! empty( $_POST['url'] ) ) {
        return esc_url_raw( wp_unslash( $_POST['url'] ) );
    }
    return $default_url;
}

// Load premium JavaScript for page selection
add_action( 'admin_enqueue_scripts', 'codemedsss_premium_admin_assets' );

function codemedsss_premium_admin_assets( $hook ) {
    if ( 'plugins_page_codemedsss-scan-plugins' !== $hook ) {
        return;
    }

    wp_enqueue_script(
        'codemedsss-premium-script',
        plugins_url( 'premium/js/admin-premium.js', dirname( __FILE__ ) . '/../code-medic-slow-site-scanner.php' ),
        array( 'jquery' ),
        '0.1.0',
        true
    );
}

// Add other premium features here as needed
// Examples: advanced reporting, export capabilities, etc.
