
-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

-- Drop table to recreate
DROP TABLE IF EXISTS logging_entry;
DROP TABLE IF EXISTS logging_entry_context;
DROP TABLE IF EXISTS logging_entry_attribute;

-- Now create the table
CREATE TABLE logging_entry (
  id SERIAL,
  event_time TIMESTAMP, 
  user_id UUID,
  message VARCHAR,
  PRIMARY KEY (id)
);

CREATE TABLE logging_entry_attribute (
  event_id INT,
  attribute_name VARCHAR,
  attribute_value VARCHAR
);
