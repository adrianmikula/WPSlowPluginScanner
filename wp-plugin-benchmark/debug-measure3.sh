#!/bin/bash
export LOCALWP_SITE_NAME="test-plugin"
cd /media/adrian/SHARED/Source/WPSlowPluginScanner/wp-plugin-benchmark
source src/config.sh
source src/localwp.sh
site_url=$(get_site_url "test-plugin")
curl -s -w "\n%{time_total}" -A "$TEST_USER_AGENT" "${site_url}/" 2>/dev/null | tail -n1