# WP Plugin Benchmark - Quick Start Guide

## Prerequisites Check

```bash
# Verify all dependencies
which lwp && lwp --version
which jq && jq --version
which curl && curl --version | head -1

# Check LocalWP site availability
lwp --list-all
```

## Setup Steps

### 1. Initialize Plugin List (one-time)
```bash
bash wp-plugin-benchmark/bin/wp-benchmark init
```
This fetches the top 100 plugins from WordPress.org and saves to `config/top-100-plugins.json`.

### 2. Prepare LocalWP Site

**Option A: Use existing site**
```bash
# Find your site name
lwp --list-all

# Use it via environment variable
export LOCALWP_SITE_NAME="your-site-name"
```

**Option B: Create new site in LocalWP**
1. Open Local by Flywheel app
2. Click "+" to create new WordPress site
3. Name it: `benchmark-run-1` (or any name)
4. Use default settings (WP latest, PHP 8.3)
5. Start the site

### 3. Run the Benchmark

**Run a single batch (10 plugins - ~5-10 minutes):**
```bash
bash wp-plugin-benchmark/bin/wp-benchmark run --batch=1
```

**Run all 10 batches (100 plugins - ~1-2 hours):**
```bash
bash wp-plugin-benchmark/bin/wp-benchmark run
```

**Use a specific site:**
```bash
export LOCALWP_SITE_NAME="test-plugin"
bash wp-plugin-benchmark/bin/wp-benchmark run --batch=1
```

### 4. Monitor Progress

```bash
# Check status
bash wp-plugin-benchmark/bin/wp-benchmark status

# View recent logs
bash wp-plugin-benchmark/bin/wp-benchmark logs

# See plugin list
bash wp-plugin-benchmark/bin/wp-benchmark list --full
```

### 5. View Results

Results are saved to `logs/results/`:
- `result-{plugin-slug}.json` - Individual plugin test results
- `benchmark-summary.json` - Aggregated report with statistics

```bash
# View summary
jq '.statistics' logs/results/benchmark-summary.json

# List all tested plugins
jq -r '.results[].plugin_slug' logs/results/benchmark-summary.json

# Get specific plugin data
jq '.results[] | select(.plugin_slug=="elementor")' logs/results/benchmark-summary.json
```

### 6. Upload to Supabase (optional)

Set your Supabase credentials:
```bash
export SUPABASE_URL="https://your-project.supabase.co"
export SUPABASE_KEY="your-anon-or-service-key"
```

Results are automatically uploaded after each batch. To manually upload all:
```bash
source wp-plugin-benchmark/src/results.sh
upload_all_results
```

## Configuration

Edit `src/config.sh` to change:
- `BATCH_SIZE` - plugins per batch (default: 10)
- `RUNS_PER_PLUGIN` - test iterations per plugin (default: 3)
- `SITE_PREFIX` - site naming pattern (default: "benchmark-run")
- `TEST_REQUEST_TIMEOUT` - curl timeout in seconds (default: 10)

## Expected Output

```
[INFO] Starting Batch 1
[INFO] Site: benchmark-run-1-1234567890
[INFO] Measuring baseline performance...
[INFO] Baseline time: 180ms
[INFO] Processing: elementor
[INFO] Installing plugin: elementor
[SUCCESS] Installed: elementor
[INFO] Test iteration 1 for elementor: 432ms
[INFO] Test iteration 2 for elementor: 445ms
[INFO] Test iteration 3 for elementor: 428ms
[SUCCESS] Plugin elementor: avg=435ms, std_dev=7ms, delta=+255ms
```

## Troubleshooting

**"Site not found"** - Create/start a site in LocalWP first
**"lwp command not found"** - Install from https://github.com/mikevalera/lwp
**"Plugin installation fails"** - Check site is running: `lwp --site=NAME site status`
**"curl timeout"** - Increase `TEST_REQUEST_TIMEOUT` in config.sh

## Notes

- The benchmark reuses ONE site per batch (deletes plugins between tests)
- Each plugin gets 3 independent HTTP requests to the homepage
- Baseline is measured before any plugins are installed
- Results include avg, std_dev, and delta vs baseline
- Full run: ~1-2 hours for 100 plugins
