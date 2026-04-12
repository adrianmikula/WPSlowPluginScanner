<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function pia_build_test_url( $url, $disable_plugin = null ) {
    $args = array(
        'pia_test'      => '1',
        'pia_scan'      => '1',
    );

    if ( ! empty( $disable_plugin ) ) {
        $args['pia_disable'] = $disable_plugin;
    }

    return add_query_arg( $args, $url );
}

function pia_compute_response_hash( $body, $status ) {
    return md5( (string) $status . '|' . (string) $body );
}

function pia_run_test( $url, $disable_plugin = null ) {
    $test_url = pia_build_test_url( $url, $disable_plugin );

    $request_args = array(
        'timeout'     => 8,
        'redirection' => 5,
        'headers'     => array(
            'Cache-Control' => 'no-cache',
            'Pragma'        => 'no-cache',
        ),
        'sslverify'   => apply_filters( 'https_local_ssl_verify', false ),
    );

    $start = microtime( true );
    $response = wp_remote_get( $test_url, $request_args );
    $duration = microtime( true ) - $start;

    if ( is_wp_error( $response ) ) {
        return array(
            'time'   => $duration,
            'status' => 0,
            'hash'   => '',
            'error'  => $response->get_error_message(),
        );
    }

    $body   = wp_remote_retrieve_body( $response );
    $status = wp_remote_retrieve_response_code( $response );

    return array(
        'time'   => $duration,
        'status' => (int) $status,
        'hash'   => pia_compute_response_hash( $body, $status ),
        'error'  => '',
    );
}
