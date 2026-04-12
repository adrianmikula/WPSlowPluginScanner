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

// Define plugin constants if not already defined.
if ( ! defined( 'PIA_PLUGIN_FILE' ) ) {
    define( 'PIA_PLUGIN_FILE', dirname( __DIR__ ) . '/plugin-impact-analyzer.php' );
}

if ( ! defined( 'PIA_PLUGIN_DIR' ) ) {
    define( 'PIA_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'PIA_TEMP_MU_PLUGIN' ) ) {
    define( 'PIA_TEMP_MU_PLUGIN', '/tmp/wordpress/wp-content/mu-plugins/pia-temp-disable.php' );
}

if ( ! defined( 'PIA_SCAN_LOCK_KEY' ) ) {
    define( 'PIA_SCAN_LOCK_KEY', 'pia_scan_lock' );
}

if ( ! defined( 'PIA_RESULTS_OPTION' ) ) {
    define( 'PIA_RESULTS_OPTION', 'pia_last_scan' );
}

if ( ! defined( 'PIA_MAX_TEST_PLUGINS' ) ) {
    define( 'PIA_MAX_TEST_PLUGINS', 6 );
}

// Mock WordPress functions that are required.
$GLOBALS['pia_mock_options'] = array();
$GLOBALS['pia_mock_transients'] = array();

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

// Load the plugin files to test.
require_once PIA_PLUGIN_DIR . 'includes/results.php';
require_once PIA_PLUGIN_DIR . 'includes/loopback.php';
require_once PIA_PLUGIN_DIR . 'includes/scanner.php';
require_once PIA_PLUGIN_DIR . 'includes/toggle.php';
