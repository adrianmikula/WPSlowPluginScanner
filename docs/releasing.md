# Release Process Documentation

This document describes how to build and release the free and premium versions of CodeMedic Slow Site Scanner while maintaining WordPress.org plugin directory compliance.

---

## Architecture Overview

The plugin follows a **clean separation** model:

1. **Free Version (WordPress.org)**: Hosted on WordPress.org plugin directory
   - Only scans the homepage
   - No URL selection UI or API support
   - Core functionality only
   - Fully functional without any upsells or locked features

2. **Premium Version**: Distributed separately (not on WordPress.org)
   - Adds URL scanning capability (any page, custom URL)
   - Provides its own UI/JS files for URL selection
   - Extends via filter hooks - does not modify free code

---

## Free Version Build Process

### Files to Include

```
code-medic-slow-site-scanner/
├── admin/
│   ├── css/
│   │   └── admin.css
│   ├── js/
│   │   └── admin.js          # No URL selection logic
│   └── ui.php                # Hardcoded "Homepage" display
├── includes/
│   ├── loopback.php
│   ├── results.php
│   ├── scanner.php           # Uses home_url() directly
│   └── toggle.php
├── code-medic-slow-site-scanner.php
├── readme.txt
├── uninstall.php
└── icon-256x256.png
```

### Files to Exclude (Premium Only)

```
admin/ui-premium.php          # URL selection UI
premium/                      # Entire premium folder
└── loader.php                # Premium extension loader
└── js/
    └── admin-premium.js      # Premium JS for URL selection
└── telemetry.php             # Telemetry module
```

### Build Script

The free version ZIP should be created with:

```bash
# Exclude premium folder entirely
zip -r codemedic-slow-site-scanner.zip code-medic-slow-site-scanner \
  -x "code-medic-slow-site-scanner/premium/*" \
  -x "code-medic-slow-site-scanner/admin/ui-premium.php"
```

### Verification Checklist

Before submitting to WordPress.org:

- [ ] `includes/scanner.php` uses `home_url()` directly (no filter)
- [ ] `admin/ui.php` displays "Homepage" only (no dropdown)
- [ ] `admin/js/admin.js` doesn't send URL in AJAX request
- [ ] `premium/` folder is NOT included in the ZIP
- [ ] `admin/ui-premium.php` is NOT included in the ZIP
- [ ] No references to "premium", "pro", "upgrade" in free code
- [ ] Plugin works fully without any upsells or locked features

---

## Premium Version Build Process

The premium version is built from a **separate source folder** that contains:

1. All free plugin files
2. Additional premium files that extend functionality

### Premium Files Structure

```
premium-build/
├── code-medic-slow-site-scanner/           # Same as free
│   ├── admin/
│   │   ├── ui.php                          # Free UI (homepage only)
│   │   └── ui-premium.php                  # PREMIUM: URL selection UI
│   ├── includes/
│   │   └── scanner.php                     # Uses filter (allows override)
│   └── premium/
│       ├── loader.php                      # PREMIUM: Extension loader
│       ├── js/
│       │   └── admin-premium.js            # PREMIUM: URL selection JS
│       └── telemetry.php                   # PREMIUM: Telemetry module
```

### Key Differences from Free

| File | Free Version | Premium Version |
|------|-------------|-----------------|
| `scanner.php` | `$url = home_url();` | `$url = apply_filters('codemedsss_scan_url', home_url());` |
| `ui.php` | Shows "Homepage" text | Same (not overridden) |
| `ui-premium.php` | Not included | Included, overrides menu via `premium/loader.php` |
| `admin.js` | No URL in AJAX | Same (premium JS handles URL) |
| `admin-premium.js` | Not included | Included, adds URL selection logic |
| `premium/loader.php` | Not included | Included, loads all premium extensions |

### Premium Loader Function

The premium loader (`premium/loader.php`):

1. Loads `ui-premium.php` which provides URL selection UI
2. Overrides the admin menu to use the premium UI
3. Adds `codemedsss_scan_url` filter that reads `$_POST['url']`
4. Enqueues `admin-premium.js` for URL selection handling

```php
// Hook into scan URL filter to provide custom URLs from premium module
add_filter( 'codemedsss_scan_url', 'codemedsss_premium_scan_url', 10, 1 );

function codemedsss_premium_scan_url( $default_url ) {
    // Check if a custom URL was posted via AJAX
    if ( isset( $_POST['url'] ) && ! empty( $_POST['url'] ) ) {
        return esc_url_raw( wp_unslash( $_POST['url'] ) );
    }
    return $default_url;
}
```

### Premium JavaScript

The premium JS (`premium/js/admin-premium.js`):

1. Adds page selection dropdown event handler
2. Implements `getScanUrl()` to return selected URL
3. Sends URL in AJAX request to backend

```javascript
// Premium overrides the scan functionality
codemedsssScan.getScanUrl = function() {
    var pageSelect = $('#codemedsss_page_select');
    var customUrl = $('#codemedsss_scan_url');

    if (pageSelect.val() === 'custom') {
        return customUrl.val() || codemedsssData.homeUrl;
    }
    return pageSelect.val();
};
```

---

## WordPress.org Compliance

