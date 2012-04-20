
-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

-- Drop table to recreate
DROP TABLE IF EXISTS service_registry;
-- Now create the type, then the table that relies on it.

CREATE TABLE service_registry (
  id SERIAL,
  service_type INT,
  service_url VARCHAR,
  service_cert vARCHAR,
  PRIMARY KEY (id)
);

-- Common query but DB not using it ?yet?
-- CREATE INDEX service_registry_index_type ON service_registry(service_type);
