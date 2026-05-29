# WordPress.org Freemium Structure & Compliance

This document defines the free/premium architecture for this plugin and provides detailed guidance on avoiding the code patterns that WordPress.org reviewers flag as trialware violations.

---

## WordPress.org Guidelines That Apply

### Guideline 5: No Trialware or Locked Features

**Requirement:** Plugins must be fully functional. You may not lock, disable, or limit built-in features behind a license key, trial period, usage limit, time, quota, or any other kind of intended restriction.

- All code hosted on WordPress.org must be free and fully functional.
- Features cannot be locked even if the code is there "just in case the user upgrades."
- The plugin may mention that a separate plugin adds extra features — but that is it.

### Guideline 6: Serviceware

Plugins may connect to an external service only if the service performs real processing that cannot be done locally. The service must be documented in `readme.txt` with Terms of Use and Privacy Policy links.

- **Allowed:** A spam-checker plugin that sends content to an external API for analysis.
- **Not Allowed:** A plugin that calls an external server to verify a license key before unlocking local code.

### Naming Guidelines

Do not use the word "free" in the plugin name or slug. The directory is exclusively for free plugins, so the label is redundant and discouraged.

### Code Standards

- Enqueue JavaScript via `wp_enqueue_script()` and CSS via `wp_enqueue_style()`.
- No inline `<script>` or `<style>` tags in PHP files.
- Use the `admin_enqueue_scripts` hook for admin-only assets.

---

## Our Architecture

### Core Principle

The free version is **self-contained and has no awareness of a premium version**. It is written as if no premium extension exists. Premium features are additive — delivered via a separate plugin distributed outside WordPress.org, not via unlocking hidden code in the free plugin.

### Free Version (WordPress.org)

- Scans the site homepage (`home_url()`) — hardcoded unconditionally.
- Scans all active plugins with no limit.
- Full results display: impact, delta, status change, output change.
- Cancel scan at any time.
- AJAX-based progress display.
- No license checks, no references to locked or restricted features.
- One non-intrusive upsell notice is permitted (Guideline 11) — it must link to an external URL and advertise additive features only. Controlled via the `CODESS_PREMIUM_URL` constant; absent from the WP.org ZIP when the constant is empty.

**Distribution:** WordPress.org plugin directory, slug `code-medic-slow-site-scanner`.

### Premium Version (Separate Distribution)

Built from a separate source that adds the `premium/` folder on top of the free codebase. Distributed outside WordPress.org (own website, Gumroad, etc.).

Additional premium features may include: URL/page selection for scanning, telemetry, advanced reporting, export.

**The `premium/` folder is never included in the WordPress.org ZIP.**

### File Structure

```
code-medic-slow-site-scanner/          # Free ZIP — everything below
├── code-medic-slow-site-scanner.php
├── includes/
│   ├── scanner.php        # Uses home_url() directly — no filter, no condition
│   ├── loopback.php
│   ├── results.php
│   └── toggle.php
├── admin/
│   ├── ui.php             # No page-selection UI, no premium references
│   ├── js/admin.js
│   └── css/admin.css
├── readme.txt
└── uninstall.php

premium/                               # EXCLUDED from free ZIP — premium build only
├── loader.php
├── telemetry.php
└── js/
    └── admin-premium.js
```

---

## Freemium Code Smell Reference

WordPress.org reviewers are trained to look for patterns that suggest the free version is intentionally limited. The following is a comprehensive list of what to check — and avoid — in every file shipped in the free ZIP.

### 1. Comments that signal tiering

Any comment that uses words like "free version", "premium only", "pro", "upgrade", or "locked" in the context of a feature is a direct red flag.

**Bad:**
```php
// Free version only scans homepage
$url = home_url();
```
```php
// No telemetry in free version
```
```php
// Telemetry moved to separate premium module, not loaded in free version
```

**Good:** Just the code, no qualifying comment.
```php
$url = home_url();
```

### 2. Conditional premium loader in the main plugin file

A block that checks for and loads a `premium/` folder is a classic freemium loader pattern. Reviewers know exactly what it means.

