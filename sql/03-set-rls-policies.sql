-- Row Level Security policies for telemetry table
-- Architecture: insert-only for anon, SELECT for authenticated, full for service_role

-- Lock down privileges:
--   anon: INSERT only (public key — untrusted)
--   authenticated: INSERT + SELECT (dashboard users)
REVOKE ALL ON telemetry FROM anon, authenticated;
GRANT INSERT ON telemetry TO anon;
GRANT INSERT, SELECT ON telemetry TO authenticated;

-- Sequence usage needed for bigserial id column (auto-increment)
GRANT USAGE ON SEQUENCE telemetry_id_seq TO anon, authenticated;

-- Full access for service_role (admin)
GRANT ALL ON telemetry TO service_role;

-- Enable RLS
ALTER TABLE telemetry ENABLE ROW LEVEL SECURITY;

-- Allow anonymous (public) insert only — no SELECT/UPDATE/DELETE for anon
CREATE POLICY "Allow anon insert" ON telemetry
    FOR INSERT
    TO anon
    WITH CHECK (true);

-- Allow authenticated users to read all data
CREATE POLICY "Allow authenticated read" ON telemetry
    FOR SELECT
    TO authenticated
    USING (true);

-- Allow service role to do anything (for admin purposes)
CREATE POLICY "Allow service role full access" ON telemetry
    FOR ALL
    TO service_role
    USING (true)
    WITH CHECK (true);
