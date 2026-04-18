# LocalWP CLI Tools Comparison

A comprehensive comparison of available LocalWP command-line tools for 2026.

## Overview

| Tool | Platform | Status | Last Update | License |
|------|----------|--------|-------------|---------|
| **localwp-wp** (this project) | Linux/macOS/Windows (via WSL) | ✅ Actively maintained | 2026 | MIT |
| lwp (Official Local CLI) | macOS only | ⚠️ Unmaintained | 2022 | Proprietary |
| wp-cli-local (soderlind) | macOS only | ✅ Maintained | 2026 | MIT |
| local-wp-cli (bigwing) | Cross-platform | ⚠️ Limited | 2024 | MIT |
| localwp-autoenv (eduwass) | macOS only | ✅ Maintained | 2025 | MIT |
| local-wp-mcp | Experimental | 🧪 Alpha | 2025 | MIT |
| WordPress Studio CLI | Cloud only | ✅ Maintained | 2026 | Proprietary |

---

## LocalWP CLI Landscape (2026)

### 1. Official lwp - The Legacy Tool

**Status: Deprecated for Linux development**

The official `lwp` command was designed for macOS and never properly supported Linux. Key issues:

- **Hardcoded macOS paths only** - looks in `~/Library/Application Support/Local` exclusively
- **LD_LIBRARY_PATH conflicts** - breaks `curl`, `git`, and other system tools
- **No active maintenance** - last commit 2022, Linux issues unresolved
- **No Windows/Linux support** - not portable

**Why it fails on Linux:**
```bash
# lwp assumes these macOS-specific paths:
~/Library/Application Support/Local/sites.json
~/Library/Application Support/Local/lightning-services/php-8.3+0/bin/linux/bin/php
~/Library/Application Support/Local/run/{site-id}/mysql/mysqld.sock

# On Linux, Local stores in:
~/.config/Local/sites.json
~/.config/Local/lightning-services/...
~/.config/Local/run/...
```

Without workarounds, `lwp` fails with errors like:
```
WPDB Error CRITICAL: wpdb::mysql_real_connect(): (HY000/2002): No such file or directory
```

---

### 2. localwp-wp - The Modern Cross-Platform Wrapper

**This repository provides a standalone, no-dependency wrapper script.**

**Architecture:**
```
bin/localwp-wp (single 69-line wrapper)
      ↓
Reads ~/.config/Local/sites.json directly
      ↓
Parses site info with Python (available on all systems)
      ↓
Sets correct PATH + LD_LIBRARY_PATH for Local's PHP
      ↓
Executes WP-CLI with the right environment
```

**Advantages:**
- ✅ **Works on Linux** (our primary test environment)
- ✅ **Zero dependencies** besides Python and WP-CLI
- ✅ **No wrapper nesting** - direct path resolution
- ✅ **Clean separation** from system binaries
- ✅ **Supports any PHP version** Local provides
- ✅ **Can be copied to any project** - self-contained
- ✅ ** transparent operation** - just works

**Key differences vs lwp:**
| lwp | localwp-wp |
|-----|-----------|
| Complex Perl wrapper | Simple bash script |
| Hardcoded macOS paths | Detects both macOS and Linux paths |
| Sets global LD_LIBRARY_PATH | Sets limited LD_LIBRARY_PATH (only for PHP) |
| Needs `brew install lwp` | Just copy the script |
| Installation required | Portable (no install) |

---

### 3. wp-cli-local (soderlind) - AI Agent Skill

**GitHub:** https://github.com/soderlind/wp-cli-local

An AI agent skill that adds LocalWP support to AI coding assistants (GitHub Copilot, Claude Code, Cursor).

**Features:**
- Auto-detects Local sites from current directory
- Runs WP-CLI with Local's PHP environment
- Designed for AI chat interactions

**Limitations:**
- ❌ **macOS only** explicitly stated in README
- ❌ Requires npx and agent ecosystem
- ❌ Not designed for direct CI/CD use
- ❌ Doesn't help Linux developers

**Quote from README:**
> "An AI agent skill that runs WP-CLI commands against Local (by Flywheel) sites on **macOS**."

---

### 4. local-wp-cli (bigwing) - Config-Based Approach

**GitHub:** https://github.com/bigwing/local-wp-cli

