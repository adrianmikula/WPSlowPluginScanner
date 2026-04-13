<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PIA_TELEMETRY_QUEUE', 'pia_telemetry_queue' );
define( 'PIA_TELEMETRY_ENABLED', 'pia_telemetry_optin' );
define( 'PIA_TELEMETRY_CRON_HOOK', 'pia_send_telemetry_cron' );

function pia_is_telemetry_enabled() {
    return (bool) get_option( PIA_TELEMETRY_ENABLED, false );
}

function pia_set_telemetry_enabled( $enabled ) {
    update_option( PIA_TELEMETRY_ENABLED, $enabled ? true : false );
}

function pia_get_telemetry_queue() {
    return get_option( PIA_TELEMETRY_QUEUE, array() );
}

function pia_add_to_telemetry_queue( $data ) {
    $queue = pia_get_telemetry_queue();
    $queue[] = $data;
    update_option( PIA_TELEMETRY_QUEUE, $queue );
}

function pia_clear_telemetry_queue() {
    delete_option( PIA_TELEMETRY_QUEUE );
}

function pia_anonymize_plugin_slug( $plugin_file ) {
    $parts = explode( '/', $plugin_file );
    return ! empty( $parts[0] ) ? $parts[0] : $plugin_file;
}

function pia_prepare_telemetry_data( $scan_results ) {
    global $wpdb;

    $plugins_data = array();
    foreach ( $scan_results['plugins'] as $plugin ) {
        $plugins_data[ pia_anonymize_plugin_slug( $plugin['file'] ) ] = array(
            'delta' => $plugin['delta'],
        );
    }

    $php_version = PHP_VERSION;
    $wp_version  = get_bloginfo( 'version' );

    return array(
        'plugins'   => array_keys( $plugins_data ),
        'results'   => $plugins_data,
        'env'       => array(
            'php_version' => $php_version,
            'wp_version'  => $wp_version,
        ),
        'timestamp' => time(),
    );
}

function pia_send_telemetry_to_supabase( $data ) {
    $supabase_url = defined( 'PIA_SUPABASE_URL' ) && ! empty( PIA_SUPABASE_URL ) ? PIA_SUPABASE_URL : '';
    $supabase_key = defined( 'PIA_SUPABASE_ANON_KEY' ) && ! empty( PIA_SUPABASE_ANON_KEY ) ? PIA_SUPABASE_ANON_KEY : '';
    $table_name   = defined( 'PIA_SUPABASE_TABLE' ) ? PIA_SUPABASE_TABLE : 'telemetry';

    if ( empty( $supabase_url ) || empty( $supabase_key ) ) {
        return false;
    }

    $url = trailingslashit( $supabase_url ) . 'rest/v1/' . $table_name;

    $response = wp_remote_post(
        $url,
        array(
            'method'  => 'POST',
            'headers' => array(
                'apikey'        => $supabase_key,
                'Authorization' => 'Bearer ' . $supabase_key,
                'Content-Type'  => 'application/json',
                'Prefer'        => 'return=minimal',
            ),
            'body'    => wp_json_encode( $data ),
            'timeout' => 15,
        )
    );

    if ( is_wp_error( $response ) ) {
        return false;
    }

    $code = wp_remote_retrieve_response_code( $response );
    return $code >= 200 && $code < 300;
}

function pia_process_telemetry_queue() {
    if ( ! pia_is_telemetry_enabled() ) {
        return;
    }

    $queue = pia_get_telemetry_queue();
    if ( empty( $queue ) ) {
        return;
    }

    $failed = array();

    foreach ( $queue as $index => $data ) {
        $sent = pia_send_telemetry_to_supabase( $data );
        if ( ! $sent ) {
            $failed[] = $index;
        }
    }

    if ( empty( $failed ) ) {
        pia_clear_telemetry_queue();
    } else {
        $remaining = array();
        foreach ( $queue as $index => $data ) {
            if ( ! in_array( $index, $failed, true ) ) {
                $remaining[] = $data;
            }
        }
        update_option( PIA_TELEMETRY_QUEUE, $remaining );
    }
}

function pia_schedule_telemetry_cron() {
    if ( ! wp_next_scheduled( PIA_TELEMETRY_CRON_HOOK ) ) {
        wp_schedule_event( time(), 'hourly', PIA_TELEMETRY_CRON_HOOK );
    }
}

function pia_unschedule_telemetry_cron() {
    wp_clear_scheduled_hook( PIA_TELEMETRY_CRON_HOOK );
}

add_action( PIA_TELEMETRY_CRON_HOOK, 'pia_process_telemetry_queue' );

register_activation_hook( __FILE__, 'pia_activate_telemetry' );

function pia_activate_telemetry() {
    if ( pia_is_telemetry_enabled() ) {
        pia_schedule_telemetry_cron();
    }
}

register_deactivation_hook( __FILE__, 'pia_unschedule_telemetry_cron' );
