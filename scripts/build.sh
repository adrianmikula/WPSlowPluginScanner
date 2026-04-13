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
trap "rm -rf $temp_dir" EXIT

cp -r "$PLUGIN_DIR"/* "$temp_dir/"

for dir in $EXCLUDE_DIRS; do
    rm -rf "$temp_dir/$dir"
done

for file in $EXCLUDE_FILES; do
    rm -f "$temp_dir/$file"
done

if [ -f "$ENV_FILE" ]; then
    CONFIG_CONTENT="<?php\n// Auto-generated config - do not commit to version control\n"
    while IFS='=' read -r key value; do
        key=$(echo "$key" | xargs)
        value=$(echo "$value" | xargs)
        if [[ "$key" == PIA_* && -n "$value" ]]; then
            CONFIG_CONTENT+="define('$key', '$value');\n"
        fi
    done < "$ENV_FILE"
    echo -e "$CONFIG_CONTENT" > "$temp_dir/config.php"
fi

cd "$temp_dir"
zip -r "$OUTPUT_ZIP" . -q
cd -

echo "Built: $OUTPUT_ZIP"
ls -lh "$OUTPUT_ZIP"