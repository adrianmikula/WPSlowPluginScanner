#!/bin/bash
# Single iteration test with debug output

export LOCALWP_SITE_NAME="test-plugin"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/src/config.sh"
source "$SCRIPT_DIR/src/localwp.sh"

site_url=$(get_site_url "test-plugin")
test_url="${site_url}/"
max_time=10

echo "Testing URL: $test_url"

# Call measure_response_time logic directly
curl_output=$(env -u LD_LIBRARY_PATH curl -s -w "\n%{time_total}" --max-time "$max_time" -A "$TEST_USER_AGENT" "$test_url" 2>/dev/null)
rc=$?
echo "curl exit: $rc"
echo "output: '$curl_output'"

time_total=$(echo "$curl_output" | tail -n1)
echo "time_total: '$time_total'"

if [[ "$time_total" =~ ^[0-9]+\.?[0-9]*$ ]]; then
    elapsed_ms=$(echo "$time_total * 1000" | bc -l 2>/dev/null)
    rounded=$(echo "$elapsed_ms" | awk '{printf "%.0f", $1}')
    echo "elapsed_ms: $elapsed_ms"
    echo "rounded: $rounded"
else
    echo "INVALID"
fi