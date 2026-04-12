<?php
/**
 * Plugin Name: WP Slow Plugin Scanner
 * Plugin URI:  https://example.com/wp-slow-plugin-scanner
 * Description: Detects the single plugin causing a slowdown or breakage on a specific page using safe loopback tests.
 * Version:     0.1.0
 * Author:      WP Impact Analyzer
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

require_once PIA_PLUGIN_DIR . 'includes/results.php';
require_once PIA_PLUGIN_DIR . 'includes/loopback.php';
require_once PIA_PLUGIN_DIR . 'includes/scanner.php';
require_once PIA_PLUGIN_DIR . 'includes/toggle.php';
require_once PIA_PLUGIN_DIR . 'admin/ui.php';

add_action( 'admin_menu', 'pia_admin_menu' );
add_action( 'admin_post_pia_scan_plugins', 'pia_admin_handle_scan_request' );
add_action( 'admin_enqueue_scripts', 'pia_admin_assets' );
add_action( 'admin_init', 'pia_clear_temp_mu_plugin' );

function pia_admin_assets( $hook ) {
    if ( 'tools_page_pia-scan-plugins' !== $hook ) {
        return;
    }

    wp_enqueue_style( 'pia-admin-style', plugins_url( 'admin/css/admin.css', __FILE__ ), array(), '0.1.0' );
}
