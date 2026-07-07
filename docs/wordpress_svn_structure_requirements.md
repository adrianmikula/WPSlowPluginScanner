# WordPress.org SVN Repository Structure & Requirements

**Source:** [Using Subversion – Plugin Handbook](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/)

---

## Repository Overview

WordPress.org plugin hosting uses **Subversion (SVN)** as a release repository, not a development system. Only push finished, ready-to-deploy changes.

- **Check out** to download a copy of plugin files
- **Check in** to upload changes (only plugin authors can do this)
- **Revisions** are tracked; you can review old versions
- **Tags** label releases for easy reference and are the only fully supported method for correct versioning

---

## Required SVN Folder Structure

All WordPress.org SVN repositories contain three directories by default:

```
/assets/
/tags/
/trunk/
```

> Note: `/branches/` is **no longer created by default** and can be ignored.

---

## Trunk

- The `/trunk` directory holds your **latest plugin code**
- Considered the development version (latest and greatest, not necessarily stable)
- For simple plugins, trunk may be the only version that exists
- **CRITICAL:** Do NOT put your main plugin file in a subfolder of trunk, e.g. `/trunk/my-plugin/my-plugin.php` — this will break downloads
- Subfolders are allowed for included/integrated files
- Trunk should always contain the latest version of your code, even if beta

---

## Tags

- The `/tags` directory stores **released versions** of your plugin
- Each release gets its own subdirectory named after the version number
- Examples:
  - Version 1.0 → `/tags/1.0`
  - Version 1.1 → `/tags/1.1`
- **Always use semantic versioning** (e.g., `1.2.3`, `2.0.0`)
- Tags are the only fully supported method to ensure correct versions are served to users
- **Always tag releases** — do not use trunk as a stable release

---

## Assets

- The `/assets` directory stores **screenshots, plugin headers, and plugin icons**
- Keeps plugin file sizes small (screenshots are not sent to WordPress installations)
- **Do NOT** put screenshots in `/trunk` — this is deprecated
- See [How Plugin Assets Work](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/) for details

---

## Best Practices

### 1. Don’t Use SVN for Development
SVN is a **release** system, not a development platform. Do not commit every small change. Each push rebuilds all zip files for all versions, causing update delays of up to 6 hours.

### 2. Use Trunk for Code
Trunk should contain the latest version of your plugin code, even if it’s a beta. Don’t use trunk merely as a placeholder for `readme.txt` updates.

### 3. Always Tag Releases
Releases must be properly tagged and iterated. This ensures compatibility with automatic updaters and allows rollbacks if issues arise.

### 4. Create Tags from Trunk
1. Update code in `/trunk` with the stable version in `readme.txt`
2. Use `svn cp` to copy trunk to a new tag directory
3. Commit the whole operation at once

This preserves history and makes changes easier to track.

```bash
# Checkout root of your repository
svn co https://plugins.svn.wordpress.org/your-plugin-name my-local-dir

# Update files in /trunk, then create tag and commit
svn copy trunk tags/1.2.3
svn ci -m "Tagging version 1.2.3"
```

---

## Important Rules

- **Never upload zip files** — SVN expects individual files only
- **Don’t put anything in SVN** you’re not prepared to have deployed to all users (includes vendor files, `.gitignore`, etc.)
- **Usernames are case-sensitive** — use the exact capitalization from your WordPress.org profile
- **Commit messages are required** for all check-ins
- If commit fails with "Access forbidden," add `--username your_username --password your_password`
- The `Stable Tag` field in `trunk/readme.txt` must always point to the current tagged version

---

## Quick Compliance Checklist

- [ ] Repository contains `/assets/`, `/tags/`, `/trunk/`
- [ ] Main plugin file is in `/trunk/`, not in a subfolder
- [ ] Releases are tagged with semantic version numbers in `/tags/`
- [ ] Screenshots and icons are in `/assets/`
- [ ] `readme.txt` `Stable Tag` matches the latest tag
- [ ] Only release-ready code is committed to SVN

---

## Related Resources

- [How Your Readme.txt Works](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/)
- [Plugin Assets (Icons/Banners)](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/)
- [Detailed Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
