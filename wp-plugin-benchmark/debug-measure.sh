#!/bin/bash
# Debug measurement

export LOCALWP_SITE_NAME="test-plugin"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

source "$SCRIPT_DIR/src/config.sh"
source "$SCRIPT_DIR/src/localwp.sh"

site_url=$(get_site_url "test-plugin")
echo "Site URL: $site_url"

test_url="${site_url}/"
echo "Test URL: $test_url"

max_time=10
TEST_USER_AGENT="WP-Benchmark/1.0"

echo "Running curl..."
curl_output=$(env -u LD_LIBRARY_PATH curl -s -w "\n%{time_total}" --max-time "$max_time" -A "$TEST_USER_AGENT" "$test_url" 2>/dev/null)
echo "Curl exit code: $?"
echo "Output (last 3 lines):"
echo "$curl_output" | tail -3

time_total=$(echo "$curl_output" | tail -n1)
echo "time_total='$time_total'"

if [[ "$time_total" =~ ^[0-9]+\.?[0-9]*$ ]]; then
    echo "VALID NUMBER"
    elapsed_ms=$(echo "$time_total * 1000" | bc -l)
    echo "elapsed_ms=$elapsed_ms"
    echo "Rounded: ${elapsed_ms%.*}"
else
    echo "INVALID - not a number"
fi