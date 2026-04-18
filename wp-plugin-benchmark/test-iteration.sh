#!/bin/bash
export LOCALWP_SITE_NAME="test-plugin"
cd /media/adrian/SHARED/Source/WPSlowPluginScanner/wp-plugin-benchmark
source src/config.sh
source src/localwp.sh
source src/test-runner.sh

# Run a single iteration
result=$(run_test_iteration "test-plugin" "test-plugin" "1")
echo "ITERATION_RESULT:$result"