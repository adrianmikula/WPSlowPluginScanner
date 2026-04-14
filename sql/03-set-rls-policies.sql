-- Row Level Security policies for telemetry table

-- Enable RLS
ALTER TABLE telemetry ENABLE ROW LEVEL SECURITY;

-- Allow anonymous (public) inserts
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