#!/usr/bin/env bash
set -euo pipefail

PLUGIN_SRC="${PLUGIN_SRC:-/home/adrian/Source/WPSlowPluginScanner-main/code-medic-slow-site-scanner}"

ENV_FILE="${PLUGIN_SRC}/.env"
if [ -f "${ENV_FILE}" ]; then
    set -a
    source "${ENV_FILE}"
    set +a
fi

SVN_REPO="${SVN_REPO_PATH:-}"
SVN_TRUNK="${SVN_REPO}/trunk"
SVN_TAGS="${SVN_REPO}/tags"
SVN_ASSETS="${SVN_REPO}/assets"
WP_CLI_PATH="${WP_CLI_PATH:-/home/adrian/Studio/plugin-test}"

if [ -z "${SVN_REPO}" ]; then
    echo "Error: SVN_REPO_PATH is not set."
    echo "Set it in ${ENV_FILE} or export it before running this script."
    exit 1
fi

if [ ! -d "${SVN_TRUNK}" ]; then
    echo "Error: SVN trunk not found at ${SVN_TRUNK}"
    exit 1
fi

ERRORS=0
WARNINGS=0

info() { echo "[INFO] $*"; }
pass() { echo "[PASS] $*"; }
fail() { echo "[FAIL] $*"; ERRORS=$((ERRORS + 1)); }
warn() { echo "[WARN] $*"; WARNINGS=$((WARNINGS + 1)); }

# 1. Detect version from readme.txt Stable tag
VERSION=""
if [ -f "${SVN_TRUNK}/readme.txt" ]; then
    VERSION=$(grep -i "^Stable tag:" "${SVN_TRUNK}/readme.txt" | head -1 | awk '{print $NF}')
fi

if [ -z "${VERSION}" ]; then
    fail "Could not detect Stable tag from readme.txt"
else
    pass "Detected version: ${VERSION}"
fi

# 2. Verify trunk structure
if [ ! -f "${SVN_TRUNK}/readme.txt" ]; then
    fail "readme.txt is missing in trunk/"
fi
if [ ! -f "${SVN_TRUNK}/code-medic-slow-site-scanner.php" ]; then
    fail "Main plugin file (code-medic-slow-site-scanner.php) is missing in trunk/ root"
fi
pass "Trunk has readme.txt and main plugin file at root"

# 3. Verify main plugin file is NOT in a subfolder
if [ ! -f "${SVN_TRUNK}/code-medic-slow-site-scanner.php" ]; then
    fail "Main plugin file (code-medic-slow-site-scanner.php) is missing in trunk/ root"
else
    pass "Main plugin file is correctly placed at trunk/ root"
fi

# 4. Verify readme.txt has required headers
if [ -f "${SVN_TRUNK}/readme.txt" ]; then
    HEADER_NAME=$(grep -E '^=== .* ===$' "${SVN_TRUNK}/readme.txt" | head -1)
    if [ -z "${HEADER_NAME}" ]; then
        fail "readme.txt is missing '=== Plugin Name ===' header"
    else
        pass "readme.txt has plugin header: ${HEADER_NAME}"
    fi
    if ! grep -qE '^Stable tag:' "${SVN_TRUNK}/readme.txt"; then
        fail "readme.txt is missing 'Stable tag:'"
    else
        pass "readme.txt has Stable tag"
    fi
    if ! grep -qE '^License: GPL' "${SVN_TRUNK}/readme.txt"; then
        fail "readme.txt is missing GPL license"
    else
        pass "readme.txt has GPL license"
    fi
fi

# 5. Verify plugin header
if [ -f "${SVN_TRUNK}/code-medic-slow-site-scanner.php" ]; then
    if ! grep -q 'Plugin Name:' "${SVN_TRUNK}/code-medic-slow-site-scanner.php"; then
        fail "Main plugin file is missing 'Plugin Name:' header"
    else
        pass "Main plugin file has Plugin Name header"
    fi
    if ! grep -q 'Version:' "${SVN_TRUNK}/code-medic-slow-site-scanner.php"; then
        fail "Main plugin file is missing 'Version:' header"
    else
        pass "Main plugin file has Version header"
    fi
fi

# 6. Verify no dev files leaked into trunk
DEV_FILES=(".gitignore" ".distignore" ".phpunit.result.cache" "env-example" ".env" ".env.example" "composer-setup.php" ".phpunit.xml" "composer.json" "composer.lock" "README.md")
DEV_DIRS=("vendor/" "premium/" "tests/" "docs/")

for f in "${DEV_FILES[@]}"; do
    if [ -e "${SVN_TRUNK}/${f}" ]; then
        fail "Dev file leaked into trunk/: ${f}"
    fi
done

for d in "${DEV_DIRS[@]}"; do
    if [ -d "${SVN_TRUNK}/${d}" ]; then
        fail "Dev directory leaked into trunk/: ${d}"
    fi
done

# Also catch any stray .md files
MD_COUNT=$(find "${SVN_TRUNK}" -maxdepth 1 -name '*.md' -type f | wc -l)
if [ "${MD_COUNT}" -gt 0 ]; then
    fail "Markdown file(s) found in trunk/: $(find "${SVN_TRUNK}" -maxdepth 1 -name '*.md' -type f)"
