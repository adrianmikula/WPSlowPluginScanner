<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function pia_admin_menu() {
    add_plugins_page(
        __( 'Plugin Impact Scanner', 'wp-slow-plugin-scanner' ),
        __( 'Scan Plugins', 'wp-slow-plugin-scanner' ),
        'manage_options',
        'pia-scan-plugins',
        'pia_render_admin_page'
    );
}

add_action( 'wp_ajax_pia_start_scan', 'pia_ajax_start_scan' );
add_action( 'wp_ajax_pia_poll_scan', 'pia_ajax_poll_scan' );
add_action( 'wp_ajax_pia_cancel_scan', 'pia_ajax_cancel_scan' );

function pia_ajax_start_scan() {
    check_ajax_referer( 'pia_scan_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Permission denied' ) );
    }

    $scan_url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : home_url();
    if ( empty( $scan_url ) ) {
        $scan_url = home_url();
    }

    $result = pia_initiate_scan( $scan_url );

    if ( isset( $result['error'] ) ) {
        wp_send_json_error( array( 'message' => $result['error'] ) );
    }

    if ( isset( $result['errors'] ) && ! empty( $result['errors'] ) ) {
        wp_send_json_error( array( 'message' => $result['errors'][0] ) );
    }

    wp_send_json_success( array(
        'total_plugins' => count( $result['plugin_files'] ),
    ) );
}

function pia_ajax_poll_scan() {
    check_ajax_referer( 'pia_scan_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Permission denied' ) );
    }

    $result = pia_scan_next_plugin();

    if ( isset( $result['complete'] ) && $result['complete'] ) {
        $final_results = pia_get_last_scan_results();
        wp_send_json_success( array(
            'complete'   => true,
            'cancelled' => isset( $result['cancelled'] ) ? $result['cancelled'] : false,
            'results'   => $final_results,
        ) );
    }

    wp_send_json_success( array(
        'complete'       => false,
        'current'        => $result['current'],
        'total'          => $result['total'],
        'current_plugin' => $result['plugin'],
    ) );
}

function pia_ajax_cancel_scan() {
    check_ajax_referer( 'pia_scan_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Permission denied' ) );
    }

    pia_set_scan_cancel_flag();
    wp_send_json_success();
}

function pia_render_admin_page() {
    $results = pia_get_last_scan_results();
    $default_url = isset( $results['url'] ) ? esc_url( $results['url'] ) : esc_url( home_url() );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Plugin Impact Scanner', 'wp-slow-plugin-scanner' ); ?></h1>
        <p><?php esc_html_e( 'Run a safe loopback scan to identify the single plugin causing slowdown or breakage on a specific page.', 'wp-slow-plugin-scanner' ); ?></p>

        <div id="pia-scan-controls">
            <p>
                <label for="pia_scan_url"><?php esc_html_e( 'URL to scan', 'wp-slow-plugin-scanner' ); ?></label>
                <input type="url" id="pia_scan_url" value="<?php echo $default_url; ?>" class="regular-text" />
            </p>
            <p>
                <button type="button" id="pia-scan-btn" class="button button-primary"><?php esc_html_e( 'Scan Plugins', 'wp-slow-plugin-scanner' ); ?></button>
                <button type="button" id="pia-cancel-btn" class="button" style="display:none;"><?php esc_html_e( 'Cancel', 'wp-slow-plugin-scanner' ); ?></button>
            </p>
        </div>

        <div id="pia-progress" style="display:none;">
            <p><?php esc_html_e( 'Scanning...', 'wp-slow-plugin-scanner' ); ?></p>
            <progress id="pia-progress-bar" value="0" max="100"></progress>
            <p id="pia-progress-text"></p>
        </div>

        <div id="pia-message-area"></div>

        <div id="pia-results-area"<?php echo empty( $results ) || ! isset( $results['baseline'] ) ? ' style="display:none;"' : ''; ?>>
            <h2><?php esc_html_e( 'Scan Results', 'wp-slow-plugin-scanner' ); ?></h2>
            <?php if ( ! empty( $results ) && isset( $results['baseline'] ) ) { ?>
                <p><strong><?php esc_html_e( 'URL:', 'wp-slow-plugin-scanner' ); ?></strong> <?php echo esc_html( $results['url'] ); ?></p>
                <p><strong><?php esc_html_e( 'Baseline status:', 'wp-slow-plugin-scanner' ); ?></strong> <?php echo esc_html( $results['baseline']['status'] ); ?></p>
                <p><strong><?php esc_html_e( 'Baseline time:', 'wp-slow-plugin-scanner' ); ?></strong> <?php echo esc_html( round( $results['baseline']['time'], 3 ) ); ?>s</p>
                <?php if ( ! empty( $results['errors'] ) ) { ?>
                    <div class="notice notice-warning"><p><?php echo esc_html( implode( ' ', $results['errors'] ) ); ?></p></div>
                <?php } ?>

                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Plugin', 'wp-slow-plugin-scanner' ); ?></th>
                            <th><?php esc_html_e( 'Impact', 'wp-slow-plugin-scanner' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'wp-slow-plugin-scanner' ); ?></th>
                            <th><?php esc_html_e( 'Delta', 'wp-slow-plugin-scanner' ); ?></th>
                            <th><?php esc_html_e( 'Output Change', 'wp-slow-plugin-scanner' ); ?></th>
                            <th><?php esc_html_e( 'Error', 'wp-slow-plugin-scanner' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $results['plugins'] as $plugin ) { ?>
                            <tr>
                                <td><?php echo esc_html( $plugin['name'] ); ?></td>
                                <td><?php echo esc_html( $plugin['impact'] ); ?></td>
                                <td><?php echo esc_html( $plugin['status'] ); ?></td>
                                <td><?php echo esc_html( $plugin['delta'] ); ?>s</td>
                                <td><?php echo $plugin['hash_changed'] ? esc_html__( 'Yes', 'wp-slow-plugin-scanner' ) : esc_html__( 'No', 'wp-slow-plugin-scanner' ); ?></td>
                                <td><?php echo esc_html( $plugin['error'] ); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <?php if ( ! empty( $results['truncated'] ) ) { ?>
                    <p><?php esc_html_e( 'The plugin list was limited for speed. Only the first few active plugins were tested.', 'wp-slow-plugin-scanner' ); ?></p>
                <?php } ?>
            <?php } ?>
        </div>
    </div>
    <?php
}