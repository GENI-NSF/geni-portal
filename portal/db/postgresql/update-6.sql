
-- Update 6 --
-- Add speaks_for table --

-- ----------------------------------------------------------------------
-- speaks for
--
-- Record a speaks for credential for users.
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS speaks_for;

CREATE TABLE speaks_for (
  id SERIAL,
  member_id UUID NOT NULL,
  member_urn VARCHAR NOT NULL,
  upload_ts timestamp NOT NULL,
  expires_ts timestamp NOT NULL,
  cred VARCHAR,
  PRIMARY KEY (id)
);
CREATE INDEX speaks_for_member_id ON speaks_for (member_id);

-- Update the schema version
INSERT INTO schema_version
    (key, extra)
  VALUES
    ('006', 'speaks_for');
