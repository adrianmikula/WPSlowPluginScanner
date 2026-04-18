-- Allow anonymous inserts to telemetry table

-- Drop existing insert policy if exists
DROP POLICY IF EXISTS "Allow anonymous inserts" ON telemetry;

-- Create RLS policy for anonymous inserts
CREATE POLICY "Allow anonymous inserts" ON telemetry
FOR INSERT WITH CHECK (true);

-- Also allow anonymous selects for debugging
DROP POLICY IF EXISTS "Allow anonymous selects" ON telemetry;
CREATE POLICY "Allow anonymous selects" ON telemetry
FOR SELECT USING (true);