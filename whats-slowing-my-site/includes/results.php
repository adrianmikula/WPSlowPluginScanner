<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function pia_get_last_scan_results() {
    $results = get_option( PIA_RESULTS_OPTION, array() );
    if ( ! is_array( $results ) ) {
        $results = array();
    }
    return $results;
}

function pia_store_scan_results( array $results ) {
    $results['last_updated'] = time();
    update_option( PIA_RESULTS_OPTION, $results );
}

function pia_scan_is_locked() {
    return get_transient( PIA_SCAN_LOCK_KEY ) ? true : false;
}

function pia_lock_scan() {
    return set_transient( PIA_SCAN_LOCK_KEY, true, 300 );
}

function pia_unlock_scan() {
    delete_transient( PIA_SCAN_LOCK_KEY );
}

function pia_get_scan_progress() {
    return get_transient( 'pia_scan_progress' );
}

function pia_set_scan_progress( $data ) {
    set_transient( 'pia_scan_progress', $data, 600 );
}

function pia_clear_scan_progress() {
    delete_transient( 'pia_scan_progress' );
}

function pia_get_scan_cancel_flag() {
    return get_transient( 'pia_scan_cancel' );
}

function pia_set_scan_cancel_flag() {
    set_transient( 'pia_scan_cancel', true, 300 );
}

function pia_clear_scan_cancel_flag() {
    delete_transient( 'pia_scan_cancel' );
}

function pia_clear_temp_mu_plugin() {
    if ( file_exists( PIA_TEMP_MU_PLUGIN ) ) {
        wp_delete_file( PIA_TEMP_MU_PLUGIN );
    }
}
