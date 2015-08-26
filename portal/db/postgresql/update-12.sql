-- ----------------------------------------------------------------------
-- user preferences
--
-- Record preferences for portal users
-- ----------------------------------------------------------------------

-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

-- Tell psql to stop on an error. Default behavior is to proceed.
\set ON_ERROR_STOP 1

DROP TABLE IF EXISTS user_preferences;

CREATE TABLE user_preferences (
  id SERIAL,
  user_urn        VARCHAR NOT NULL,
  preference_name VARCHAR NOT NULL,
  preference_value VARCHAR NOT NULL,
  PRIMARY KEY (id)
);
