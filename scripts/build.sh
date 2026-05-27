#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

PLUGIN_DIR="$PROJECT_ROOT/code-medic-slow-site-scanner"

ENV_FILE="$PROJECT_ROOT/code-medic-slow-site-scanner/.env"
MODE="free"
if [ -f "$ENV_FILE" ]; then
    MODE=$(grep "^CODEMEDSSS_MODE=" "$ENV_FILE" | cut -d'=' -f2 | tr -d ' ')
    if [ -z "$MODE" ]; then
        MODE="free"
    fi
fi

OUTPUT_DIR="$PROJECT_ROOT/build"
if [ "$MODE" = "premium" ]; then
    OUTPUT_ZIP="$OUTPUT_DIR/code-medic-slow-site-scanner-${MODE}.zip"
else
    OUTPUT_ZIP="$OUTPUT_DIR/code-medic-slow-site-scanner.zip"
fi

EXCLUDE_DIRS="tests vendor .git premium"
EXCLUDE_FILES=".gitignore .distignore .phpunit.result.cache composer-setup.php .phpunit.xml composer.json composer.lock README.md .env .env.example env-example admin/ui-premium.php"

echo "Building WordPress plugin ZIP..."

mkdir -p "$OUTPUT_DIR"

temp_dir=$(mktemp -d)
rm -rf "$temp_dir"/*

trap "rm -rf $temp_dir" EXIT

cp -r "$PLUGIN_DIR/." "$temp_dir/code-medic-slow-site-scanner/"

for dir in $EXCLUDE_DIRS; do
    rm -rf "$temp_dir/code-medic-slow-site-scanner/$dir"
done

for file in $EXCLUDE_FILES; do
    rm -f "$temp_dir/code-medic-slow-site-scanner/$file"
done

if [ "$MODE" = "premium" ]; then
    PLUGIN_NAME="CodeMedic Slow Site Scanner Premium"
    PLUGIN_SLUG="code-medic-slow-site-scanner-premium"
    mv "$temp_dir/code-medic-slow-site-scanner" "$temp_dir/$PLUGIN_SLUG"
    CONFIG_PATH="$temp_dir/$PLUGIN_SLUG/config.php"
    # Copy premium module back for premium build
    cp -r "$PLUGIN_DIR/premium" "$temp_dir/$PLUGIN_SLUG/"
else
    PLUGIN_NAME="CodeMedic Slow Site Scanner"
    PLUGIN_SLUG="code-medic-slow-site-scanner"
    CONFIG_PATH="$temp_dir/$PLUGIN_SLUG/config.php"
fi

sed -i "s/=== CodeMedic Slow Site Scanner ===/=== $PLUGIN_NAME ===/" "$temp_dir/$PLUGIN_SLUG/readme.txt"

sed -i "s/Plugin Name: CodeMedic Slow Site Scanner/Plugin Name: $PLUGIN_NAME/" "$temp_dir/$PLUGIN_SLUG/code-medic-slow-site-scanner.php"

if [ -f "$ENV_FILE" ]; then
    CONFIG_CONTENT="<?php\nif ( ! defined( 'ABSPATH' ) ) { exit; }\n// Auto-generated config - do not commit to version control\n"
    while IFS='=' read -r key value; do
        key=$(echo "$key" | xargs)
        value=$(echo "$value" | xargs)
        if [[ "$key" == CODEMEDSSS_* && -n "$value" ]]; then
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
