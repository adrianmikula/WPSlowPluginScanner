-- Add test_type column to identify benchmark vs production telemetry

ALTER TABLE telemetry ADD COLUMN IF NOT EXISTS test_type TEXT DEFAULT 'production';

COMMENT ON COLUMN telemetry.test_type IS 'Type of test: "production" or "benchmark"';