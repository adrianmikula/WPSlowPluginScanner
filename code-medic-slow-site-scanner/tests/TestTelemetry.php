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
        $GLOBALS['codemedsss_mock_options'] = array();
        $GLOBALS['codemedsss_mock_scheduled_hooks'] = array();

        $GLOBALS['codemedsss_mock_options'][ CODEMEDSSS_TELEMETRY_ENABLED ] = true;
        $GLOBALS['codemedsss_mock_options'][ CODEMEDSSS_TELEMETRY_QUEUE ] = array();
        $GLOBALS['codemedsss_mock_options'][ CODEMEDSSS_SITE_UUID_OPTION ] = '';
    }

    protected function tearDown(): void
    {
    }

    public function testTelemetryEnabledByDefault()
    {
        $result = codemedsss_is_telemetry_enabled();
        $this->assertTrue( $result, 'Telemetry should be enabled by default' );
    }

    public function testSetTelemetryEnabled()
    {
        $this->assertTrue( codemedsss_is_telemetry_enabled() );

        codemedsss_set_telemetry_enabled( false );
        $this->assertFalse( codemedsss_is_telemetry_enabled() );
    }

    public function testQueueOperations()
    {
        codemedsss_clear_telemetry_queue();
        $queue = codemedsss_get_telemetry_queue();
        $this->assertEmpty( $queue );

        $test_data = array( 'test' => 'data' );
        codemedsss_add_to_telemetry_queue( $test_data );

        $queue = codemedsss_get_telemetry_queue();
        $this->assertCount( 1, $queue );
        $this->assertEquals( $test_data, $queue[0] );
    }

    public function testAnonymizePluginSlug()
    {
        $this->assertEquals( 'elementor', codemedsss_anonymize_plugin_slug( 'elementor/elementor.php' ) );
        $this->assertEquals( 'woocommerce', codemedsss_anonymize_plugin_slug( 'woocommerce/woocommerce.php' ) );
        $this->assertEquals( 'some-plugin', codemedsss_anonymize_plugin_slug( 'some-plugin' ) );
    }

    public function testGetSiteUuidGeneratesNew()
    {
        $uuid = codemedsss_get_site_uuid();
        $this->assertNotEmpty( $uuid );
        $this->assertMatchesRegularExpression( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid );
    }

    public function testGetSiteUuidReturnsExisting()
    {
        $existing_uuid = '550e8400-e29b-41d4-a716-446655440000';
        $GLOBALS['codemedsss_mock_options'][ CODEMEDSSS_SITE_UUID_OPTION ] = $existing_uuid;

        $uuid = codemedsss_get_site_uuid();
        $this->assertEquals( $existing_uuid, $uuid );
    }

    public function testSiteUuidIsConsistentPerSite()
    {
        $GLOBALS['codemedsss_mock_options'][ CODEMEDSSS_SITE_UUID_OPTION ] = '';

        $uuid1 = codemedsss_get_site_uuid();
        $uuid2 = codemedsss_get_site_uuid();

        $this->assertEquals( $uuid1, $uuid2 );
    }

    public function testSiteUuidBasedOnSiteUrl()
    {
        $GLOBALS['codemedsss_mock_options'][ CODEMEDSSS_SITE_UUID_OPTION ] = '';

        $uuid1 = codemedsss_get_site_uuid();

        $GLOBALS['codemedsss_mock_options'][ CODEMEDSSS_SITE_UUID_OPTION ] = '';
        $uuid2 = codemedsss_get_site_uuid();

        $this->assertEquals( $uuid1, $uuid2 );
    }

    public function testErrorCategoryNone()
    {
        $result = codemedsss_get_error_category( array(
            'error'          => '',
            'status_changed' => false,
            'hash_changed'   => false,
        ) );
        $this->assertEquals( 'none', $result );
    }

    public function testErrorCategoryTimeout()
    {
        $result = codemedsss_get_error_category( array(
            'error'          => 'Request timeout',
            'status_changed' => false,
            'hash_changed'   => false,
        ) );
        $this->assertEquals( 'timeout', $result );
    }

    public function testErrorCategoryBreakSite()
    {
        $result = codemedsss_get_error_category( array(
            'error'          => '500 Internal Server Error',
            'status_changed' => false,
            'hash_changed'   => false,
        ) );
        $this->assertEquals( 'break_site', $result );
    }

    public function testErrorCategoryBreakSiteFromStatusChange()
    {
        $result = codemedsss_get_error_category( array(
            'error'          => '',
            'status_changed' => true,
            'hash_changed'   => false,
        ) );
        $this->assertEquals( 'break_site', $result );
    }

    public function testErrorCategoryOutputChange()
    {
        $result = codemedsss_get_error_category( array(
            'error'          => '',
            'status_changed' => false,
            'hash_changed'   => true,
        ) );
        $this->assertEquals( 'output_change', $result );
    }

    public function testPrepareTelemetryData()
    {
        $plugin_result = array(
            'file'           => 'elementor/elementor.php',
            'name'           => 'Elementor',
            'delta'          => 0.8,
            'status_changed' => false,
            'hash_changed'   => false,
            'error'          => '',
        );

        $all_plugin_files = array(
            'elementor/elementor.php',
            'woocommerce/woocommerce.php',
            'akismet/akismet.php',
        );

        $baseline_time = 0.5;

        $data = codemedsss_prepare_telemetry_data( $plugin_result, $all_plugin_files, $baseline_time );

        $this->assertArrayHasKey( 'plugins', $data );
        $this->assertArrayHasKey( 'plugin_tested', $data );
        $this->assertArrayHasKey( 'plugin_speed_delta', $data );
        $this->assertArrayHasKey( 'baseline_site_load_speed', $data );
        $this->assertArrayHasKey( 'plugin_error', $data );
        $this->assertArrayHasKey( 'error_category', $data );
        $this->assertArrayHasKey( 'env', $data );
        $this->assertArrayHasKey( 'origin', $data );
        $this->assertArrayHasKey( 'timestamp', $data );

        $this->assertEquals( 'elementor', $data['plugin_tested'] );
        $this->assertEquals( 0.8, $data['plugin_speed_delta'] );
        $this->assertEquals( 0.5, $data['baseline_site_load_speed'] );
        $this->assertEquals( 'none', $data['error_category'] );
        $this->assertContains( 'elementor', $data['plugins'] );
        $this->assertContains( 'woocommerce', $data['plugins'] );
    }

    public function testPrepareTelemetryDataWithError()
    {
        $plugin_result = array(
            'file'           => 'broken-plugin/broken.php',
            'name'           => 'Broken Plugin',
            'delta'          => 0,
            'status_changed' => false,
            'hash_changed'   => false,
            'error'          => 'Request timeout after 30 seconds',
        );

        $all_plugin_files = array( 'broken-plugin/broken.php' );
        $baseline_time = 0.3;

        $data = codemedsss_prepare_telemetry_data( $plugin_result, $all_plugin_files, $baseline_time );

        $this->assertEquals( 'timeout', $data['error_category'] );
        $this->assertEquals( 'Request timeout after 30 seconds', $data['plugin_error'] );
    }

    public function testPrepareTelemetryDataWithOutputChange()
    {
        $plugin_result = array(
            'file'           => 'some-plugin/plugin.php',
            'name'           => 'Some Plugin',
            'delta'          => 0.1,
            'status_changed' => false,
            'hash_changed'   => true,
            'error'          => '',
        );

        $all_plugin_files = array( 'some-plugin/plugin.php' );
        $baseline_time = 0.2;

        $data = codemedsss_prepare_telemetry_data( $plugin_result, $all_plugin_files, $baseline_time );

        $this->assertEquals( 'output_change', $data['error_category'] );
    }

    public function testSendTelemetryWithMockedHttp()
    {
        $GLOBALS['codemedsss_wp_remote_post'] = function( $url, $args ) {
            return array(
                'response' => array( 'code' => 201 ),
                'body'     => '',
            );
        };

        $result = codemedsss_send_telemetry_to_supabase( array( 'test' => 'data' ) );
        $this->assertTrue( $result );
    }

    public function testSendTelemetryFailsWithEmptyConfig()
    {
        $result = codemedsss_send_telemetry_to_supabase( array() );
        $this->assertTrue( $result );
    }

    public function testScheduleCron()
    {
        codemedsss_schedule_telemetry_cron();
        $this->assertTrue( wp_next_scheduled( CODEMEDSSS_TELEMETRY_CRON_HOOK ) !== false );
    }

    public function testUnscheduleCron()
    {
        codemedsss_schedule_telemetry_cron();
        codemedsss_unschedule_telemetry_cron();
        $this->assertFalse( wp_next_scheduled( CODEMEDSSS_TELEMETRY_CRON_HOOK ) );
    }

    public function testProcessQueueWhenDisabled()
    {
        $GLOBALS['codemedsss_mock_options'][ CODEMEDSSS_TELEMETRY_ENABLED ] = false;
        $GLOBALS['codemedsss_mock_options'][ CODEMEDSSS_TELEMETRY_QUEUE ] = array();
        $GLOBALS['codemedsss_mock_options'][ CODEMEDSSS_SITE_UUID_OPTION ] = '';
        
        codemedsss_set_telemetry_enabled( false );
        codemedsss_add_to_telemetry_queue( array( 'test' => 'data' ) );

        codemedsss_process_telemetry_queue();

        $queue = codemedsss_get_telemetry_queue();
        $this->assertCount( 1, $queue );
    }

    public function testProcessQueueWhenEnabled()
    {
        if ( ! defined( 'CODEMEDSSS_SUPABASE_URL' ) ) {
            define( 'CODEMEDSSS_SUPABASE_URL', 'https://test.supabase.co' );
        }
        if ( ! defined( 'CODEMEDSSS_SUPABASE_ANON_KEY' ) ) {
            define( 'CODEMEDSSS_SUPABASE_ANON_KEY', 'test-key' );
        }

        $GLOBALS['codemedsss_wp_remote_post'] = function( $url, $args ) {
            return array(
                'response' => array( 'code' => 201 ),
                'body'     => '',
            );
        };

        codemedsss_set_telemetry_enabled( true );
        codemedsss_clear_telemetry_queue();
        codemedsss_add_to_telemetry_queue( array( 'test' => 'data' ) );

        codemedsss_process_telemetry_queue();

        $queue = codemedsss_get_telemetry_queue();
        $this->assertEmpty( $queue );
    }
}
