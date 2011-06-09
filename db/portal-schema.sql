
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


-- ----------------------------------------------------------------------
-- Shibboleth attributes, based on attribute-map.xml
-- ----------------------------------------------------------------------
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
  name varchar,
  expiration timestamp,
  PRIMARY KEY (slice_id)
);

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

-- ----------------------------------------------------------------------
-- Public keys
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS public_key;
CREATE TABLE public_key (
  public_key_id UUID,
  account_id UUID REFERENCES account,
  public_key VARCHAR,
  description VARCHAR,
  certificate VARCHAR
);

CREATE INDEX public_key_index_public_key_id ON public_key (public_key_id);
CREATE INDEX public_key_index_account_id ON public_key (account_id);
