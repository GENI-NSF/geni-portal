-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

-- Tell psql to stop on an error. Default behavior is to proceed.
\set ON_ERROR_STOP 1

-- Tables for the KM

-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS km_asserted_attribute;

CREATE TABLE km_asserted_attribute (
  id SERIAL PRIMARY KEY,
  eppn VARCHAR NOT NULL,
  name VARCHAR NOT NULL,
  value VARCHAR NOT NULL,
  asserter_id UUID,
  created timestamp DEFAULT (NOW() AT TIME ZONE 'UTC')
);

CREATE INDEX km_asserted_attribute_eppn ON km_asserted_attribute (eppn);

-- Table for people whose IdPs don't send email to self assert their address
-- See kmnoemail.php

DROP TABLE IF EXISTS km_email_confirm;

CREATE TABLE km_email_confirm (
    id SERIAL PRIMARY KEY,
    eppn VARCHAR NOT NULL,
    email VARCHAR NOT NULL,
    nonce VARCHAR NOT NULL,
    created timestamp DEFAULT (NOW() at time zone 'utc') NOT NULL
);

