
-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

-- Drop table to recreate
DROP TABLE IF EXISTS logging_entry;
DROP TABLE IF EXISTS logging_entry_context;

-- Now create the table
CREATE TABLE logging_entry (
  id SERIAL,
  event_time TIMESTAMP, 
  user_id UUID,
  message vARCHAR,
  PRIMARY KEY (id)
);

CREATE TABLE logging_entry_context (
  id SERIAL,
  context_type INT,
  context_id UUID
);

