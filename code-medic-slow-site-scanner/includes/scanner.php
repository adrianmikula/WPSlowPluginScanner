<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function codemedsss_get_active_plugin_entries() {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';

    $active_plugins = get_option( 'active_plugins', array() );
    $all_plugins    = get_plugins();
    $entries        = array();

    foreach ( $active_plugins as $plugin_file ) {
        if ( isset( $all_plugins[ $plugin_file ] ) ) {
            $entries[ $plugin_file ] = array(
                'file' => $plugin_file,
                'name' => $all_plugins[ $plugin_file ]['Name'],
            );
        } else {
            $entries[ $plugin_file ] = array(
                'file' => $plugin_file,
                'name' => $plugin_file,
            );
        }
    }

    return $entries;
}

function codemedsss_initiate_scan( $url ) {
    // User consent required
    if ( ! get_option( 'codemedsss_mu_consent', false ) ) {
        return array( 'error' => __( 'You must enable consent to create temporary files before scanning.', 'code-medic-slow-site-scanner' ) );
    }

    if ( codemedsss_scan_is_locked() ) {
        return array( 'error' => 'Scan already in progress' );
    }

    // Free mode: lock to homepage only
    if ( ! codemedsss_is_premium() ) {
        $url = home_url();
    } else {
        $url = esc_url_raw( $url );
        if ( empty( $url ) ) {
            $url = home_url();
        }
    }

    $baseline = codemedsss_run_test( $url );
    $active_entries = codemedsss_get_active_plugin_entries();
    $own_plugin_file = plugin_basename( CODESS_PLUGIN_FILE );

    $plugin_files = array_keys( $active_entries );
    $plugin_files = array_filter( $plugin_files, function( $file ) use ( $own_plugin_file ) {
        return $file !== $own_plugin_file;
    } );

    // Enforce free mode limit
    $limit = codemedsss_get_free_limit();
    $truncated = count( $plugin_files ) > $limit;
    $plugin_files = array_slice( $plugin_files, 0, $limit );

    $scan_data = array(
        'url'           => $url,
        'baseline'      => $baseline,
        'plugin_files'  => array_values( $plugin_files ),
        'active_count'  => count( $active_entries ),
        'truncated'     => $truncated,
        'plugin_results'=> array(),
        'scanned'       => 0,
    );

    if ( ! empty( $baseline['error'] ) ) {
        $scan_data['errors'] = array( 'Baseline request failed: ' . $baseline['error'] );
        return $scan_data;
    }

    codemedsss_lock_scan();
    codemedsss_prepare_temp_mu_plugin();
    codemedsss_set_scan_progress( $scan_data );

    return $scan_data;
}

function codemedsss_scan_next_plugin() {
    $progress = codemedsss_get_scan_progress();
    if ( ! $progress ) {
        return array( 'error' => 'No scan in progress', 'complete' => true );
    }

    if ( codemedsss_get_scan_cancel_flag() ) {
        codemedsss_clear_scan_cancel_flag();
        codemedsss_clear_scan_progress();
        codemedsss_unlock_scan();
        codemedsss_clear_temp_mu_plugin();
        return array( 'complete' => true, 'cancelled' => true );
    }

    $index = $progress['scanned'];
    $plugin_files = $progress['plugin_files'];

    if ( $index >= count( $plugin_files ) ) {
        codemedsss_complete_scan();
        return array( 'complete' => true );
    }

    $plugin_file = $plugin_files[ $index ];
    $active_entries = codemedsss_get_active_plugin_entries();
    $plugin_name = isset( $active_entries[ $plugin_file ] ) ? $active_entries[ $plugin_file ]['name'] : $plugin_file;

    $test_result = codemedsss_run_test( $progress['url'], $plugin_file );
    $baseline = $progress['baseline'];

    $delta = $test_result['time'] - $baseline['time'];
    $percentage = $baseline['time'] > 0 ? round( ( $delta / $baseline['time'] ) * 100, 1 ) : 0;
    $status_changed = $test_result['status'] !== $baseline['status'];
    $hash_changed = $test_result['hash'] !== $baseline['hash'];

    $impact = 'No significant impact';
    if ( $status_changed ) {
        $impact = 'Breaks site';
    } elseif ( $delta > 0.3 ) {
        $impact = 'Slows site';
    } elseif ( $hash_changed ) {
        $impact = 'Changes output';
    }

    $plugin_result = array(
        'file'                 => $plugin_file,
        'name'                 => $plugin_name,
        'time'                 => $test_result['time'],
        'status'               => $test_result['status'],
        'hash'                 => $test_result['hash'],
        'error'                => $test_result['error'],
        'delta'                => round( $delta, 3 ),
        'percentage'           => $percentage,
        'scanner_engine_version' => CODESS_SCANNER_ENGINE_VERSION,
        'status_changed'       => $status_changed,
        'hash_changed'         => $hash_changed,
        'impact'               => $impact,
    );

    $progress['plugin_results'][] = $plugin_result;

    // No telemetry in free version

    $progress['scanned']++;
    codemedsss_set_scan_progress( $progress );

    return array(
        'current'  => $index + 1,
        'total'    => count( $plugin_files ),
        'plugin'   => $plugin_name,
        'progress' => $progress,
    );
}

function codemedsss_complete_scan() {
    $progress = codemedsss_get_scan_progress();
    if ( ! $progress ) {
        return;
    }

    usort( $progress['plugin_results'], function( $a, $b ) {
        return $b['delta'] <=> $a['delta'];
    } );

    $results = array(
        'url'                  => $progress['url'],
        'baseline'             => $progress['baseline'],
        'plugins'              => $progress['plugin_results'],
        'scanned'              => $progress['scanned'],
        'active_count'         => $progress['active_count'],
        'truncated'            => $progress['truncated'],
        'scanner_engine_version' => CODESS_SCANNER_ENGINE_VERSION,
        'errors'               => isset( $progress['errors'] ) ? $progress['errors'] : array(),
    );

    codemedsss_store_scan_results( $results );

    codemedsss_clear_scan_progress();
    codemedsss_unlock_scan();
    codemedsss_clear_temp_mu_plugin();
}
