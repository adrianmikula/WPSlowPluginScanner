<?php
/**
 * Syntax validation tests to catch parse errors before runtime.
 *
 * Run with: php vendor/bin/phpunit tests/TestSyntax.php
 *
 * @package PIA\Tests
 */

namespace PIA\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Test cases to validate PHP syntax of all plugin source files.
 */
class TestSyntax extends TestCase
{
    /**
     * Base directory of the plugin (where this test file lives + /..).
     *
     * @var string
     */
    private $baseDir;

    /**
     * List of all PHP source files to validate.
     *
     * @var array
     */
    private static $sourceFiles = array(
        'plugin-impact-analyzer.php',
        'admin/ui.php',
        'includes/scanner.php',
        'includes/toggle.php',
        'includes/loopback.php',
        'includes/results.php',
    );

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = dirname( dirname( __DIR__ ) );
    }

    /**
     * Test all source PHP files have valid syntax.
     *
     * @dataProvider sourceFileProvider
     */
    public function testSourceFileHasValidSyntax( $filePath )
    {
        $fullPath = $this->baseDir . '/wp-plugin-impact-analyzer/' . $filePath;

        $this->assertFileExists( $fullPath, "Source file $filePath should exist at $fullPath" );

        $output = shell_exec( 'php -l ' . escapeshellarg( $fullPath ) . ' 2>&1' );

        $this->assertStringContainsString(
            'No syntax errors',
            $output,
            "File $filePath should have valid PHP syntax. Output: $output"
        );
    }

    /**
     * Data provider for source files.
     */
    public function sourceFileProvider()
    {
        $data = array();
        foreach ( self::$sourceFiles as $file ) {
            $data[] = array( $file );
        }
        return $data;
    }

    /**
     * Test all admin JS files exist and are valid.
     */
    public function testAdminJsExists()
    {
        $jsPath = $this->baseDir . '/wp-plugin-impact-analyzer/admin/js/admin.js';
        $this->assertFileExists( $jsPath, 'admin.js should exist' );
    }

    /**
     * Test generated MU plugin content is valid PHP.
     */
    public function testGeneratedMuPluginIsValidPhp()
    {
        $muDir = dirname( PIA_TEMP_MU_PLUGIN );
        if ( ! file_exists( $muDir ) ) {
            mkdir( $muDir, 0755, true );
        }

        pia_prepare_temp_mu_plugin();

        $this->assertFileExists( PIA_TEMP_MU_PLUGIN );

        $output = shell_exec( 'php -l ' . escapeshellarg( PIA_TEMP_MU_PLUGIN ) . ' 2>&1' );

        $this->assertStringContainsString(
            'No syntax errors',
            $output,
            'Generated MU plugin should have valid PHP syntax. Output: ' . $output
        );
    }
}