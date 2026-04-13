<?php
/**
 * Plugin Name: Slow Plugin Scanner
 * Plugin URI:  https://github.com/adrianmikula/WPSlowPluginScanner
 * Description: Find which WordPress plugin is slowing down your site. Test plugin performance safely, detect conflicts, and identify speed bottlenecks in seconds.
 * Version:     0.1.0
 * Author:      WP Impact Analyzer
 * Author URI:  https://github.com/adrianmikula
 * License:     GPLv2 or later
 * Text Domain: slow-plugin-scanner
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

$config_file = PIA_PLUGIN_DIR . 'config.php';
if ( file_exists( $config_file ) ) {
    require_once $config_file;
}

$env_file = PIA_PLUGIN_DIR . '.env';
if ( file_exists( $env_file ) ) {
    $env_vars = parse_ini_file( $env_file );
    if ( $env_vars ) {
        $mode = isset( $env_vars['PIA_MODE'] ) ? strtolower( trim( $env_vars['PIA_MODE'] ) ) : 'free';
        if ( ! defined( 'PIA_MODE' ) ) {
            define( 'PIA_MODE', $mode );
        }

        $free_limit = isset( $env_vars['PIA_FREE_PLUGIN_LIMIT'] ) ? (int) $env_vars['PIA_FREE_PLUGIN_LIMIT'] : 3;
        if ( ! defined( 'PIA_FREE_PLUGIN_LIMIT' ) ) {
            define( 'PIA_FREE_PLUGIN_LIMIT', $free_limit );
        }

        $premium_url = isset( $env_vars['PIA_PREMIUM_URL'] ) ? trim( $env_vars['PIA_PREMIUM_URL'] ) : '';
        if ( ! defined( 'PIA_PREMIUM_URL' ) ) {
            define( 'PIA_PREMIUM_URL', $premium_url );
        }
    } else {
        if ( ! defined( 'PIA_MODE' ) ) {
            define( 'PIA_MODE', 'free' );
        }
        if ( ! defined( 'PIA_FREE_PLUGIN_LIMIT' ) ) {
            define( 'PIA_FREE_PLUGIN_LIMIT', 3 );
        }
        if ( ! defined( 'PIA_PREMIUM_URL' ) ) {
            define( 'PIA_PREMIUM_URL', '' );
        }
    }
} else {
    if ( ! defined( 'PIA_MODE' ) ) {
        define( 'PIA_MODE', 'free' );
    }
    if ( ! defined( 'PIA_FREE_PLUGIN_LIMIT' ) ) {
        define( 'PIA_FREE_PLUGIN_LIMIT', 3 );
    }
    if ( ! defined( 'PIA_PREMIUM_URL' ) ) {
        define( 'PIA_PREMIUM_URL', '' );
    }
}

$env_file = PIA_PLUGIN_DIR . '.env';
if ( file_exists( $env_file ) ) {
    $env_vars = parse_ini_file( $env_file );
    if ( $env_vars ) {
        $supabase_url = isset( $env_vars['PIA_SUPABASE_URL'] ) ? trim( $env_vars['PIA_SUPABASE_URL'] ) : '';
        if ( ! defined( 'PIA_SUPABASE_URL' ) ) {
            define( 'PIA_SUPABASE_URL', $supabase_url );
        }

        $supabase_key = isset( $env_vars['PIA_SUPABASE_ANON_KEY'] ) ? trim( $env_vars['PIA_SUPABASE_ANON_KEY'] ) : '';
        if ( ! defined( 'PIA_SUPABASE_ANON_KEY' ) ) {
            define( 'PIA_SUPABASE_ANON_KEY', $supabase_key );
        }

        $supabase_table = isset( $env_vars['PIA_SUPABASE_TABLE'] ) ? trim( $env_vars['PIA_SUPABASE_TABLE'] ) : 'telemetry';
        if ( ! defined( 'PIA_SUPABASE_TABLE' ) ) {
            define( 'PIA_SUPABASE_TABLE', $supabase_table );
        }
    } else {
        if ( ! defined( 'PIA_SUPABASE_URL' ) ) {
            define( 'PIA_SUPABASE_URL', '' );
        }
        if ( ! defined( 'PIA_SUPABASE_ANON_KEY' ) ) {
            define( 'PIA_SUPABASE_ANON_KEY', '' );
        }
        if ( ! defined( 'PIA_SUPABASE_TABLE' ) ) {
            define( 'PIA_SUPABASE_TABLE', 'telemetry' );
        }
    }
} else {
    if ( ! defined( 'PIA_SUPABASE_URL' ) ) {
        define( 'PIA_SUPABASE_URL', '' );
    }
    if ( ! defined( 'PIA_SUPABASE_ANON_KEY' ) ) {
        define( 'PIA_SUPABASE_ANON_KEY', '' );
    }
    if ( ! defined( 'PIA_SUPABASE_TABLE' ) ) {
        define( 'PIA_SUPABASE_TABLE', 'telemetry' );
    }
}

function pia_is_premium() {
    return defined( 'PIA_MODE' ) && PIA_MODE === 'premium';
}

function pia_get_free_limit() {
    return defined( 'PIA_FREE_PLUGIN_LIMIT' ) ? (int) PIA_FREE_PLUGIN_LIMIT : 3;
}

function pia_get_premium_url() {
    return defined( 'PIA_PREMIUM_URL' ) ? PIA_PREMIUM_URL : '';
}

require_once PIA_PLUGIN_DIR . 'includes/results.php';
require_once PIA_PLUGIN_DIR . 'includes/loopback.php';
require_once PIA_PLUGIN_DIR . 'includes/scanner.php';
require_once PIA_PLUGIN_DIR . 'includes/toggle.php';
require_once PIA_PLUGIN_DIR . 'includes/telemetry.php';
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
            'scanningText'   => __( 'Scanning...', 'slow-plugin-scanner' ),
            'completedText' => __( 'Scan completed successfully.', 'slow-plugin-scanner' ),
            'cancelledText'  => __( 'Scan cancelled.', 'slow-plugin-scanner' ),
            'errorText'     => __( 'An error occurred.', 'slow-plugin-scanner' ),
            // translators: %1$d: Current plugin number, %2$d: Total number of plugins.
            'pluginText'     => __( 'Scanning plugin %1$d of %2$d', 'slow-plugin-scanner' ),
            // translators: %s: Plugin name.
            'currentPlugin'  => __( 'Currently scanning: %s', 'slow-plugin-scanner' ),
            'resultsHeader'  => __( 'Scan Results', 'slow-plugin-scanner' ),
            'urlLabel'      => __( 'URL:', 'slow-plugin-scanner' ),
            'baselineStatus' => __( 'Baseline status:', 'slow-plugin-scanner' ),
            'baselineTime'  => __( 'Baseline time:', 'slow-plugin-scanner' ),
            'pluginCol'     => __( 'Plugin', 'slow-plugin-scanner' ),
            'impactCol'     => __( 'Impact', 'slow-plugin-scanner' ),
            'statusCol'     => __( 'Status', 'slow-plugin-scanner' ),
            'deltaCol'     => __( 'Delta', 'slow-plugin-scanner' ),
            'changeCol'     => __( 'Output Change', 'slow-plugin-scanner' ),
            'errorCol'      => __( 'Error', 'slow-plugin-scanner' ),
            'yesLabel'      => __( 'Yes', 'slow-plugin-scanner' ),
            'noLabel'      => __( 'No', 'slow-plugin-scanner' ),
            'truncatedText' => __( 'The plugin list was limited for speed. Only the first few active plugins were tested.', 'slow-plugin-scanner' ),
            'telemetryEnabled' => pia_is_telemetry_enabled(),
            'supabaseConfigured' => defined( 'PIA_SUPABASE_URL' ) && ! empty( PIA_SUPABASE_URL ),
        )
    );
}
