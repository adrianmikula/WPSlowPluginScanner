#!/bin/bash
# Plugin List Management - Fetch and manage top 100 plugins

source "$(dirname "${BASH_SOURCE[0]}")/config.sh"

PLUGIN_LIST_FILE="$CONFIG_DIR/top-100-plugins.json"
PLUGIN_API_URL="https://api.wordpress.org/plugins/info/1.2/?action=query_plugins&request%5Bper_page%5D=100&request%5Border%5D=desc&request%5Borderby%5D=rating"

# Fetch top 100 plugins from WordPress.org API
fetch_plugin_list() {
    log_info "Fetching top 100 plugins from WordPress.org..."
    
    local response
    response=$(curl -s --max-time 60 "$PLUGIN_API_URL")
    
    if [[ $? -ne 0 ]]; then
        log_error "Failed to fetch plugin list from WordPress.org"
        return 1
    fi
    
    # Parse and save to JSON (basic parsing with jq if available)
    if command -v jq &> /dev/null; then
        echo "$response" | jq '[.plugins[] | {slug: .slug, name: .name, version: .version, rating: .rating}]' > "$PLUGIN_LIST_FILE"
        log_info "Plugin list saved to $PLUGIN_LIST_FILE"
    else
        # Fallback: save raw response
        echo "$response" > "$PLUGIN_LIST_FILE"
        log_warn "jq not found - saved raw API response. Install jq for formatted output."
    fi
    
    return 0
}

# Get plugin list (fetch if not exists)
get_plugin_list() {
    if [[ ! -f "$PLUGIN_LIST_FILE" ]]; then
        fetch_plugin_list || return 1
    fi
    
    if command -v jq &> /dev/null; then
        jq -r '.[].slug' "$PLUGIN_LIST_FILE"
    else
        # Fallback: basic grep if jq not available
        grep -o '"slug"[[:space:]]*:[[:space:]]*"[^"]*"' "$PLUGIN_LIST_FILE" | cut -d'"' -f4
    fi
}

# Get plugins for a specific batch (start index, count)
get_batch_plugins() {
    local start=$1
    local count=$2
    
    if [[ ! -f "$PLUGIN_LIST_FILE" ]]; then
        log_error "Plugin list not found. Run 'wp-benchmark init' first."
        return 1
    fi
    
    if command -v jq &> /dev/null; then
        jq -r ".[$start:($start+$count)] | .[] | .slug" "$PLUGIN_LIST_FILE"
    else
        log_error "jq is required for batch processing"
        return 1
    fi
}

# Get plugin details by slug
get_plugin_details() {
    local slug=$1
    
    if command -v jq &> /dev/null; then
        jq -r ".[] | select(.slug == \"$slug\")" "$PLUGIN_LIST_FILE"
    else
        log_error "jq is required for plugin details"
        return 1
    fi
}

# Count total plugins in list
count_plugins() {
    if [[ ! -f "$PLUGIN_LIST_FILE" ]]; then
        echo "0"
        return
    fi
    
    if command -v jq &> /dev/null; then
        jq 'length' "$PLUGIN_LIST_FILE"
    else
        grep -c '"slug"' "$PLUGIN_LIST_FILE"
    fi
}

# Log functions (output to stderr)
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1" >&2
    log_to_file "INFO" "$1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1" >&2
    log_to_file "WARN" "$1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
    log_to_file "ERROR" "$1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1" >&2
    log_to_file "SUCCESS" "$1"
}

log_debug() {
    if [[ "$LOG_LEVEL" == "DEBUG" ]]; then
        echo -e "${BLUE}[DEBUG]${NC} $1" >&2
        log_to_file "DEBUG" "$1"
    fi
}

log_to_file() {
    local level=$1
    local message=$2
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] [$level] $message" >> "$LOG_FILE"
}

# Initialize logging
init_logging() {
    LOG_FILE="$LOGS_DIR/benchmark-$(date +%Y%m%d-%H%M%S).log"
    echo "========================================" >> "$LOG_FILE"
    echo "WP Plugin Benchmark started at $(date)" >> "$LOG_FILE"
    echo "========================================" >> "$LOG_FILE"
}

# Show plugin list
show_plugin_list() {
    if [[ ! -f "$PLUGIN_LIST_FILE" ]]; then
        log_error "Plugin list not found. Run 'wp-benchmark init' first."
        return 1
    fi
    
    echo -e "${BLUE}Top $(count_plugins) Plugins:${NC}"
    echo "----------------------------------------"
    
    if command -v jq &> /dev/null; then
        jq -r '.[] | "\(.slug) - \(.name)"' "$PLUGIN_LIST_FILE" | head -20
        echo "..."
        echo "(Run with --full to see all)"
    fi
}

# Verify required tools
check_dependencies() {
    local missing=()
    
    # Check for lwp
    if ! command -v lwp &> /dev/null; then
        missing+=("lwp - https://github.com/mikevalera/lwp")
    fi
    
    # Check for jq (optional but recommended)
    if ! command -v jq &> /dev/null; then
        log_warn "jq not found - some features will be limited"
    fi
    
    if [[ ${#missing[@]} -gt 0 ]]; then
        log_error "Missing dependencies:"
        for tool in "${missing[@]}"; do
            echo "  - $tool"
        done
        return 1
    fi
    
    log_success "All dependencies satisfied"
    return 0
}

# Main entry point
main() {
    case "$1" in
        init)
            fetch_plugin_list
            check_dependencies
            ;;
        list)
            show_plugin_list
            ;;
        count)
            count_plugins
            ;;
        *)
            echo "Usage: $0 {init|list|count}"
            ;;
    esac
}

# Run if executed directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi