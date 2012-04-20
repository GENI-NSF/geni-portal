-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

-- Tables for the PA (Project Authority)
-- ----------------------------------------------------------------------
--
-- ----------------------------------------------------------------------
-- Drop the data first, then the type.
DROP TABLE IF EXISTS pa_project CASCADE;

CREATE TABLE pa_project (
  id SERIAL,
  project_id UUID,
  project_name VARCHAR,
  lead_id UUID,
  project_email VARCHAR,
  project_purpose VARCHAR,
  PRIMARY KEY (id)
);

-- These are for common queries, but so far the DB doesn't use these. Tables too small?
-- CREATE INDEX project_index_project_id ON pa_project (project_id);
-- CREATE INDEX project_index_lead_project ON pa_project (lead_id, project_id);
