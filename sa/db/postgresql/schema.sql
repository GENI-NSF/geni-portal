
-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';


-- ----------------------------------------------------------------------
--
-- ----------------------------------------------------------------------
-- Drop the data first, then the type.
DROP TABLE IF EXISTS sa_slice CASCADE;

create TABLE sa_slice (
  id SERIAL,
  slice_id UUID, 
  slice_name VARCHAR,
  project_id UUID,
  expiration TIMESTAMP,
  owner_id UUID,
  slice_urn VARCHAR,
  slice_email VARCHAR,
  certificate VARCHAR,
  PRIMARY KEY (id)
);

-- These match our common queries, but in my simple tests my DB doesn't use these
-- CREATE INDEX sa_slice_index_name_project ON sa_slice (slice_name, project_id);
-- CREATE INDEX sa_slice_index_slice_id ON sa_slice(slice_id);
-- CREATE INDEX sa_slice_index_project_owner ON sa_slice(project_id, owner_id);
-- CREATE INDEX sa_slice_index_owner ON sa_slice(owner_id);
