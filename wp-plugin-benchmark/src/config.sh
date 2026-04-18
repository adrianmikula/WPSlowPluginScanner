#!/bin/bash
# Configuration & Constants for WP Plugin Benchmark Tool

# Project paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
CONFIG_DIR="$PROJECT_ROOT/config"
LOGS_DIR="$PROJECT_ROOT/logs"
SRC_DIR="$PROJECT_ROOT/src"

# Benchmark settings
BATCH_SIZE=10
TOTAL_PLUGINS=100
RUNS_PER_PLUGIN=3
SITE_PREFIX="benchmark-run"

# Timeouts (seconds)
PLUGIN_INSTALL_TIMEOUT=120
TEST_REQUEST_TIMEOUT=10
SITE_CREATE_TIMEOUT=300
SITE_DELETE_TIMEOUT=120

# Test settings
TEST_URL="index.php"  # Homepage
TEST_USER_AGENT="WP-Benchmark/1.0"

# Supabase (from environment or use existing plugin values)
SUPABASE_URL="${SUPABASE_URL:-}"
SUPABASE_KEY="${SUPABASE_KEY:-}"
SUPABASE_TABLE="${SUPABASE_TABLE:-telemetry}"

# LocalWP settings
LOCALWP_SITE_NAME="${LOCALWP_SITE_NAME:-}"
LOCALWP_PHP_VERSION="${LOCALWP_PHP_VERSION:-8.3}"
LOCALWP_WP_VERSION="${LOCALWP_WP_VERSION:-latest}"

# Fix LD_LIBRARY_PATH for Local lightning services (Linux)
# lwp's PHP needs access to shared libraries from lightning services
if [[ -d "$HOME/.config/Local/lightning-services" ]]; then
    # Add all PHP service library paths to support any site's PHP version
    for php_dir in "$HOME/.config/Local/lightning-services"/php-*/bin/linux; do
        if [[ -d "$php_dir/shared-libs" ]]; then
            export LD_LIBRARY_PATH="$php_dir/shared-libs:$LD_LIBRARY_PATH"
        fi
        if [[ -d "$php_dir/lib" ]]; then
            export LD_LIBRARY_PATH="$php_dir/lib:$LD_LIBRARY_PATH"
        fi
    done
fi

# Colors for console output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging (only set defaults if not already set)
: "${LOG_FILE:=}"
: "${LOG_LEVEL:=INFO}"  # DEBUG, INFO, WARN, ERROR

# Ensure logs directory exists
mkdir -p "$LOGS_DIR"