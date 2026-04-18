#!/bin/bash
# Performance Test Runner - Execute benchmark tests for plugins

source "$(dirname "${BASH_SOURCE[0]}")/config.sh"
source "$(dirname "${BASH_SOURCE[0]}")/plugins.sh"
source "$(dirname "${BASH_SOURCE[0]}")/localwp.sh"

# Track current batch and plugin for logging
CURRENT_BATCH=""
CURRENT_PLUGIN=""
PROGRESS_FILE="$LOGS_DIR/progress.json"

# Initialize progress tracking
init_progress() {
    echo "{\"total_batches\":10,\"current_batch\":0,\"completed_plugins\":[],\"failed_plugins\":[]}" > "$PROGRESS_FILE"
}

# Update progress after completing a plugin
mark_plugin_complete() {
    local batch_num=$1
    local plugin_slug=$2
    local status=$3  # "success" or "failed"
    
    # Use jq to update JSON if available
    if command -v jq &> /dev/null; then
        local temp_file=$(mktemp)
        jq ".completed_plugins += [{\"batch\":$batch_num,\"plugin\":\"$plugin_slug\",\"status\":\"$status\",\"timestamp\":\"$(date -Iseconds)\"}]" "$PROGRESS_FILE" > "$temp_file"
        mv "$temp_file" "$PROGRESS_FILE"
    else
        echo "$batch_num,$plugin_slug,$status,$(date)" >> "$LOGS_DIR/completed_plugins.csv"
    fi
}

# Measure response time for a given URL
# Returns time in milliseconds
measure_response_time() {
    local url=$1
    local max_time=$2
    local start_time end_time elapsed_ms
    
    # Use curl with timing metrics
    local curl_output=$(curl -s -w "\n%{time_total}" --max-time "$max_time" -A "$TEST_USER_AGENT" "$url" 2>/dev/null)
    
    if [[ $? -ne 0 ]]; then
        echo "ERROR"
        return 1
    fi
    
    # Extract time (last line)
    local time_total=$(echo "$curl_output" | tail -n1)
    
    # Convert to milliseconds
    elapsed_ms=$(echo "$time_total * 1000" | bc -l 2>/dev/null || echo "0")
    
    # Round to integer
    echo "${elapsed_ms%.*}"
    return 0
}

# Run a single test iteration for a plugin
# Returns time in ms or "ERROR"
run_test_iteration() {
    local site_name=$1
    local plugin_slug=$2
    local iteration=$3
    
    log_info "Test iteration $iteration for $plugin_slug on $site_name"
    
    # Get site URL
    local site_url=$(get_site_url "$site_name")
    local test_url="${site_url}/"
    
    # Measure response time
    local response_time=$(measure_response_time "$test_url" "$TEST_REQUEST_TIMEOUT")
    
    if [[ "$response_time" == "ERROR" ]]; then
        log_error "Iteration $iteration failed: site unreachable"
        echo "ERROR"
        return 1
    fi
    
    log_info "Iteration $iteration: ${response_time}ms"
    echo "$response_time"
    return 0
}

# Run all test iterations for a plugin and calculate stats
# Arguments: site_name, plugin_slug
# Returns echo JSON result to stdout
test_plugin() {
    local site_name=$1
    local plugin_slug=$2
    local batch_num=$3
    
    CURRENT_PLUGIN="$plugin_slug"
    log_info "Testing plugin: $plugin_slug (batch $batch_num)"
    
    local test_runs=()
    local errors=0
    
    # Run iterations
    for i in $(seq 1 $RUNS_PER_PLUGIN); do
        local result=$(run_test_iteration "$site_name" "$plugin_slug" "$i")
        
        if [[ "$result" == "ERROR" ]]; then
            ((errors++))
            test_runs+=("null")
        else
            test_runs+=("$result")
        fi
        
        # Small delay between tests
        sleep 1
    done
    
    # Check if too many failures
    if [[ $errors -ge $((RUNS_PER_PLUGIN / 2 + 1)) ]]; then
        log_error "Plugin $plugin_slug had too many failures ($errors/$RUNS_PER_PLUGIN)"
        mark_plugin_complete "$batch_num" "$plugin_slug" "failed"
        return 1
    fi
    
    # Calculate statistics
    local sum=0
    local count=0
    local min_val=999999
    local max_val=0
    
    for time in "${test_runs[@]}"; do
        if [[ "$time" != "null" ]]; then
            ((sum += time))
            ((count++))
            (( time < min_val )) && min_val=$time
            (( time > max_val )) && max_val=$time
        fi
    done
    
    if [[ $count -eq 0 ]]; then
        log_error "All iterations failed for $plugin_slug"
        mark_plugin_complete "$batch_num" "$plugin_slug" "failed"
        return 1
    fi
    
    local avg_time=$((sum / count))
    
    # Calculate standard deviation
    local sum_sq_diff=0
    for time in "${test_runs[@]}"; do
        if [[ "$time" != "null" ]]; then
            local diff=$((time - avg_time))
            ((sum_sq_diff += diff * diff))
        fi
    done
    local variance=$((sum_sq_diff / count))
    local std_dev=$(echo "sqrt($variance)" | bc -l 2>/dev/null || echo "0")
    std_dev=${std_dev%.*}
    
    # Get baseline (site without plugin) - should be measured earlier
    local baseline_time="${BASELINE_TIME:-0}"
    local delta=$((avg_time - baseline_time))
    
    # Get plugin version
    local plugin_version=$(lwp --site="$site_name" plugin get "$plugin_slug" --field=version 2>/dev/null | tail -1)
    plugin_version="${plugin_version:-unknown}"
    
    # Get WordPress and PHP versions
    local wp_version=$(get_wp_version "$site_name")
    local php_version=$(get_php_version "$site_name")
    
    # Build result JSON
    local result_json=$(cat << EOF
{
  "test_type": "benchmark",
  "batch_id": "run-$(printf "%03d" "$batch_num")",
  "plugin_slug": "$plugin_slug",
  "plugin_version": "$plugin_version",
  "test_runs": [$(printf '%s,' "${test_runs[@]}" | sed 's/,$//')],
  "avg_time_ms": $avg_time,
  "std_dev_ms": $std_dev,
  "baseline_time_ms": $baseline_time,
  "delta_ms": $delta,
  "error": null,
  "error_category": null,
  "wp_version": "$wp_version",
  "php_version": "$php_version",
  "timestamp": $(date +%s)
}
EOF
)
    
    log_success "Plugin $plugin_slug: avg=${avg_time}ms, std_dev=${std_dev}ms, delta=${delta}ms"
    
    # Store result for later upload
    echo "$result_json" > "$LOGS_DIR/result-$plugin_slug.json"
    
    mark_plugin_complete "$batch_num" "$plugin_slug" "success"
    return 0
}

