<?php
/**
 * PHPUnit Bootstrap file for WP Slow Plugin Scanner tests.
 */

// Define ABSPATH if not already defined.
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
    define( 'WP_CONTENT_DIR', '/tmp/wordpress/wp-content' );
}

if ( ! defined( 'PIA_PLUGIN_DIR' ) ) {
    define( 'PIA_PLUGIN_DIR', __DIR__ . '/../' );
}

if ( ! defined( 'PIA_TELEMETRY_QUEUE' ) ) {
    define( 'PIA_TELEMETRY_QUEUE', 'pia_telemetry_queue' );
}
if ( ! defined( 'PIA_TELEMETRY_ENABLED' ) ) {
    define( 'PIA_TELEMETRY_ENABLED', 'pia_telemetry_optin' );
}
if ( ! defined( 'PIA_TELEMETRY_CRON_HOOK' ) ) {
    define( 'PIA_TELEMETRY_CRON_HOOK', 'pia_send_telemetry_cron' );
}

define( 'PIA_SUPABASE_URL', 'https://test.supabase.co' );
define( 'PIA_SUPABASE_ANON_KEY', 'test-key' );
define( 'PIA_SUPABASE_TABLE', 'telemetry' );
define( 'PIA_SITE_UUID_OPTION', 'pia_site_uuid' );

// Mock WordPress functions that are required.
$GLOBALS['pia_mock_options'] = array();
$GLOBALS['pia_mock_transients'] = array();
$GLOBALS['pia_mock_scheduled_hooks'] = array();

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $option, $default = false ) {
        if ( isset( $GLOBALS['wp_test_get_option'] ) && $option === PIA_RESULTS_OPTION ) {
            $val = $GLOBALS['wp_test_get_option'];
            unset( $GLOBALS['wp_test_get_option'] );
            return $val;
        }
        $val = $GLOBALS['pia_mock_options'][ $option ] ?? $default;
        if ( $option === PIA_RESULTS_OPTION && ! is_array( $val ) ) {
            return array();
        }
        return $val;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    function update_option( $option, $value, $autoload = null ) {
        if ( isset( $GLOBALS['wp_test_update_option'] ) ) {
            $func = $GLOBALS['wp_test_update_option'];
            return $func( $option, $value );
        }
        $GLOBALS['pia_mock_options'][ $option ] = $value;
        return true;
    }
}

if ( ! function_exists( 'delete_option' ) ) {
    function delete_option( $option ) {
        unset( $GLOBALS['pia_mock_options'][ $option ] );
        return true;
    }
}

if ( ! function_exists( 'get_transient' ) ) {
    function get_transient( $transient ) {
        return $GLOBALS['pia_mock_transients'][ $transient ] ?? false;
    }
}

if ( ! function_exists( 'set_transient' ) ) {
    function set_transient( $transient, $value, $expiration = 0 ) {
        $GLOBALS['pia_mock_transients'][ $transient ] = $value;
        return true;
    }
}

if ( ! function_exists( 'delete_transient' ) ) {
    function delete_transient( $transient ) {
        unset( $GLOBALS['pia_mock_transients'][ $transient ] );
        return true;
    }
}

if ( ! function_exists( 'plugin_basename' ) ) {
    function plugin_basename( $file ) {
        return basename( dirname( $file ) ) . '/' . basename( $file );
    }
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
    function plugin_dir_path( $file ) {
        return trailingslashit( dirname( $file ) );
    }
}

if ( ! function_exists( 'trailingslashit' ) ) {
    function trailingslashit( $string ) {
        return rtrim( $string, '/\\' ) . '/';
    }
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
    function wp_mkdir_p( $dir ) {
        if ( ! file_exists( $dir ) ) {
            return mkdir( $dir, 0755, true );
        }
        return true;
    }
}

if ( ! function_exists( 'home_url' ) ) {
    function home_url( $path = '', $scheme = null ) {
        return 'http://example.com' . $path;
    }
}

if ( ! function_exists( 'esc_url_raw' ) ) {
    function esc_url_raw( $url, $protocols = null ) {
        return filter_var( $url, FILTER_SANITIZE_URL );
    }
}

if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $text ) {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $url, $protocols = null, $_context = 'display' ) {
        return filter_var( $url, FILTER_SANITIZE_URL );
    }
}

if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $text, $domain = null ) {
        return esc_html( $text );
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $str ) {
        return trim( strip_tags( $str ) );
    }
}

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) {
        return stripslashes( $value );
    }
}

if ( ! function_exists( 'add_query_arg' ) ) {
    function add_query_arg( ...$args ) {
        $uri = isset( $args[2] ) ? $args[1] : $_SERVER['REQUEST_URI'] ?? '';
        $params = [];
        
        if ( is_array( $args[0] ) ) {
            $params = $args[0];
        } else {
            $params[ $args[0] ] = $args[1] ?? '';
        }
        
        return $uri . '?' . http_build_query( $params );
    }
}

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value, ...$args ) {
        return $value;
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
        return true;
    }
}

