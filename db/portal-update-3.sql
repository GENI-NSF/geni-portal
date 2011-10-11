
-- Update 3 --

-- Create a table for ABAC assertions

CREATE TABLE abac_assertion (
  issuer VARCHAR, -- the issuer fingerprint
  issuer_role VARCHAR,
  subject VARCHAR, -- the subject fingerprint
  expiration TIMESTAMP,
  credential VARCHAR -- Base64 encoded abac assertion
);

-- Create some indexes on the table

CREATE INDEX abac_assertion_issuer ON abac_assertion (issuer);
CREATE INDEX abac_assertion_issuer_role ON abac_assertion (issuer_role);
CREATE INDEX abac_assertion_subject ON abac_assertion (subject);

-- Update the schema version
INSERT INTO schema_version
    (key, extra)
  VALUES
    ('003', 'Add table abac_assertion');