# Measure baseline performance (site with no extra plugins)
measure_baseline() {
    local site_name=$1
    
    log_info "Measuring baseline performance..."
    
    local times=()
    for i in $(seq 1 3); do
        local time=$(run_test_iteration "$site_name" "baseline" "$i")
        if [[ "$time" != "ERROR" ]]; then
            times+=("$time")
        fi
        sleep 1
    done
    
    if [[ ${#times[@]} -eq 0 ]]; then
        log_error "Baseline measurement failed"
        BASELINE_TIME=0
        return 1
    fi
    
    local sum=0
    for t in "${times[@]}"; do ((sum += t)); done
    BASELINE_TIME=$((sum / ${#times[@]}))
    
    log_info "Baseline time: ${BASELINE_TIME}ms"
    return 0
}

# Test a single batch of plugins
# Arguments: batch_number (1-10)
run_batch() {
    local batch_num=$1
    CURRENT_BATCH="$batch_num"
    
    log_info "=========================================="
    log_info "Starting Batch $batch_num"
    log_info "=========================================="
    
    # Calculate batch range (0-indexed for jq)
    local start_idx=$(( (batch_num - 1) * BATCH_SIZE ))
    local plugin_slugs=($(get_batch_plugins "$start_idx" "$BATCH_SIZE"))
    
    if [[ ${#plugin_slugs[@]} -eq 0 ]]; then
        log_error "No plugins found for batch $batch_num"
        return 1
    fi
    
    log_info "Batch $batch_num plugins: ${plugin_slugs[*]}"
    
    # Create site for this batch
    local site_name="${SITE_PREFIX}-${batch_num}-$(date +%s)"
    log_info "Creating site: $site_name"
    
    # Note: lwp cannot create sites; we need to use an existing site or manual creation
    # For now, check if we can use a pre-existing site or create via alternative
    if [[ -n "$LOCALWP_SITE_NAME" ]]; then
        site_name="$LOCALWP_SITE_NAME"
        log_info "Using provided site: $site_name"
    else
        log_warn "lwp cannot create sites. Please create site '$site_name' manually in LocalWP first."
        log_info "Attempting to use site anyway..."
    fi
    
    # Verify site is accessible
    if ! ensure_site_running "$site_name"; then
        log_error "Site $site_name is not accessible"
        return 1
    fi
    
    # Measure baseline before installing any plugins
    measure_baseline "$site_name" || log_warn "Baseline measurement failed, continuing..."
    
    # Process each plugin in batch
    local success_count=0
    local fail_count=0
    
    for plugin_slug in "${plugin_slugs[@]}"; do
        log_info "----------------------------------------"
        log_info "Processing: $plugin_slug"
        
        # Install plugin
        if ! install_plugin "$site_name" "$plugin_slug"; then
            log_error "Failed to install $plugin_slug, skipping..."
            ((fail_count++))
            mark_plugin_complete "$batch_num" "$plugin_slug" "failed"
            continue
        fi
        
        # Run performance tests
        if test_plugin "$site_name" "$plugin_slug" "$batch_num"; then
            ((success_count++))
        else
            ((fail_count++))
        fi
        
        # Cleanup: deactivate and delete plugin
        deactivate_plugin "$site_name" "$plugin_slug" 2>/dev/null
        delete_plugin "$site_name" "$plugin_slug" 2>/dev/null
        
        # Brief pause between plugins
        sleep 2
    done
    
    log_info "----------------------------------------"
    log_info "Batch $batch_num complete: $success_count success, $fail_count failed"
    
    # Cleanup batch (remove all test plugins)
    cleanup_batch "$site_name"
    
    # Optionally delete site if we created it specifically
    if [[ -z "$LOCALWP_SITE_NAME" ]]; then
        log_info "Site $site_name will remain. Delete manually via Local app when done."
    fi
    
    CURRENT_BATCH=""
    return 0
}

# Run all 10 batches
run_full_benchmark() {
    log_info "Starting full benchmark: 10 batches × $BATCH_SIZE plugins"
    
    init_progress
    
    for batch in $(seq 1 10); do
        if run_batch "$batch"; then
            log_info "Batch $batch completed successfully"
        else
            log_error "Batch $batch failed or had errors"
        fi
        
        # Small break between batches
        sleep 5
    done
    
    log_info "=========================================="
    log_info "Benchmark complete! All results saved to $LOGS_DIR"
    log_success "Full benchmark run finished"
}

# Show current progress
show_status() {
    if [[ ! -f "$PROGRESS_FILE" ]]; then
        echo "No benchmark in progress. Run 'wp-benchmark init' then 'wp-benchmark run'"
        return 0
    fi
    
    echo "Benchmark Status:"
    echo "-----------------"
    
    if command -v jq &> /dev/null; then
        local completed=$(jq 'length' "$PROGRESS_FILE" 2>/dev/null || echo "0")
        echo "Plugins tested: $completed / 100"
        echo ""
        echo "Recent activity:"
        jq -r '.[-5:] | "  \(.plugin) - \(.status) (\(.timestamp))"' "$PROGRESS_FILE" 2>/dev/null
    else
        if [[ -f "$LOGS_DIR/completed_plugins.csv" ]]; then
            local count=$(wc -l < "$LOGS_DIR/completed_plugins.csv")
            echo "Plugins tested: $count / 100"
            echo ""
            echo "Recent activity:"
            tail -5 "$LOGS_DIR/completed_plugins.csv" | while IFS=',' read -r batch plugin status timestamp; do
                echo "  $plugin - $status ($timestamp)"
            done
        fi
    fi
}

# Show recent logs
show_logs() {
    local lines=${1:-50}
    if [[ -f "$LOG_FILE" ]]; then
        tail -"$lines" "$LOG_FILE"
    else
        log_warn "No log file found"
    fi
}

# Export functions for use by main script
# (Already sourced, so functions are available)
