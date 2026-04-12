# WP Slow Plugin Scanner

WordPress plugin to detect the single plugin causing slowdown or breakage on a specific page using safe loopback tests.

## Testing

This plugin uses PHPUnit for unit testing.

### Setup

1. Install dependencies:
```bash
composer install
```

2. Create the WordPress test environment (one-time setup):
```bash
mkdir -p /tmp/wordpress
# You'll need to copy or link your WordPress installation here
# or use a proper WordPress test environment like wp-env
```

3. Run the tests:
```bash
./vendor/bin/phpunit
```

### Test Structure

- `tests/bootstrap.php` - Test bootstrap file with WordPress function mocks
- `tests/TestBootstrap.php` - Basic test to verify setup
- `tests/TestResults.php` - Tests for results.php functions
- `tests/TestLoopback.php` - Tests for loopback.php functions
- `tests/TestScanner.php` - Tests for scanner.php functions
- `tests/TestToggle.php` - Tests for toggle.php functions

### Writing Tests

Each test class should:
1. Be in the `PIA\Tests` namespace
2. Extend `PHPUnit\Framework\TestCase`
3. Test one specific file's functions

Example:
```php
<?php
namespace PIA\Tests;

use PHPUnit\Framework\TestCase;

class TestExample extends TestCase
{
    public function testSomething()
    {
        $this->assertTrue( true );
    }
}
```

## Plugin Structure

- `plugin-impact-analyzer.php` - Main plugin file
- `admin/ui.php` - Admin interface
- `includes/scanner.php` - Core scanning logic
- `includes/loopback.php` - Loopback testing
- `includes/toggle.php` - Plugin toggle functionality
- `includes/results.php` - Results handling
