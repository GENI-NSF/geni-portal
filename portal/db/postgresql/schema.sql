
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
  ts timestamp not null default (NOW() AT TIME ZONE 'UTC'),
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


-- ----------------------------------------------------------------------
-- lead request
--
-- Record project lead requests that users create
-- ----------------------------------------------------------------------

DROP TABLE IF EXISTS lead_request;

DROP TYPE IF EXISTS request_status;

CREATE TYPE request_status AS ENUM ('open', 'approved', 'denied');

CREATE TABLE lead_request (
  id SERIAL,
  requester_urn   VARCHAR NOT NULL,
  requester_uuid  UUID NOT NULL,
  requester_eppn  VARCHAR NOT NULL,
  request_ts timestamp NOT NULL default (NOW() AT TIME ZONE 'UTC'),
  approver VARCHAR default '',
  notes VARCHAR default '',
  reason VARCHAR default '',
  status request_status NOT NULL default 'open',
  PRIMARY KEY (id)
);
CREATE INDEX lead_request_index_requester_urn ON lead_request (requester_urn);

-- ----------------------------------------------------------------------
-- user preferences
--
-- Record preferences for portal users
-- ----------------------------------------------------------------------

DROP TABLE IF EXISTS user_preferences;

CREATE TABLE user_preferences (
  id SERIAL,
  user_urn        VARCHAR NOT NULL,
  preference_name VARCHAR NOT NULL,
  preference_value VARCHAR NOT NULL,
  PRIMARY KEY (id)
);

