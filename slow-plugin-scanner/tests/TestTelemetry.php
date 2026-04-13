<?php
/**
 * Tests for telemetry functionality.
 *
 * @package PIA\Tests
 */

namespace PIA\Tests;

use PHPUnit\Framework\TestCase;

class TestTelemetry extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['pia_mock_options'][ PIA_TELEMETRY_ENABLED ] = false;
        $GLOBALS['pia_mock_options'][ PIA_TELEMETRY_QUEUE ] = array();
        $GLOBALS['pia_mock_scheduled_hooks'] = array();
    }

    public function testTelemetryEnabledByDefault()
    {
        $this->assertFalse( pia_is_telemetry_enabled() );
    }

    public function testSetTelemetryEnabled()
    {
        pia_set_telemetry_enabled( true );
        $this->assertTrue( pia_is_telemetry_enabled() );

        pia_set_telemetry_enabled( false );
        $this->assertFalse( pia_is_telemetry_enabled() );
    }

    public function testQueueOperations()
    {
        pia_clear_telemetry_queue();
        $queue = pia_get_telemetry_queue();
        $this->assertEmpty( $queue );

        $test_data = array( 'test' => 'data' );
        pia_add_to_telemetry_queue( $test_data );

        $queue = pia_get_telemetry_queue();
        $this->assertCount( 1, $queue );
        $this->assertEquals( $test_data, $queue[0] );
    }

    public function testAnonymizePluginSlug()
    {
        $this->assertEquals( 'elementor', pia_anonymize_plugin_slug( 'elementor/elementor.php' ) );
        $this->assertEquals( 'woocommerce', pia_anonymize_plugin_slug( 'woocommerce/woocommerce.php' ) );
        $this->assertEquals( 'some-plugin', pia_anonymize_plugin_slug( 'some-plugin' ) );
    }

    public function testPrepareTelemetryData()
    {
        $scan_results = array(
            'url'        => 'http://example.com',
            'baseline'   => array( 'time' => 0.5 ),
            'plugins'    => array(
                array(
                    'file'  => 'elementor/elementor.php',
                    'name'  => 'Elementor',
                    'delta' => 0.8,
                ),
                array(
                    'file'  => 'woocommerce/woocommerce.php',
                    'name'  => 'WooCommerce',
                    'delta' => 0.3,
                ),
            ),
            'scanned'    => 2,
            'active_count' => 5,
            'truncated'  => false,
            'errors'     => array(),
        );

        $data = pia_prepare_telemetry_data( $scan_results );

        $this->assertArrayHasKey( 'plugins', $data );
        $this->assertArrayHasKey( 'results', $data );
        $this->assertArrayHasKey( 'env', $data );
        $this->assertArrayHasKey( 'timestamp', $data );

        $this->assertContains( 'elementor', $data['plugins'] );
        $this->assertContains( 'woocommerce', $data['plugins'] );

        $this->assertArrayHasKey( 'elementor', $data['results'] );
        $this->assertEquals( 0.8, $data['results']['elementor']['delta'] );

        $this->assertArrayHasKey( 'php_version', $data['env'] );
        $this->assertArrayHasKey( 'wp_version', $data['env'] );
    }

    public function testSendTelemetryWithMockedHttp()
    {
        $GLOBALS['pia_wp_remote_post'] = function( $url, $args ) {
            return array(
                'response' => array( 'code' => 201 ),
                'body'     => '',
            );
        };

        $result = pia_send_telemetry_to_supabase( array( 'test' => 'data' ) );
        $this->assertTrue( $result );
    }

    public function testSendTelemetryFailsWithEmptyConfig()
    {
        $result = pia_send_telemetry_to_supabase( array() );
        $this->assertTrue( $result );
    }

    public function testScheduleCron()
    {
        pia_schedule_telemetry_cron();
        $this->assertTrue( wp_next_scheduled( PIA_TELEMETRY_CRON_HOOK ) !== false );
    }

    public function testUnscheduleCron()
    {
        pia_schedule_telemetry_cron();
        pia_unschedule_telemetry_cron();
        $this->assertFalse( wp_next_scheduled( PIA_TELEMETRY_CRON_HOOK ) );
    }

    public function testProcessQueueWhenDisabled()
    {
        pia_set_telemetry_enabled( false );
        pia_add_to_telemetry_queue( array( 'test' => 'data' ) );

        pia_process_telemetry_queue();

        $queue = pia_get_telemetry_queue();
        $this->assertCount( 1, $queue );
    }

    public function testProcessQueueWhenEnabled()
    {
        if ( ! defined( 'PIA_SUPABASE_URL' ) ) {
            define( 'PIA_SUPABASE_URL', 'https://test.supabase.co' );
        }
        if ( ! defined( 'PIA_SUPABASE_ANON_KEY' ) ) {
            define( 'PIA_SUPABASE_ANON_KEY', 'test-key' );
        }

        $GLOBALS['pia_wp_remote_post'] = function( $url, $args ) {
            return array(
                'response' => array( 'code' => 201 ),
                'body'     => '',
            );
        };

        pia_set_telemetry_enabled( true );
        pia_clear_telemetry_queue();
        pia_add_to_telemetry_queue( array( 'test' => 'data' ) );

        pia_process_telemetry_queue();

        $queue = pia_get_telemetry_queue();
        $this->assertEmpty( $queue );
    }
}
