<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'CODEMEDSSS_TELEMETRY_QUEUE' ) ) {
    define( 'CODEMEDSSS_TELEMETRY_QUEUE', 'codemedsss_telemetry_queue' );
}
if ( ! defined( 'CODEMEDSSS_TELEMETRY_ENABLED' ) ) {
    define( 'CODEMEDSSS_TELEMETRY_ENABLED', 'codemedsss_telemetry_optin' );
}
if ( ! defined( 'CODEMEDSSS_TELEMETRY_CRON_HOOK' ) ) {
    define( 'CODEMEDSSS_TELEMETRY_CRON_HOOK', 'codemedsss_send_telemetry_cron' );
}
if ( ! defined( 'CODEMEDSSS_SITE_UUID_OPTION' ) ) {
    define( 'CODEMEDSSS_SITE_UUID_OPTION', 'codemedsss_site_uuid' );
}

function codemedsss_is_telemetry_enabled() {
    return (bool) get_option( CODEMEDSSS_TELEMETRY_ENABLED, true );
}

function codemedsss_set_telemetry_enabled( $enabled ) {
    update_option( CODEMEDSSS_TELEMETRY_ENABLED, $enabled ? true : false );
}

function codemedsss_get_site_uuid() {
    $uuid = get_option( CODEMEDSSS_SITE_UUID_OPTION, '' );
    if ( empty( $uuid ) ) {
        $uuid = codemedsss_generate_site_uuid();
        update_option( CODEMEDSSS_SITE_UUID_OPTION, $uuid );
    }
    return $uuid;
}

function codemedsss_generate_site_uuid() {
    $site_url = get_site_url();
    $salt = 'pia-telemetry-v1';
    $hash = md5( $site_url . $salt );

    return sprintf(
        '%s-%s-%s-%s-%s',
        substr( $hash, 0, 8 ),
        substr( $hash, 8, 4 ),
        substr( $hash, 12, 4 ),
        substr( $hash, 16, 4 ),
        substr( $hash, 20, 12 )
    );
}

function codemedsss_get_telemetry_queue() {
    return get_option( CODEMEDSSS_TELEMETRY_QUEUE, array() );
}

function codemedsss_add_to_telemetry_queue( $data ) {
    $queue = codemedsss_get_telemetry_queue();
    $queue[] = $data;
    update_option( CODEMEDSSS_TELEMETRY_QUEUE, $queue );
}

function codemedsss_clear_telemetry_queue() {
    delete_option( CODEMEDSSS_TELEMETRY_QUEUE );
}

function codemedsss_anonymize_plugin_slug( $plugin_file ) {
    $parts = explode( '/', $plugin_file );
    return ! empty( $parts[0] ) ? $parts[0] : $plugin_file;
}

function codemedsss_get_error_category( $plugin_result ) {
    if ( ! empty( $plugin_result['error'] ) ) {
        $error = strtolower( $plugin_result['error'] );
        if ( strpos( $error, 'timeout' ) !== false ) {
            return 'timeout';
        }
        return 'break_site';
    }

    if ( $plugin_result['status_changed'] ) {
        return 'break_site';
    }

    if ( $plugin_result['hash_changed'] ) {
        return 'output_change';
    }

    return 'none';
}

function codemedsss_get_plugin_version( $plugin_file ) {
    if ( ! function_exists( 'get_plugin_data' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false, false );
    return ! empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : null;
}

function codemedsss_count_plugin_settings( $plugin_file ) {
	global $wpdb;

	$plugin_slug = codemedsss_anonymize_plugin_slug( $plugin_file );
	$cache_key   = 'codemedsss_plugin_settings_' . md5( $plugin_slug );

	$cached_count = wp_cache_get( $cache_key, 'codemedsss_settings_count' );
	if ( false !== $cached_count ) {
		return $cached_count;
	}

	$option_patterns = array(
		$wpdb->prepare( '%s_', $plugin_slug ),
		$wpdb->prepare( '%s_', sanitize_key( $plugin_slug ) ),
	);

	$total_count = 0;

	foreach ( $option_patterns as $pattern ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . $wpdb->options . ' WHERE option_name LIKE %s',
				$pattern . '%'
			)
		);
		$total_count += (int) $count;
	}

	wp_cache_set( $cache_key, $total_count, 'codemedsss_settings_count', HOUR_IN_SECONDS );

	return $total_count;
}

