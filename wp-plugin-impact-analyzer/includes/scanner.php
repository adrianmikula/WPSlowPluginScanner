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

function pia_run_plugin_scan( $url ) {
    $url = esc_url_raw( $url );
    if ( empty( $url ) ) {
        $url = home_url();
    }

    $baseline = pia_run_test( $url );
    $active_entries = pia_get_active_plugin_entries();
    $own_plugin_file = plugin_basename( PIA_PLUGIN_FILE );

    $results = array(
        'url'          => $url,
        'baseline'     => $baseline,
        'plugins'      => array(),
        'scanned'      => 0,
        'active_count' => count( $active_entries ),
        'truncated'    => false,
        'errors'       => array(),
    );

    if ( ! empty( $baseline['error'] ) ) {
        $results['errors'][] = sprintf( 'Baseline request failed: %s', $baseline['error'] );
        return $results;
    }

    $plugin_files = array_keys( $active_entries );
    $plugin_files = array_filter( $plugin_files, function( $file ) use ( $own_plugin_file ) {
        return $file !== $own_plugin_file;
    } );

    if ( count( $plugin_files ) > PIA_MAX_TEST_PLUGINS ) {
        $plugin_files = array_slice( $plugin_files, 0, PIA_MAX_TEST_PLUGINS );
        $results['truncated'] = true;
    }

    foreach ( $plugin_files as $plugin_file ) {
        $plugin_name = isset( $active_entries[ $plugin_file ] ) ? $active_entries[ $plugin_file ]['name'] : $plugin_file;
        $test_result = pia_run_test( $url, $plugin_file );

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

        $results['plugins'][] = array(
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

        $results['scanned']++;
    }

    usort( $results['plugins'], function( $a, $b ) {
        return $b['delta'] <=> $a['delta'];
    } );

    pia_store_scan_results( $results );
    return $results;
}
