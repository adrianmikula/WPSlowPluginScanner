#!/usr/bin/env php
<?php
/**
 * Upload benchmark results to Supabase
 * Called from results.sh after each batch completes
 */

$options = getopt('', ['file:', 'url:', 'key:', 'table:', 'test-type::']);
$json_file = $options['file'] ?? '';

if (empty($json_file) || !file_exists($json_file)) {
    fwrite(STDERR, "Error: JSON file not specified or not found\n");
    exit(1);
}

$supabase_url = $options['url'] ?? getenv('SUPABASE_URL') ?: '';
$supabase_key = $options['key'] ?? getenv('SUPABASE_KEY') ?: getenv('PIA_SUPABASE_ANON_KEY') ?: '';
$table = $options['table'] ?? getenv('SUPABASE_TABLE') ?: 'telemetry';
$test_type = $options['test-type'] ?? 'benchmark';

if (empty($supabase_url) || empty($supabase_key)) {
    fwrite(STDERR, "Error: SUPABASE_URL and SUPABASE_KEY must be set\n");
    exit(1);
}

$json = file_get_contents($json_file);
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    fwrite(STDERR, "Error: Invalid JSON: " . json_last_error_msg() . "\n");
    exit(1);
}

$env = array(
    'wp_version' => $data['wp_version'] ?? 'unknown',
    'php_version' => $data['php_version'] ?? PHP_VERSION,
);

$telemetry = array(
    'plugins' => array($data['plugin_slug']),
    'plugin_tested' => $data['plugin_slug'],
    'plugin_speed_delta' => $data['delta_ms'],
    'baseline_site_load_speed' => $data['baseline_time_ms'],
    'plugin_error' => $data['error'],
    'error_category' => $data['error_category'] ?? 'none',
    'env' => (object)$env,
    'origin' => 'benchmark-' . ($data['batch_id'] ?? 'local'),
    'timestamp' => $data['timestamp'] ?? time(),
);

if ($test_type !== 'production') {
    $telemetry['test_type'] = $test_type;
}

$json_payload = json_encode($telemetry);
$temp_file = sys_get_temp_dir() . '/pia_upload_' . uniqid() . '.json';
file_put_contents($temp_file, $json_payload);

$url = $supabase_url . '/rest/v1/' . $table;

$cmd = "env -u LD_LIBRARY_PATH /usr/bin/curl -s -w \"%{http_code}\" -X POST \"" . $url . "\" -H \"apikey: " . $supabase_key . "\" -H \"Authorization: Bearer " . $supabase_key . "\" -H \"Content-Type: application/json\" -H \"Prefer: return=minimal\" -d @" . escapeshellarg($temp_file);

$output = shell_exec($cmd);
unlink($temp_file);

$code = (int)substr($output, -3);
$response = substr($output, 0, -3);

if ($code >= 200 && $code < 300) {
    echo "Uploaded: {$data['plugin_slug']} (test_type=$test_type)\n";
    exit(0);
} else {
    fwrite(STDERR, "Error: HTTP $code - $response\n");
    exit(1);
}