function codemedsss_prepare_telemetry_data( $plugin_result, $all_plugin_files, $baseline_time ) {
    $plugin_file = $plugin_result['file'];
    $plugin_slug = codemedsss_anonymize_plugin_slug( $plugin_file );
    $origin = codemedsss_get_site_uuid();

    $php_version = PHP_VERSION;
    $wp_version  = get_bloginfo( 'version' );

    $all_plugins = array();
    foreach ( $all_plugin_files as $file ) {
        $all_plugins[] = codemedsss_anonymize_plugin_slug( $file );
    }

    $error_category = codemedsss_get_error_category( $plugin_result );

    $result_data = array(
        $plugin_slug => array(
            'delta' => $plugin_result['delta'],
        ),
    );

    $plugin_version = codemedsss_get_plugin_version( $plugin_file );
    $settings_count = codemedsss_count_plugin_settings( $plugin_file );

    $data = array(
        'plugins'                  => $all_plugins,
        'plugin_tested'           => $plugin_slug,
        'plugin_version'          => $plugin_version,
        'plugin_speed_delta'      => $plugin_result['delta'],
        'plugin_speed_percentage' => isset( $plugin_result['percentage'] ) ? $plugin_result['percentage'] : 0,
        'baseline_site_load_speed' => $baseline_time,
        'plugin_error'            => $plugin_result['error'] ?: null,
        'error_category'          => $error_category,
        'settings_count'          => $settings_count,
        'scanner_engine_version'  => CODEMEDSSS_SCANNER_ENGINE_VERSION,
        'env'                     => array(
            'php_version' => $php_version,
            'wp_version'  => $wp_version,
        ),
        'origin'                  => $origin,
        'timestamp'               => time(),
    );

    return $data;
}

function codemedsss_send_telemetry_to_supabase( $data ) {
    $supabase_url = defined( 'CODEMEDSSS_SUPABASE_URL' ) && ! empty( CODEMEDSSS_SUPABASE_URL ) ? CODEMEDSSS_SUPABASE_URL : '';
    $supabase_key = defined( 'CODEMEDSSS_SUPABASE_ANON_KEY' ) && ! empty( CODEMEDSSS_SUPABASE_ANON_KEY ) ? CODEMEDSSS_SUPABASE_ANON_KEY : '';
    $table_name   = defined( 'CODEMEDSSS_SUPABASE_TABLE' ) ? CODEMEDSSS_SUPABASE_TABLE : 'telemetry';

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

function codemedsss_process_telemetry_queue() {
    if ( ! codemedsss_is_telemetry_enabled() ) {
        return;
    }

    $queue = codemedsss_get_telemetry_queue();
    if ( empty( $queue ) ) {
        return;
    }

    $failed = array();

    foreach ( $queue as $index => $data ) {
        $sent = codemedsss_send_telemetry_to_supabase( $data );
        if ( ! $sent ) {
            $failed[] = $index;
        }
    }

    if ( empty( $failed ) ) {
        codemedsss_clear_telemetry_queue();
    } else {
        $remaining = array();
        foreach ( $queue as $index => $data ) {
            if ( ! in_array( $index, $failed, true ) ) {
                $remaining[] = $data;
            }
        }
        update_option( CODEMEDSSS_TELEMETRY_QUEUE, $remaining );
    }
}

function codemedsss_schedule_telemetry_cron() {
    if ( ! wp_next_scheduled( CODEMEDSSS_TELEMETRY_CRON_HOOK ) ) {
        wp_schedule_event( time(), 'hourly', CODEMEDSSS_TELEMETRY_CRON_HOOK );
    }
}

function codemedsss_unschedule_telemetry_cron() {
    wp_clear_scheduled_hook( CODEMEDSSS_TELEMETRY_CRON_HOOK );
}

add_action( CODEMEDSSS_TELEMETRY_CRON_HOOK, 'codemedsss_process_telemetry_queue' );

register_activation_hook( __FILE__, 'codemedsss_activate_telemetry' );

function codemedsss_activate_telemetry() {
    if ( codemedsss_is_telemetry_enabled() ) {
        codemedsss_schedule_telemetry_cron();
    }
}

register_deactivation_hook( __FILE__, 'codemedsss_unschedule_telemetry_cron' );