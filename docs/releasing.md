# Release Process

How to build and release the free and premium versions of CodeMedic Slow Site Scanner.

---

## Architecture

- **Free Version (WordPress.org):** Self-contained. Scans the homepage. No premium references, no extension points, no locked features. Fully functional as-is.
- **Premium Version:** Assembled separately. Overlays the `premium/` folder and any premium-only changes on top of the free source. Distributed outside WordPress.org.

See [`wordpress_freemium_structure.md`](wordpress_freemium_structure.md) for the full architecture and code-smell guidance.

---

## Building

A single script run produces both ZIPs:

```bash
./scripts/build.sh
```

Outputs:
- `build/code-medic-slow-site-scanner.zip` — free version
- `build/code-medic-slow-site-scanner-premium.zip` — premium version

The build script excludes:
- `premium/` — entire folder (free build only)
- `.env*`, `tests/`, `vendor/`, `composer-setup.php`, `docs/`

If a `.env` file is present, non-`CODEMEDSSS_MODE` constants (e.g. Supabase keys) are baked into a `config.php` inside the premium ZIP only.

---

## Version Bump

Before building a release, update the version number in:

1. `code-medic-slow-site-scanner/code-medic-slow-site-scanner.php` — plugin header `Version:`, `CODESS_SCANNER_ENGINE_VERSION` constant, and asset enqueue versions
2. `code-medic-slow-site-scanner/readme.txt` — `Stable tag:`, and add a changelog entry under `== Changelog ==`

---

## Pre-Submission Checklist

**Freemium code smells** — the most common rejection reasons. Run against the built ZIP. See [`wordpress_freemium_structure.md`](wordpress_freemium_structure.md) for detailed explanations of each.

```bash
grep -r "free version" build/
grep -r "premium" build/
grep -r "upgrade" build/
grep -r "apply_filters.*scan_url" build/
grep -r "_POST\[.url.\]" build/
ls build/code-medic-slow-site-scanner/premium 2>/dev/null && echo "FAIL: premium/ present"
grep -i "enter the url" build/code-medic-slow-site-scanner/readme.txt
grep -i "up to [0-9]* plugin" build/code-medic-slow-site-scanner/readme.txt
```

Manual checks:
- [ ] `includes/scanner.php` — `$url = home_url();` with no comment, no filter
- [ ] `admin/ui.php` — no page-selection UI, no unused functions, no "Page to scan:" label
- [ ] `code-medic-slow-site-scanner.php` — no premium loader block, no "free version" comments
- [ ] `uninstall.php` — no "premium" comments
- [ ] `readme.txt` — steps and feature list match actual free-version behaviour
- [ ] Plugin name and slug contain no "free"
- [ ] Run Plugin Check plugin on a clean WP install with the free ZIP

---

## Other Compliance Requirements

| # | Requirement |
|---|-------------|
| G1 | License must be `GPLv2 or later` |
| G5 | Free version has no locked, hidden, or restricted features (see [`wordpress_freemium_structure.md`](wordpress_freemium_structure.md)) |
| G7 | No external server contact without explicit opt-in consent; telemetry must default to `false` |
| A3 | ABSPATH guard must be single-line: `if ( ! defined( 'ABSPATH' ) ) exit;` |
| A4 | `register_activation_hook` / `register_deactivation_hook` must use `CODESS_PLUGIN_FILE`, not `__FILE__` |

---

## Testing

### Free Version
1. Install free ZIP on a clean WordPress site.
2. Scan runs against the homepage successfully.
3. No JavaScript console errors.
4. No premium-related files present (`find wp-content/plugins/code-medic-slow-site-scanner -name "*.php" | xargs grep -l "premium"`).

### Premium Version
1. Install premium ZIP (includes `premium/` folder) on a clean WordPress site.
2. Additional premium features function correctly.
3. Plugin also works correctly if the `premium/` folder is removed.

---

## Distribution

| Version | Channel | License |
|---------|---------|---------|
| Free | WordPress.org Plugin Directory | GPLv2 or later |
| Premium | Own website / Gumroad / etc. | Commercial |
