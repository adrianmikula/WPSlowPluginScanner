#!/bin/bash

PLUGIN_DIR="wp-plugin-impact-analyzer"
OUTPUT_DIR="build"
OUTPUT_ZIP="$OUTPUT_DIR/wp-plugin-impact-analyzer.zip"

EXCLUDE_DIRS="tests vendor .git"
EXCLUDE_FILES=".gitignore .distignore .phpunit.result.cache composer-setup.php .phpunit.xml composer.json composer.lock README.md"

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

cd "$temp_dir"
zip -r "$OUTPUT_ZIP" . -q
cd -

echo "Built: $OUTPUT_ZIP"
ls -lh "$OUTPUT_ZIP"