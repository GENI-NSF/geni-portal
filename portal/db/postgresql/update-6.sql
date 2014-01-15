
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
  token VARCHAR UNIQUE NOT NULL,
  signer_urn VARCHAR UNIQUE NOT NULL,
  upload_ts timestamp NOT NULL,
  expires_ts timestamp NOT NULL,
  cred VARCHAR,
  PRIMARY KEY (id)
);
CREATE INDEX speaks_for_token ON speaks_for (token);

-- Update the schema version
INSERT INTO schema_version
    (key, extra)
  VALUES
    ('006', 'speaks_for');
