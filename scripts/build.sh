#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

PLUGIN_DIR="$PROJECT_ROOT/codemedic-speed-scanner-for-plugins"

ENV_FILE="$PROJECT_ROOT/codemedic-speed-scanner-for-plugins/.env"
MODE="free"
if [ -f "$ENV_FILE" ]; then
    MODE=$(grep "^PIA_MODE=" "$ENV_FILE" | cut -d'=' -f2 | tr -d ' ')
    if [ -z "$MODE" ]; then
        MODE="free"
    fi
fi

OUTPUT_DIR="$PROJECT_ROOT/build"
OUTPUT_ZIP="$OUTPUT_DIR/codemedic-speed-scanner-for-plugins-${MODE}.zip"

EXCLUDE_DIRS="tests vendor .git"
EXCLUDE_FILES=".gitignore .distignore .phpunit.result.cache composer-setup.php .phpunit.xml composer.json composer.lock README.md .env .env.example env-example"

echo "Building WordPress plugin ZIP..."

mkdir -p "$OUTPUT_DIR"

temp_dir=$(mktemp -d)
rm -rf "$temp_dir"/*

trap "rm -rf $temp_dir" EXIT

cp -r "$PLUGIN_DIR/." "$temp_dir/codemedic-speed-scanner-for-plugins/"

for dir in $EXCLUDE_DIRS; do
    rm -rf "$temp_dir/codemedic-speed-scanner-for-plugins/$dir"
done

for file in $EXCLUDE_FILES; do
    rm -f "$temp_dir/codemedic-speed-scanner-for-plugins/$file"
done

if [ "$MODE" = "premium" ]; then
    PLUGIN_NAME="CodeMedic Speed Scanner for Plugins Premium"
    PLUGIN_SLUG="codemedic-speed-scanner-for-plugins-premium"
    mv "$temp_dir/codemedic-speed-scanner-for-plugins" "$temp_dir/$PLUGIN_SLUG"
    CONFIG_PATH="$temp_dir/$PLUGIN_SLUG/config.php"
else
    PLUGIN_NAME="CodeMedic Speed Scanner for Plugins"
    PLUGIN_SLUG="codemedic-speed-scanner-for-plugins"
    CONFIG_PATH="$temp_dir/codemedic-speed-scanner-for-plugins/config.php"
fi

sed -i "s/=== CodeMedic Speed Scanner for Plugins ===/=== $PLUGIN_NAME ===/" "$temp_dir/$PLUGIN_SLUG/readme.txt"

sed -i "s/Plugin Name: CodeMedic Speed Scanner for Plugins/Plugin Name: $PLUGIN_NAME/" "$temp_dir/$PLUGIN_SLUG/codemedic-speed-scanner-for-plugins.php"

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
