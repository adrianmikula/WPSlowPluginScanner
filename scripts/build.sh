#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

PLUGIN_DIR="$PROJECT_ROOT/slow-plugin-scanner"

ENV_FILE="$PROJECT_ROOT/slow-plugin-scanner/.env"
MODE="free"
if [ -f "$ENV_FILE" ]; then
    MODE=$(grep "^PIA_MODE=" "$ENV_FILE" | cut -d'=' -f2 | tr -d ' ')
    if [ -z "$MODE" ]; then
        MODE="free"
    fi
fi

OUTPUT_DIR="$PROJECT_ROOT/build"
OUTPUT_ZIP="$OUTPUT_DIR/slow-plugin-scanner-${MODE}.zip"

EXCLUDE_DIRS="tests vendor .git"
EXCLUDE_FILES=".gitignore .distignore .phpunit.result.cache composer-setup.php .phpunit.xml composer.json composer.lock README.md .env"

echo "Building WordPress plugin ZIP..."

mkdir -p "$OUTPUT_DIR"

temp_dir=$(mktemp -d)
rm -rf "$temp_dir"/*

trap "rm -rf $temp_dir" EXIT

cp -r "$PLUGIN_DIR/." "$temp_dir/slow-plugin-scanner/"

for dir in $EXCLUDE_DIRS; do
    rm -rf "$temp_dir/slow-plugin-scanner/$dir"
done

for file in $EXCLUDE_FILES; do
    rm -f "$temp_dir/slow-plugin-scanner/$file"
done

if [ "$MODE" = "premium" ]; then
    PLUGIN_NAME="Slow Plugin Scanner Premium"
    PLUGIN_SLUG="slow-plugin-scanner-premium"
    mv "$temp_dir/slow-plugin-scanner" "$temp_dir/$PLUGIN_SLUG"
    CONFIG_PATH="$temp_dir/$PLUGIN_SLUG/config.php"
else
    PLUGIN_NAME="Slow Plugin Scanner"
    PLUGIN_SLUG="slow-plugin-scanner"
    CONFIG_PATH="$temp_dir/slow-plugin-scanner/config.php"
fi

sed -i "s/Plugin Name: Slow Plugin Scanner/Plugin Name: $PLUGIN_NAME/" "$temp_dir/$PLUGIN_SLUG/slow-plugin-scanner.php"

if [ -f "$ENV_FILE" ]; then
    CONFIG_CONTENT="<?php\n// Auto-generated config - do not commit to version control\n"
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