### Guideline 5: No Trialware

**Our Approach:**
- Free version is **genuinely** homepage-only - no artificial restrictions
- URL selection is **added** by premium, not **unlocked** from free
- Free code has no knowledge of premium features
- No license checks, upsells, or feature flags in free code

**What This Means:**
- The free plugin does NOT contain code that checks for premium
- The free plugin does NOT have disabled/hidden features
- Premium functionality is truly additive via separate files

### How the Reviewer Should Verify

1. **Search free code for URL-related terms:**
   ```bash
   grep -r "apply_filters.*codemedsss_scan_url" code-medic-slow-site-scanner/
   grep -r "_POST\[.url.\]" code-medic-slow-site-scanner/
   grep -r "page_select" code-medic-slow-site-scanner/
   ```
   Expected: No matches in free code (only in premium/ folder)

2. **Verify scanner behavior:**
   ```php
   // Free: includes/scanner.php line 38
   $url = home_url();  // Hardcoded, no filter
   ```

3. **Verify UI:**
   ```php
   // Free: admin/ui.php line 121-122
   <strong>Page to scan:</strong> Homepage  // No selection UI
   ```

---

## Distribution Channels

### Free Version
- **Channel:** WordPress.org Plugin Directory
- **Files:** Core plugin only (no premium folder)
- **Updates:** Via WordPress.org
- **License:** GPLv2 or later

### Premium Version
- **Channel:** Separate distribution (website, marketplace, etc.)
- **Files:** Core plugin + premium extensions
- **Updates:** Via custom update mechanism
- **License:** Commercial license

---

## Version Numbering

Both versions share the same version number to maintain compatibility:

- Free: `0.1.0` (WordPress.org)
- Premium: `0.1.0` (with premium extensions)

Future releases should keep versions in sync to avoid confusion.

---

## Testing Before Release

### Free Version Test

1. Install free ZIP on clean WordPress
2. Verify: Only "Homepage" displayed in UI
3. Verify: Scan runs successfully on homepage
4. Verify: No premium-related files exist
5. Verify: No JavaScript errors in console

### Premium Version Test

1. Install premium ZIP on clean WordPress
2. Verify: Page selection dropdown appears
3. Verify: Custom URL input works
4. Verify: Scan runs on selected page
5. Verify: Telemetry module loads (if applicable)

---

## Common Issues to Avoid

### Issue 1: Leaving Premium Code in Free Build
**Symptom:** WordPress.org reviewer finds `premium/` folder in free ZIP.
**Fix:** Ensure build script excludes `premium/` folder.

### Issue 2: Filter Left in Free Code
**Symptom:** Free scanner uses `apply_filters('codemedsss_scan_url', ...)`
**Fix:** Free scanner must use `home_url()` directly.

### Issue 3: UI-Premium.php Left in Free Build
**Symptom:** Bundled premium UI creates "trialware" perception.
**Fix:** Ensure `admin/ui-premium.php` is excluded from free ZIP.

### Issue 4: JavaScript Sends URL Parameter
**Symptom:** Free AJAX sends `{action, nonce, url}` even though backend ignores it.
**Fix:** Free JavaScript should only send `{action, nonce}`.

---

## Build Scripts

### Linux/macOS (scripts/build.sh)

```bash
#!/bin/bash
# Build free version for WordPress.org

PLUGIN_DIR="code-medic-slow-site-scanner"
VERSION=$(grep "Version:" "$PLUGIN_DIR/code-medic-slow-site-scanner.php" | awk '{print $2}')
OUTPUT="build/codemedic-slow-site-scanner-${VERSION}.zip"

# Create build directory
mkdir -p build

# Create ZIP excluding premium files
zip -r "$OUTPUT" "$PLUGIN_DIR" \
  -x "$PLUGIN_DIR/premium/*" \
  -x "$PLUGIN_DIR/admin/ui-premium.php" \
  -x "$PLUGIN_DIR/.env*" \
  -x "$PLUGIN_DIR/tests/*" \
  -x "$PLUGIN_DIR/vendor/*"

echo "Free version built: $OUTPUT"
```

### Windows (scripts/build.ps1)

```powershell
# Build free version for WordPress.org
$pluginDir = "code-medic-slow-site-scanner"
$version = (Select-String -Path "$pluginDir\code-medic-slow-site-scanner.php" -Pattern "Version:\s*(.+)$").Matches.Groups[1].Value
$output = "build\codemedic-slow-site-scanner-$version.zip"

# Create build directory
New-Item -ItemType Directory -Force -Path "build" | Out-Null

# Create ZIP excluding premium files
Compress-Archive -Path "$pluginDir\*" -DestinationPath $output -Force
# Remove premium files from ZIP (manual step or use 7-Zip exclusion)

Write-Host "Free version built: $output"
```

---

## Summary

The free and premium versions are cleanly separated:

- **Free** = Core functionality (homepage scanning only)
- **Premium** = Core + Extensions (URL selection, telemetry, etc.)

This architecture ensures:
1. WordPress.org compliance (no trialware)
2. Free version is genuinely limited (not artificially restricted)
3. Premium adds value without modifying free code
4. Clear separation for reviewers and users