Clones WP-CLI into project and manages `wp-cli.local.yml` config files.

**Approach:**
1. Downloads WP-CLI into project folder
2. Generates `wp-cli.local.yml` with site-specific DB settings
3. Uses `local.php` to configure environment

**Why we didn't choose this:**
- ❌ Adds complexity with config files per project
- ❌ Modifies project directories (pollutes git)
- ❌ Needs maintenance of YAML configs
- ❌ Still needs path resolution logic (just hidden)

**Our take:** Clever but over-engineered. Direct wrapper is simpler.

---

### 5. localwp-autoenv (eduwass) - Interactive Shell Integration

**GitHub:** https://github.com/eduwass/localwp-autoenv

A shell module that auto-loads Local environment when you `cd` into a site directory.

**Use case:** Interactive terminal work, not automation.

**Features:**
- Automatic environment loading on `cd`
- Shows PHP/MySQL versions on load
- Cleans up on `cd` out

**Limitations:**
- ❌ **macOS only** (explicitly stated)
- ❌ Zsh-only (no bash support documented)
- ❌ Interactive only - doesn't help scripts
- ❌ Can't specify arbitrary site name

**Quote:**
> "Currently tested on **macOS only**. Contributions for Linux and Windows support are welcome!"

---

### 6. local-wp-mcp - New Experimental Protocol

**Status: Experimental/Alpha**

MCP (Model Context Protocol) based approach to expose LocalWP functionality to AI agents.

- Uses JSON-RPC over stdio
- Designed for AI agent integration
- Brand new (2025), unproven
- Likely macOS-focused like other MCP tools

**Usefulness for CLI automation:** Low - it's for AI agents, not scripts.

---

### 7. WordPress Studio CLI - Cloud Alternative

**Verdict:** Wrong tool entirely.

WordPress Studio is a cloud hosting/management service, not a LocalWP CLI. It's for managing live WordPress sites, not local development environments.

---

## Decision Matrix

| Criterion | lwp | localwp-wp | wp-cli-local | local-wp-cli | localwp-autoenv |
|-----------|-----|-----------|--------------|--------------|-----------------|
| **Linux support** | ❌ No | ✅ Yes | ❌ No | ⚠️ Partial | ❌ No |
| **Portable** | ❌ Install needed | ✅ Single file | ❌ npx req. | ❌ Project mods | ❌ Shell mods |
| **CI/CD friendly** | ❌ | ✅ | ❌ | ⚠️ | ❌ |
| **Zero config** | ❌ | ✅ | ✅ | ❌ | ❌ |
| **Direct script use** | ⚠️ Broken | ✅ | ❌ | ⚠️ | ❌ |
| **Actively maintained** | ❌ | ✅ | ✅ | ⚠️ | ✅ |
| **Cross-platform** | ❌ | ✅ | ❌ | ✅ | ❌ |

---

## Why localwp-wp Wins for CI/CD

### Problem
Need to run `wp plugin install` and performance benchmarks on LocalWP sites from automated scripts on **Linux**.

### Solution Comparison

**lwp fails because:**
```bash
$ lwp --site=mysite plugin list
# Error: MySQL CRITICAL - socket not found
# Even though site is running fine in Local
```

**localwp-wp succeeds:**
```bash
$ bin/localwp-wp mysite plugin list
# Lists plugins correctly
# Environment auto-configured
```

**Technical reasons:**

1. **Path resolution** - Reads Linux-native `~/.config/Local/sites.json`
2. **Version-agnostic** - Works with any PHP version Local provides
3. **No global pollution** - Doesn't modify user's shell config
4. **Error handling** - Clear error messages when site not found or not running

---

## Implementation Details

### How localwp-wp Works

