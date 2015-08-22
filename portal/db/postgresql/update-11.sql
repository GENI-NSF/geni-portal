-- Modify timestamps with a default to use now() but in UTC (not local)
ALTER TABLE ONLY last_seen ALTER COLUMN ts SET DEFAULT NOW() AT TIME ZONE 'UTC';

ALTER TABLE ONLY lead_request ALTER COLUMN request_ts SET DEFAULT NOW() AT TIME ZONE 'UTC';
