
-- ----------------------------------------------------------------------
-- A few initial records to insert into the database
-- ----------------------------------------------------------------------

-- Define attributes
INSERT INTO cs_attribute (id, name) values (1, 'REGISTRAR');
INSERT INTO cs_attribute (id, name) values (2, 'ADMIN');
INSERT INTO cs_attribute (id, name) values (3, 'PROJECT_LEAD');
INSERT INTO cs_attribute (id, name) values (4, 'PROJECT_MEMBER');
INSERT INTO cs_attribute (id, name) values (5, 'PROJECT_AUDITOR');
INSERT INTO cs_attribute (id, name) values (6, 'SLICE_LEAD');
INSERT INTO cs_attribute (id, name) values (7, 'SLICE_MEMBER');
INSERT INTO cs_attribute (id, name) values (8, 'SLICE_AUDITOR');
INSERT INTO cs_attribute (id, name) values (9, 'SLIVER_LEAD');
INSERT INTO cs_attribute (id, name) values (10, 'SLIVER_MEMBER');
INSERT INTO cs_attribute (id, name) values (11, 'SLIVER_AUDITOR');

-- Define (permissions to perform) Actions 
INSERT INTO cs_action (id, name) values (1, 'MEMBER_DELEGATE'); 
INSERT INTO cs_action (id, name) values (2, 'MEMBER_READ');
INSERT INTO cs_action (id, name) values (3, 'MEMBER_WRITE');
INSERT INTO cs_action (id, name) values (4, 'SERVICE_DELEGATE');
INSERT INTO cs_action (id, name) values (5, 'SERVICE_READ');
INSERT INTO cs_action (id, name) values (6, 'SERVICE_WRITE');
INSERT INTO cs_action (id, name) values (7, 'PROJECT_CREATE');
INSERT INTO cs_action (id, name) values (8, 'PROJECT_DELEGATE');
INSERT INTO cs_action (id, name) values (9, 'PROJECT_READ');
INSERT INTO cs_action (id, name) values (10, 'PROJECT_WRITE');
INSERT INTO cs_action (id, name) values (11, 'SLICE_DELEGATE');
INSERT INTO cs_action (id, name) values (12, 'SLICE_READ');
INSERT INTO cs_action (id, name) values (13, 'SLICE_WRITE');
INSERT INTO cs_action (id, name) values (14, 'SLIVER_DELEGATE');
INSERT INTO cs_action (id, name) values (15, 'SLIVER_READ');
INSERT INTO cs_action (id, name) values (16, 'SLIVER_WRITE');

-- Need to insert the policies at CS initialization time
-- The attributes are written by the MA through the CS interface

