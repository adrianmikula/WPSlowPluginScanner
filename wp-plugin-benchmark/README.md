# WP Plugin Performance Benchmark CLI

A bash CLI tool that automates testing the top 100 WordPress plugins for performance impact using LocalWP and stores results in Supabase.

## Project Structure

```
wp-plugin-benchmark/
├── bin/
│   └── wp-benchmark              # Main CLI entry point
├── src/
│   ├── config.sh                 # Configuration & constants
│   ├── plugins.sh                # Plugin list management
│   ├── localwp.sh                # LocalWP integration via lwp
│   ├── test-runner.sh            # Performance test execution
│   └── results.sh                # Result aggregation & Supabase upload
├── config/
│   └── top-100-plugins.json      # Top 100 plugins list
├── logs/
│   └── benchmark-YYYYMMDD.log    # Test run logs
└── README.md
```

## Workflow

### 1. Initialization
```bash
wp-benchmark init
```
- Fetches top 100 plugins from WordPress.org API
- Saves to `config/top-100-plugins.json`
- Verifies lwp is installed

### 2. Batch Testing Loop
For each of 10 batches (10 plugins each):

1. **Create fresh LocalWP site**: `benchmark-run[N]`
2. **For each plugin in batch**:
   - Install via: `lwp --site=$SITE plugin install $PLUGIN --activate`
   - Run 3 test iterations (measure response time)
   - Calculate average of 3 runs
   - Deactivate & delete plugin
   - On error: log and continue to next
3. **Upload results to Supabase** (flagged as benchmark data)
4. **Delete site** (cleanup)

### 3. Results Storage
- Aggregate 3 runs per plugin (average time)
- Add `test_type: "benchmark"` flag to existing telemetry
- Send to Supabase via existing `codemedsss_send_telemetry_to_supabase()`

## CLI Commands

| Command | Description |
|---------|-------------|
| `wp-benchmark init` | Fetch & save top 100 plugins list |
| `wp-benchmark run` | Run full benchmark (10 batches) |
| `wp-benchmark run --batch=N` | Run specific batch only |
| `wp-benchmark status` | Show current progress |
| `wp-benchmark logs` | View recent test logs |
| `wp-benchmark list` | Show loaded plugin list |

## Configuration

Edit `src/config.sh` to modify:

- **BATCH_SIZE**: Plugins per batch (default: 10)
- **RUNS_PER_PLUGIN**: Test iterations per plugin (default: 3)
- **SITE_PREFIX**: LocalWP site naming prefix (default: "benchmark-run")
- **SUPABASE_URL/KEY**: Supabase credentials

## Test Result Data Structure

```json
{
  "test_type": "benchmark",
  "batch_id": "run-001",
  "plugin_slug": "elementor",
  "plugin_version": "3.25.0",
  "test_runs": [0.432, 0.445, 0.428],
  "avg_time_ms": 435,
  "std_dev_ms": 7,
  "baseline_time_ms": 180,
  "delta_ms": 255,
  "error": null,
  "error_category": null,
  "wp_version": "6.7",
  "php_version": "8.3",
  "timestamp": 1713364800
}
```

## Error Handling

- **Plugin breaks site** → Log error, mark as `break_site`, continue
- **Install fails** → Log error, skip plugin, continue
- **Network timeout** → Retry once, then skip

## Prerequisites

1. **Local by Flywheel** installed with at least one site
2. **lwp** - LocalWP CLI wrapper: https://github.com/mikevalera/lwp
3. **jq** - JSON processor (recommended): `brew install jq`
4. **curl** - HTTP client (usually pre-installed)

## Estimated Runtime

- 10 batches × 10 plugins × 3 runs = 300 tests
- ~30-60 seconds per plugin (install + 3 tests + cleanup)
- **~1-2 hours total** for full benchmark