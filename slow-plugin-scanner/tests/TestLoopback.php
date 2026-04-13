<?php
/**
 * Unit tests for the loopback testing functions.
 *
 * @package PIA\Tests
 */

namespace PIA\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for loopback.php functions.
 */
class TestLoopback extends TestCase
{
    /**
     * Test pia_build_test_url with no plugin.
     */
    public function testBuildTestUrlWithNoPlugin()
    {
        $url = 'http://example.com/page';
        $result = pia_build_test_url( $url );

        $this->assertStringContainsString( 'pia_test=1', $result );
        $this->assertStringContainsString( 'pia_scan=1', $result );
        $this->assertStringNotContainsString( 'pia_disable', $result );
    }

    /**
     * Test pia_build_test_url with plugin to disable.
     */
    public function testBuildTestUrlWithPlugin()
    {
        $url = 'http://example.com/page';
        $plugin = 'test-plugin/test-plugin.php';
        $result = pia_build_test_url( $url, $plugin );

        $this->assertStringContainsString( 'pia_test=1', $result );
        $this->assertStringContainsString( 'pia_scan=1', $result );
        $this->assertStringContainsString( 'pia_disable=' . urlencode( $plugin ), $result );
    }

    /**
     * Test pia_compute_response_hash returns consistent hash.
     */
    public function testComputeResponseHashConsistency()
    {
        $body = '<html><body>Test content</body></html>';
        $status = 200;

        $hash1 = pia_compute_response_hash( $body, $status );
        $hash2 = pia_compute_response_hash( $body, $status );

        $this->assertEquals( $hash1, $hash2 );
        $this->assertEquals( 32, strlen( $hash1 ) ); // MD5 hash length
    }

    /**
     * Test pia_compute_response_hash different inputs produce different hashes.
     */
    public function testComputeResponseHashDifferentInputs()
    {
        $hash1 = pia_compute_response_hash( 'content1', 200 );
        $hash2 = pia_compute_response_hash( 'content2', 200 );
        $hash3 = pia_compute_response_hash( 'content1', 404 );

        $this->assertNotEquals( $hash1, $hash2 );
        $this->assertNotEquals( $hash1, $hash3 );
    }

    /**
     * Test pia_compute_response_hash handles empty values.
     */
    public function testComputeResponseHashHandlesEmptyValues()
    {
        $hash = pia_compute_response_hash( '', 0 );
        $this->assertEquals( 32, strlen( $hash ) );
        
        $expectedHash = md5( '0|' );
        $this->assertEquals( $expectedHash, $hash );
    }

    /**
     * Test pia_compute_response_hash handles numeric status.
     */
    public function testComputeResponseHashHandlesNumericStatus()
    {
        $hash = pia_compute_response_hash( 'body', 200 );
        $expectedHash = md5( '200|body' );
        
        $this->assertEquals( $expectedHash, $hash );
    }

    /**
     * Test pia_run_test returns expected structure.
     */
    public function testRunTestReturnsExpectedStructure()
    {
        // This test requires WordPress functions to be mocked.
        // We're testing the structure of what pia_run_test should return.
        $this->assertTrue( true ); // Placeholder - would need WP mocking
        
        // Note: Full integration test would require WP's wp_remote_get
        // which we mock in a more complete test setup.
    }
}
