-- Indexes for telemetry table performance

-- GIN index for array containment queries (plugin co-occurrence)
CREATE INDEX IF NOT EXISTS idx_telemetry_plugins ON telemetry USING GIN(plugins);

-- Index on timestamp for time-based queries
CREATE INDEX IF NOT EXISTS idx_telemetry_timestamp ON telemetry(timestamp DESC);

-- Index on origin for site-specific queries
CREATE INDEX IF NOT EXISTS idx_telemetry_origin ON telemetry(origin);

-- Index on error_category for filtering
CREATE INDEX IF NOT EXISTS idx_telemetry_error_category ON telemetry(error_category);

-- Index on plugin_tested for plugin-specific queries
CREATE INDEX IF NOT EXISTS idx_telemetry_plugin_tested ON telemetry(plugin_tested);