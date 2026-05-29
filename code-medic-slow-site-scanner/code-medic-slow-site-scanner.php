<?php
/**
 * Plugin Name: CodeMedic Slow Site Scanner
 * Plugin URI:  https://github.com/adrianmikula/WPSlowPluginScanner
 * Description: Find which WordPress plugin is slowing down your site. Test plugin performance safely, detect conflicts, and identify speed bottlenecks in seconds.
 * Version:     0.1.0
 * Author:      Adrian M
 * Author URI:  https://github.com/adrianmikula
 * License:     GPLv2 or later
 * Text Domain: code-medic-slow-site-scanner
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Plugin constants (CODESS_* prefix for backward compatibility)
define( 'CODESS_PLUGIN_FILE', __FILE__ );
define( 'CODESS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CODESS_PLUGIN_SLUG', basename( dirname( __FILE__ ) ) );
define( 'CODESS_TEMP_MU_PLUGIN', WPMU_PLUGIN_DIR . '/codemedsss-temp-disable.php' );
define( 'CODESS_SCAN_LOCK_KEY', 'codemedsss_scan_lock' );
define( 'CODESS_RESULTS_OPTION', 'codemedsss_last_scan' );
define( 'CODESS_PROGRESS_KEY', 'codemedsss_scan_progress' );
define( 'CODESS_CANCEL_KEY', 'codemedsss_scan_cancel' );
define( 'CODESS_SCANNER_ENGINE_VERSION', '0.1.0' );

// Load config if present (defines CODEMEDSSS_* constants)
$codemedsss_config_file = CODESS_PLUGIN_DIR . 'config.php';
if ( file_exists( $codemedsss_config_file ) ) {
    require_once $codemedsss_config_file;
}

// CODEMEDSSS_* constant aliases for test compatibility (if not already defined via config)
if ( ! defined( 'CODEMEDSSS_PLUGIN_FILE' ) ) {
    define( 'CODEMEDSSS_PLUGIN_FILE', CODESS_PLUGIN_FILE );
}
if ( ! defined( 'CODEMEDSSS_PLUGIN_DIR' ) ) {
    define( 'CODEMEDSSS_PLUGIN_DIR', CODESS_PLUGIN_DIR );
}
if ( ! defined( 'CODEMEDSSS_PLUGIN_SLUG' ) ) {
    define( 'CODEMEDSSS_PLUGIN_SLUG', CODESS_PLUGIN_SLUG );
}
if ( ! defined( 'CODEMEDSSS_TEMP_MU_PLUGIN' ) ) {
    define( 'CODEMEDSSS_TEMP_MU_PLUGIN', CODESS_TEMP_MU_PLUGIN );
}
if ( ! defined( 'CODEMEDSSS_SCAN_LOCK_KEY' ) ) {
    define( 'CODEMEDSSS_SCAN_LOCK_KEY', CODESS_SCAN_LOCK_KEY );
}
if ( ! defined( 'CODEMEDSSS_RESULTS_OPTION' ) ) {
    define( 'CODEMEDSSS_RESULTS_OPTION', CODESS_RESULTS_OPTION );
}
if ( ! defined( 'CODEMEDSSS_PROGRESS_KEY' ) ) {
    define( 'CODEMEDSSS_PROGRESS_KEY', CODESS_PROGRESS_KEY );
}
if ( ! defined( 'CODEMEDSSS_CANCEL_KEY' ) ) {
    define( 'CODEMEDSSS_CANCEL_KEY', CODESS_CANCEL_KEY );
}
if ( ! defined( 'CODEMEDSSS_SCANNER_ENGINE_VERSION' ) ) {
    define( 'CODEMEDSSS_SCANNER_ENGINE_VERSION', CODESS_SCANNER_ENGINE_VERSION );
}

// Include core files
require_once CODESS_PLUGIN_DIR . 'includes/results.php';
require_once CODESS_PLUGIN_DIR . 'includes/loopback.php';
require_once CODESS_PLUGIN_DIR . 'includes/scanner.php';
require_once CODESS_PLUGIN_DIR . 'includes/toggle.php';
require_once CODESS_PLUGIN_DIR . 'admin/ui.php';

// Hook into admin
add_action( 'admin_menu', 'codemedsss_admin_menu' );
add_action( 'admin_enqueue_scripts', 'codemedsss_admin_assets' );
add_action( 'admin_init', 'codemedsss_clear_temp_mu_plugin' );

function codemedsss_admin_assets( $hook ) {
    if ( 'plugins_page_codemedsss-scan-plugins' !== $hook ) {
        return;
    }

    wp_enqueue_style( 'codemedsss-admin-style', plugins_url( 'admin/css/admin.css', __FILE__ ), array(), '0.1.0' );
    wp_enqueue_script( 'codemedsss-admin-script', plugins_url( 'admin/js/admin.js', __FILE__ ), array( 'jquery' ), '0.1.0', true );

    $is_scanning = codemedsss_scan_is_locked();
    $progress = $is_scanning ? codemedsss_get_scan_progress() : null;

    wp_localize_script(
        'codemedsss-admin-script',
        'codemedsssData',
        array(
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'codemedsss_scan_nonce' ),
            'isScanning'     => $is_scanning,
            'totalPlugins'   => $progress ? count( $progress['plugin_files'] ) : 0,
            'scannedCount'   => $progress ? $progress['scanned'] : 0,
            'scanningText'   => __( 'Scanning...', 'code-medic-slow-site-scanner' ),
            'completedText'  => __( 'Scan completed successfully.', 'code-medic-slow-site-scanner' ),
            'cancelledText'  => __( 'Scan cancelled.', 'code-medic-slow-site-scanner' ),
            'errorText'      => __( 'An error occurred.', 'code-medic-slow-site-scanner' ),
            /* translators: 1: current plugin number, 2: total plugin count */
            'pluginText'     => __( 'Scanning plugin %1$d of %2$d', 'code-medic-slow-site-scanner' ),
            /* translators: %s: plugin name */
            'currentPlugin'  => __( 'Currently scanning: %s', 'code-medic-slow-site-scanner' ),
            'resultsHeader'  => __( 'Scan Results', 'code-medic-slow-site-scanner' ),
            'urlLabel'       => __( 'URL:', 'code-medic-slow-site-scanner' ),
            'baselineStatus' => __( 'Baseline status:', 'code-medic-slow-site-scanner' ),
            'baselineTime'   => __( 'Baseline time:', 'code-medic-slow-site-scanner' ),
            'pluginCol'      => __( 'Plugin', 'code-medic-slow-site-scanner' ),
            'impactCol'      => __( 'Impact', 'code-medic-slow-site-scanner' ),
            'statusCol'      => __( 'Status', 'code-medic-slow-site-scanner' ),
            'deltaCol'       => __( 'Delta', 'code-medic-slow-site-scanner' ),
            'changeCol'      => __( 'Output Change', 'code-medic-slow-site-scanner' ),
            'errorCol'       => __( 'Error', 'code-medic-slow-site-scanner' ),
            'yesLabel'       => __( 'Yes', 'code-medic-slow-site-scanner' ),
            'noLabel'        => __( 'No', 'code-medic-slow-site-scanner' ),
        )
    );
}
