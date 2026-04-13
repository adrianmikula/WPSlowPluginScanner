<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function pia_get_active_plugin_entries() {
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

function pia_initiate_scan( $url ) {
    if ( pia_scan_is_locked() ) {
        return array( 'error' => 'Scan already in progress' );
    }

    $url = esc_url_raw( $url );
    if ( empty( $url ) ) {
        $url = home_url();
    }

    $baseline = pia_run_test( $url );
    $active_entries = pia_get_active_plugin_entries();
    $own_plugin_file = plugin_basename( PIA_PLUGIN_FILE );

    $plugin_files = array_keys( $active_entries );
    $plugin_files = array_filter( $plugin_files, function( $file ) use ( $own_plugin_file ) {
        return $file !== $own_plugin_file;
    } );

    $truncated = false;
    $limit = pia_is_premium() ? PHP_INT_MAX : pia_get_free_limit();
    if ( count( $plugin_files ) > $limit ) {
        $plugin_files = array_slice( $plugin_files, 0, $limit );
        $truncated = true;
    }

    $scan_data = array(
        'url'           => $url,
        'baseline'      => $baseline,
        'plugin_files'  => array_values( $plugin_files ),
        'active_count'  => count( $active_entries ),
        'truncated'     => $truncated,
        'plugin_results'=> array(),
        'scanned'        => 0,
    );

    if ( ! empty( $baseline['error'] ) ) {
        $scan_data['errors'] = array( 'Baseline request failed: ' . $baseline['error'] );
        return $scan_data;
    }

    pia_lock_scan();
    pia_prepare_temp_mu_plugin();
    pia_set_scan_progress( $scan_data );

    return $scan_data;
}

function pia_scan_next_plugin() {
    $progress = pia_get_scan_progress();
    if ( ! $progress ) {
        return array( 'error' => 'No scan in progress', 'complete' => true );
    }

    if ( pia_get_scan_cancel_flag() ) {
        pia_clear_scan_cancel_flag();
        pia_clear_scan_progress();
        pia_unlock_scan();
        pia_clear_temp_mu_plugin();
        return array( 'complete' => true, 'cancelled' => true );
    }

    $index = $progress['scanned'];
    $plugin_files = $progress['plugin_files'];

    if ( $index >= count( $plugin_files ) ) {
        pia_complete_scan();
        return array( 'complete' => true );
    }

    $plugin_file = $plugin_files[ $index ];
    $active_entries = pia_get_active_plugin_entries();
    $plugin_name = isset( $active_entries[ $plugin_file ] ) ? $active_entries[ $plugin_file ]['name'] : $plugin_file;

    $test_result = pia_run_test( $progress['url'], $plugin_file );
    $baseline = $progress['baseline'];

    $delta = $test_result['time'] - $baseline['time'];
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

    $progress['plugin_results'][] = array(
        'file'           => $plugin_file,
        'name'           => $plugin_name,
        'time'           => $test_result['time'],
        'status'         => $test_result['status'],
        'hash'           => $test_result['hash'],
        'error'          => $test_result['error'],
        'delta'          => round( $delta, 3 ),
        'status_changed' => $status_changed,
        'hash_changed'   => $hash_changed,
        'impact'         => $impact,
    );

    $progress['scanned']++;
    pia_set_scan_progress( $progress );

    return array(
        'current'  => $index + 1,
        'total'    => count( $plugin_files ),
        'plugin'   => $plugin_name,
        'progress' => $progress,
    );
}

function pia_complete_scan() {
    $progress = pia_get_scan_progress();
    if ( ! $progress ) {
        return;
    }

    usort( $progress['plugin_results'], function( $a, $b ) {
        return $b['delta'] <=> $a['delta'];
    } );

    $results = array(
        'url'          => $progress['url'],
        'baseline'    => $progress['baseline'],
        'plugins'     => $progress['plugin_results'],
        'scanned'     => $progress['scanned'],
        'active_count'=> $progress['active_count'],
        'truncated'   => $progress['truncated'],
        'errors'       => isset( $progress['errors'] ) ? $progress['errors'] : array(),
    );

    pia_store_scan_results( $results );

    if ( pia_is_telemetry_enabled() ) {
        $telemetry_data = pia_prepare_telemetry_data( $results );
        pia_add_to_telemetry_queue( $telemetry_data );
    }

    pia_clear_scan_progress();
    pia_unlock_scan();
    pia_clear_temp_mu_plugin();
}
