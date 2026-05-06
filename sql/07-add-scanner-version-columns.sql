-- Add scanner engine version and percentage columns to telemetry table

ALTER TABLE telemetry 
ADD COLUMN IF NOT EXISTS plugin_speed_percentage NUMERIC(10, 1),
ADD COLUMN IF NOT EXISTS scanner_engine_version TEXT;

COMMENT ON COLUMN telemetry.plugin_speed_percentage IS 'Percentage time increase/decrease compared to baseline';
COMMENT ON COLUMN telemetry.scanner_engine_version IS 'Version of the scanner engine used for this test';