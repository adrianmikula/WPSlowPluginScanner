<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function pia_admin_menu() {
    add_plugins_page(
        __( 'Plugin Impact Scanner', 'slow-plugin-scanner' ),
        __( 'Scan Plugins', 'slow-plugin-scanner' ),
        'manage_options',
        'pia-scan-plugins',
        'pia_render_admin_page'
    );
}

add_action( 'wp_ajax_pia_start_scan', 'pia_ajax_start_scan' );
add_action( 'wp_ajax_pia_poll_scan', 'pia_ajax_poll_scan' );
add_action( 'wp_ajax_pia_cancel_scan', 'pia_ajax_cancel_scan' );
add_action( 'wp_ajax_pia_toggle_telemetry', 'pia_ajax_toggle_telemetry' );

function pia_ajax_toggle_telemetry() {
    check_ajax_referer( 'pia_scan_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Permission denied' ) );
    }

    $enabled = isset( $_POST['enabled'] ) ? (bool) $_POST['enabled'] : false;
    pia_set_telemetry_enabled( $enabled );

    if ( $enabled ) {
        pia_schedule_telemetry_cron();
    } else {
        pia_unschedule_telemetry_cron();
    }

    wp_send_json_success();
}

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

function pia_get_published_pages() {
    $pages = get_posts(
        array(
            'post_type'   => 'page',
            'post_status' => 'publish',
            'numberposts' => -1,
        )
    );
    return $pages ? $pages : array();
}

