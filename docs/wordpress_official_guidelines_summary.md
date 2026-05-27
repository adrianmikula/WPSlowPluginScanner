# WordPress.org Plugin Directory Guidelines - Summary

**Source:** [Detailed Plugin Guidelines – Plugin Handbook](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

## The 18 Guidelines at a Glance

### 1. GPL Compatibility
Plugins must be compatible with the GNU General Public License. Using "GPLv2 or later" is strongly recommended. All code, data, and images must comply with GPL or a GPL-Compatible license.

**Reference:** [Guideline 1](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

### 2. Developer Responsibility
Developers are responsible for all contents and actions of their plugins. Intentionally circumventing guidelines or restoring removed code is prohibited.

**Reference:** [Guideline 2](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

### 3. Stable Version Available
A stable version must be available from the WordPress Plugin Directory page. Code distributed via alternate methods while not keeping the directory version up to date may result in removal.

**Reference:** [Guideline 3](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

### 4. Human Readable Code
Code must be (mostly) human readable. Obfuscation via packers, uglify's mangle, or unclear naming conventions (e.g., `$z12sdf813d`) is not permitted.

**Reference:** [Guideline 4](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

### 5. ❌ Trialware is NOT Permitted (Critical for this plugin)

> "Plugins may not contain functionality that is restricted or locked, only to be made available by payment or upgrade. Functionality may not be disabled after a trial period or quota is met."

**Key Points:**
- No features locked behind payment or license checks
- No functionality disabled after trial/expiration
- No sandbox-only access to APIs
- Paid functionality in services is permitted (see Guideline 6)
- **Recommended approach:** Use add-on plugins hosted outside WordPress.org

**What This Means for Premium Plugins:**
- The free plugin must be **genuinely** functional on its own
- Premium features must be **truly additive**, not **unlocked** from free
- Free code should not contain "premium" feature flags or checks
- Upsells are acceptable but must be limited (see Guideline 11)

**Reference:** [Guideline 5](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

### 6. Software as a Service (SaaS) is Permitted
Plugins acting as interfaces to external third-party services are allowed, even for paid services. The service must:
- Provide functionality of substance
- Be clearly documented in the readme
- Include Terms of Use link

**Not Allowed:**
- Services existing solely to validate licenses/keys
- Arbitrary code moved out of plugin to falsely appear as supplemented functionality
- Storefronts that are not services

**Reference:** [Guideline 6](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

### 7. No Tracking Without Consent
Plugins may not contact external servers without explicit and authorized consent. Examples of prohibited tracking:
- Automated user data collection without confirmation
- Misleading users into submitting information
- Offloading unrelated assets
- Undocumented use of external data
- Third-party advertisement tracking

**Exception:** SaaS plugins (e.g., Twitter, Amazon CDN, Akismet) where consent is granted via installation/activation.

**Reference:** [Guideline 7](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

### 8. No Executable Code via Third-Party
Externally loading code from documented services is permitted. Not allowed:
- Installing plugins/themes/add-ons from non-WordPress.org servers
- Installing premium versions of the same plugin
- Third-party CDNs for non-service JS/CSS (fonts excepted)
- Using iframes to connect admin pages

**Reference:** [Guideline 8](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

### 9. No Illegal, Dishonest, or Offensive Actions
Includes:
- Artificially manipulating search results (keyword stuffing, black hat SEO)
- Offering to drive traffic to sites
- Compensating/extorting for reviews
- **Implying users must pay to unlock included features**
- Fake reviews (sockpuppeting)
- Presenting others' plugins as original work
- Crypto-mining without permission
- Harassment or threats

**Reference:** [Guideline 9](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

### 10. No External Links/Credits Without Permission
All "Powered By" links must be optional and default to not showing. Users must opt-in via clear choices, not buried in terms. Plugins may not require credit/links to function.

**Reference:** [Guideline 10](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

### 11. Don't Hijack the Admin Dashboard
Plugins should feel like part of WordPress. Requirements:
- Upgrade prompts/notices limited in scope and used sparingly
- Site-wide notices must be dismissible or self-dismiss when resolved
- Error messages must include resolution info and remove when completed
- Advertising in dashboard should be avoided
- Tracking referrals via ads is not permitted

**Reference:** [Guideline 11](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

### 12. No Spam in Readmes
Public pages may not spam via:
- Unnecessary affiliate links
- Tags to competitors' plugins
- Over 5 tags total
- Blackhat SEO/keyword stuffing

Affiliate links must be disclosed and link directly (no redirects/cloaking).

**Reference:** [Guideline 12](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

### 13. Use WordPress Default Libraries
Plugins must not include libraries that WordPress already includes (jQuery, Atom Lib, SimplePie, PHPMailer, PHPass, etc.). Use WordPress-packaged versions instead.

**Reference:** [Guideline 13](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

### 14. Avoid Frequent Commits
SVN is a release repository, not development. Only commit code ready for deployment. Multiple rapid-fire commits tweaking minor aspects can be seen as gaming "Recently Updated" lists.

**Exception:** Readme updates solely to indicate support for latest WordPress release.

**Reference:** [Guideline 14](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

### 15. Increment Version Numbers
Users are only alerted to updates when version increases. Trunk readme.txt must always reflect current version.

**Reference:** [Guideline 15](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

### 16. Complete Plugin at Submission
All plugins are examined prior to approval. Names cannot be "reserved" for future use.

**Reference:** [Guideline 16](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

### 17. Respect Trademarks/Copyrights
Plugins must respect trademarks, copyrights, and project names.

**Reference:** [Guideline 17](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

### 18. WordPress.org Maintenance Rights
WordPress.org reserves the right to maintain the Plugin Directory to the best of their ability.

**Reference:** [Guideline 18](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

## Critical Guidelines for Freemium Plugins

### The "Trialware" Trap (Guideline 5)

**The Problem:**
Many plugins violate Guideline 5 by:
- Including premium code in the free plugin that's "locked"
- Using feature flags to disable functionality
- Having UI that says "Upgrade to unlock"

**The Solution:**
- Free plugin should be **genuinely** limited (not artificially restricted)
- Premium functionality should be **additive** via separate files
- Free code should not reference premium features

**Our Approach:**
```
Free Version:
- Scans homepage only (hardcoded)
- No knowledge of URL selection
- Fully functional for homepage scanning

Premium Version:
- Same core code
- Adds UI files for URL selection
- Adds filter hook to accept custom URLs
- Extends, doesn't unlock
```

**Why This Complies:**
- Free version is **complete** for its intended purpose
- URL scanning is **not included** in free, not **locked** in free
- Premium **adds** new files, doesn't **enable** hidden code

---

## Related Official Resources

- [Plugin Developer FAQ](https://developer.wordpress.org/plugins/wordpress-org/plugin-developer-faq/)
- [How the Readme.txt Works](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/)
- [SVN Directions for Plugins](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/)
- [Plugin Assets (Icons/Banners)](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/)
- [Default Scripts Included by WordPress](https://developer.wordpress.org/reference/functions/wp_enqueue_script/)

---

## Quick Compliance Checklist

Before submitting to WordPress.org:

- [ ] **Guideline 1:** GPL-compatible license declared
- [ ] **Guideline 4:** No obfuscated/minified code without source
- [ ] **Guideline 5:** No locked/trial features in free version
- [ ] **Guideline 7:** Opt-in for any external data collection
- [ ] **Guideline 8:** No premium installs from external servers
- [ ] **Guideline 9:** No "pay to unlock" implications
- [ ] **Guideline 10:** Optional "powered by" links only
- [ ] **Guideline 11:** Limited upsells, dismissible notices
- [ ] **Guideline 12:** Max 5 tags, no competitor tags
- [ ] **Guideline 13:** Using WordPress default libraries
- [ ] **Guideline 15:** Version number incremented