if ( ! function_exists( 'admin_url' ) ) {
    function admin_url( $path = '', $scheme = 'admin' ) {
        return 'http://example.com/wp-admin/' . ltrim( $path, '/' );
    }
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
    function wp_create_nonce( $action = -1 ) {
        return 'test_nonce_' . $action;
    }
}

if ( ! function_exists( 'wp_send_json_success' ) ) {
    function wp_send_json_success( $data = null, $status_code = null ) {
        echo json_encode( $data );
        exit;
    }
}

if ( ! function_exists( 'wp_send_json_error' ) ) {
    function wp_send_json_error( $data = null, $status_code = null ) {
        echo json_encode( $data );
        exit;
    }
}

if ( ! function_exists( 'plugins_url' ) ) {
    function plugins_url( $path = '', $plugin = '' ) {
        return 'http://example.com/wp-content/plugins/' . ltrim( $path, '/' );
    }
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
    function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
        return true;
    }
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
    function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
        return true;
    }
}

if ( ! function_exists( 'wp_localize_script' ) ) {
    function wp_localize_script( $handle, $object_name, $l10n ) {
        return true;
    }
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
    function wp_verify_nonce( $nonce, $action = -1 ) {
        return 1;
    }
}

if ( ! function_exists( 'current_user_can' ) ) {
    function current_user_can( $capability, ...$args ) {
        return true;
    }
}

$GLOBALS['pia_mock_options'][ PIA_TELEMETRY_ENABLED ] = false;
$GLOBALS['pia_mock_options'][ PIA_TELEMETRY_QUEUE ] = array();

if ( ! function_exists( 'wp_remote_post' ) ) {
    function wp_remote_post( $url, $args = array() ) {
        return $GLOBALS['pia_wp_remote_post']( $url, $args ) ?? array(
            'response' => array( 'code' => 200 ),
            'body'     => '',
        );
    }
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
    function wp_remote_retrieve_response_code( $response ) {
        return $response['response']['code'] ?? 0;
    }
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
    function wp_remote_retrieve_body( $response ) {
        return $response['body'] ?? '';
    }
}

if ( ! function_exists( 'wp_remote_post' ) ) {
    function wp_remote_post( $url, $args = array() ) {
        return $GLOBALS['pia_wp_remote_post']( $url, $args ) ?? array(
            'response' => array( 'code' => 200 ),
            'body'     => '',
        );
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) {
        return $thing instanceof WP_Error;
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data, $options = 0, $depth = 512 ) {
        return json_encode( $data, $options, $depth );
    }
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
    function wp_next_scheduled( $hook, $args = array() ) {
        return $GLOBALS['pia_mock_scheduled_hooks'][ $hook ] ?? false;
    }
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
    function wp_schedule_event( $timestamp, $recurrence, $hook, $args = array() ) {
        $GLOBALS['pia_mock_scheduled_hooks'][ $hook ] = $timestamp;
        return true;
    }
}

if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
    function wp_clear_scheduled_hook( $hook, $args = array() ) {
        unset( $GLOBALS['pia_mock_scheduled_hooks'][ $hook ] );
        return true;
    }
}

if ( ! function_exists( 'register_activation_hook' ) ) {
    function register_activation_hook( $file, $function ) {
        return true;
    }
}

if ( ! function_exists( 'register_deactivation_hook' ) ) {
    function register_deactivation_hook( $file, $function ) {
        return true;
    }
}

if ( ! function_exists( 'wp_rand' ) ) {
    function wp_rand( $min = 0, $max = 0 ) {
        if ( $min === 0 && $max === 0 ) {
            return mt_rand();
        }
        return mt_rand( $min, $max );
    }
}

if ( ! function_exists( 'check_ajax_referer' ) ) {
    function check_ajax_referer( $action = -1, $query_arg = false, $die = true ) {
        return true;
    }
}

if ( ! function_exists( 'wp_delete_file' ) ) {
    function wp_delete_file( $file ) {
        if ( file_exists( $file ) ) {
            return unlink( $file );
        }
        return false;
    }
}

class WP_Error {
    public function __construct( $code, $message, $data = '' ) {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }
}

if ( ! function_exists( 'get_bloginfo' ) ) {
    function get_bloginfo( $show = '', $filter = 'raw' ) {
        if ( $show === 'version' ) {
            return '6.4.0';
        }
        return '';
    }
}

// Load the plugin files to test.
require_once __DIR__ . '/../slow-plugin-scanner.php';
require_once __DIR__ . '/../includes/results.php';
require_once __DIR__ . '/../includes/loopback.php';
require_once __DIR__ . '/../includes/scanner.php';
require_once __DIR__ . '/../includes/toggle.php';
require_once __DIR__ . '/../includes/telemetry.php';
