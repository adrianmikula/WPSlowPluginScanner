#!/bin/bash
# LocalWP Integration - Uses direct wp-cli via localwp-wp wrapper
# No lwp dependency - works on Linux/macOS

source "$(dirname "${BASH_SOURCE[0]}")/config.sh"
source "$(dirname "${BASH_SOURCE[0]}")/plugins.sh"

# Path to our wrapper script (relative to this file)
LOCALWP_WP_WRAPPER="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/bin/localwp-wp"

# Verify wrapper exists and is executable
check_wrapper() {
    if [[ ! -f "$LOCALWP_WP_WRAPPER" ]]; then
        log_error "localwp-wp wrapper not found: $LOCALWP_WP_WRAPPER"
        return 1
    fi
    if [[ ! -x "$LOCALWP_WP_WRAPPER" ]]; then
        log_warn "Wrapper not executable, will run via bash"
    fi
    return 0
}

# Get site info
get_site_info() {
    local site_name=$1
    local sites_json="${HOME}/.config/Local/sites.json"
    
    if [[ ! -f "$sites_json" ]]; then
        sites_json="${HOME}/Library/Application Support/Local/sites.json"
        if [[ ! -f "$sites_json" ]]; then
            log_error "Local sites config not found"
            return 1
        fi
    fi
    
    python3 -c "
import json, os, sys
with open('$sites_json') as f:
    sites = json.load(f)
for sid, s in sites.items():
    if s.get('name', '') == '$site_name':
        php_ver = s.get('services', {}).get('php', {}).get('version', '')
        mysql_ver = s.get('services', {}).get('mysql', {}).get('version', '')
        path = os.path.expanduser(s.get('path', ''))
        domain = s.get('domain', '')
        print(f'{sid}|{path}|{domain}|{php_ver}|{mysql_ver}')
        break
else:
    sys.exit(1)
" 2>/dev/null || return 1
}

