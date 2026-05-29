#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

PLUGIN_DIR="$PROJECT_ROOT/code-medic-slow-site-scanner"
ENV_FILE="$PLUGIN_DIR/.env"
OUTPUT_DIR="$PROJECT_ROOT/build"

EXCLUDE_DIRS="tests vendor .git premium"
EXCLUDE_FILES=".gitignore .distignore .phpunit.result.cache composer-setup.php .phpunit.xml composer.json composer.lock README.md .env .env.example env-example admin/ui-premium.php"

mkdir -p "$OUTPUT_DIR"

build_plugin() {
    local mode="$1"
    local temp_dir
    temp_dir=$(mktemp -d)

    cp -r "$PLUGIN_DIR/." "$temp_dir/code-medic-slow-site-scanner/"

    for dir in $EXCLUDE_DIRS; do
        rm -rf "$temp_dir/code-medic-slow-site-scanner/$dir"
    done

    for file in $EXCLUDE_FILES; do
        rm -f "$temp_dir/code-medic-slow-site-scanner/$file"
    done

    if [ "$mode" = "premium" ]; then
        local plugin_name="CodeMedic Slow Site Scanner Premium"
        local plugin_slug="code-medic-slow-site-scanner-premium"
        local output_zip="$OUTPUT_DIR/code-medic-slow-site-scanner-premium.zip"
        mv "$temp_dir/code-medic-slow-site-scanner" "$temp_dir/$plugin_slug"
        # Copy premium module back for premium build
        cp -r "$PLUGIN_DIR/premium" "$temp_dir/$plugin_slug/"
    else
        local plugin_name="CodeMedic Slow Site Scanner"
        local plugin_slug="code-medic-slow-site-scanner"
        local output_zip="$OUTPUT_DIR/code-medic-slow-site-scanner.zip"
    fi

    local config_path="$temp_dir/$plugin_slug/config.php"

    sed -i "s/=== CodeMedic Slow Site Scanner ===/=== $plugin_name ===/" "$temp_dir/$plugin_slug/readme.txt"
    sed -i "s/Plugin Name: CodeMedic Slow Site Scanner/Plugin Name: $plugin_name/" "$temp_dir/$plugin_slug/code-medic-slow-site-scanner.php"

    if [ ! -f "$ENV_FILE" ]; then
        echo "ERROR: .env file not found at $ENV_FILE"
        exit 1
    fi

    local env_premium_url
    env_premium_url=$(grep "^CODEMEDSSS_PREMIUM_URL=" "$ENV_FILE" | cut -d'=' -f2- | xargs)
    local env_supabase_url
    env_supabase_url=$(grep "^CODEMEDSSS_SUPABASE_URL=" "$ENV_FILE" | cut -d'=' -f2- | xargs)
    local env_supabase_key
    env_supabase_key=$(grep "^CODEMEDSSS_SUPABASE_ANON_KEY=" "$ENV_FILE" | cut -d'=' -f2- | xargs)

    if [ "$mode" = "free" ] && [ -z "$env_premium_url" ]; then
        echo "ERROR (free build): CODEMEDSSS_PREMIUM_URL is not set in .env — upsell notice would be suppressed."
        exit 1
    fi

    if [ "$mode" = "premium" ] && [ -z "$env_supabase_url" ]; then
        echo "ERROR (premium build): CODEMEDSSS_SUPABASE_URL is not set in .env"
        exit 1
    fi

    if [ "$mode" = "premium" ] && [ -z "$env_supabase_key" ]; then
        echo "ERROR (premium build): CODEMEDSSS_SUPABASE_ANON_KEY is not set in .env"
        exit 1
    fi

    if [ -f "$ENV_FILE" ]; then
        local config_content="<?php\nif ( ! defined( 'ABSPATH' ) ) { exit; }\n// Auto-generated config - do not commit to version control\n"
        while IFS='=' read -r key value; do
            key=$(echo "$key" | xargs)
            value=$(echo "$value" | xargs)
            if [[ "$mode" == "free" && "$key" =~ ^CODEMEDSSS_(SUPABASE_URL|SUPABASE_ANON_KEY|SUPABASE_TABLE)$ ]]; then
                continue
            fi
            if [[ "$key" == CODEMEDSSS_* && "$key" != "CODEMEDSSS_MODE" && -n "$value" ]]; then
                config_content+="define('$key', '$value');\n"
            fi
        done < "$ENV_FILE"
        echo -e "$config_content" > "$config_path"
    fi

    (
        cd "$temp_dir"
        zip -r "$output_zip" . -q
    )

    rm -rf "$temp_dir"

    echo "Built: $output_zip"
    ls -lh "$output_zip"
}

echo "Building WordPress plugin ZIPs..."

build_plugin "free"
build_plugin "premium"
