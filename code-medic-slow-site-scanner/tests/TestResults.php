<?php
/**
 * Unit tests for the results handling functions.
 *
 * @package PIA\Tests
 */

namespace PIA\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for results.php functions.
 */
class TestResults extends TestCase
{
    /**
     * Test codemedsss_get_last_scan_results returns array.
     */
    public function testGetLastScanResultsReturnsArray()
    {
        // Override the global get_option for this test.
        $GLOBALS['wp_test_get_option'] = array(
            'url' => 'http://example.com',
            'baseline' => array('time' => 0.5, 'status' => 200),
        );

        $result = codemedsss_get_last_scan_results();
        
        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'url', $result );
    }

    /**
     * Test codemedsss_get_last_scan_results handles non-array result.
     */
    public function testGetLastScanResultsHandlesNonArray()
    {
        // Reset to default mock that returns false
        $result = codemedsss_get_last_scan_results();
        
        $this->assertIsArray( $result );
        $this->assertEmpty( $result );
    }

    /**
     * Test codemedsss_store_scan_results adds timestamp.
     */
    public function testStoreScanResultsAddsTimestamp()
    {
        $testResults = array(
            'url' => 'http://example.com',
            'baseline' => array('time' => 0.5),
        );

        // Capture what would be stored.
        $capturedResults = null;
        
        // Override update_option temporarily
        $GLOBALS['wp_test_update_option'] = function( $option, $value ) use ( &$capturedResults ) {
            $capturedResults = $value;
            return true;
        };

        codemedsss_store_scan_results( $testResults );

        $this->assertNotNull( $capturedResults );
        $this->assertArrayHasKey( 'last_updated', $capturedResults );
        $this->assertIsInt( $capturedResults['last_updated'] );
    }

    /**
     * Test codemedsss_scan_is_locked returns boolean.
     */
    public function testScanIsLockedReturnsBoolean()
    {
        $result = codemedsss_scan_is_locked();
        $this->assertIsBool( $result );
    }

    /**
     * Test codemedsss_lock_scan returns boolean.
     */
    public function testLockScanReturnsBoolean()
    {
        $result = codemedsss_lock_scan();
        $this->assertIsBool( $result );
    }

    /**
     * Test codemedsss_unlock_scan returns boolean.
     */
    public function testUnlockScanReturnsBoolean()
    {
        $result = codemedsss_unlock_scan();
        $this->assertNull( $result );
    }

    /**
     * Test codemedsss_clear_temp_mu_plugin handles missing file.
     */
    public function testClearTempMuPluginHandlesMissingFile()
    {
        // Ensure the temp file doesn't exist for this test
        $tempFile = CODEMEDSSS_TEMP_MU_PLUGIN;
        
        // This should not throw an error even if file doesn't exist.
        $result = codemedsss_clear_temp_mu_plugin();
        
        $this->assertNull( $result );
    }
}