# Check if site is running (socket exists)
is_site_running() {
    local site_name=$1
    local sites_json="${HOME}/.config/Local/sites.json"
    
    [[ -f "$sites_json" ]] || sites_json="${HOME}/Library/Application Support/Local/sites.json"
    [[ -f "$sites_json" ]] || return 1
    
    local site_id
    site_id=$(python3 -c "
import json
with open('$sites_json') as f:
    sites = json.load(f)
for sid, s in sites.items():
    if s.get('name', '') == '$site_name':
        print(sid)
        break
else:
    print('')
" 2>/dev/null) || return 1
    
    [[ -n "$site_id" ]] || return 1
    
    local socket="${HOME}/.config/Local/run/${site_id}/mysql/mysqld.sock"
    [[ -S "$socket" ]]
}

# Get site URL
get_site_url() {
    local site_name=$1
    local site_info
    site_info=$(get_site_info "$site_name") || return 1
    
    IFS='|' read -r site_id site_path domain php_ver mysql_ver <<< "$site_info"
    if [[ -n "$domain" ]]; then
        echo "http://${domain}"
    else
        echo "http://${site_name}.local"
    fi
}

# Get WordPress version
get_wp_version() {
    local site_name=$1
    bash "$LOCALWP_WP_WRAPPER" "$site_name" core version 2>/dev/null | tail -1
}

# Get PHP version
get_php_version() {
    local site_name=$1
    local site_info
    site_info=$(get_site_info "$site_name") || return 1
    
    IFS='|' read -r site_id site_path domain php_ver mysql_ver <<< "$site_info"
    echo "$php_ver"
}

# Ensure site is running
ensure_site_running() {
    local site_name=$1
    
    if ! is_site_running "$site_name"; then
        log_error "Site '$site_name' is not running"
        log_info "Start it in Local by Flywheel first"
        return 1
    fi
    return 0
}

# Install and activate a plugin
install_plugin() {
    local site_name=$1
    local plugin_slug=$2
    
    log_info "Installing plugin: $plugin_slug"
    
    if ! check_wrapper; then
        return 1
    fi
    
    if ! is_site_running "$site_name"; then
        log_error "Site '$site_name' is not running"
        return 1
    fi
    
    if bash "$LOCALWP_WP_WRAPPER" "$site_name" plugin install "$plugin_slug" --activate 2>&1; then
        log_success "Installed: $plugin_slug"
        return 0
    else
        log_error "Failed to install $plugin_slug"
        return 1
    fi
}

# Deactivate a plugin
deactivate_plugin() {
    local site_name=$1
    local plugin_slug=$2
    
    if ! check_wrapper; then
        return 1
    fi
    
    bash "$LOCALWP_WP_WRAPPER" "$site_name" plugin deactivate "$plugin_slug" 2>/dev/null
    return $?
}

# Delete a plugin
delete_plugin() {
    local site_name=$1
    local plugin_slug=$2
    
    # Deactivate first
    deactivate_plugin "$site_name" "$plugin_slug"
    
    log_info "Deleting plugin: $plugin_slug"
    
    if ! check_wrapper; then
        return 1
    fi
    
    if bash "$LOCALWP_WP_WRAPPER" "$site_name" plugin delete "$plugin_slug" 2>/dev/null; then
        log_success "Deleted: $plugin_slug"
        return 0
    else
        log_warn "Delete failed: $plugin_slug"
        return 1
    fi
}

# List plugins
list_plugins() {
    local site_name=$1
    
    if ! check_wrapper; then
        return 1
    fi
    
    bash "$LOCALWP_WP_WRAPPER" "$site_name" plugin list --status=active 2>/dev/null
}

# List all LocalWP sites
list_sites() {
    local sites_json="${HOME}/.config/Local/sites.json"
    
    if [[ ! -f "$sites_json" ]]; then
        sites_json="${HOME}/Library/Application Support/Local/sites.json"
        if [[ ! -f "$sites_json" ]]; then
            echo "No Local sites config found"
            return 1
        fi
    fi
    
    echo -e "${CYAN}%-20s %-25s %-15s %-10s${NC}" "FOLDER" "DOMAIN" "PHP" "STATUS"
    echo "%-20s %-25s %-15s %-10s" "------" "------" "---" "------"
    
    python3 -c "
import json, os, sys
with open('$sites_json') as f:
    sites = json.load(f)
for sid, s in sites.items():
    folder = os.path.basename(s.get('path', ''))
    domain = s.get('domain', '')
    php = s.get('services', {}).get('php', {}).get('version', '')
    socket = os.path.expanduser('${HOME}/.config/Local/run/' + sid + '/mysql/mysqld.sock')
    running = os.path.exists(socket)
    status = '${GREEN}running${NC}' if running else '${RED}stopped${NC}'
    print(folder.ljust(20) + domain.ljust(25) + php.ljust(15) + status)
"
}

# Get site status string
get_site_status() {
    local site_name=$1
    if is_site_running "$site_name"; then
        echo "running"
    else
        echo "stopped"
    fi
}

# Check if site exists
site_exists() {
    local site_name=$1
    get_site_info "$site_name" &>/dev/null
}

# Start a site (requires GUI)
start_site() {
    log_warn "Starting sites requires Local by Flywheel GUI application"
    log_info "Please start site '$1' manually in Local"
    return 1
}

# Stop a site
stop_site() {
    log_warn "Stopping sites requires Local by Flywheel GUI application"
    return 1
}

# Cleanup after batch
cleanup_batch() {
    local site_name=$1
    
    log_info "Cleaning up site: $site_name"
    
    if ! check_wrapper; then
        return 1
    fi
    
    local plugins
    plugins=$(bash "$LOCALWP_WP_WRAPPER" "$site_name" plugin list --status=active --format=column --field=name 2>/dev/null)
    
    for plugin in $plugins; do
        case "$plugin" in
            akismet|hello-dolly) continue ;;
        esac
        deactivate_plugin "$site_name" "$plugin" 2>/dev/null
        delete_plugin "$site_name" "$plugin" 2>/dev/null
    done
    
    log_success "Cleanup complete"
}
