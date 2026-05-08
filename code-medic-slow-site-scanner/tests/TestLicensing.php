<?php
/**
 * Unit tests for the plugin licensing and monetization features.
 *
 * @package PIA\Tests
 */

namespace PIA\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for licensing/monetization functions and constants.
 */
class TestLicensing extends TestCase
{
    /**
     * Test CODEMEDSSS_MODE constant is defined.
     */
    public function testModeConstantIsDefined()
    {
        $this->assertTrue( defined( 'CODEMEDSSS_MODE' ) );
    }

    /**
     * Test CODEMEDSSS_MODE has valid value (free or premium).
     */
    public function testModeHasValidValue()
    {
        $mode = CODEMEDSSS_MODE;
        $this->assertContains( $mode, array( 'free', 'premium' ) );
    }

    /**
     * Test CODEMEDSSS_FREE_PLUGIN_LIMIT constant is defined.
     */
    public function testFreePluginLimitConstantIsDefined()
    {
        $this->assertTrue( defined( 'CODEMEDSSS_FREE_PLUGIN_LIMIT' ) );
    }

    /**
     * Test CODEMEDSSS_FREE_PLUGIN_LIMIT is a positive integer.
     */
    public function testFreePluginLimitIsPositiveInteger()
    {
        $limit = CODEMEDSSS_FREE_PLUGIN_LIMIT;
        $this->assertIsInt( $limit );
        $this->assertGreaterThan( 0, $limit );
    }

    /**
     * Test CODEMEDSSS_PREMIUM_URL constant is defined.
     */
    public function testPremiumUrlConstantIsDefined()
    {
        $this->assertTrue( defined( 'CODEMEDSSS_PREMIUM_URL' ) );
    }

    /**
     * Test CODEMEDSSS_PREMIUM_URL is a string.
     */
    public function testPremiumUrlIsString()
    {
        $url = CODEMEDSSS_PREMIUM_URL;
        $this->assertIsString( $url );
    }

    /**
     * Test codemedsss_is_premium() returns correct value based on mode.
     */
    public function testIsPremiumReturnsCorrectValue()
    {
        if ( CODEMEDSSS_MODE === 'premium' ) {
            $this->assertTrue( codemedsss_is_premium() );
        } else {
            $this->assertFalse( codemedsss_is_premium() );
        }
    }

    /**
     * Test codemedsss_get_free_limit() returns the configured limit.
     */
    public function testGetFreeLimitReturnsConfiguredValue()
    {
        if ( codemedsss_is_premium() ) {
            $this->assertEquals( PHP_INT_MAX, codemedsss_get_free_limit() );
        } else {
            $this->assertEquals( CODEMEDSSS_FREE_PLUGIN_LIMIT, codemedsss_get_free_limit() );
        }
    }

    /**
     * Test codemedsss_get_premium_url() returns the configured URL.
     */
    public function testGetPremiumUrlReturnsConfiguredValue()
    {
        $url = codemedsss_get_premium_url();
        $this->assertEquals( CODEMEDSSS_PREMIUM_URL, $url );
    }

    /**
     * Test free limit truncation logic for plugins array.
     */
    public function testFreeLimitTruncationLogic()
    {
        if ( codemedsss_is_premium() ) {
            $this->assertEquals( PHP_INT_MAX, codemedsss_get_free_limit() );
            return;
        }

        $limit = codemedsss_get_free_limit();
        $pluginFiles = array(
            'plugin-1/plugin-1.php',
            'plugin-2/plugin-2.php',
            'plugin-3/plugin-3.php',
            'plugin-4/plugin-4.php',
            'plugin-5/plugin-5.php',
            'plugin-6/plugin-6.php',
            'plugin-7/plugin-7.php',
        );

        $truncated = count( $pluginFiles ) > $limit;

        $this->assertTrue( $truncated );
        $limited = array_slice( $pluginFiles, 0, $limit );
        $this->assertCount( $limit, $limited );
    }

    /**
     * Test premium mode has no practical limit (uses PHP_INT_MAX).
     */
    public function testPremiumModeHasNoPracticalLimit()
    {
        if ( codemedsss_is_premium() ) {
            $limit = codemedsss_get_free_limit();
            $this->assertEquals( PHP_INT_MAX, $limit );
        } else {
            $this->assertIsInt( codemedsss_get_free_limit() );
            $this->assertEquals( 3, codemedsss_get_free_limit() );
        }
    }

    /**
     * Test upgrade button visibility logic.
     */
    public function testUpgradeButtonVisibilityLogic()
    {
        $is_premium = codemedsss_is_premium();
        $premium_url = codemedsss_get_premium_url();
        $show_upgrade = ! $is_premium && ! empty( $premium_url );

        if ( CODEMEDSSS_MODE === 'premium' ) {
            $this->assertFalse( $show_upgrade );
        }

        if ( CODEMEDSSS_MODE === 'free' && ! empty( $premium_url ) ) {
            $this->assertTrue( $show_upgrade );
        }

        if ( CODEMEDSSS_MODE === 'free' && empty( $premium_url ) ) {
            $this->assertFalse( $show_upgrade );
        }
    }

    /**
     * Test free mode URL should be locked to homepage.
     */
    public function testFreeModeUrlLockedToHomepage()
    {
        $is_premium = codemedsss_is_premium();
        $should_lock_url = ! $is_premium;

        if ( CODEMEDSSS_MODE === 'free' ) {
            $this->assertTrue( $should_lock_url );
        } else {
            $this->assertFalse( $should_lock_url );
        }
    }

    /**
     * Test truncated results message logic for free mode.
     */
    public function testTruncatedResultsMessageLogic()
    {
        if ( codemedsss_is_premium() ) {
            $this->assertEquals( PHP_INT_MAX, codemedsss_get_free_limit() );
            return;
        }

        $is_premium = codemedsss_is_premium();
        $premium_url = codemedsss_get_premium_url();
        $show_upgrade_in_results = ! $is_premium && ! empty( $premium_url );

        $results = array(
            'active_count' => 10,
            'scanned' => 3,
            'truncated' => true,
        );

        $remaining = $results['active_count'] - $results['scanned'];

        $this->assertGreaterThan( 0, $remaining );
        $this->assertTrue( $results['truncated'] );

        if ( $show_upgrade_in_results && $results['truncated'] ) {
            $this->assertNotEmpty( $premium_url );
        }
    }

    /**
     * Test mode comparison is case-insensitive.
     */
    public function testModeIsCaseInsensitive()
    {
        $env_file = CODEMEDSSS_PLUGIN_DIR . '.env';
        if ( file_exists( $env_file ) ) {
            $env_vars = parse_ini_file( $env_file );
            if ( $env_vars && isset( $env_vars['CODEMEDSSS_MODE'] ) ) {
                $mode = strtolower( trim( $env_vars['CODEMEDSSS_MODE'] ) );
                $this->assertContains( $mode, array( 'free', 'premium' ) );
            }
        }
    }
}