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

