
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
  slice_id UUID UNIQUE, 
  owner_id UUID NOT NULL REFERENCES ma_member (member_id),
  project_id UUID NOT NULL REFERENCES pa_project (project_id),
  creation TIMESTAMP,
  expiration TIMESTAMP,
  expired BOOLEAN NOT NULL DEFAULT 'FALSE',
  slice_name VARCHAR NOT NULL,
  slice_urn VARCHAR,
  slice_email VARCHAR,
  certificate VARCHAR,
  private_key VARCHAR
  slice_description VARCHAR,
  PRIMARY KEY (id)
);

CREATE INDEX sa_slice_expired ON sa_slice (expired);

DROP TABLE IF EXISTS sa_slice_member CASCADE;
CREATE TABLE sa_slice_member (
  id SERIAL,
  slice_id UUID NOT NULL REFERENCES sa_slice (slice_id),
  member_id UUID NOT NULL REFERENCES ma_member (member_id),
  role int,
  PRIMARY KEY (id)
);

CREATE INDEX sa_slice_member_member_id on sa_slice_member(member_id);

-- These match our common queries, but in my simple tests my DB doesn't use these
-- CREATE INDEX sa_slice_index_name_project ON sa_slice (slice_name, project_id);
-- CREATE INDEX sa_slice_index_slice_id ON sa_slice(slice_id);
-- CREATE INDEX sa_slice_index_project_owner ON sa_slice(project_id, owner_id);
-- CREATE INDEX sa_slice_index_owner ON sa_slice(owner_id);


-- Create tables for requests relative to membership on slices
drop TABLE IF EXISTS sa_slice_member_request;
create table sa_slice_member_request (
       id SERIAL PRIMARY KEY,
       context_type  INT NOT NULL, 
       context_id UUID NOT NULL,
       request_text VARCHAR, 
        -- 0 = JOIN, 1 = UPDATE_ATTRIBUTES, 2 = .... [That's all for now]
       request_type INT,
       -- This is a JSON string with a dictionary of requested attributes 
       -- for the case of a user wanting a change to his attributes
       request_details VARCHAR, 
       requestor UUID NOT NULL REFERENCES ma_member (member_id),
       status INT, -- 0 = PENDING, 1 = APPROVED, 2 = CANCELED, 3 = REJECTED
       creation_timestamp TIMESTAMP,
       resolver UUID NOT NULL,
       resolution_timestamp TIMESTAMP,
       resolution_description VARCHAR
);



-- Add tables for sliver info in SA

DROP TABLE if EXISTS sa_sliver_info CASCADE;

CREATE TABLE sa_sliver_info (
       id SERIAL,
       slice_urn varchar not null,
       sliver_urn varchar unique not null,
       creation timestamp without time zone,
       expiration timestamp without time zone,
       creator_urn varchar not null,
       aggregate_urn varchar not null,
       PRIMARY KEY (id)
);
CREATE INDEX sa_sliver_info_urn ON sa_sliver_info(sliver_urn);

-- Update the schema version
INSERT INTO schema_version
       (key, extra)
VALUES ('006', 'sa_sliver_info');



-- Add archiving tables sa_slice_old and sa_slice_member_old
DROP TABLE IF EXISTS sa_slice_old CASCADE;

create TABLE sa_slice_old (
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

DROP TABLE IF EXISTS sa_slice_member_old CASCADE;
CREATE TABLE sa_slice_member_old (
  id SERIAL,
  slice_id UUID,
  member_id UUID,
  role int,
  PRIMARY KEY (id)
);

