
-- Tables for the PA (Project Authority)
-- ----------------------------------------------------------------------
--
-- ----------------------------------------------------------------------
-- Drop the data first, then the type.
DROP TABLE IF EXISTS pa_project;


CREATE TABLE pa_project (
  id SERIAL,
  project_id UUID,
  project_name VARCHAR,
  lead_id UUID,
  project_email VARCHAR,
  project_purpose VARCHAR,
  PRIMARY KEY (id)
);

