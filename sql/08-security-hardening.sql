-- Security hardening migration for existing deployments
-- Fixes: anon SELECT leak, SECURITY DEFINER views, missing REVOKE grants

-- 1. Lock down privileges:
--    anon: INSERT only
--    authenticated: INSERT + SELECT
REVOKE ALL ON telemetry FROM anon, authenticated;
GRANT INSERT ON telemetry TO anon;
GRANT INSERT, SELECT ON telemetry TO authenticated;
GRANT USAGE ON SEQUENCE telemetry_id_seq TO anon, authenticated;

-- 2. Drop the problematic SELECT policy for anon (if it exists from sql/06)
DROP POLICY IF EXISTS "Allow anonymous selects" ON telemetry;

-- 3. Drop the old insert policy name from sql/06 and recreate if needed
DROP POLICY IF EXISTS "Allow anonymous inserts" ON telemetry;
CREATE POLICY "Allow anonymous inserts" ON telemetry
FOR INSERT TO anon WITH CHECK (true);

-- 4. Switch views to SECURITY INVOKER so they respect RLS
--    (default is SECURITY DEFINER which bypasses RLS — a leak risk)
ALTER VIEW plugin_performance_ranking SET (security_invoker = on);
ALTER VIEW plugin_error_analysis SET (security_invoker = on);
ALTER VIEW plugin_cooccurrence SET (security_invoker = on);
ALTER VIEW plugin_delta_by_php SET (security_invoker = on);

-- 5. Grant SELECT on views to authenticated only (not anon)
GRANT SELECT ON plugin_performance_ranking TO authenticated;
GRANT SELECT ON plugin_error_analysis TO authenticated;
GRANT SELECT ON plugin_cooccurrence TO authenticated;
GRANT SELECT ON plugin_delta_by_php TO authenticated;
