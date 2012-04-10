
-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';


-- ----------------------------------------------------------------------
--
-- ----------------------------------------------------------------------
-- Drop the data first, then the type.
DROP TABLE IF EXISTS sa_slice;
DROP TABLE IF EXISTS sa_slice_member;

CREATE TABLE sa_slice (
  id SERIAL,
  name VARCHAR,
  project_id VARCHAR,
  expiration timestamp,
  slice_id UUID,
  PRIMARY KEY (id)
);

CREATE TABLE sa_slice_member (
  slice_id UUID,
  member_id UUID,
  PRIMARY KEY (slice_id)
);

CREATE INDEX sa_slice_member_member_id
  ON sa_slice_member (member_id);