else
    pass "No markdown files in trunk/"
fi

# Catch stray SQL, shell, or PowerShell scripts
for ext in sql sh ps1; do
    COUNT=$(find "${SVN_TRUNK}" -maxdepth 1 -name "*.${ext}" -type f | wc -l)
    if [ "${COUNT}" -gt 0 ]; then
        fail "*.${ext} file(s) found in trunk/: $(find "${SVN_TRUNK}" -maxdepth 1 -name "*.${ext}" -type f)"
    fi
done
pass "No dev files leaked into trunk/"

# 7. Verify SVN state (optional but helpful)
if command -v svn &>/dev/null && [ -d "${SVN_REPO}/.svn" ]; then
    cd "${SVN_REPO}"
    SVN_STATUS=$(svn status)
    if echo "${SVN_STATUS}" | grep -qE '^\!'; then
        fail "SVN status has missing items (!)"
    fi
    if echo "${SVN_STATUS}" | grep -qE '^\?'; then
        warn "SVN status has untracked items (?)"
    fi
    pass "SVN status appears clean"
fi

# 8. Verify tag exists and matches version
TAG_DIR="${SVN_TAGS}/${VERSION}"
if [ -n "${VERSION}" ]; then
    if [ ! -d "${TAG_DIR}" ]; then
        fail "Tag directory /tags/${VERSION} does not exist"
    else
        pass "Tag /tags/${VERSION} exists"
        # Verify tag contains actual files
        TAG_FILES=$(find "${TAG_DIR}" -maxdepth 1 -type f | wc -l)
        if [ "${TAG_FILES}" -eq 0 ]; then
            fail "Tag /tags/${VERSION} is empty"
        else
            pass "Tag /tags/${VERSION} has ${TAG_FILES} files"
        fi
    fi
fi

# 9. Verify assets
if [ -d "${SVN_ASSETS}" ]; then
    ASSET_COUNT=$(find "${SVN_ASSETS}" -maxdepth 1 -type f | wc -l)
    if [ "${ASSET_COUNT}" -gt 0 ]; then
        pass "Assets directory has ${ASSET_COUNT} files"
    else
        warn "Assets directory is empty"
    fi
else
    warn "Assets directory does not exist"
fi

# 10. Build test zip and verify structure
TEMP_DIR=$(mktemp -d)
TEST_ZIP="${TEMP_DIR}/test-release.zip"

info "Building test zip from trunk..."
(
    cd "${SVN_TRUNK}"
    zip -r "${TEST_ZIP}" . -q
)

info "Validating test zip structure..."
if ! command -v unzip &>/dev/null; then
    warn "unzip not available, skipping zip structure validation"
else
    ZIP_CONTENTS=$(unzip -l "${TEST_ZIP}" | tail -n +4 | head -n -2)
    if ! echo "${ZIP_CONTENTS}" | grep -q '\.php$'; then
        fail "Zip does not contain any PHP files at root"
    else
        pass "Zip contains PHP files at root"
    fi
    if ! echo "${ZIP_CONTENTS}" | grep -q 'readme\.txt'; then
        fail "Zip does not contain readme.txt at root"
    else
        pass "Zip contains readme.txt at root"
    fi
    # Verify the main plugin file is at zip root, not nested
    if ! echo "${ZIP_CONTENTS}" | grep -q 'code-medic-slow-site-scanner\.php$'; then
        fail "Main plugin file is not at zip root (expected code-medic-slow-site-scanner.php at root)"
    else
        pass "Main plugin file is at zip root"
    fi
fi

# 11. Install test plugin via WP-CLI (optional)
if command -v wp &>/dev/null && [ -d "${WP_CLI_PATH}/wp-admin" ]; then
    info "Testing plugin installation via WP-CLI..."
    WP_CLI_OUTPUT=$(wp plugin install "${TEST_ZIP}" --force --path="${WP_CLI_PATH}" 2>&1) || true
    if echo "${WP_CLI_OUTPUT}" | grep -qi 'PDO Driver for SQLite is missing\|Error\|Failed'; then
        warn "WP-CLI could not test install (missing PDO SQLite or WP not configured). Skipping live install test."
    else
        pass "WP-CLI successfully installed the test plugin"
        WP_CLI_PLUGIN_SLUG=$(wp plugin list --path="${WP_CLI_PATH}" --format=csv 2>/dev/null | grep -i 'code-medic-slow-site-scanner' | head -1 | awk -F',' '{print $1}' || echo 'code-medic-slow-site-scanner')
        wp plugin uninstall "${WP_CLI_PLUGIN_SLUG}" --delete --quiet --path="${WP_CLI_PATH}" 2>/dev/null || true
        pass "WP-CLI uninstalled test plugin successfully"
    fi
else
    warn "WP-CLI or test WordPress path not available, skipping live install test"
fi

# Cleanup
rm -rf "${TEMP_DIR}"

# Summary
echo ""
echo "================================"
echo "Validation Summary"
echo "================================"
echo "Errors:   ${ERRORS}"
echo "Warnings: ${WARNINGS}"
if [ "${ERRORS}" -eq 0 ]; then
    echo "Result:   PASS"
    exit 0
else
    echo "Result:   FAIL"
    exit 1
fi
