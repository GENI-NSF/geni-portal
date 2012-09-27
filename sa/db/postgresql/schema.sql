
-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

-- Tell psql to stop on an error. Default behavior is to proceed.
\set ON_ERROR_STOP 1

-- ----------------------------------------------------------------------
--
-- ----------------------------------------------------------------------
-- Drop the data first, then the type.
DROP TABLE IF EXISTS sa_slice CASCADE;

create TABLE sa_slice (
  id SERIAL,
  slice_id UUID, 
  owner_id UUID,
  project_id UUID,
  creation TIMESTAMP,
  expiration TIMESTAMP,
  expired BOOLEAN NOT NULL DEFAULT 'FALSE',
  slice_name VARCHAR,
  slice_urn VARCHAR,
  slice_email VARCHAR,
  certificate VARCHAR,
  slice_description VARCHAR,
  PRIMARY KEY (id)
);

CREATE INDEX sa_slice_expired ON sa_slice (expired);

DROP TABLE IF EXISTS sa_slice_member CASCADE;
CREATE TABLE sa_slice_member (
  id SERIAL,
  slice_id UUID,
  member_id UUID,
  role int,
  PRIMARY KEY (id)
);



-- These match our common queries, but in my simple tests my DB doesn't use these
-- CREATE INDEX sa_slice_index_name_project ON sa_slice (slice_name, project_id);
-- CREATE INDEX sa_slice_index_slice_id ON sa_slice(slice_id);
-- CREATE INDEX sa_slice_index_project_owner ON sa_slice(project_id, owner_id);
-- CREATE INDEX sa_slice_index_owner ON sa_slice(owner_id);


-- Create tables for requests relative to membership on slices
drop TABLE IF EXISTS sa_slice_member_request;
create table sa_slice_member_request (
       id SERIAL,
       context_type  INT, 
       context_id UUID,
       request_text VARCHAR, 
        -- 0 = JOIN, 1 = UPDATE_ATTRIBUTES, 2 = .... [That's all for now]
       request_type INT,
       -- This is a JSON string with a dictionary of requested attributes 
       -- for the case of a user wanting a change to his attributes
       request_details VARCHAR, 
       requestor UUID,
       status INT, -- 0 = PENDING, 1 = APPROVED, 2 = CANCELED, 3 = REJECTED
       creation_timestamp TIMESTAMP,
       resolver UUID,
       resolution_timestamp TIMESTAMP,
       resolution_description VARCHAR
);
