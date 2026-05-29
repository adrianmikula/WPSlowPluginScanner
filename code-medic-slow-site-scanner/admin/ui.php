<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// AJAX handlers
add_action( 'wp_ajax_codemedsss_start_scan', 'codemedsss_ajax_start_scan' );
add_action( 'wp_ajax_codemedsss_poll_scan', 'codemedsss_ajax_poll_scan' );
add_action( 'wp_ajax_codemedsss_cancel_scan', 'codemedsss_ajax_cancel_scan' );
add_action( 'wp_ajax_codemedsss_save_consent', 'codemedsss_save_mu_consent' );

function codemedsss_ajax_start_scan() {
    check_ajax_referer( 'codemedsss_scan_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Permission denied' ) );
    }

    $result = codemedsss_initiate_scan();

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

function codemedsss_ajax_poll_scan() {
    check_ajax_referer( 'codemedsss_scan_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Permission denied' ) );
    }

    $result = codemedsss_scan_next_plugin();

    if ( isset( $result['complete'] ) && $result['complete'] ) {
        $final_results = codemedsss_get_last_scan_results();
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

function codemedsss_ajax_cancel_scan() {
    check_ajax_referer( 'codemedsss_scan_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Permission denied' ) );
    }

    codemedsss_set_scan_cancel_flag();
    wp_send_json_success();
}

function codemedsss_save_mu_consent() {
    check_ajax_referer( 'codemedsss_scan_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Permission denied' ) );
    }
    $enabled = isset( $_POST['enabled'] ) ? (bool) $_POST['enabled'] : false;
    update_option( 'codemedsss_mu_consent', $enabled );
    wp_send_json_success();
}

function codemedsss_admin_menu() {
    add_plugins_page(
        __( 'CodeMedic Slow Site Scanner', 'code-medic-slow-site-scanner' ),
        __( 'Scan Plugins', 'code-medic-slow-site-scanner' ),
        'manage_options',
        'codemedsss-scan-plugins',
        'codemedsss_render_admin_page'
    );
}

function codemedsss_render_admin_page() {
    $results = codemedsss_get_last_scan_results();
    $mu_consent = get_option( 'codemedsss_mu_consent', false );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'CodeMedic Slow Site Scanner', 'code-medic-slow-site-scanner' ); ?></h1>
        <p><?php esc_html_e( 'Run a safe loopback scan to identify the plugin causing slowdown or breakage on your site.', 'code-medic-slow-site-scanner' ); ?></p>

        <!-- MU Plugin Consent -->
        <div class="notice notice-info" style="margin-top:10px;margin-bottom:10px;">
            <p>
                <label for="codemedsss_mu_consent">
                    <input type="checkbox" id="codemedsss_mu_consent" data-nonce="<?php echo esc_attr( wp_create_nonce( 'codemedsss_scan_nonce' ) ); ?>" <?php checked( $mu_consent ); ?> />
                    <?php esc_html_e( 'I consent to the plugin creating temporary files to disable plugins during testing. This is required for scans to function.', 'code-medic-slow-site-scanner' ); ?>
                </label>
                <span id="codemedsss_consent_status" style="margin-left:10px;font-style:italic;"></span>
            </p>
        </div>

        <div id="codemedsss-scan-controls">
            <p>
                <button type="button" id="codemedsss-scan-btn" class="button button-primary" <?php disabled( ! $mu_consent ); ?>><?php esc_html_e( 'Scan Plugins', 'code-medic-slow-site-scanner' ); ?></button>
                <button type="button" id="codemedsss-cancel-btn" class="button" style="display:none;"><?php esc_html_e( 'Cancel', 'code-medic-slow-site-scanner' ); ?></button>
            </p>
        </div>

        <div id="codemedsss-progress" style="display:none;">
            <p><?php esc_html_e( 'Scanning...', 'code-medic-slow-site-scanner' ); ?></p>
            <progress id="codemedsss-progress-bar" value="0" max="100"></progress>
            <p id="codemedsss-progress-text"></p>
        </div>

        <div id="codemedsss-message-area"></div>

        <div id="codemedsss-results-area"<?php echo empty( $results ) || ! isset( $results['baseline'] ) ? ' style="display:none;"' : ''; ?>>
            <h2><?php esc_html_e( 'Scan Results', 'code-medic-slow-site-scanner' ); ?></h2>
            <?php if ( ! empty( $results ) && isset( $results['baseline'] ) ) : ?>
                <p><strong><?php esc_html_e( 'URL:', 'code-medic-slow-site-scanner' ); ?></strong> <?php echo esc_html( $results['url'] ); ?></p>
                <p><strong><?php esc_html_e( 'Baseline status:', 'code-medic-slow-site-scanner' ); ?></strong> <?php echo esc_html( $results['baseline']['status'] ); ?></p>
                <p><strong><?php esc_html_e( 'Baseline time:', 'code-medic-slow-site-scanner' ); ?></strong> <?php echo esc_html( round( $results['baseline']['time'], 3 ) ); ?>s</p>
                <?php if ( ! empty( $results['errors'] ) ) : ?>
                    <div class="notice notice-warning"><p><?php echo esc_html( implode( ' ', $results['errors'] ) ); ?></p></div>
                <?php endif; ?>

                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Plugin', 'code-medic-slow-site-scanner' ); ?></th>
                            <th><?php esc_html_e( 'Impact', 'code-medic-slow-site-scanner' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'code-medic-slow-site-scanner' ); ?></th>
                            <th><?php esc_html_e( 'Delta', 'code-medic-slow-site-scanner' ); ?></th>
                            <th><?php esc_html_e( '%', 'code-medic-slow-site-scanner' ); ?></th>
                            <th><?php esc_html_e( 'Scanner Ver', 'code-medic-slow-site-scanner' ); ?></th>
                            <th><?php esc_html_e( 'Output Change', 'code-medic-slow-site-scanner' ); ?></th>
                            <th><?php esc_html_e( 'Error', 'code-medic-slow-site-scanner' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $results['plugins'] as $plugin ) : ?>
                            <tr>
                                <td><?php echo esc_html( $plugin['name'] ); ?></td>
                                <td><?php echo esc_html( $plugin['impact'] ); ?></td>
                                <td><?php echo esc_html( $plugin['status'] ); ?></td>
                                <td><?php echo esc_html( $plugin['delta'] ); ?>s</td>
                                <td><?php echo esc_html( $plugin['percentage'] ); ?>%</td>
                                <td><?php echo esc_html( $plugin['scanner_engine_version'] ); ?></td>
                                <td><?php echo $plugin['hash_changed'] ? esc_html__( 'Yes', 'code-medic-slow-site-scanner' ) : esc_html__( 'No', 'code-medic-slow-site-scanner' ); ?></td>
                                <td><?php echo esc_html( $plugin['error'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php
}
