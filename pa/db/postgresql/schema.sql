-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

-- Tell psql to stop on an error. Default behavior is to proceed.
\set ON_ERROR_STOP 1

-- Tables for the PA (Project Authority)
-- ----------------------------------------------------------------------
--
-- ----------------------------------------------------------------------
-- Drop the data first, then the type.
DROP TABLE IF EXISTS pa_project CASCADE;

CREATE TABLE pa_project (
  id SERIAL,
  project_id UUID,
  project_name VARCHAR,
  lead_id UUID,
  project_email VARCHAR,
  project_purpose VARCHAR,
  creation TIMESTAMP,
  expiration TIMESTAMP,
  expired BOOLEAN NOT NULL DEFAULT 'FALSE',
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS pa_project_member CASCADE;
CREATE TABLE pa_project_member (
  id SERIAL,
  project_id UUID,
  member_id UUID,
  role int,
  PRIMARY KEY (id)
);

-- These are for common queries, but so far the DB doesn't use these. Tables too small?
-- CREATE INDEX project_index_project_id ON pa_project (project_id);
-- CREATE INDEX project_index_lead_project ON pa_project (lead_id, project_id);


-- Create tables for requests relative to membership on projects
drop TABLE IF EXISTS pa_project_member_request;
create table pa_project_member_request (
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

-- Create table of invitations from leads to candidate members
drop TABLE if EXISTS pa_project_member_invitation;
create TABLE pa_project_member_invitation(
       id SERIAL,
       invite_id UUID,
       project_id UUID,
       role INT,
       expiration TIMESTAMP
);
