<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function pia_admin_menu() {
    add_management_page(
        __( 'Scan Plugins', 'wp-slow-plugin-scanner' ),
        __( 'Scan Plugins', 'wp-slow-plugin-scanner' ),
        'manage_options',
        'pia-scan-plugins',
        'pia_render_admin_page'
    );
}

function pia_render_admin_page() {
    $results = pia_get_last_scan_results();
    $scan_errors = array();
    $message = '';

    if ( isset( $_GET['pia_scan_status'] ) ) {
        $message = sanitize_text_field( $_GET['pia_scan_status'] );
    }

    if ( pia_scan_is_locked() ) {
        $scan_errors[] = __( 'A scan is already running. Please wait a few minutes and try again.', 'wp-slow-plugin-scanner' );
    }

    if ( ! empty( $scan_errors ) ) {
        foreach ( $scan_errors as $error ) {
            echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
        }
    }

    if ( ! empty( $message ) ) {
        echo '<div class="notice notice-success"><p>' . esc_html( $message ) . '</p></div>';
    }

    $default_url = isset( $results['url'] ) ? esc_url( $results['url'] ) : esc_url( home_url() );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Plugin Impact Scanner', 'wp-slow-plugin-scanner' ); ?></h1>
        <p><?php esc_html_e( 'Run a safe loopback scan to identify the single plugin causing slowdown or breakage on a specific page.', 'wp-slow-plugin-scanner' ); ?></p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'pia_scan_action', 'pia_scan_nonce' ); ?>
            <input type="hidden" name="action" value="pia_scan_plugins" />
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="pia_scan_url"><?php esc_html_e( 'URL to scan', 'wp-slow-plugin-scanner' ); ?></label></th>
                    <td><input name="pia_scan_url" type="url" id="pia_scan_url" value="<?php echo $default_url; ?>" class="regular-text" /></td>
                </tr>
            </table>
            <?php submit_button( __( 'Scan Plugins', 'wp-slow-plugin-scanner' ) ); ?>
        </form>

        <?php if ( ! empty( $results ) && isset( $results['baseline'] ) ) : ?>
            <h2><?php esc_html_e( 'Last Scan Results', 'wp-slow-plugin-scanner' ); ?></h2>
            <p><strong><?php esc_html_e( 'URL:', 'wp-slow-plugin-scanner' ); ?></strong> <?php echo esc_html( $results['url'] ); ?></p>
            <p><strong><?php esc_html_e( 'Baseline status:', 'wp-slow-plugin-scanner' ); ?></strong> <?php echo esc_html( $results['baseline']['status'] ); ?></p>
            <p><strong><?php esc_html_e( 'Baseline time:', 'wp-slow-plugin-scanner' ); ?></strong> <?php echo esc_html( round( $results['baseline']['time'], 3 ) ); ?>s</p>
            <?php if ( ! empty( $results['errors'] ) ) : ?>
                <div class="notice notice-warning"><p><?php echo esc_html( implode( ' ', $results['errors'] ) ); ?></p></div>
            <?php endif; ?>

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
                    <?php foreach ( $results['plugins'] as $plugin ) : ?>
                        <tr>
                            <td><?php echo esc_html( $plugin['name'] ); ?></td>
                            <td><?php echo esc_html( $plugin['impact'] ); ?></td>
                            <td><?php echo esc_html( $plugin['status'] ); ?></td>
                            <td><?php echo esc_html( $plugin['delta'] ); ?>s</td>
                            <td><?php echo $plugin['hash_changed'] ? esc_html__( 'Yes', 'wp-slow-plugin-scanner' ) : esc_html__( 'No', 'wp-slow-plugin-scanner' ); ?></td>
                            <td><?php echo esc_html( $plugin['error'] ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ( ! empty( $results['truncated'] ) ) : ?>
                <p><?php esc_html_e( 'The plugin list was limited for speed. Only the first few active plugins were tested.', 'wp-slow-plugin-scanner' ); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

function pia_admin_handle_scan_request() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Permission denied.', 'wp-slow-plugin-scanner' ) );
    }

    check_admin_referer( 'pia_scan_action', 'pia_scan_nonce' );

    if ( pia_scan_is_locked() ) {
        wp_redirect( add_query_arg( 'pia_scan_status', rawurlencode( __( 'A scan is already running. Please try later.', 'wp-slow-plugin-scanner' ) ), wp_get_referer() ) );
        exit;
    }

    $scan_url = isset( $_POST['pia_scan_url'] ) ? esc_url_raw( wp_unslash( $_POST['pia_scan_url'] ) ) : home_url();
    if ( empty( $scan_url ) ) {
        $scan_url = home_url();
    }

    pia_lock_scan();
    pia_prepare_temp_mu_plugin();
    $results = pia_run_plugin_scan( $scan_url );
    pia_clear_temp_mu_plugin();
    pia_unlock_scan();

    if ( ! empty( $results['errors'] ) ) {
        $message = __( 'Scan completed with warnings. Review the results below.', 'wp-slow-plugin-scanner' );
    } else {
        $message = __( 'Scan completed successfully.', 'wp-slow-plugin-scanner' );
    }

    wp_redirect( add_query_arg( 'pia_scan_status', rawurlencode( $message ), wp_get_referer() ) );
    exit;
}
