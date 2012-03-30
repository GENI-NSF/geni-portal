
-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

DROP TABLE IF EXISTS schema_version;

CREATE TABLE schema_version (
    key varchar(256) not null,
    installed timestamp not null default CURRENT_TIMESTAMP,
    extra varchar(256),
    PRIMARY KEY (key)
);

INSERT INTO schema_version(key, extra) values ('1', 'initial version');

-- ----------------------------------------------------------------------
--
-- ----------------------------------------------------------------------
-- Drop the data first, then the type.
DROP TABLE IF EXISTS service;
DROP TYPE IF EXISTS service_type;

-- Now create the type, then the table that relies on it.
CREATE TYPE service_type AS ENUM (
  'credential-store',
  'aggregate-manager',
  'slice-authority');

CREATE TABLE service (
  id SERIAL,
  stype SERVICE_TYPE,
  url VARCHAR,
  priority INT,
  PRIMARY KEY (id)
);

CREATE INDEX service_index_stype
  ON service (stype);
