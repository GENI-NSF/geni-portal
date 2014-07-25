
-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

-- Tell psql to stop on an error. Default behavior is to proceed.
\set ON_ERROR_STOP 1

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
  username VARCHAR UNIQUE NOT NULL,
  PRIMARY KEY (account_id)
);

CREATE VIEW requested_account AS
  SELECT * FROM account WHERE status = 'requested';

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
  eppn varchar UNIQUE NOT NULL,
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
  name varchar NOT NULL,
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
-- OBE
DROP TABLE IF EXISTS account_slice;

-- "public_key" is an obsolete table. Remove it if it is there.
DROP TABLE IF EXISTS public_key;

-- ----------------------------------------------------------------------
-- Outside keys
--
-- Outside keys moved to MA. Drop the table if it exists to clean up
-- older database.
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS outside_key;

-- ----------------------------------------------------------------------
-- Inside keys
--
-- Inside keys moved to MA. Drop the table if it exists to clean up
-- older databases.
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS inside_key;

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
DROP TYPE IF EXISTS rspec_visibility CASCADE;
CREATE TYPE rspec_visibility AS ENUM ('public', 'private');

DROP TABLE IF EXISTS rspec;
CREATE TABLE rspec (
  id SERIAL,
  name VARCHAR NOT NULL,
  schema VARCHAR NOT NULL,         -- ProtoGENI, GENI, etc.
  schema_version VARCHAR NOT NULL, -- 2, 3, etc.
  description VARCHAR NOT NULL,
  rspec VARCHAR NOT NULL,
  owner_id UUID,
  owner_name VARCHAR,
  owner_email VARCHAR,
  visibility rspec_visibility NOT NULL,
  bound boolean,
  stitch boolean,
  am_urns VARCHAR,
  PRIMARY KEY (id)
);
CREATE INDEX rspec_name ON rspec (name);
CREATE INDEX rspec_schema ON rspec (schema);
CREATE INDEX rspec_owner_id ON rspec(owner_id);
CREATE INDEX rspec_visibility ON rspec(visibility);


-- ----------------------------------------------------------------------
-- ssh keys
--
-- SSH keys moved to MA. Drop the table if it exists to clean up
-- older databases.
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS ssh_key;

-- ----------------------------------------------------------------------
-- projects
--
-- Projects have moved to PA. Drop legacy tables.
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS project_member;
DROP TABLE IF EXISTS project_privilege;
DROP TABLE IF EXISTS project;

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