**Bad (in the free ZIP's main plugin file):**
```php
// Load premium module if present (additional features only)
if ( file_exists( CODESS_PLUGIN_DIR . 'premium/loader.php' ) ) {
    require_once CODESS_PLUGIN_DIR . 'premium/loader.php';
}
```

**Rule:** This block must not appear in the free ZIP at all. If the premium build needs it, it should be injected during the premium build process, not live in the shared source.

### 3. Filter hooks designed as premium extension points

A filter registered in free code — but never consumed in free code — is a signal that something was deliberately held back.

**Bad (in free scanner.php):**
```php
$url = apply_filters( 'codemedsss_scan_url', home_url() );
```

**Rule:** The free code must not register any filter or action that exists solely to allow premium code to override a feature. If a filter is present, it must serve a legitimate purpose for free users (e.g., developer customisation with a documented use case).

### 4. Unused functions for future-premium features

Functions that exist in free code but are never called — especially ones related to page/URL selection — imply an intentionally dormant feature.

**Bad (in free admin/ui.php):**
```php
function codemedsss_get_published_pages() {
    return get_posts( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
}
```

**Rule:** Remove any function from free code that has no caller in free code.

### 5. UI elements that imply a locked selection

A label like "Page to scan: Homepage" implies the user should be able to choose but cannot. It looks like a disabled dropdown.

**Bad:**
```html
<strong>Page to scan:</strong> Homepage
```

**Rule:** The UI should simply present what the plugin does, with no implied restriction. Remove the label entirely if it serves no purpose beyond naming a locked selection.

### 6. `readme.txt` describing features the free version does not have

If the readme says "Enter the URL of the page you want to test" but the free version has no URL input, a reviewer reading the readme then looking at the code will see a discrepancy that reads as a locked feature.

Similarly, if the readme implies a plugin limit ("tests up to 6 plugins") but the code has no such limit, it implies a tier.

**Rule:** Keep `readme.txt` strictly accurate to what the free version does.

### 7. References to "premium" in uninstall.php

`uninstall.php` ships in the free ZIP. Comments or code that reference premium options signal a tiered product.

**Bad:**
```php
// Clean up premium telemetry options if they exist
delete_option( 'codemedsss_telemetry_enabled' );
```

**Rule:** The free `uninstall.php` should only clean up options that the free version itself creates. If you want to be defensive about leftover premium options, do it silently without any comment that says "premium".

### 8. A `premium/` directory present in the free ZIP

Even if the folder is empty or contains only comments, its presence is enough to trigger a rejection. WordPress.org reviewers will unzip and inspect the directory tree.

**Rule:** The `premium/` folder must be excluded via `.distignore` and verified absent from the built ZIP before every submission.

### 9. Enqueuing scripts that do not exist

Registering a script file path that does not exist in the free build (e.g., `premium/js/admin-premium.js`) will generate PHP notices and looks like dead premium scaffolding.

**Rule:** Only enqueue files that exist in the distributed ZIP.

### 10. The word "free" in plugin name, slug, or comments

Do not use "free version", "(Free)", or "-free" anywhere in the distributed plugin.

### 11. Constants or variables that imply a scan/feature limit

A constant named `MAX_PLUGINS`, `MAX_TEST_PLUGINS`, `PLUGIN_LIMIT`, or similar — even if only used in test code — signals an artificial cap. The test file is excluded from the ZIP, but the constant name itself in the test suite is visible in the public source repository and will be read by reviewers.

**Bad (in tests/bootstrap.php or tests/*.php):**
```php
define( 'CODEMEDSSS_MAX_TEST_PLUGINS', 6 );
```
```php
$limitedPlugins = array_slice( $allPlugins, 0, $maxPlugins );
```

**Rule:** Remove any test that validates a plugin count limit if no such limit exists in production code. Rename test helpers that use limiting language.

### 12. Test methods that test features not in the free version

Test files are excluded from the ZIP but live in the public repo. Test method names like `testUrlValidationLogic()` or `testMaxPluginLimitLogic()` imply the corresponding feature exists (or was removed). Reviewers may inspect the repo.

**Rule:** Remove or rename test methods that reference features not present in the free version (URL selection, plugin count limits, etc.).

### 13. Localised JS data for features not used in free JS

Passing `homeUrl` or other URL data to the free JavaScript via `wp_localize_script()` implies the JS was designed to use it — even if it currently doesn't. It looks like a disabled feature waiting to be unlocked.

**Bad (in main plugin file):**
```php
'homeUrl' => home_url(),
```
...if `homeUrl` is never referenced in `admin/js/admin.js`.

**Rule:** Only localise data that is actively used by the free JavaScript. Remove unused keys.

**Grep check:**
```bash
# Find keys localised to JS but not used in free JS
grep -o "'[a-zA-Z]*'\s*=>" code-medic-slow-site-scanner.php | sed "s/'\|\s*=>\|,//g" | while read key; do
  grep -q "codemedsssData\.$key" admin/js/admin.js || echo "UNUSED localised key: $key"
done
```

### 14. `do_action` and `apply_filters` hooks — acceptable vs. suspicious

Not all hooks are red flags. The distinction:

- **Acceptable:** A hook that serves a genuine developer customisation purpose for free users, with a clear semantic name unrelated to premium features.
  - `do_action( 'codemedsss_plugin_scanned', ... )` — fires after each plugin scan, useful for logging, custom reporting, etc.
  - `apply_filters( 'codemedsss_https_local_ssl_verify', false )` — allows SSL override for local dev environments.
- **Suspicious:** A hook whose name or position implies it exists to let premium override a specific restricted behaviour.
  - `apply_filters( 'codemedsss_scan_url', home_url() )` — only makes sense if something else changes the URL.
  - `apply_filters( 'codemedsss_plugin_limit', 6 )` — the filter name reveals a limit.

**Rule:** Every hook in free code must make sense as a standalone developer tool. If removing the premium module would leave the hook with no conceivable non-premium use, it should not be in the free code.

---

## Pre-Submission Compliance Checklist

Run these grep searches against the free build ZIP before every submission. All should return zero matches.

```bash
DIR="build/code-medic-slow-site-scanner"

# --- Tiering language in comments/strings ---
grep -r "free version" "$DIR"
grep -r "premium only" "$DIR"
grep -r "premium module" "$DIR"
# "upgrade" is allowed only if it appears in an external-link-only upsell notice
# Verify manually that any match is a <a href> pointing outward, not a feature gate
grep -r "upgrade" "$DIR"
grep -r "locked" "$DIR"
grep -r "pro version" "$DIR"
# "Pro version" in upsell notice text is acceptable; grep below catches broader "premium" references
grep -r "premium" "$DIR"   # broad catch-all for anything missed above

# --- Extension point filters implying locked behaviour ---
grep -r "apply_filters.*scan_url" "$DIR"
grep -r "apply_filters.*limit" "$DIR"
grep -r "apply_filters.*max" "$DIR"

# --- Premium POST param handling ---
grep -r "_POST\[.url.\]" "$DIR"

# --- Limit constants anywhere in distributed code ---
grep -ri "MAX_PLUGIN\|PLUGIN_LIMIT\|max_test" "$DIR"

# --- UI implying locked selection ---
grep -r "Page to scan" "$DIR"

# --- Premium folder presence ---
ls "$DIR/premium" 2>/dev/null && echo "FAIL: premium/ found"

# --- readme.txt accuracy ---
grep -i "enter the url" "$DIR/readme.txt"
grep -i "up to [0-9]* plugin" "$DIR/readme.txt"

# --- Unused localised JS keys (run from plugin source dir) ---
grep -o "'[a-zA-Z]*'\s*=>" code-medic-slow-site-scanner.php \
  | sed "s/'//g; s/\s*=>//g" \
  | while read key; do
      grep -q "codemedsssData\.$key" admin/js/admin.js \
        || echo "UNUSED localised key: $key"
    done
```

Also verify manually:
- [ ] `includes/scanner.php` — `$url = home_url();` with no comment and no filter
- [ ] `admin/ui.php` — no `codemedsss_get_published_pages()` function, no "Page to scan:" label
- [ ] `admin/ui.php` — description text does not reference homepage as a limitation
- [ ] `admin/ui.php` — if upsell notice present, it links only to an external URL and uses additive language (no "unlock", "restricted", "limited")
- [ ] `code-medic-slow-site-scanner.php` — no premium loader block, no "free version" comments
- [ ] `uninstall.php` — no "premium" comments
- [ ] `readme.txt` — how-it-works steps match actual free-version UI exactly
- [ ] Plugin name and slug contain no "free"
- [ ] All `do_action`/`apply_filters` hooks in free code have a plausible non-premium use case
- [ ] No test methods reference features or limits absent from production code
- [ ] Run Plugin Check plugin on clean WP install with free ZIP

---

## Build Process

### Free Build (for WordPress.org)

```bash
./scripts/build.sh
```

Output: `build/code-medic-slow-site-scanner-{version}.zip`

Excludes (enforced via `.distignore`):
- `premium/`
- `.env*`
- `tests/`
- `vendor/`
- `composer-setup.php`
- `docs/`
- `.gitignore`, `.distignore`

### Premium Build (separate distribution)

Assembled separately — starts from the free source and overlays the `premium/` folder plus any premium-only modifications to the main plugin file (e.g., the premium loader block).

---

## References

- [WordPress.org Plugin Directory Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
- [Guideline 5: No Trialware](https://developer.wordpress.org/plugins/wordpress-org/plugin-guideline-5-no-trialware-or-locked-features/)
- [Guideline 6: Serviceware](https://developer.wordpress.org/plugins/wordpress-org/plugin-guideline-6-serviceware/)
- [Plugin Check](https://wordpress.org/plugins/plugin-check/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
