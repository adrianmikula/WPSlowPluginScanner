<?php
/**
 * Basic test to verify the test framework is working.
 *
 * @package PIA\Tests
 */

namespace PIA\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Basic test case to verify PHPUnit setup.
 */
class TestBootstrap extends TestCase
{
    /**
     * Test that constants are defined.
     */
    public function testConstantsAreDefined()
    {
        $this->assertDefined( 'ABSPATH' );
        $this->assertDefined( 'PIA_PLUGIN_FILE' );
        $this->assertDefined( 'PIA_PLUGIN_DIR' );
        $this->assertDefined( 'PIA_TEMP_MU_PLUGIN' );
        $this->assertDefined( 'PIA_SCAN_LOCK_KEY' );
        $this->assertDefined( 'PIA_RESULTS_OPTION' );
        $this->assertDefined( 'PIA_MAX_TEST_PLUGINS' );
    }

    /**
     * Helper to assert a constant is defined.
     */
    private function assertDefined( $name )
    {
        $this->assertTrue(
            defined( $name ),
            "Constant $name should be defined"
        );
    }

    /**
     * Test that PIA_MAX_TEST_PLUGINS is set correctly.
     */
    public function testMaxTestPluginsValue()
    {
        $this->assertEquals( 6, PIA_MAX_TEST_PLUGINS );
    }

    /**
     * Test that WordPress mocking functions exist.
     */
    public function testWordPressMockFunctionsExist()
    {
        $this->assertTrue( function_exists( 'get_option' ) );
        $this->assertTrue( function_exists( 'update_option' ) );
        $this->assertTrue( function_exists( 'get_transient' ) );
        $this->assertTrue( function_exists( 'set_transient' ) );
        $this->assertTrue( function_exists( 'delete_transient' ) );
        $this->assertTrue( function_exists( 'plugin_basename' ) );
        $this->assertTrue( function_exists( 'home_url' ) );
        $this->assertTrue( function_exists( 'esc_url_raw' ) );
        $this->assertTrue( function_exists( 'esc_html' ) );
        $this->assertTrue( function_exists( 'esc_url' ) );
        $this->assertTrue( function_exists( 'sanitize_text_field' ) );
        $this->assertTrue( function_exists( 'wp_unslash' ) );
        $this->assertTrue( function_exists( 'add_query_arg' ) );
        $this->assertTrue( function_exists( 'apply_filters' ) );
    }

    /**
     * Test that plugin functions are loadable.
     */
    public function testPluginFunctionsAreLoadable()
    {
        $this->assertTrue( function_exists( 'pia_get_active_plugin_entries' ) );
        $this->assertTrue( function_exists( 'pia_run_plugin_scan' ) );
        $this->assertTrue( function_exists( 'pia_build_test_url' ) );
        $this->assertTrue( function_exists( 'pia_compute_response_hash' ) );
        $this->assertTrue( function_exists( 'pia_run_test' ) );
        $this->assertTrue( function_exists( 'pia_prepare_temp_mu_plugin' ) );
        $this->assertTrue( function_exists( 'pia_create_mu_plugins_directory' ) );
        $this->assertTrue( function_exists( 'pia_get_last_scan_results' ) );
        $this->assertTrue( function_exists( 'pia_store_scan_results' ) );
        $this->assertTrue( function_exists( 'pia_scan_is_locked' ) );
        $this->assertTrue( function_exists( 'pia_lock_scan' ) );
        $this->assertTrue( function_exists( 'pia_unlock_scan' ) );
        $this->assertTrue( function_exists( 'pia_clear_temp_mu_plugin' ) );
    }
}
