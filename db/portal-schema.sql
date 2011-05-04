

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

CREATE TABLE account (
  account_id SERIAL,
  valid boolean,
  PRIMARY KEY (account_id)
);


-- ----------------------------------------------------------------------
-- An identity from an external identity provider
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS identity CASCADE;

CREATE TABLE identity (
  identity_id SERIAL,
  provider_url varchar,
  eppn varchar,
  -- We may need to support other shib id fields
  -- like transient id, etc.
  account_id INTEGER REFERENCES account,
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
  self_asserted boolean,
  PRIMARY KEY (identity_id)
);


-- ----------------------------------------------------------------------
-- Shibboleth attributes, based on attribute-map.xml
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS shib_attribute;

CREATE TABLE shib_attribute (
  name varchar,
  PRIMARY KEY (name)
);
