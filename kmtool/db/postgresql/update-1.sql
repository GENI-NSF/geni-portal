-- Change default timestamp to be in UTC (and by schema, it drops the time zone)
ALTER TABLE ONLY km_asserted_attribute ALTER COLUMN created SET DEFAULT NOW() AT TIME ZONE 'UTC';
