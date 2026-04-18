#!/bin/bash
# Direct test: source test-runner and call measure function

export LOCALWP_SITE_NAME="test-plugin"
cd /media/adrian/SHARED/Source/WPSlowPluginScanner/wp-plugin-benchmark

# Source modules
source src/config.sh
source src/localwp.sh
source src/test-runner.sh

# Direct function call
url="http://test-plugin.local/"
echo "Testing URL: $url"
result=$(measure_response_time "$url" 10)
echo "FINAL_RESULT: $result"