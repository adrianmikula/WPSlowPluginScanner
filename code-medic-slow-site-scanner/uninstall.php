<?php
/**
 * Uninstall routine for CodeMedic Slow Site Scanner
 *
 * This file is called automatically by WordPress when the plugin is deleted.
 * It cleans up all plugin data including temporary files, options, and transients.
 *
 * @package CodeMedic_Slow_Site_Scanner
 */

// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Define plugin constants for cleanup
define( 'CODESS_PLUGIN_FILE', __FILE__ );
define( 'CODESS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CODESS_TEMP_MU_PLUGIN', WPMU_PLUGIN_DIR . '/codemedsss-temp-disable.php' );

// Option keys
define( 'CODESS_RESULTS_OPTION', 'codemedsss_last_scan' );

// Transient keys
define( 'CODESS_SCAN_LOCK_KEY', 'codemedsss_scan_lock' );
define( 'CODESS_PROGRESS_KEY', 'codemedsss_scan_progress' );
define( 'CODESS_CANCEL_KEY', 'codemedsss_scan_cancel' );

/**
 * Clean up temporary mu-plugin file
 */
function codemedsss_uninstall_cleanup_mu_plugin() {
    if ( file_exists( CODESS_TEMP_MU_PLUGIN ) ) {
        wp_delete_file( CODESS_TEMP_MU_PLUGIN );
    }
}

/**
 * Clean up plugin options
 */
function codemedsss_uninstall_cleanup_options() {
    delete_option( CODESS_RESULTS_OPTION );
    delete_option( 'codemedsss_mu_consent' );
    delete_option( 'codemedsss_telemetry_enabled' );
    delete_option( 'codemedsss_site_uuid' );
    delete_option( 'codemedsss_telemetry_queue' );
}

/**
 * Clean up plugin transients
 */
function codemedsss_uninstall_cleanup_transients() {
    delete_transient( CODESS_SCAN_LOCK_KEY );
    delete_transient( CODESS_PROGRESS_KEY );
    delete_transient( CODESS_CANCEL_KEY );
}

// Execute cleanup
codemedsss_uninstall_cleanup_mu_plugin();
codemedsss_uninstall_cleanup_options();
codemedsss_uninstall_cleanup_transients();
