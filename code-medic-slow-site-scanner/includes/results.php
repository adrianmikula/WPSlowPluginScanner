<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function codemedsss_get_last_scan_results() {
    $results = get_option( CODESS_RESULTS_OPTION, array() );
    if ( ! is_array( $results ) ) {
        $results = array();
    }
    return $results;
}

function codemedsss_store_scan_results( array $results ) {
    $results['last_updated'] = time();
    update_option( CODESS_RESULTS_OPTION, $results );
}

function codemedsss_scan_is_locked() {
    return get_transient( CODESS_SCAN_LOCK_KEY ) ? true : false;
}

function codemedsss_lock_scan() {
    return set_transient( CODESS_SCAN_LOCK_KEY, true, 300 );
}

function codemedsss_unlock_scan() {
    delete_transient( CODESS_SCAN_LOCK_KEY );
}

function codemedsss_get_scan_progress() {
    return get_transient( CODESS_PROGRESS_KEY );
}

function codemedsss_set_scan_progress( $data ) {
    set_transient( CODESS_PROGRESS_KEY, $data, 600 );
}

function codemedsss_clear_scan_progress() {
    delete_transient( CODESS_PROGRESS_KEY );
}

function codemedsss_get_scan_cancel_flag() {
    return get_transient( CODESS_CANCEL_KEY );
}

function codemedsss_set_scan_cancel_flag() {
    set_transient( CODESS_CANCEL_KEY, true, 300 );
}

function codemedsss_clear_scan_cancel_flag() {
    delete_transient( CODESS_CANCEL_KEY );
}

function codemedsss_clear_temp_mu_plugin() {
    if ( file_exists( CODESS_TEMP_MU_PLUGIN ) ) {
        wp_delete_file( CODESS_TEMP_MU_PLUGIN );
    }
}
