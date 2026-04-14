<?php
/**
 * Plugin Name: What's Slowing My Site
 * Plugin URI:  https://github.com/adrianmikula/WPSlowPluginScanner
 * Description: Find which WordPress plugin is slowing down your site. Test plugin performance safely, detect conflicts, and identify speed bottlenecks in seconds.
 * Version:     0.1.0
 * Author:      Adrian M
 * Author URI:  https://github.com/adrianmikula
 * License:     GPLv2 or later
 * Text Domain: whats-slowing-my-site
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
    if ( pia_is_premium() ) {
        return PHP_INT_MAX;
    }
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
            'scanningText'   => __( 'Scanning...', 'whats-slowing-my-site' ),
            'completedText' => __( 'Scan completed successfully.', 'whats-slowing-my-site' ),
            'cancelledText'  => __( 'Scan cancelled.', 'whats-slowing-my-site' ),
            'errorText'     => __( 'An error occurred.', 'whats-slowing-my-site' ),
            // translators: %1$d: Current plugin number, %2$d: Total number of plugins.
            'pluginText'     => __( 'Scanning plugin %1$d of %2$d', 'whats-slowing-my-site' ),
            // translators: %s: Plugin name.
            'currentPlugin'  => __( 'Currently scanning: %s', 'whats-slowing-my-site' ),
            'resultsHeader'  => __( 'Scan Results', 'whats-slowing-my-site' ),
            'urlLabel'      => __( 'URL:', 'whats-slowing-my-site' ),
            'baselineStatus' => __( 'Baseline status:', 'whats-slowing-my-site' ),
            'baselineTime'  => __( 'Baseline time:', 'whats-slowing-my-site' ),
            'pluginCol'     => __( 'Plugin', 'whats-slowing-my-site' ),
            'impactCol'     => __( 'Impact', 'whats-slowing-my-site' ),
            'statusCol'     => __( 'Status', 'whats-slowing-my-site' ),
            'deltaCol'     => __( 'Delta', 'whats-slowing-my-site' ),
            'changeCol'     => __( 'Output Change', 'whats-slowing-my-site' ),
            'errorCol'      => __( 'Error', 'whats-slowing-my-site' ),
            'yesLabel'      => __( 'Yes', 'whats-slowing-my-site' ),
            'noLabel'      => __( 'No', 'whats-slowing-my-site' ),
            'truncatedText' => __( 'The plugin list was limited for speed. Only the first few active plugins were tested.', 'whats-slowing-my-site' ),
            'telemetryEnabled' => pia_is_telemetry_enabled(),
            'supabaseConfigured' => defined( 'PIA_SUPABASE_URL' ) && ! empty( PIA_SUPABASE_URL ),
        )
    );
}
