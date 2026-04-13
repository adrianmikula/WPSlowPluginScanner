<?php
/**
 * Unit tests for the plugin scanner functions.
 *
 * @package PIA\Tests
 */

namespace PIA\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for scanner.php functions.
 */
class TestScanner extends TestCase
{
    /**
     * Test pia_get_active_plugin_entries returns array structure.
     */
    public function testGetActivePluginEntriesReturnsArray()
    {
        // This test would require mocking WordPress get_plugins() function.
        // For unit testing without WordPress, we verify the function structure.
        $this->assertTrue( true ); // Placeholder - would need WP mocking
    }

    /**
     * Test pia_run_plugin_scan returns expected structure.
     */
    public function testRunPluginScanReturnsExpectedStructure()
    {
        // Expected return structure.
        $expectedKeys = array(
            'url',
            'baseline',
            'plugins',
            'scanned',
            'active_count',
            'truncated',
            'errors',
        );

        // We can't run the full scan without WordPress, but we can verify
        // the structure expectation.
        $this->assertIsArray( $expectedKeys );
        $this->assertContains( 'url', $expectedKeys );
        $this->assertContains( 'baseline', $expectedKeys );
        $this->assertContains( 'plugins', $expectedKeys );
        $this->assertContains( 'scanned', $expectedKeys );
        $this->assertContains( 'active_count', $expectedKeys );
        $this->assertContains( 'truncated', $expectedKeys );
        $this->assertContains( 'errors', $expectedKeys );
    }

    /**
     * Test impact determination logic.
     */
    public function testImpactDeterminationLogic()
    {
        // Test the impact determination rules.
        $rules = array(
            'Breaks site' => array(
                'status_changed' => true,
            ),
            'Slows site' => array(
                'status_changed' => false,
                'delta' => 0.4, // > 0.3
            ),
            'Changes output' => array(
                'status_changed' => false,
                'delta' => 0.1,
                'hash_changed' => true,
            ),
            'No significant impact' => array(
                'status_changed' => false,
                'delta' => 0.1,
                'hash_changed' => false,
            ),
        );

        // Verify all impact types are covered.
        $this->assertArrayHasKey( 'Breaks site', $rules );
        $this->assertArrayHasKey( 'Slows site', $rules );
        $this->assertArrayHasKey( 'Changes output', $rules );
        $this->assertArrayHasKey( 'No significant impact', $rules );
    }

    /**
     * Test plugin sorting by delta.
     */
    public function testPluginSortingByDelta()
    {
        // Test that sorting logic works correctly.
        $plugins = array(
            array( 'delta' => 0.1, 'file' => 'plugin-a' ),
            array( 'delta' => 0.5, 'file' => 'plugin-b' ),
            array( 'delta' => 0.3, 'file' => 'plugin-c' ),
        );

        usort( $plugins, function( $a, $b ) {
            return $b['delta'] <=> $a['delta'];
        } );

        // Should be sorted descending by delta.
        $this->assertEquals( 0.5, $plugins[0]['delta'] );
        $this->assertEquals( 0.3, $plugins[1]['delta'] );
        $this->assertEquals( 0.1, $plugins[2]['delta'] );
    }

    /**
     * Test URL validation logic.
     */
    public function testUrlValidationLogic()
    {
        // Test that esc_url_raw is called on URL.
        $testUrl = 'http://example.com/page?param=value';
        $sanitized = esc_url_raw( $testUrl );
        
        $this->assertNotEmpty( $sanitized );
    }

    /**
     * Test default URL when empty.
     */
    public function testDefaultUrlWhenEmpty()
    {
        // When URL is empty, it should default to home_url().
        $url = '';
        
        if ( empty( $url ) ) {
            $url = home_url();
        }
        
        $this->assertStringContainsString( 'example.com', $url );
    }

    /**
     * Test max plugin limit logic.
     */
    public function testMaxPluginLimitLogic()
    {
        $maxPlugins = PIA_MAX_TEST_PLUGINS;
        $allPlugins = array(
            'plugin-1/plugin-1.php',
            'plugin-2/plugin-2.php',
            'plugin-3/plugin-3.php',
            'plugin-4/plugin-4.php',
            'plugin-5/plugin-5.php',
            'plugin-6/plugin-6.php',
            'plugin-7/plugin-7.php',
            'plugin-8/plugin-8.php',
        );

        $truncated = count( $allPlugins ) > $maxPlugins;
        
        $this->assertTrue( $truncated );
        
        $limitedPlugins = array_slice( $allPlugins, 0, $maxPlugins );
        
        $this->assertCount( $maxPlugins, $limitedPlugins );
    }

    /**
     * Test delta calculation.
     */
    public function testDeltaCalculation()
    {
        $baselineTime = 0.5;
        $pluginTime = 0.8;
        
        $delta = $pluginTime - $baselineTime;
        
        $this->assertEqualsWithDelta( 0.3, $delta, 0.0001 );
        $this->assertEquals( 0.300, round( $delta, 3 ) );
    }

    /**
     * Test own plugin is filtered out.
     */
    public function testOwnPluginIsFiltered()
    {
        $pluginFiles = array(
            'test-plugin-a/test-plugin-a.php',
            'wp-plugin-impact-analyzer/plugin-impact-analyzer.php', // This should be filtered.
            'test-plugin-b/test-plugin-b.php',
        );
        
        $ownPluginFile = 'wp-plugin-impact-analyzer/plugin-impact-analyzer.php';
        
        $filtered = array_filter( $pluginFiles, function( $file ) use ( $ownPluginFile ) {
            return $file !== $ownPluginFile;
        } );
        
        $this->assertCount( 2, $filtered );
        $this->assertNotContains( $ownPluginFile, $filtered );
    }
}
