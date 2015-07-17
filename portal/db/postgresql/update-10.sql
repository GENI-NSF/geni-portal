
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
  show_map        boolean,
  PRIMARY KEY (id)
);
