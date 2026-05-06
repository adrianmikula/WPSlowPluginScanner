# CodeMedic Slow Site Scanner

WordPress plugin to detect the single plugin causing slowdown or breakage on a specific page using safe loopback tests.

## Features

- **Safe Loopback Testing** - Tests each plugin individually without affecting visitors
- **Page Selection** - Choose which page to scan from a dropdown of all published pages
- **Free Mode** - Limited scanning with upgrade prompts to Gumroad
- **Premium Mode** - Unlimited plugins, all published pages, and custom URL support
- **Anonymous Telemetry** - Opt-in data sharing to build a shared plugin performance database

## Free vs Premium

| Feature | Free | Premium |
|---------|------|---------|
| Plugin limit | 3 (configurable) | Unlimited |
| URL options | Homepage only | Any URL |
| Upgrade prompt | Yes (Gumroad link) | No |

### Building Free/Premium Versions

This plugin supports building separate free and premium ZIPs from the same source code. The build mode is configured via the `.env` file in the plugin directory.

#### Configuration

Edit the `.env` file in the `code-medic-slow-site-scanner/` directory:

```ini
# Build mode: "free" or "premium"
PIA_MODE=free

# Gumroad product URL for upgrade link (premium mode hides this)
PIA_PREMIUM_URL=https://gumroad.com/l/your-product
```

#### Building the ZIP

Run the build script from the project root:

```bash
./scripts/build.sh
```

This creates `build/code-medic-slow-site-scanner-${MODE}.zip` with the configured mode baked in.

- **Free version**: Set `PIA_MODE=free` in `.env` before building
- **Premium version**: Set `PIA_MODE=premium` in `.env` before building

### Anonymous Telemetry

When enabled (default), the plugin shares anonymous performance data to help build a shared plugin compatibility database. Users can opt-out via the plugin settings.

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

The scanner includes a dropdown that lists all published pages on your WordPress site:

- **Free users**: Only the Homepage option is enabled. Other pages show as "Pro" and require an upgrade.
- **Premium users**: All published pages are available, plus a "Custom URL" option for scanning any URL.

## Configuration

Settings are configured via a `.env` file in the plugin directory:

```ini
# Build mode: "free" or "premium"
# - free: Limited scans with upgrade prompts to Gumroad
# - premium: Full functionality, no limits
PIA_MODE=free

# Number of plugins to scan in free mode (premium has unlimited)
PIA_FREE_PLUGIN_LIMIT=3

# Gumroad product URL for upgrade link
PIA_PREMIUM_URL=https://gumroad.com/l/your-product

# Supabase configuration for anonymous telemetry (optional)
PIA_SUPABASE_URL=         # Your Supabase project URL (e.g., https://xxxxx.supabase.co)
PIA_SUPABASE_ANON_KEY=    # Your Supabase anon/public key
PIA_SUPABASE_TABLE=       # Table name (default: telemetry)
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
- `tests/TestTelemetry.php` - Tests for telemetry.php functions
- `tests/TestLicensing.php` - Tests for licensing/monetization functions

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
