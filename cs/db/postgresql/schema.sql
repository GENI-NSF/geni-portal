-- Tables for CS (Credential Store) of GENI Prototype Clearinghouse

-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

-- ----------------------------------------------------------------------
--
-- ----------------------------------------------------------------------
-- Drop the data first, then the type.
DROP TABLE IF EXISTS cs_attribute;
DROP TABLE IF EXISTS cs_privilege;
DROP TABLE IF EXISTS cs_assertion;
DROP TABLE IF EXISTS cs_policy;
DROP TABLE IF EXISTS cs_action;

-- List of all known attributes/roles on a principal
CREATE TABLE cs_attribute (
   id SERIAL,
   name VARCHAR
);

-- List of all known privileges that a principal may take
CREATE TABLE cs_privilege  (
   id SERIAL,
   name VARCHAR
);

-- List of all known actions and the required privilege and context type
CREATE TABLE cs_action (
   id SERIAL,
   name VARCHAR,
   privilege int,
   context_type int
);

-- An assertion is a signed statement that a given principal has a given 
-- attribute, possibly in a given context
CREATE TABLE cs_assertion (
  id SERIAL,
  signer UUID,
  principal UUID,
  attribute INT, -- Index into cs_attribute table
  context_type INT, -- 0 = NONE, 1 = PROJECT, 2 = SLICE, 3 = SLIVER
  context UUID, 
  expiration TIMESTAMP,
  assertion_cert VARCHAR,
  PRIMARY KEY (id)
);

-- A policy is a signed statement that a given holder of a given attribute
-- has a given privilege. Again, this is potentially context dependent.
CREATE TABLE cs_policy (
  id SERIAL,
  signer UUID,
  attribute INT, -- Index into cs_attribute
  context_type INT, -- 0 = NONE, 1 = PROJECT, 2 = SLICE, 3 = SLIVER
  privilege INT, -- Index into cs_privilege
  policy_cert VARCHAR
);

