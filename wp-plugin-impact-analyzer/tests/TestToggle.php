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
        
        // Create temp directory for mu-plugins if needed.
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
        // Remove temp file if it exists.
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
        
        // Clean up first.
        if ( file_exists( $muDir ) ) {
            rmdir( $muDir );
        }
        
        $this->assertFalse( file_exists( $muDir ) );
        
        pia_create_mu_plugins_directory();
        
        $this->assertTrue( file_exists( $muDir ) );
        $this->assertTrue( is_dir( $muDir ) );
        
        // Clean up.
        rmdir( $muDir );
    }

    /**
     * Test pia_create_mu_plugins_directory doesn't fail if directory exists.
     */
    public function testCreateMuPluginsDirectoryExisting()
    {
        $muDir = WP_CONTENT_DIR . '/mu-plugins';
        
        // Ensure directory exists.
        if ( ! file_exists( $muDir ) ) {
            mkdir( $muDir, 0755, true );
        }
        
        // Should not throw exception.
        pia_create_mu_plugins_directory();
        
        $this->assertTrue( file_exists( $muDir ) );
    }

    /**
     * Test pia_prepare_temp_mu_plugin creates file with correct content.
     */
    public function testPrepareTempMuPluginCreatesFile()
    {
        // Ensure directory exists.
        $muDir = dirname( PIA_TEMP_MU_PLUGIN );
        if ( ! file_exists( $muDir ) ) {
            mkdir( $muDir, 0755, true );
        }
        
        // Remove any existing file.
        if ( file_exists( PIA_TEMP_MU_PLUGIN ) ) {
            unlink( PIA_TEMP_MU_PLUGIN );
        }
        
        $this->assertFalse( file_exists( PIA_TEMP_MU_PLUGIN ) );
        
        pia_prepare_temp_mu_plugin();
        
        $this->assertTrue( file_exists( PIA_TEMP_MU_PLUGIN ) );
        
        $content = file_get_contents( PIA_TEMP_MU_PLUGIN );
        
        // Verify key parts of the generated MU plugin content.
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
        // Ensure directory exists.
        $muDir = dirname( PIA_TEMP_MU_PLUGIN );
        if ( ! file_exists( $muDir ) ) {
            mkdir( $muDir, 0755, true );
        }
        
        pia_prepare_temp_mu_plugin();
        
        // Check PHP syntax.
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
        // Ensure directory exists.
        $muDir = dirname( PIA_TEMP_MU_PLUGIN );
        if ( ! file_exists( $muDir ) ) {
            mkdir( $muDir, 0755, true );
        }
        
        pia_prepare_temp_mu_plugin();
        
        $content = file_get_contents( PIA_TEMP_MU_PLUGIN );
        
        // Verify that the preg_replace sanitization is present.
        $this->assertStringContainsString( 'preg_replace', $content );
        $this->assertStringContainsString( '[^A-Za-z0-9_\\-\\/.]', $content );
    }
}
