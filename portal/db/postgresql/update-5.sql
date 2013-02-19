
-- Update 5 --
-- Add last_seen table --

-- ----------------------------------------------------------------------
-- last seen
--
-- Record the time an account was last seen, and the page they were
-- requesting.
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS last_seen;

CREATE TABLE last_seen (
  id SERIAL,
  member_id UUID NOT NULL,
  ts timestamp not null default CURRENT_TIMESTAMP,
  request_uri VARCHAR,
  PRIMARY KEY (id)
);
CREATE INDEX last_seen_member_id ON last_seen (member_id);

-- Update the schema version
INSERT INTO schema_version
    (key, extra)
  VALUES
    ('005', 'last_seen');