function pia_render_admin_page() {
    $results     = pia_get_last_scan_results();
    $default_url = isset( $results['url'] ) ? esc_url( $results['url'] ) : esc_url( home_url() );
    $is_premium  = pia_is_premium();
    $premium_url = pia_get_premium_url();
    $free_limit  = pia_get_free_limit();
    $show_upgrade = ! $is_premium && ! empty( $premium_url );
    $pages = pia_get_published_pages();
    $home_url = home_url();
    $telemetry_enabled = pia_is_telemetry_enabled();
    $supabase_configured = defined( 'PIA_SUPABASE_URL' ) && ! empty( PIA_SUPABASE_URL );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Plugin Impact Scanner', 'slow-plugin-scanner' ); ?></h1>
        <?php if ( $is_premium ) { ?>
            <div class="notice notice-info"><p><?php esc_html_e( 'Premium Mode - Unlimited scanning enabled.', 'slow-plugin-scanner' ); ?></p></div>
        <?php } else { ?>
            <div class="notice notice-info"><p><?php echo esc_html( sprintf( __( 'Free Mode - Limited to %d plugins per scan.', 'slow-plugin-scanner' ), $free_limit ) ); ?></p></div>
        <?php } ?>

        <?php if ( $supabase_configured ) { ?>
        <div id="pia-telemetry-settings" class="notice notice-info">
            <p>
                <label for="pia-telemetry-toggle">
                    <input type="checkbox" id="pia-telemetry-toggle" <?php checked( $telemetry_enabled ); ?> />
                    <?php esc_html_e( 'Share anonymous plugin performance data to help build a shared plugin compatibility database.', 'slow-plugin-scanner' ); ?>
                </label>
            </p>
            <p class="description">
                <?php esc_html_e( 'Data sent: plugin slug, performance delta, PHP version, WordPress version. No personally identifiable information is collected.', 'slow-plugin-scanner' ); ?>
            </p>
        </div>
        <?php } else { ?>
            <div class="notice notice-warning">
                <p><?php esc_html_e( 'Telemetry not configured. Add PIA_SUPABASE_URL and PIA_SUPABASE_ANON_KEY to your .env file to enable anonymous data sharing.', 'slow-plugin-scanner' ); ?></p>
            </div>
        <?php } ?>
        <p><?php esc_html_e( 'Run a safe loopback scan to identify the single plugin causing slowdown or breakage on a specific page.', 'slow-plugin-scanner' ); ?></p>

        <div id="pia-scan-controls">
            <p>
                <label for="pia_page_select"><?php esc_html_e( 'Page to scan', 'slow-plugin-scanner' ); ?></label>
                <select id="pia_page_select" class="regular-text">
                    <option value="<?php echo esc_attr( $home_url ); ?>" selected><?php esc_html_e( 'Homepage', 'slow-plugin-scanner' ); ?></option>
                    <?php
                    foreach ( $pages as $page ) {
                        $page_url   = get_permalink( $page->ID );
                        $page_title = $page->post_title;
                        if ( $is_premium ) {
                            ?>
                            <option value="<?php echo esc_attr( $page_url ); ?>"><?php echo esc_html( $page_title ); ?></option>
                            <?php
                        } else {
                            ?>
                            <option value="<?php echo esc_attr( $page_url ); ?>" disabled><?php echo esc_html( $page_title ); ?> (<?php esc_html_e( 'Pro', 'slow-plugin-scanner' ); ?>)</option>
                            <?php
                        }
                    }
                    if ( $is_premium ) {
                        ?>
                        <option value="custom"><?php esc_html_e( 'Custom URL', 'slow-plugin-scanner' ); ?></option>
                        <?php
                    }
                ?>
                </select>
                <input type="url" id="pia_scan_url" value="<?php echo esc_attr( $default_url ); ?>" class="regular-text" style="display:none;" />
                <?php if ( ! $is_premium ) { ?>
                    <span class="description"> (<?php esc_html_e( 'Free mode limited to homepage', 'slow-plugin-scanner' ); ?>)</span>
                <?php } ?>
            </p>
            <?php if ( $show_upgrade ) { ?>
                <p>
                    <a href="<?php echo esc_url( $premium_url ); ?>" target="_blank" class="button button-primary"><?php esc_html_e( 'Upgrade to Pro', 'slow-plugin-scanner' ); ?></a>
                    <span class="description"><?php esc_html_e( 'Scan unlimited plugins on any page', 'slow-plugin-scanner' ); ?></span>
                </p>
            <?php } else { ?>
                <p>
                    <button type="button" id="pia-scan-btn" class="button button-primary"><?php esc_html_e( 'Scan Plugins', 'slow-plugin-scanner' ); ?></button>
                    <button type="button" id="pia-cancel-btn" class="button" style="display:none;"><?php esc_html_e( 'Cancel', 'slow-plugin-scanner' ); ?></button>
                </p>
            <?php } ?>
        </div>

        <div id="pia-progress" style="display:none;">
            <p><?php esc_html_e( 'Scanning...', 'slow-plugin-scanner' ); ?></p>
            <progress id="pia-progress-bar" value="0" max="100"></progress>
            <p id="pia-progress-text"></p>
        </div>

        <div id="pia-message-area"></div>

        <div id="pia-results-area"<?php echo empty( $results ) || ! isset( $results['baseline'] ) ? ' style="display:none;"' : ''; ?>>
            <h2><?php esc_html_e( 'Scan Results', 'slow-plugin-scanner' ); ?></h2>
            <?php if ( ! empty( $results ) && isset( $results['baseline'] ) ) { ?>
                <p><strong><?php esc_html_e( 'URL:', 'slow-plugin-scanner' ); ?></strong> <?php echo esc_html( $results['url'] ); ?></p>
                <p><strong><?php esc_html_e( 'Baseline status:', 'slow-plugin-scanner' ); ?></strong> <?php echo esc_html( $results['baseline']['status'] ); ?></p>
                <p><strong><?php esc_html_e( 'Baseline time:', 'slow-plugin-scanner' ); ?></strong> <?php echo esc_html( round( $results['baseline']['time'], 3 ) ); ?>s</p>
                <?php if ( ! empty( $results['errors'] ) ) { ?>
                    <div class="notice notice-warning"><p><?php echo esc_html( implode( ' ', $results['errors'] ) ); ?></p></div>
                <?php } ?>

                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Plugin', 'slow-plugin-scanner' ); ?></th>
                            <th><?php esc_html_e( 'Impact', 'slow-plugin-scanner' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'slow-plugin-scanner' ); ?></th>
                            <th><?php esc_html_e( 'Delta', 'slow-plugin-scanner' ); ?></th>
                            <th><?php esc_html_e( 'Output Change', 'slow-plugin-scanner' ); ?></th>
                            <th><?php esc_html_e( 'Error', 'slow-plugin-scanner' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $results['plugins'] as $plugin ) { ?>
                            <tr>
                                <td><?php echo esc_html( $plugin['name'] ); ?></td>
                                <td><?php echo esc_html( $plugin['impact'] ); ?></td>
                                <td><?php echo esc_html( $plugin['status'] ); ?></td>
                                <td><?php echo esc_html( $plugin['delta'] ); ?>s</td>
                                <td><?php echo $plugin['hash_changed'] ? esc_html__( 'Yes', 'slow-plugin-scanner' ) : esc_html__( 'No', 'slow-plugin-scanner' ); ?></td>
                                <td><?php echo esc_html( $plugin['error'] ); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <?php if ( ! empty( $results['truncated'] ) && $show_upgrade ) { ?>
                    <div class="notice notice-warning">
                        <p>
                            <?php
                            $remaining = $results['active_count'] - $results['scanned'];
                            echo esc_html( sprintf(
                                __( 'Free mode limited to %1$d plugins. %2$d more plugins were not scanned.', 'slow-plugin-scanner' ),
                                $results['scanned'],
                                $remaining
                            ) );
                            ?>
                            <a href="<?php echo esc_url( $premium_url ); ?>" target="_blank"><?php esc_html_e( 'Upgrade to Pro', 'slow-plugin-scanner' ); ?></a>
                            <?php esc_html_e( ' to scan all plugins.', 'slow-plugin-scanner' ); ?>
                        </p>
                    </div>
                <?php } elseif ( ! empty( $results['truncated'] ) ) { ?>
                    <p><?php esc_html_e( 'The plugin list was limited for speed. Only the first few active plugins were tested.', 'slow-plugin-scanner' ); ?></p>
                <?php } ?>
            <?php } ?>
        </div>
    </div>
    <?php
}