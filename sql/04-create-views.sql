-- Views for telemetry data analysis

-- Plugin performance ranking (slowest to fastest)
CREATE OR REPLACE VIEW plugin_performance_ranking AS
SELECT 
    plugin_tested AS plugin,
    COUNT(*) AS scan_count,
    AVG(plugin_speed_delta) AS avg_delta,
    PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY plugin_speed_delta) AS median_delta,
    MIN(plugin_speed_delta) AS min_delta,
    MAX(plugin_speed_delta) AS max_delta,
    SUM(CASE WHEN plugin_speed_delta > 0.3 THEN 1 ELSE 0 END) AS slowdown_count,
    SUM(CASE WHEN error_category = 'break_site' THEN 1 ELSE 0 END) AS break_count,
    SUM(CASE WHEN error_category = 'timeout' THEN 1 ELSE 0 END) AS timeout_count,
    SUM(CASE WHEN error_category = 'output_change' THEN 1 ELSE 0 END) AS output_change_count,
    AVG(baseline_site_load_speed) AS avg_baseline_speed
FROM telemetry
WHERE plugin_speed_delta IS NOT NULL
GROUP BY plugin_tested
ORDER BY avg_delta DESC;

-- Error analysis by plugin
CREATE OR REPLACE VIEW plugin_error_analysis AS
SELECT 
    plugin_tested AS plugin,
    COUNT(*) AS total_scans,
    SUM(CASE WHEN error_category IS NOT NULL AND error_category != 'none' THEN 1 ELSE 0 END) AS error_count,
    SUM(CASE WHEN error_category = 'break_site' THEN 1 ELSE 0 END) AS break_count,
    SUM(CASE WHEN error_category = 'timeout' THEN 1 ELSE 0 END) AS timeout_count,
    SUM(CASE WHEN error_category = 'output_change' THEN 1 ELSE 0 END) AS output_change_count,
    ROUND(
        SUM(CASE WHEN error_category IS NOT NULL AND error_category != 'none' THEN 1 ELSE 0 END)::numeric / 
        COUNT(*)::numeric * 100, 2
    ) AS error_rate_percent
FROM telemetry
GROUP BY plugin_tested;

-- Plugin co-occurrence (which plugins are installed together)
CREATE OR REPLACE VIEW plugin_cooccurrence AS
SELECT 
    p1 AS plugin_a,
    p2 AS plugin_b,
    COUNT(*) AS pair_count
FROM telemetry,
    LATERAL unnest(plugins) WITH ORDINALITY AS t1(p1, ord1),
    LATERAL unnest(plugins) WITH ORDINALITY AS t2(p2, ord2)
WHERE p1 < p2
GROUP BY p1, p2
ORDER BY pair_count DESC;

-- Performance by PHP version
CREATE OR REPLACE VIEW plugin_delta_by_php AS
SELECT 
    plugin_tested AS plugin,
    env->>'php_version' AS php_version,
    COUNT(*) AS scans,
    AVG(plugin_speed_delta) AS avg_delta,
    PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY plugin_speed_delta) AS median_delta
FROM telemetry
WHERE env IS NOT NULL AND plugin_speed_delta IS NOT NULL
GROUP BY plugin_tested, php_version
ORDER BY avg_delta DESC;