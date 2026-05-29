<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( file_exists( dirname( __FILE__ ) . '/telemetry.php' ) ) {
    require_once dirname( __FILE__ ) . '/telemetry.php';
}

add_action( 'codemedsss_plugin_scanned', 'codemedsss_premium_queue_telemetry', 10, 3 );

function codemedsss_premium_queue_telemetry( $plugin_result, $all_plugin_files, $baseline_time ) {
    if ( ! function_exists( 'codemedsss_is_telemetry_enabled' ) || ! codemedsss_is_telemetry_enabled() ) {
        return;
    }
    if ( ! function_exists( 'codemedsss_prepare_telemetry_data' ) || ! function_exists( 'codemedsss_add_to_telemetry_queue' ) ) {
        return;
    }
    $data = codemedsss_prepare_telemetry_data( $plugin_result, $all_plugin_files, $baseline_time );
    codemedsss_add_to_telemetry_queue( $data );
}

add_action( 'admin_enqueue_scripts', 'codemedsss_premium_admin_assets' );

function codemedsss_premium_admin_assets( $hook ) {
    if ( 'plugins_page_codemedsss-scan-plugins' !== $hook ) {
        return;
    }

    $js_file = dirname( __FILE__ ) . '/js/admin-premium.js';
    if ( ! file_exists( $js_file ) ) {
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
