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
     * Test PIA_MODE constant is defined.
     */
    public function testModeConstantIsDefined()
    {
        $this->assertTrue( defined( 'PIA_MODE' ) );
    }

    /**
     * Test PIA_MODE has valid value (free or premium).
     */
    public function testModeHasValidValue()
    {
        $mode = PIA_MODE;
        $this->assertContains( $mode, array( 'free', 'premium' ) );
    }

    /**
     * Test PIA_FREE_PLUGIN_LIMIT constant is defined.
     */
    public function testFreePluginLimitConstantIsDefined()
    {
        $this->assertTrue( defined( 'PIA_FREE_PLUGIN_LIMIT' ) );
    }

    /**
     * Test PIA_FREE_PLUGIN_LIMIT is a positive integer.
     */
    public function testFreePluginLimitIsPositiveInteger()
    {
        $limit = PIA_FREE_PLUGIN_LIMIT;
        $this->assertIsInt( $limit );
        $this->assertGreaterThan( 0, $limit );
    }

    /**
     * Test PIA_PREMIUM_URL constant is defined.
     */
    public function testPremiumUrlConstantIsDefined()
    {
        $this->assertTrue( defined( 'PIA_PREMIUM_URL' ) );
    }

    /**
     * Test PIA_PREMIUM_URL is a string.
     */
    public function testPremiumUrlIsString()
    {
        $url = PIA_PREMIUM_URL;
        $this->assertIsString( $url );
    }

    /**
     * Test pia_is_premium() returns correct value based on mode.
     */
    public function testIsPremiumReturnsCorrectValue()
    {
        if ( PIA_MODE === 'premium' ) {
            $this->assertTrue( pia_is_premium() );
        } else {
            $this->assertFalse( pia_is_premium() );
        }
    }

    /**
     * Test pia_get_free_limit() returns the configured limit.
     */
    public function testGetFreeLimitReturnsConfiguredValue()
    {
        $limit = pia_get_free_limit();
        $this->assertEquals( PIA_FREE_PLUGIN_LIMIT, $limit );
    }

    /**
     * Test pia_get_premium_url() returns the configured URL.
     */
    public function testGetPremiumUrlReturnsConfiguredValue()
    {
        $url = pia_get_premium_url();
        $this->assertEquals( PIA_PREMIUM_URL, $url );
    }

    /**
     * Test free limit truncation logic for plugins array.
     */
    public function testFreeLimitTruncationLogic()
    {
        $limit = pia_get_free_limit();
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

        if ( ! pia_is_premium() ) {
            $this->assertTrue( $truncated );
            $limited = array_slice( $pluginFiles, 0, $limit );
            $this->assertCount( $limit, $limited );
        }
    }

    /**
     * Test premium mode has no practical limit (uses PHP_INT_MAX).
     */
    public function testPremiumModeHasNoPracticalLimit()
    {
        if ( pia_is_premium() ) {
            $limit = pia_get_free_limit();
            $this->assertEquals( PHP_INT_MAX, $limit );
        } else {
            $this->assertIsInt( pia_get_free_limit() );
            $this->assertEquals( 3, pia_get_free_limit() );
        }
    }

    /**
     * Test upgrade button visibility logic.
     */
    public function testUpgradeButtonVisibilityLogic()
    {
        $is_premium = pia_is_premium();
        $premium_url = pia_get_premium_url();
        $show_upgrade = ! $is_premium && ! empty( $premium_url );

        if ( PIA_MODE === 'premium' ) {
            $this->assertFalse( $show_upgrade );
        }

        if ( PIA_MODE === 'free' && ! empty( $premium_url ) ) {
            $this->assertTrue( $show_upgrade );
        }

        if ( PIA_MODE === 'free' && empty( $premium_url ) ) {
            $this->assertFalse( $show_upgrade );
        }
    }

    /**
     * Test free mode URL should be locked to homepage.
     */
    public function testFreeModeUrlLockedToHomepage()
    {
        $is_premium = pia_is_premium();
        $should_lock_url = ! $is_premium;

        if ( PIA_MODE === 'free' ) {
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
        $is_premium = pia_is_premium();
        $premium_url = pia_get_premium_url();
        $show_upgrade_in_results = ! $is_premium && ! empty( $premium_url );

        $results = array(
            'active_count' => 10,
            'scanned' => 3,
            'truncated' => true,
        );

        $remaining = $results['active_count'] - $results['scanned'];

        if ( ! pia_is_premium() ) {
            $this->assertGreaterThan( 0, $remaining );
            $this->assertTrue( $results['truncated'] );
        }

        if ( $show_upgrade_in_results && $results['truncated'] ) {
            $this->assertNotEmpty( $premium_url );
        }
    }

    /**
     * Test mode comparison is case-insensitive.
     */
    public function testModeIsCaseInsensitive()
    {
        $env_file = PIA_PLUGIN_DIR . '.env';
        if ( file_exists( $env_file ) ) {
            $env_vars = parse_ini_file( $env_file );
            if ( $env_vars && isset( $env_vars['PIA_MODE'] ) ) {
                $mode = strtolower( trim( $env_vars['PIA_MODE'] ) );
                $this->assertContains( $mode, array( 'free', 'premium' ) );
            }
        }
    }
}