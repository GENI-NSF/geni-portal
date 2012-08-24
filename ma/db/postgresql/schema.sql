-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

-- Tell psql to stop on an error. Default behavior is to proceed.
\set ON_ERROR_STOP 1

-- Tables for the MA (Member Authority)

-- ----------------------------------------------------------------------
-- Member table. Store the member ids. Attribute are in
-- ma_member_attribute.
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS ma_member CASCADE;

CREATE TABLE ma_member (
  id SERIAL PRIMARY KEY,
  member_id UUID UNIQUE
);

CREATE INDEX ma_member_index_member_id ON ma_member (member_id);

-- ----------------------------------------------------------------------
-- Member attribute table. Store all attributes of members as name/value
-- pairs keyed to the member id.
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS ma_member_attribute;

CREATE TABLE ma_member_attribute (
  id SERIAL PRIMARY KEY,
  member_id UUID NOT NULL REFERENCES ma_member (member_id),
  name VARCHAR NOT NULL,
  value VARCHAR NOT NULL,
  self_asserted BOOLEAN NOT NULL
);

-- ----------------------------------------------------------------------
-- Client table. Each client has a certificate and is "approved" in
-- some way.
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS ma_client CASCADE;

CREATE TABLE ma_client (
  id SERIAL PRIMARY KEY,
  client_urn VARCHAR UNIQUE NOT NULL
);

CREATE INDEX ma_client_index_client_urn ON ma_client (client_urn);

-- ----------------------------------------------------------------------
-- Inside keys
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS ma_inside_key;

CREATE TABLE ma_inside_key (
  id SERIAL PRIMARY KEY,
  client_urn VARCHAR REFERENCES ma_client (client_urn),
  member_id UUID REFERENCES ma_member (member_id),
  private_key VARCHAR,
  certificate VARCHAR,
  UNIQUE (client_urn, member_id)
);

CREATE INDEX ma_inside_key_index_member_id ON ma_inside_key (member_id);

-- ----------------------------------------------------------------------
--
-- ----------------------------------------------------------------------

-- Approved clients (member_id, client_id)
-- client + id -> member_id
-- Outside keys
-- SSH keys
