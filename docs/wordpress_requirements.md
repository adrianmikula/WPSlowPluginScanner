# WordPress.org Plugin Directory Requirements & Architecture

This document outlines the WordPress.org plugin directory guidelines that apply to this plugin, and documents our free/premium architecture that ensures compliance.

---

## WordPress.org Plugin Directory Guidelines

### Guideline 5: No Trialware or Locked Features

**Requirement:** Plugins must be fully functional. You may not lock, disable, or limit built-in features behind a license key, trial period, usage limit, time, quota, or any other kind of intended restriction.

**Key Points:**
- All plugin code hosted on WordPress.org must be free and fully functional
- No features can be locked behind payment or license checks
- Even if locked features are "just in case the user upgrades," this is not allowed
- Plugins may point out which features are available through a separate plugin, but that's it

### Guideline 6: Serviceware

**Requirement:** Plugins may connect to a legitimate external service to perform certain functionality, provided:
- The service performs actual processing on external servers
- The functionality provided cannot be done locally by the plugin
- The service is clearly documented in the readme, including Terms of Use and Privacy Policy links

**Examples:**
- **Allowed:** A "Spam checker" plugin that connects to an external service to check for spam
- **Not Allowed:** A plugin that simply checks a license key to unlock local features

### Naming Guidelines

**Requirement:** The use of names that mention "free" is discouraged since the WordPress.org plugin directory is already for free plugins.

**Key Points:**
- Don't use "free" in the plugin name or slug
- The directory is exclusively for free-to-use plugins
- Adding "(Free)" to the name is unnecessary and discouraged

### Code Standards

**Requirement:** Plugins must use proper WordPress functions for including JavaScript and CSS.

**Key Points:**
- Use `wp_register_script()` and `wp_enqueue_script()` for JavaScript
- Use `wp_register_style()` and `wp_enqueue_style()` for CSS
- Use `wp_add_inline_script()` for inline JavaScript
- Use `wp_add_inline_style()` for inline CSS
- Do not use inline `<script>` or `<style>` tags directly in PHP files
- Use admin enqueue hooks for admin pages: `admin_enqueue_scripts`

---

## Our Free/Premium Architecture

### Core Principle

**The free version hosted on WordPress.org is 100% fully functional with no artificial restrictions.** Premium features are ADDITIONAL functionality, not locked free features.

### Free Version (WordPress.org)

**Features:**
- Unlimited plugin scanning (no limits)
- Any URL/page selection (homepage, published pages, custom URLs)
- Core scanning functionality
- Safe loopback testing
- Results display with impact analysis
- Cancel scan functionality
- AJAX-based progress display

**Distribution:**
- Hosted on WordPress.org plugin directory
- Plugin name: "CodeMedic Slow Site Scanner"
- Plugin slug: "code-medic-slow-site-scanner"
- No premium folder included in the ZIP
- No upgrade prompts in the UI
- No license checks or restrictions

### Premium Version (Separate Distribution)

**Features:**
- All free features PLUS:
- Anonymous telemetry collection
- Advanced reporting capabilities
- Export functionality
- Future additional features (TBD)

**Distribution:**
- Hosted separately (not on WordPress.org)
- Plugin name: "CodeMedic Slow Site Scanner Premium"
- Plugin slug: "code-medic-slow-site-scanner-premium"
- Includes `premium/` folder with additional modules
- Distributed via Gumroad, own website, or other channels

### Build Process

#### Free Build
```bash
# Set mode to free in .env
CODEMEDSSS_MODE=free

# Run build script
./scripts/build.sh
```

**Output:** `build/code-medic-slow-site-scanner.zip`
- Excludes `premium/` folder
- Plugin name: "CodeMedic Slow Site Scanner"
- Plugin slug: "code-medic-slow-site-scanner"
- No premium code included

#### Premium Build
```bash
# Set mode to premium in .env
CODEMEDSSS_MODE=premium

# Run build script
./scripts/build.sh
```

