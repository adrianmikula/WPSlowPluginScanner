#!/bin/bash
export LOCALWP_SITE_NAME="test-plugin"
cd /media/adrian/SHARED/Source/WPSlowPluginScanner/wp-plugin-benchmark
source src/config.sh
source src/localwp.sh

# Inline measure_response_time
measure_response_time() {
    local url=$1 max_time=$2
    local curl_output
    curl_output=$(env -u LD_LIBRARY_PATH curl -s -w "\n%{time_total}" --max-time "$max_time" -A "$TEST_USER_AGENT" "$url" 2>/dev/null)
    local curl_rc=$?
    [[ $curl_rc -eq 0 ]] || { echo "ERROR"; return 1; }
    local time_total
    time_total=$(echo "$curl_output" | tail -n1)
    [[ "$time_total" =~ ^[0-9]+\.?[0-9]*$ ]] || { echo "ERROR"; return 1; }
    local elapsed_ms
    elapsed_ms=$(echo "$time_total * 1000" | bc -l 2>/dev/null)
    echo "$elapsed_ms" | awk '{printf "%.0f", $1}'
}

url="http://test-plugin.local/"
result=$(measure_response_time "$url" 10)
echo "RESULT:$result"