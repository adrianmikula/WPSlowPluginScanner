#!/bin/bash
# Results Aggregation & Supabase Upload

source "$(dirname "${BASH_SOURCE[0]}")/config.sh"
source "$(dirname "${BASH_SOURCE[0]}")/plugins.sh"

# Results directory
RESULTS_DIR="$LOGS_DIR/results"
mkdir -p "$RESULTS_DIR"

# Aggregate all results from a batch
aggregate_batch_results() {
    local batch_num=$1
    local result_files=()
    
    # Find all result JSON files for this batch
    if [[ -d "$RESULTS_DIR" ]]; then
        result_files=($(find "$RESULTS_DIR" -name "result-*_batch${batch_num}.json" 2>/dev/null))
    fi
    
    if [[ ${#result_files[@]} -eq 0 ]]; then
        log_warn "No result files found for batch $batch_num"
        return 1
    fi
    
    # Combine into batch summary
    local batch_summary="$RESULTS_DIR/batch-${batch_num}-summary.json"
    
    # Create array of results
    echo "[" > "$batch_summary"
    local first=true
    for file in "${result_files[@]}"; do
        if [[ "$first" != true ]]; then
            echo "," >> "$batch_summary"
        fi
        cat "$file"
        first=false
    done
    echo "]" >> "$batch_summary"
    
    log_info "Batch $batch_num summary created: $batch_summary"
}

# Upload single result to Supabase
upload_result() {
    local result_file=$1
    
    if [[ -z "$SUPABASE_URL" || -z "$SUPABASE_KEY" ]]; then
        log_warn "Supabase credentials not set - skipping upload"
        log_info "Set SUPABASE_URL and SUPABASE_KEY environment variables"
        return 1
    fi
    
    if [[ ! -f "$result_file" ]]; then
        log_error "Result file not found: $result_file"
        return 1
    fi
    
    log_info "Uploading result to Supabase..."
    
    # Use PHP uploader to include test_type field
    local php_uploader="$BENCHMARK_DIR/bin/upload-results.php"
    if [[ -f "$php_uploader" ]]; then
        php "$php_uploader" --file="$result_file" --url="$SUPABASE_URL" --key="$SUPABASE_KEY" --table="$SUPABASE_TABLE" --test-type="benchmark"
        if [[ $? -eq 0 ]]; then
            log_success "Uploaded to Supabase (benchmark)"
            return 0
        else
            log_error "Upload failed"
            return 1
        fi
    else
        log_error "PHP uploader not found: $php_uploader"
        return 1
    fi
}

# Upload all results from current batch
upload_batch_results() {
    local batch_num=$1
    local uploaded=0
    local failed=0
    
    log_info "Uploading batch $batch_num results..."
    
    # Find all result files for current batch
    local result_files=($(find "$RESULTS_DIR" -name "result-*.json" -newer "$PROGRESS_FILE" 2>/dev/null))
    
    if [[ ${#result_files[@]} -eq 0 ]]; then
        # If no new files, get all for this batch
        result_files=($(find "$RESULTS_DIR" -name "result-*.json" 2>/dev/null))
    fi
    
    for result_file in "${result_files[@]}"; do
        if upload_result "$result_file"; then
            ((uploaded++))
        else
            ((failed++))
        fi
    done
    
    log_info "Upload complete: $uploaded success, $failed failed"
}

# Upload all pending results
upload_all_results() {
    local result_files=($(find "$RESULTS_DIR" -name "result-*.json" 2>/dev/null))
    local total=${#result_files[@]}
    local uploaded=0
    local failed=0
    
    log_info "Uploading all pending results ($total files)..."
    
    for result_file in "${result_files[@]}"; do
        if upload_result "$result_file"; then
            ((uploaded++))
        else
            ((failed++))
        fi
    done
    
    log_info "Upload complete: $uploaded success, $failed failed"
    return $failed
}

# Generate summary report
generate_summary() {
    local output_file="$RESULTS_DIR/benchmark-summary.json"
    
    log_info "Generating benchmark summary..."
    
    # Collect all results into an array
    local all_results="[]"
    if command -v jq &> /dev/null; then
        local result_files=($(find "$RESULTS_DIR" -name "result-*.json" 2>/dev/null))
        if [[ ${#result_files[@]} -gt 0 ]]; then
            # Build array by reading each file as an element
            all_results="["
            local first=true
            for file in "${result_files[@]}"; do
                if [[ "$first" != true ]]; then
                    all_results+=","
                fi
                all_results+="$(cat "$file")"
                first=false
            done
            all_results+="]"
        fi
    fi
    
    # Calculate statistics
    local total_tested=$(echo "$all_results" | jq 'length' 2>/dev/null || echo "0")
    local avg_performance=$(echo "$all_results" | jq '[.[] | .avg_time_ms] | add / length' 2>/dev/null || echo "0")
    local slowest=$(echo "$all_results" | jq -r 'max_by(.avg_time_ms) | .plugin_slug' 2>/dev/null || echo "N/A")
    local fastest=$(echo "$all_results" | jq -r 'min_by(.avg_time_ms) | .plugin_slug' 2>/dev/null || echo "N/A")
    
    # Build summary
    local summary=$(cat << EOF
{
  "benchmark_id": "$(date +%Y%m%d-%H%M%S)",
  "timestamp": "$(date -Iseconds)",
  "total_plugins_tested": $total_tested,
  "batch_size": $BATCH_SIZE,
  "runs_per_plugin": $RUNS_PER_PLUGIN,
  "site_prefix": "$SITE_PREFIX",
  "statistics": {
    "avg_performance_ms": $avg_performance,
    "slowest_plugin": "$slowest",
    "fastest_plugin": "$fastest"
  },
  "results": $all_results
}
EOF
)
    
    echo "$summary" | jq '.' > "$output_file" 2>/dev/null || echo "$summary" > "$output_file"
    
    log_success "Summary generated: $output_file"
    
    # Print top-level summary
    echo ""
    echo "Benchmark Summary"
    echo "================="
    echo "Plugins tested: $total_tested / 100"
    echo "Average response: ${avg_performance}ms"
    echo "Slowest: $slowest"
    echo "Fastest: $fastest"
    echo "Full report: $output_file"
}

# Export functions for main script
# Functions are already sourced via test-runner.sh