```bash
# 1. Find the Local data directory
if [[ -d "$HOME/Library/Application Support/Local" ]]; then
    LOCAL_DATA="$HOME/Library/Application Support/Local"  # macOS
elif [[ -d "$HOME/.config/Local" ]]; then
    LOCAL_DATA="$HOME/.config/Local"  # Linux
fi

# 2. Read sites.json to find site ID, path, PHP version
python3 -c "import json; ..."

# 3. Build paths to Local's binaries
PHP_BIN="$LOCAL_DATA/lightning-services/php-${PHP_VER}+0/bin/linux/bin/php"
MYSQL_SOCKET="$LOCAL_DATA/run/${SITE_ID}/mysql/mysqld.sock"

# 4. Set minimal environment
export PATH="$(dirname "$PHP_BIN"):$PATH"
export LD_LIBRARY_PATH="$PHP_BASE/shared-libs:$PHP_BASE/lib:${LD_LIBRARY_PATH:-}"
export MYSQL_UNIX_PORT="$MYSQL_SOCKET"

# 5. Run wp-cli with correct PHP
exec "$PHP_BIN" -d "mysqli.default_socket=$MYSQL_SOCKET" "$(which wp)" --path="$WP_PATH" "$@"
```

**Key insight:** Use `exec` to replace the shell process with PHP, avoiding subshell issues with environment variables.

---

## Why Other Approaches Don't Work

### Approach 1: `wp-cli.local.yml` in project root
```yaml
# wp-cli.local.yml
path: /path/to/wordpress
url: http://site.test
db:
  host: localhost
  user: root
  password: root
```
**Problem:** Needs to know the MySQL socket path, which is per-site and dynamic.

### Approach 2: Edit `wp-config.php` conditionally
```php
defined('WP_CLI') && WP_CLI
  ? define('DB_HOST', 'localhost:/path/to/socket.sock')
  : define('DB_HOST', 'localhost');
```
**Problem:** Modifies tracked files, needs manual edits, error-prone.

### Approach 3: Source Local's environment script
```bash
source ~/.config/Local/run/{site-id}/wp-cli.env
# Then run wp
```
**Problem:** Local doesn't generate such scripts by default; would need custom setup per site.

---

## Recommended Usage

### For Projects (like wp-plugin-benchmark)

Copy `bin/localwp-wp` into your project:

```bash
# Project structure
my-project/
├── bin/
│   └── localwp-wp           # Copy from this repo
├── scripts/
│   └── benchmark.sh         # Your automation
└── README.md
```

In your scripts:
```bash
#!/bin/bash
export LOCALWP_SITE_NAME="my-local-site"
./bin/localwp-wp "$LOCALWP_SITE_NAME" plugin install akismet --activate
./bin/localwp-wp "$LOCALWP_SITE_NAME" plugin list
```

**Advantage:** No installation, works immediately on any machine with Local + WP-CLI.

### For Development Workflow

Option A: Create symlink (if on Linux-native filesystem):
```bash
ln -s /path/to/project/bin/localwp-wp ~/.local/bin/localwp-wp
# Now available everywhere
```

Option B: Add to PATH:
```bash
export PATH="$PATH:/path/to/project/bin"
```

---

## Known Gotchas

### 1. FAT32/External Drive Permissions
If your project is on a FAT32 or network drive (like `/media/adrian/SHARED`):
- Unix permissions don't work (`chmod +x` ineffective)
- Solution: Move project to ext4 filesystem (`/home/adrian/` or internal drive)

### 2. Python Dependency
The wrapper uses Python 3 to parse JSON. Ensure:
```bash
python3 --version  # Should be installed (standard on Linux/macOS)
```

### 3. Site Must Be Running
LocalWP sites need to be started in the Local app first:
```bash
# Check socket exists
[[ -S ~/.config/Local/run/{site-id}/mysql/mysqld.sock ]] || echo "Start site in Local first"
```

### 4. Site Name Must Match Exactly
```bash
# In Local app, site name is "My Site"
export LOCALWP_SITE_NAME="My Site"  # Match exactly, spaces included
```

---

## Contributing to localwp-wp

This tool is designed to be **minimal and maintainable**. Future improvements:

- [ ] Detect Windows WSL paths
- [ ] Auto-detect site from current directory if inside Local site folder
- [ ] Support `--list` to show all sites
- [ ] Fallback to `which wp` if not found (clear error now)
- [ ] Add `--debug` flag to show resolved paths

---

## License

MIT - feel free to copy, modify, and redistribute.

---

## References

- Local by Flywheel: https://localwp.com/
- WP-CLI: https://wp-cli.org/
- lwp documentation: https://localwp.com/help-docs/cli-commands/local-cli/
- Our benchmark project: https://github.com/your-org/wp-plugin-benchmark (example)
