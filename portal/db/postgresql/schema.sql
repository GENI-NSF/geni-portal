
-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

-- ----------------------------------------------------------------------
-- How do we represent account requests? Separate table?
--   No - one column in accounts table, then use views for
--        active_accounts, requested_accounts, etc.
-- ----------------------------------------------------------------------

DROP TABLE IF EXISTS schema_version;

CREATE TABLE schema_version (
    key varchar(256) not null,
    installed timestamp not null default CURRENT_TIMESTAMP,
    extra varchar(256),
    PRIMARY KEY (key)
);

-- ----------------------------------------------------------------------
-- A geni user account
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS account CASCADE;
DROP TYPE IF EXISTS account_status;

CREATE TYPE account_status AS ENUM ('requested', 'active', 'disabled');

CREATE TABLE account (
  account_id UUID,
  status ACCOUNT_STATUS,
  username VARCHAR UNIQUE,
  PRIMARY KEY (account_id)
);

CREATE VIEW requested_account AS
  SELECT * FROM account WHERE status = 'requested';

CREATE VIEW active_account AS
  SELECT * FROM account WHERE status = 'active';

CREATE VIEW disabled_account AS
  SELECT * FROM account WHERE status = 'disabled';

-- ----------------------------------------------------------------------
-- A geni user privilege
-- ----------------------------------------------------------------------

DROP TABLE IF EXISTS account_privilege CASCADE;
DROP TYPE IF EXISTS site_privilege;

CREATE TYPE site_privilege AS ENUM ('admin', 'slice');

CREATE TABLE account_privilege (
  account_id UUID REFERENCES account,
  privilege SITE_PRIVILEGE
);
CREATE INDEX account_privilege_index_account_id
  ON account_privilege (account_id);


-- ----------------------------------------------------------------------
-- An identity from an external identity provider
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS identity CASCADE;

CREATE TABLE identity (
  identity_id SERIAL,
  provider_url varchar,
  eppn varchar,
  affiliation varchar,
  -- We may need to support other shib id fields
  -- like transient id, etc.
  account_id UUID REFERENCES account,
  PRIMARY KEY (identity_id)
);

-- Common indices that don't help ?yet?
-- CREATE INDEX identity_index_eppn ON identity (eppn);
-- CREATE INDEX identity_index_account ON identity (account_id);

-- ----------------------------------------------------------------------
-- Identity attributes
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS identity_attribute;

CREATE TABLE identity_attribute (
  identity_id INTEGER references identity,
  name varchar,
  value varchar,
  self_asserted boolean
);
-- Common query but not helping ?yet?
-- CREATE INDEX identity_attribute_index_identity ON identity_attribute (identity_id);

-- ----------------------------------------------------------------------
-- Shibboleth attributes, based on attribute-map.xml
-- ----------------------------------------------------------------------
-- *** CANDIDATE FOR DELETION ***
DROP TABLE IF EXISTS shib_attribute;

CREATE TABLE shib_attribute (
  name varchar,
  PRIMARY KEY (name)
);

-- ----------------------------------------------------------------------
-- Slices
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS slice CASCADE;

CREATE TABLE slice (
  slice_id UUID,
  name varchar unique,
  expiration timestamp,
  owner UUID REFERENCES account (account_id),
  urn varchar unique not null,
  PRIMARY KEY (slice_id)
);

CREATE INDEX slice_index_owner ON slice (owner);

-- ----------------------------------------------------------------------
-- Account to slice mapping
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS account_slice;
CREATE TABLE account_slice (
  account_id UUID REFERENCES account,
  slice_id UUID REFERENCES slice
);

CREATE INDEX account_slice_index_account_id ON account_slice (account_id);
CREATE INDEX account_slice_index_slice_id ON account_slice (slice_id);

-- "public_key" is an obsolete table. Remove it if it is there.
DROP TABLE IF EXISTS public_key;

-- ----------------------------------------------------------------------
-- Outside keys
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS outside_key;
CREATE TABLE outside_key (
  account_id UUID REFERENCES account UNIQUE,
  private_key VARCHAR,
  certificate VARCHAR
);

CREATE INDEX outside_key_index_account_id ON outside_key (account_id);

-- ----------------------------------------------------------------------
-- Inside keys
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS inside_key;
CREATE TABLE inside_key (
  account_id UUID REFERENCES account UNIQUE,
  private_key VARCHAR,
  certificate VARCHAR
);

CREATE INDEX inside_key_index_account_id ON inside_key (account_id);

-- ----------------------------------------------------------------------
-- ABAC identities
-- ----------------------------------------------------------------------
-- *** CANDIDATE for DELETE ***
DROP TABLE IF EXISTS abac;
CREATE TABLE abac (
  account_id UUID REFERENCES account,
  abac_id VARCHAR,
  abac_key VARCHAR,
  abac_fingerprint VARCHAR
);

CREATE INDEX abac_index_account_id ON abac (account_id);

-- ----------------------------------------------------------------------
-- ABAC identities
-- ----------------------------------------------------------------------
-- *** CANDIDATE for DELETE ***
DROP TABLE IF EXISTS abac_assertion;
CREATE TABLE abac_assertion (
  issuer VARCHAR, -- the issuer fingerprint
  issuer_role VARCHAR,
  subject VARCHAR, -- the subject fingerprint
  expiration TIMESTAMP,
  credential VARCHAR -- Base64 encoded abac assertion
);

CREATE INDEX abac_assertion_issuer ON abac_assertion (issuer);
CREATE INDEX abac_assertion_issuer_role ON abac_assertion (issuer_role);
CREATE INDEX abac_assertion_subject ON abac_assertion (subject);

-- ----------------------------------------------------------------------
-- RSpecs
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS rspec;
CREATE TABLE rspec (
  id SERIAL,
  name VARCHAR NOT NULL,
  schema VARCHAR NOT NULL,         -- ProtoGENI, GENI, etc.
  schema_version VARCHAR NOT NULL, -- 2, 3, etc.
  description VARCHAR NOT NULL,
  rspec VARCHAR NOT NULL,
  PRIMARY KEY (id)
);

CREATE INDEX rspec_name ON rspec (name);
CREATE INDEX rspec_schema ON rspec (schema);

-- ----------------------------------------------------------------------
-- ssh keys
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS ssh_key;
CREATE TABLE ssh_key (
  id SERIAL,
  account_id UUID REFERENCES account NOT NULL,
  filename VARCHAR,
  description VARCHAR,
  public_key VARCHAR NOT NULL,
  PRIMARY KEY (id)
);
CREATE INDEX ssh_key_account_id ON ssh_key (account_id);
