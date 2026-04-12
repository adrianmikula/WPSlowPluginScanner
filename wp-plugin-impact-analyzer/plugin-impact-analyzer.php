<?php
/**
 * Plugin Name: WP Slow Plugin Scanner
 * Plugin URI:  https://example.com/wp-slow-plugin-scanner
 * Description: Detects the single plugin causing a slowdown or breakage on a specific page using safe loopback tests.
 * Version:     0.1.0
 * Author:      WP Impact Analyzer
 * Author URI:  https://example.com/wp-impact-analyzer
 * License:     GPLv2 or later
 * Text Domain: wp-slow-plugin-scanner
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PIA_PLUGIN_FILE', __FILE__ );
define( 'PIA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PIA_PLUGIN_SLUG', basename( dirname( __FILE__ ) ) );
define( 'PIA_TEMP_MU_PLUGIN', WP_CONTENT_DIR . '/mu-plugins/pia-temp-disable.php' );
define( 'PIA_SCAN_LOCK_KEY', 'pia_scan_lock' );
define( 'PIA_RESULTS_OPTION', 'pia_last_scan' );
define( 'PIA_MAX_TEST_PLUGINS', 6 );
define( 'PIA_PROGRESS_KEY', 'pia_scan_progress' );
define( 'PIA_CANCEL_KEY', 'pia_scan_cancel' );

require_once PIA_PLUGIN_DIR . 'includes/results.php';
require_once PIA_PLUGIN_DIR . 'includes/loopback.php';
require_once PIA_PLUGIN_DIR . 'includes/scanner.php';
require_once PIA_PLUGIN_DIR . 'includes/toggle.php';
require_once PIA_PLUGIN_DIR . 'admin/ui.php';

add_action( 'admin_menu', 'pia_admin_menu' );
add_action( 'admin_enqueue_scripts', 'pia_admin_assets' );
add_action( 'admin_init', 'pia_clear_temp_mu_plugin' );

function pia_admin_assets( $hook ) {
    if ( 'plugins_page_pia-scan-plugins' !== $hook ) {
        return;
    }

    wp_enqueue_style( 'pia-admin-style', plugins_url( 'admin/css/admin.css', __FILE__ ), array(), '0.1.0' );
    wp_enqueue_script( 'pia-admin-script', plugins_url( 'admin/js/admin.js', __FILE__ ), array( 'jquery' ), '0.1.0', true );

    $is_scanning = pia_scan_is_locked();
    $progress = $is_scanning ? pia_get_scan_progress() : null;

    wp_localize_script(
        'pia-admin-script',
        'piaData',
        array(
            'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'pia_scan_nonce' ),
            'homeUrl'        => home_url(),
            'isScanning'     => $is_scanning,
            'totalPlugins'   => $progress ? count( $progress['plugin_files'] ) : 0,
            'scannedCount'   => $progress ? $progress['scanned'] : 0,
            'scanningText'   => __( 'Scanning...', 'wp-slow-plugin-scanner' ),
            'completedText' => __( 'Scan completed successfully.', 'wp-slow-plugin-scanner' ),
            'cancelledText'  => __( 'Scan cancelled.', 'wp-slow-plugin-scanner' ),
            'errorText'     => __( 'An error occurred.', 'wp-slow-plugin-scanner' ),
            'pluginText'     => __( 'Scanning plugin %d of %d', 'wp-slow-plugin-scanner' ),
            'currentPlugin'  => __( 'Currently scanning: %s', 'wp-slow-plugin-scanner' ),
            'resultsHeader'  => __( 'Scan Results', 'wp-slow-plugin-scanner' ),
            'urlLabel'      => __( 'URL:', 'wp-slow-plugin-scanner' ),
            'baselineStatus' => __( 'Baseline status:', 'wp-slow-plugin-scanner' ),
            'baselineTime'  => __( 'Baseline time:', 'wp-slow-plugin-scanner' ),
            'pluginCol'     => __( 'Plugin', 'wp-slow-plugin-scanner' ),
            'impactCol'     => __( 'Impact', 'wp-slow-plugin-scanner' ),
            'statusCol'     => __( 'Status', 'wp-slow-plugin-scanner' ),
            'deltaCol'     => __( 'Delta', 'wp-slow-plugin-scanner' ),
            'changeCol'     => __( 'Output Change', 'wp-slow-plugin-scanner' ),
            'errorCol'      => __( 'Error', 'wp-slow-plugin-scanner' ),
            'yesLabel'      => __( 'Yes', 'wp-slow-plugin-scanner' ),
            'noLabel'      => __( 'No', 'wp-slow-plugin-scanner' ),
            'truncatedText' => __( 'The plugin list was limited for speed. Only the first few active plugins were tested.', 'wp-slow-plugin-scanner' ),
        )
    );
}
