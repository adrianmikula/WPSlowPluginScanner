<?php
/**
 * Unit tests for the plugin toggle functions.
 *
 * @package PIA\Tests
 */

namespace PIA\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for toggle.php functions.
 */
class TestToggle extends TestCase
{
    /**
     * Set up test environment before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $muDir = dirname( PIA_TEMP_MU_PLUGIN );
        if ( ! file_exists( $muDir ) ) {
            @mkdir( $muDir, 0755, true );
        }
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        if ( file_exists( PIA_TEMP_MU_PLUGIN ) ) {
            @unlink( PIA_TEMP_MU_PLUGIN );
        }

        parent::tearDown();
    }

    /**
     * Test pia_create_mu_plugins_directory creates directory.
     */
    public function testCreateMuPluginsDirectory()
    {
        $muDir = WP_CONTENT_DIR . '/mu-plugins';

        if ( file_exists( $muDir ) ) {
            $this->recursiveDelete( $muDir );
        }

        $this->assertFalse( file_exists( $muDir ) );

        pia_create_mu_plugins_directory();

        $this->assertTrue( file_exists( $muDir ) );
        $this->assertTrue( is_dir( $muDir ) );

        $this->recursiveDelete( $muDir );
    }

    /**
     * Test pia_create_mu_plugins_directory doesn't fail if directory exists.
     */
    public function testCreateMuPluginsDirectoryExisting()
    {
        $muDir = WP_CONTENT_DIR . '/mu-plugins';

        if ( ! file_exists( $muDir ) ) {
            mkdir( $muDir, 0755, true );
        }

        pia_create_mu_plugins_directory();

        $this->assertTrue( file_exists( $muDir ) );
    }

    /**
     * Test pia_prepare_temp_mu_plugin creates file with correct content.
     */
    public function testPrepareTempMuPluginCreatesFile()
    {
        $muDir = dirname( PIA_TEMP_MU_PLUGIN );
        if ( ! file_exists( $muDir ) ) {
            mkdir( $muDir, 0755, true );
        }

        if ( file_exists( PIA_TEMP_MU_PLUGIN ) ) {
            unlink( PIA_TEMP_MU_PLUGIN );
        }

        $this->assertFalse( file_exists( PIA_TEMP_MU_PLUGIN ) );

        pia_prepare_temp_mu_plugin();

        $this->assertTrue( file_exists( PIA_TEMP_MU_PLUGIN ) );

        $content = file_get_contents( PIA_TEMP_MU_PLUGIN );

        $this->assertStringContainsString( '<?php', $content );
        $this->assertStringContainsString( 'pia_test', $content );
        $this->assertStringContainsString( 'pia_disable', $content );
        $this->assertStringContainsString( 'pre_option_active_plugins', $content );
        $this->assertStringContainsString( 'pre_site_option_active_sitewide_plugins', $content );
    }

    /**
     * Test pia_prepare_temp_mu_plugin generates valid PHP.
     */
    public function testPrepareTempMuPluginGeneratesValidPhp()
    {
        $muDir = dirname( PIA_TEMP_MU_PLUGIN );
        if ( ! file_exists( $muDir ) ) {
            mkdir( $muDir, 0755, true );
        }

        pia_prepare_temp_mu_plugin();

        $content = file_get_contents( PIA_TEMP_MU_PLUGIN );
        $tempFile = tempnam( sys_get_temp_dir(), 'php_check_' );
        file_put_contents( $tempFile, $content );

        $output = shell_exec( 'php -l ' . escapeshellarg( $tempFile ) . ' 2>&1' );
        unlink( $tempFile );

        $this->assertStringContainsString( 'No syntax errors', $output );
    }

    /**
     * Test the generated MU plugin sanitizes plugin path.
     */
    public function testPrepareTempMuPluginSanitizesPluginPath()
    {
        $muDir = dirname( PIA_TEMP_MU_PLUGIN );
        if ( ! file_exists( $muDir ) ) {
            mkdir( $muDir, 0755, true );
        }

        pia_prepare_temp_mu_plugin();

        $content = file_get_contents( PIA_TEMP_MU_PLUGIN );

        $this->assertStringContainsString( 'preg_replace', $content );
        $this->assertStringContainsString( '[^A-Za-z0-9_\\-\\/.]', $content );
    }

    /**
     * Recursively delete a directory.
     */
    private function recursiveDelete( $dir )
    {
        if ( ! is_dir( $dir ) ) {
            return;
        }
        $items = array_diff( scandir( $dir ), array( '.', '..' ) );
        foreach ( $items as $item ) {
            $path = "$dir/$item";
            if ( is_dir( $path ) ) {
                $this->recursiveDelete( $path );
            } else {
                @unlink( $path );
            }
        }
        @rmdir( $dir );
    }
}