**Output:** `build/code-medic-slow-site-scanner-premium.zip`
- Includes `premium/` folder
- Plugin name: "CodeMedic Slow Site Scanner Premium"
- Plugin slug: "code-medic-slow-site-scanner-premium"
- Contains premium loader and additional modules

### Code Structure

```
code-medic-slow-site-scanner/
├── code-medic-slow-site-scanner.php    # Main plugin file
├── includes/
│   ├── scanner.php                      # Core scanning logic (fully functional)
│   ├── loopback.php                     # Loopback testing
│   ├── results.php                      # Results handling
│   └── toggle.php                       # Plugin toggle functionality
├── admin/
│   ├── ui.php                           # Admin interface (no premium checks)
│   ├── js/admin.js                      # Frontend JavaScript (properly enqueued)
│   └── css/admin.css                    # Admin styles (properly enqueued)
├── premium/                             # EXCLUDED from free build
│   ├── loader.php                       # Premium module loader
│   └── telemetry.php                     # Anonymous telemetry (premium only)
└── readme.txt                           # Plugin readme
```

### Premium Module Loading

The main plugin file conditionally loads the premium module:

```php
// Load premium module if present (additional features only)
if ( file_exists( CODESS_PLUGIN_DIR . 'premium/loader.php' ) ) {
    require_once CODESS_PLUGIN_DIR . 'premium/loader.php';
}
```

**Key Points:**
- Premium folder is optional - plugin works 100% without it
- No license checks or mode detection in core code
- Premium features are truly additional, not locked
- Free version has no knowledge of premium features

### Compliance Verification

**Guideline 5 (No Trialware):**
- ✅ Free version scans unlimited plugins (no 3-plugin limit)
- ✅ Free version allows any URL selection (no homepage-only restriction)
- ✅ No license checks in core code
- ✅ No upgrade prompts in free version UI
- ✅ No locked features behind payment
- ✅ Premium code is completely separate and excluded from free ZIP

**Guideline 6 (Serviceware):**
- ✅ Telemetry is a premium-only feature (not in free version)
- ✅ Telemetry performs actual processing (data collection and transmission)
- ✅ Telemetry functionality cannot be done locally (requires external Supabase)
- ✅ Will be documented in readme with Terms of Use and Privacy Policy links

**Naming Guidelines:**
- ✅ No "(Free)" in plugin name
- ✅ No "-free" in plugin slug
- ✅ Free version uses clean name: "CodeMedic Slow Site Scanner"

**Code Standards:**
- ✅ JavaScript enqueued via `wp_enqueue_script()`
- ✅ CSS enqueued via `wp_enqueue_style()`
- ✅ No inline `<script>` tags
- ✅ Uses `admin_enqueue_scripts` hook

---

## Testing Compliance

Before submitting to WordPress.org:

1. **Test Free Version:**
   - Build with `CODEMEDSSS_MODE=free`
   - Verify premium folder is not in ZIP
   - Verify plugin name does not contain "(Free)"
   - Verify slug does not contain "-free"
   - Test unlimited plugin scanning
   - Test any URL selection
   - Verify no upgrade prompts in UI

2. **Test Premium Version:**
   - Build with `CODEMEDSSS_MODE=premium`
   - Verify premium folder is included in ZIP
   - Verify premium features work when present
   - Verify plugin still works without premium folder

3. **Code Review:**
   - Run Plugin Check plugin
   - Run PHPCS with WPCS
   - Verify no inline scripts or styles
   - Verify proper enqueue functions used

4. **Documentation Review:**
   - Verify readme.txt has no premium upgrade prompts
   - Verify no references to locked features
   - Verify Terms of Use and Privacy Policy links for any external services

---

## References

- [WordPress.org Plugin Directory Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
- [Guideline 5: No Trialware](https://developer.wordpress.org/plugins/wordpress-org/plugin-guideline-5-no-trialware-or-locked-features/)
- [Guideline 6: Serviceware](https://developer.wordpress.org/plugins/wordpress-org/plugin-guideline-6-serviceware/)
- [Plugin Check](https://wordpress.org/plugins/plugin-check/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
