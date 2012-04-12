
-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';


-- ----------------------------------------------------------------------
--
-- ----------------------------------------------------------------------
-- Drop the data first, then the type.
DROP TABLE IF EXISTS sa_slice;

create TABLE sa_slice (
  id SERIAL,
  slice_id UUID, 
  slice_name VARCHAR,
  project_id VARCHAR,
  expiration TIMESTAMP,
  owner_id UUID,
  slice_urn VARCHAR,
  slice_email VARCHAR,
  certificate VARCHAR,
  PRIMARY KEY (id)
);

