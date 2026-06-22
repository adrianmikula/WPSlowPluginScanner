-- Allow anonymous inserts to telemetry table only (no SELECT/UPDATE/DELETE for anon)

-- Drop any existing insert/select policies for anon (regardless of naming variant)
DROP POLICY IF EXISTS "Allow anon insert" ON telemetry;
DROP POLICY IF EXISTS "Allow anonymous inserts" ON telemetry;
DROP POLICY IF EXISTS "Allow anonymous selects" ON telemetry;

-- Create RLS policy for anonymous inserts only
-- No FOR SELECT policy means reads are denied for anon
CREATE POLICY "Allow anon insert" ON telemetry
FOR INSERT TO anon WITH CHECK (true);
