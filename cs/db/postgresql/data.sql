
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

-- Define  privileges
INSERT INTO cs_privilege (id, name) values (1, 'DELEGATE'); 
INSERT INTO cs_privilege (id, name) values (2, 'READ');
INSERT INTO cs_privilege (id, name) values (3, 'WRITE');

-- Define actions
-- CS_CONTROLLER actions
INSERT INTO cs_action (id, name, privilege, context_type) values (1, 'create_assertion', 3, 0);
INSERT INTO cs_action (id, name, privilege, context_type) values (2, 'create_policy', 3, 0);
INSERT INTO cs_action (id, name, privilege, context_type) values (3, 'renew_assertion', 3, 0);
INSERT INTO cs_action (id, name, privilege, context_type) values (4, 'delete_policy', 3, 0);
INSERT INTO cs_action (id, name, privilege, context_type) values (5, 'query_assertions', 2, 0);
INSERT INTO cs_action (id, name, privilege, context_type) values (6, 'query_policies', 2, 0);

-- SA_CONTROLLER actions
INSERT INTO cs_action (id, name, privilege, context_type) values (7, 'create_slice', 3, 1);

-- SR_CONTROLLER actions
INSERT INTO cs_action (id, name, privilege, context_type) values (8, 'get_services', 2, 0);
INSERT INTO cs_action (id, name, privilege, context_type) values (9, 'get_services_of_type', 2, 0);
INSERT INTO cs_action (id, name, privilege, context_type) values (10, 'register_service', 3, 0);
INSERT INTO cs_action (id, name, privilege, context_type) values (11, 'remove_service', 3, 0);




