# CodeMedic Slow Site Scanner

WordPress plugin to detect the single plugin causing slowdown or breakage on a specific page using safe loopback tests.

## Features

- **Safe Loopback Testing** - Tests each plugin individually without affecting visitors
- **Page Selection** - Choose which page to scan from a dropdown of all published pages
- **Unlimited Plugin Scanning** - Scan all active plugins without limitations
- **Custom URL Support** - Scan any URL on your site
- **Anonymous Telemetry (Premium)** - Opt-in data sharing to build a shared plugin performance database (premium feature)

### Building Free/Premium Versions

A single script run builds both ZIPs:

```bash
./scripts/build.sh
```

Outputs:
- `build/code-medic-slow-site-scanner.zip` — free version
- `build/code-medic-slow-site-scanner-premium.zip` — premium version

For full release steps (version bumping, pre-submission checklist, distribution), see [docs/releasing.md](docs/releasing.md).

### Anonymous Telemetry (Premium Feature)

When enabled (premium only), the plugin shares anonymous performance data to help build a shared plugin compatibility database. Users can opt-out via the plugin settings.

**Data shared:**
- Plugin slugs (anonymized to folder name only)
- Performance delta (in seconds)
- PHP version
- WordPress version

**Data NOT shared:**
- Site URLs
- Plugin configurations
- Any personally identifiable information

The data is queued and sent asynchronously via wp_cron (hourly) to avoid blocking any requests.

### Page Selection

The scanner includes a dropdown that lists all published pages on your WordPress site. All users can:
- Select from all published pages
- Use the "Custom URL" option to scan any URL on their site

## WordPress.org Compliance

This plugin is designed to comply with WordPress.org plugin directory guidelines. The free version is fully functional with no artificial restrictions, while premium features are additional functionality hosted separately.

For detailed information about WordPress.org requirements and our free/premium architecture, see [docs/wordpress_requirements.md](docs/wordpress_requirements.md).

## Configuration

Premium Supabase telemetry is configured via a `.env` file in the plugin directory (see `.env.example`):

```ini
# Supabase configuration for anonymous telemetry (premium only, optional)
CODEMEDSSS_SUPABASE_URL=         # Your Supabase project URL (e.g., https://xxxxx.supabase.co)
CODEMEDSSS_SUPABASE_ANON_KEY=    # Your Supabase anon/public key
CODEMEDSSS_SUPABASE_TABLE=       # Table name (default: telemetry)
```

### Setting up Supabase for Telemetry

1. Create a Supabase project at https://supabase.com
2. Run the SQL scripts in the `sql/` folder (in order):
   - `sql/01-create-tables.sql` - Creates the telemetry table
   - `sql/02-create-indexes.sql` - Creates indexes for performance
   - `sql/03-set-rls-policies.sql` - Enables RLS with secure policies
   - `sql/04-create-views.sql` - Creates analysis views
3. Add your Supabase URL and anon key to the `.env` file

For detailed documentation on the database schema and views, see the SQL scripts in the `sql/` folder.

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
- `tests/TestTelemetry.php` - Tests for telemetry.php functions (premium only)

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

- `code-medic-slow-site-scanner.php` - Main plugin file
- `admin/ui.php` - Admin interface and AJAX handlers
- `admin/js/admin.js` - Frontend JavaScript
- `includes/scanner.php` - Core scanning logic
- `includes/loopback.php` - Loopback testing
- `includes/toggle.php` - Plugin toggle functionality
- `includes/results.php` - Results handling
- `includes/telemetry.php` - Anonymous telemetry collection
