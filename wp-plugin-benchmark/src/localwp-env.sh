#!/bin/bash
# LocalWP Environment Bootstrap - Sets up proper environment for lwp and wp-cli
# This script should be sourced before using lwp/wp-cli with LocalWP on Linux

# Determine Local config paths (handle both macOS and Linux)
if [[ -d "$HOME/Library/Application Support/Local" ]]; then
    LOCAL_DATA="$HOME/Library/Application Support/Local"
elif [[ -d "$HOME/.config/Local" ]]; then
    LOCAL_DATA="$HOME/.config/Local"
    # Create macOS-style symlinks for tools that expect them
    mkdir -p "$HOME/Library/Application Support/Local"
    if [[ ! -e "$HOME/Library/Application Support/Local/sites.json" ]]; then
        ln -sf "$HOME/.config/Local/sites.json" "$HOME/Library/Application Support/Local/sites.json" 2>/dev/null
    fi
    if [[ ! -e "$HOME/Library/Application Support/Local/run" ]]; then
        ln -sf "$HOME/.config/Local/run" "$HOME/Library/Application Support/Local/run" 2>/dev/null
    fi
    if [[ ! -e "$HOME/Library/Application Support/Local/lightning-services" ]]; then
        ln -sf "$HOME/.config/Local/lightning-services" "$HOME/Library/Application Support/Local/lightning-services" 2>/dev/null
    fi
fi

export LOCAL_DATA

# Get site info for a given site name
# Usage: localwp_env_for_site "site-name"
localwp_env_for_site() {
    local site_name="$1"
    local sites_json="${LOCAL_DATA}/sites.json"
    
    if [[ ! -f "$sites_json" ]]; then
        echo "ERROR:sites.json not found"
        return 1
    fi
    
    # Get site config via Python
    python3 -c "
import json, os
with open('$sites_json') as f:
    sites = json.load(f)
for sid, s in sites.items():
    if s.get('name', '') == '$site_name':
        php_ver = s.get('services', {}).get('php', {}).get('version', '')
        mysql_ver = s.get('services', {}).get('mysql', {}).get('version', '')
        path = s.get('path', '')
        domain = s.get('domain', '')
        print(f'{sid}|{path}|{domain}|{php_ver}|{mysql_ver}')
        break
else:
    print('ERROR:Site not found')
" || return 1
}

# Setup environment variables for a LocalWP site
# Usage: localwp_setup_env "site-name"
# Sets: PATH, LD_LIBRARY_PATH, MYSQL_UNIX_PORT
# Returns: WordPress installation path on success
localwp_setup_env() {
    local site_name="$1"
    local site_info
    site_info=$(localwp_env_for_site "$site_name") || return 1
    
    if [[ "$site_info" == "ERROR:Site not found" ]]; then
        echo "ERROR:Site '$site_name' not found in Local"
        return 1
    fi
    
    IFS='|' read -r site_id site_path domain php_ver mysql_ver <<< "$site_info"
    
    # Paths to lightning services
    local php_base="${LOCAL_DATA}/lightning-services/php-${php_ver}+0"
    local mysql_base="${LOCAL_DATA}/lightning-services/mysql-${mysql_ver}+0"
    
    # Binary directories
    local php_bin_dir="${php_base}/bin/linux/bin"
    local mysql_bin_dir="${mysql_base}/bin/linux/bin"
    
    # Library directories (shared libs + PHP libs)
    local php_lib_dirs="${php_bin_dir}/../shared-libs:${php_bin_dir}/../lib"
    
    # MySQL socket
    local socket="${LOCAL_DATA}/run/${site_id}/mysql/mysqld.sock"
    
    # WordPress path
    local wp_path="${site_path}/app/public"
    
    # Export environment
    export PATH="${php_bin_dir}:${mysql_bin_dir}:${PATH}"
    export LD_LIBRARY_PATH="${php_lib_dirs}:${LD_LIBRARY_PATH}"
    export MYSQL_UNIX_PORT="${socket}"
    
    # Return wp path
    echo "${wp_path}"
}

# Check if a site is running (socket exists and responding)
localwp_is_running() {
    local site_name="$1"
    local site_info
    site_info=$(localwp_env_for_site "$site_name") 2>/dev/null || return 1
    
    IFS='|' read -r site_id site_path domain php_ver mysql_ver <<< "$site_info"
    local socket="${LOCAL_DATA}/run/${site_id}/mysql/mysqld.sock"
    
    if [[ ! -S "$socket" ]]; then
        return 1
    fi
    
    # Verify MySQL is accepting connections
    perl -e '
        use IO::Socket::UNIX;
        my $sock = IO::Socket::UNIX->new(Peer => $ARGV[0], Type => SOCK_STREAM, Timeout => 1);
        exit($sock ? 0 : 1);
    ' "$socket" 2>/dev/null
}

# List all LocalWP sites
localwp_list_sites() {
    local sites_json="${LOCAL_DATA}/sites.json"
    
    if [[ ! -f "$sites_json" ]]; then
        echo "No Local sites config found at $sites_json"
        return 1
    fi
    
    echo "FOLDER               DOMAIN                    PHP             STATUS"
    echo "------               ------                    ---             ------"
    
    python3 -c "
import json, os, subprocess
with open('$sites_json') as f:
    sites = json.load(f)
for sid, s in sites.items():
    folder = os.path.basename(s.get('path',''))
    domain = s.get('domain','')
    php = s.get('services',{}).get('php',{}).get('version','')
    name = s.get('name','')
    socket = os.path.expanduser('${LOCAL_DATA}/run/' + sid + '/mysql/mysqld.sock')
    running = os.path.exists(socket)
    status = 'running' if running else 'stopped'
    print(f'{folder.ljust(20)} {domain.ljust(25)} {php.ljust(15)} {status}')
"
}

# Run a WP-CLI command with proper environment
# Usage: localwp_wp "site-name" <wp-cli-args...>
localwp_wp() {
    local site_name="$1"
    shift
    local wp_path
    
    wp_path=$(localwp_setup_env "$site_name") || return 1
    
    # Use global wp-cli with proper PHP environment
    "${php_bin_dir}/php" \
        -d "mysqli.default_socket=${MYSQL_UNIX_PORT}" \
        -d "memory_limit=512M" \
        "$(which wp)" \
        --path="${wp_path}" \
        "$@"
}

# Get site URL
localwp_site_url() {
    local site_name="$1"
    local site_info
    site_info=$(localwp_env_for_site "$site_name") || return 1
    
    IFS='|' read -r site_id site_path domain php_ver mysql_ver <<< "$site_info"
    if [[ -n "$domain" ]]; then
        echo "http://${domain}"
    else
        echo "http://${site_name}.local"
    fi
}

# Export key variables for sourcing
export LOCAL_DATA
