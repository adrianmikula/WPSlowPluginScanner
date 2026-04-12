=== WP Slow Plugin Scanner ===
Contributors: wp-impact-analyzer
Tags: debugging, performance, slow, plugin, scanner
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://example.com/donate

Detects the single plugin causing a slowdown or breakage on a specific page using safe loopback tests.

== Description ==

WP Slow Plugin Scanner helps you identify which plugin is causing your site to be slow or broken. It runs safe loopback tests that temporarily disable individual plugins to measure their impact on page load time and output.

= How it works =

1. Enter the URL of the page you want to test
2. Click "Scan Plugins" to start the scan
3. The scanner runs a baseline test with all plugins enabled
4. Then it tests each plugin individually by temporarily disabling it
5. Results show the impact (time delta, status changes, output changes) for each plugin

= Features =

* Safe loopback testing that doesn't affect visitors
* Measures response time impact per plugin
* Detects plugins that break the site (status changes)
* Detects plugins that change page output
* Results sorted by impact (highest first)
* Cancel scan at any time
* Works with AJAX-based progress display

== Installation ==

1. Upload the `wp-plugin-impact-analyzer` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Tools > Scan Plugins to use the scanner

== Frequently Asked Questions ==

= Is this safe to use on a live site? =

Yes, the scanner uses loopback requests internally and doesn't affect what visitors see. It temporarily disables plugins only during its own test requests.

= How long does a scan take? =

The scan time depends on the number of plugins and your site's response time. By default, it tests up to 6 plugins plus the baseline, so approximately 7 requests. Each request has an 8-second timeout.

= Will this disable my plugins permanently? =

No. The plugin temporarily disables plugins only during its own test requests using a must-use plugin. The temporary file is automatically cleaned up after the scan completes or is cancelled.

= Can I cancel a scan in progress? =

Yes, click the "Cancel" button to stop the scan. The temporary files will be cleaned up automatically.

== Screenshots ==

1. The scanner interface showing results after a scan
2. Progress display during scanning

== Changelog ==

= 0.1.0 =
* Initial release
* Baseline and per-plugin loopback testing
* AJAX-based scanning with progress display
* Results sorted by impact
* Cancel functionality

== Upgrade Notice ==

= 0.1.0 =
Initial release of WP Slow Plugin Scanner.