-- Create telemetry table for plugin speed test data

CREATE TABLE IF NOT EXISTS telemetry (
    id BIGSERIAL PRIMARY KEY,
    plugins TEXT[] NOT NULL,
    plugin_tested TEXT NOT NULL,
    plugin_version TEXT,
    plugin_speed_delta NUMERIC(10, 3),
    baseline_site_load_speed NUMERIC(10, 3),
    plugin_error TEXT,
    error_category TEXT NOT NULL DEFAULT 'none',
    settings_count INTEGER DEFAULT 0,
    env JSONB,
    origin TEXT NOT NULL,
    timestamp BIGINT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

COMMENT ON TABLE telemetry IS 'Stores plugin speed test telemetry data from WordPress sites';