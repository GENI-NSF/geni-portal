-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

-- Tables for the MA (Member Authority)
-- ----------------------------------------------------------------------
--
-- ----------------------------------------------------------------------
-- Drop the data first, then the type.
DROP TABLE IF EXISTS ma_member;


CREATE TABLE ma_member (
  id SERIAL,
  member_id UUID,
  role_type int,
  context_type int,
  context_id UUID,
  PRIMARY KEY (id)
);

-- A common query, but so far the DB Doesn't use this.
-- CREATE INDEX ma_member_index_member ON ma_member (member_id);
