#!/usr/bin/env bash
set -euo pipefail

# Load environment variables from .env file
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

if [ -z "${SVN_REPO}" ]; then
    echo "Error: SVN_REPO_PATH is not set."
    echo "Set it in ${ENV_FILE} or export it before running this script."
    exit 1
fi

# Detect version from readme.txt Stable Tag
VERSION="${1:-}"
if [ -z "${VERSION}" ]; then
    VERSION=$(grep -i "^Stable tag:" "${PLUGIN_SRC}/readme.txt" | awk '{print $NF}')
fi

if [ -z "${VERSION}" ]; then
    echo "Error: Could not detect version. Pass it as an argument or set Stable Tag in readme.txt."
    exit 1
fi

echo "Using version: ${VERSION}"

# Clean trunk
echo "Cleaning ${SVN_TRUNK}..."
rm -rf "${SVN_TRUNK:?}"/*
mkdir -p "${SVN_TRUNK}"

# Copy plugin files to trunk, respecting .distignore
echo "Copying plugin files to trunk..."
DISTIGNORE="${PLUGIN_SRC}/.distignore"
EXCLUDE_PATTERNS=()

if [ -f "${DISTIGNORE}" ]; then
    while IFS= read -r line || [ -n "$line" ]; do
        # Skip empty lines and comments
        [[ -z "$line" ]] && continue
        [[ "$line" =~ ^# ]] && continue
        EXCLUDE_PATTERNS+=("$line")
    done < "${DISTIGNORE}"
fi

# Copy files, excluding patterns from .distignore
cd "${PLUGIN_SRC}"

rsync -av --delete \
    $(printf -- '--exclude=%s ' "${EXCLUDE_PATTERNS[@]}") \
    "${PLUGIN_SRC}/" "${SVN_TRUNK}/"

# Ensure excluded patterns are not present in trunk (rsync --delete may not remove excluded files)
cd "${SVN_TRUNK}"
for pattern in "${EXCLUDE_PATTERNS[@]}"; do
    [[ -z "$pattern" ]] && continue
    [[ "$pattern" =~ ^# ]] && continue
    for f in ${pattern}; do
        if [ -e "${SVN_TRUNK}/${f}" ]; then
            rm -rf "${SVN_TRUNK}/${f}"
        fi
    done
done

# Mark files added/removed in SVN so status is clean
if command -v svn &>/dev/null && [ -d "${SVN_REPO}/.svn" ]; then
    cd "${SVN_REPO}"
    svn add trunk/ --force 2>/dev/null || true
    # Remove any tracked files that no longer exist
    MISSING=$(svn status trunk/ | grep '^!' || true | awk '{print $2}')
    [ -n "${MISSING}" ] && xargs -r svn rm <<< "${MISSING}" || true
fi

echo "Plugin files copied to trunk."

# Copy assets
echo "Copying assets..."
ASSETS_SRC="/home/adrian/Source/WPSlowPluginScanner-main/assets"
if [ -d "${ASSETS_SRC}" ] && [ "$(ls -A "${ASSETS_SRC}")" ]; then
    rm -rf "${SVN_ASSETS:?}"/*
    rsync -av "${ASSETS_SRC}/" "${SVN_ASSETS}/"
    if command -v svn &>/dev/null && [ -d "${SVN_REPO}/.svn" ]; then
        cd "${SVN_REPO}"
        svn add assets/ --force 2>/dev/null || true
        MISSING=$(svn status assets/ | grep '^!' || true | awk '{print $2}')
        [ -n "${MISSING}" ] && xargs -r svn rm <<< "${MISSING}" || true
    fi
    echo "Assets copied."
else
    echo "No assets to copy."
fi

# Optionally create tag
if [ -n "${VERSION}" ]; then
    TAG_DIR="${SVN_TAGS}/${VERSION}"
    if [ -d "${TAG_DIR}" ]; then
        echo "Tag ${VERSION} already exists. Skipping tag creation."
    else
        echo "Creating tag ${VERSION}..."
        svn copy "${SVN_TRUNK}" "${TAG_DIR}"
        echo "Tag created. Remember to svn commit if you haven't already."
    fi
fi

echo "Done. Remember to svn commit if you haven't already."
