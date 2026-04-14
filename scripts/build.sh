#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

PLUGIN_DIR="$PROJECT_ROOT/whats-slowing-my-site"

ENV_FILE="$PROJECT_ROOT/whats-slowing-my-site/.env"
MODE="free"
if [ -f "$ENV_FILE" ]; then
    MODE=$(grep "^PIA_MODE=" "$ENV_FILE" | cut -d'=' -f2 | tr -d ' ')
    if [ -z "$MODE" ]; then
        MODE="free"
    fi
fi

OUTPUT_DIR="$PROJECT_ROOT/build"
OUTPUT_ZIP="$OUTPUT_DIR/whats-slowing-my-site-${MODE}.zip"

EXCLUDE_DIRS="tests vendor .git"
EXCLUDE_FILES=".gitignore .distignore .phpunit.result.cache composer-setup.php .phpunit.xml composer.json composer.lock README.md .env .env.example env-example"

echo "Building WordPress plugin ZIP..."

mkdir -p "$OUTPUT_DIR"

temp_dir=$(mktemp -d)
rm -rf "$temp_dir"/*

trap "rm -rf $temp_dir" EXIT

cp -r "$PLUGIN_DIR/." "$temp_dir/whats-slowing-my-site/"

for dir in $EXCLUDE_DIRS; do
    rm -rf "$temp_dir/whats-slowing-my-site/$dir"
done

for file in $EXCLUDE_FILES; do
    rm -f "$temp_dir/whats-slowing-my-site/$file"
done

if [ "$MODE" = "premium" ]; then
    PLUGIN_NAME="What's Slowing My Site Premium"
    PLUGIN_SLUG="whats-slowing-my-site-premium"
    mv "$temp_dir/whats-slowing-my-site" "$temp_dir/$PLUGIN_SLUG"
    CONFIG_PATH="$temp_dir/$PLUGIN_SLUG/config.php"
else
    PLUGIN_NAME="What's Slowing My Site"
    PLUGIN_SLUG="whats-slowing-my-site"
    CONFIG_PATH="$temp_dir/whats-slowing-my-site/config.php"
fi

sed -i "s/=== What's Slowing My Site ===/=== $PLUGIN_NAME ===/" "$temp_dir/$PLUGIN_SLUG/readme.txt"

sed -i "s/Plugin Name: What's Slowing My Site/Plugin Name: $PLUGIN_NAME/" "$temp_dir/$PLUGIN_SLUG/whats-slowing-my-site.php"

if [ -f "$ENV_FILE" ]; then
    CONFIG_CONTENT="<?php\nif ( ! defined( 'ABSPATH' ) ) { exit; }\n// Auto-generated config - do not commit to version control\n"
    while IFS='=' read -r key value; do
        key=$(echo "$key" | xargs)
        value=$(echo "$value" | xargs)
        if [[ "$key" == PIA_* && -n "$value" ]]; then
            CONFIG_CONTENT+="define('$key', '$value');\n"
        fi
    done < "$ENV_FILE"
    echo -e "$CONFIG_CONTENT" > "$CONFIG_PATH"
fi

(
    cd "$temp_dir"
    zip -r "$OUTPUT_ZIP" . -q
)

echo "Built: $OUTPUT_ZIP"
ls -lh "$OUTPUT_ZIP"