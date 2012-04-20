
-- ----------------------------------------------------------------------
-- A few initial records to insert into the database
-- ----------------------------------------------------------------------

-- Define attributes
INSERT INTO cs_attribute (id, name) values (1, 'LEAD');
INSERT INTO cs_attribute (id, name) values (2, 'ADMIN');
INSERT INTO cs_attribute (id, name) values (3, 'MEMBER');
INSERT INTO cs_attribute (id, name) values (4, 'AUDITOR');

-- Define  privileges
INSERT INTO cs_privilege (id, name) values (1, 'DELEGATE'); 
INSERT INTO cs_privilege (id, name) values (2, 'READ');
INSERT INTO cs_privilege (id, name) values (3, 'WRITE');

-- Define context types
insert into cs_context_type (id, name) values (1, 'PROJECT');
insert into cs_context_type (id, name) values (2, 'SLICE');
insert into cs_context_type (id, name) values (3, 'RESOURCE');
insert into cs_context_type (id, name) values (4, 'SERVICE');
insert into cs_context_type (id, name) values (5, 'MEMBER');



-- Define actions
-- CS_CONTROLLER actions
INSERT INTO cs_action (id, name, privilege, context_type) values (1, 'create_assertion', 3, 5);
INSERT INTO cs_action (id, name, privilege, context_type) values (2, 'create_policy', 3, 5);
INSERT INTO cs_action (id, name, privilege, context_type) values (3, 'renew_assertion', 3, 5);
INSERT INTO cs_action (id, name, privilege, context_type) values (4, 'delete_policy', 3, 5);
INSERT INTO cs_action (id, name, privilege, context_type) values (5, 'query_assertions', 2, 5);
INSERT INTO cs_action (id, name, privilege, context_type) values (6, 'query_policies', 2, 5);

-- SA_CONTROLLER actions
INSERT INTO cs_action (id, name, privilege, context_type) values (7, 'create_slice', 3, 1);
INSERT INTO cs_action (id, name, privilege, context_type) values (8, 'delete_slice', 3, 2);
INSERT INTO cs_action (id, name, privilege, context_type) values (9, 'lookup_slice', 2, 2);

-- SR_CONTROLLER actions
INSERT INTO cs_action (id, name, privilege, context_type) values (10, 'get_services', 2, 4);
INSERT INTO cs_action (id, name, privilege, context_type) values (11, 'get_services_of_type', 2, 4);
INSERT INTO cs_action (id, name, privilege, context_type) values (12, 'register_service', 3, 4);
INSERT INTO cs_action (id, name, privilege, context_type) values (13, 'remove_service', 3, 4);

-- PA_CONTROLLER ations
INSERT INTO cs_action (id, name, privilege, context_type) values (14, 'create_project', 3, 3);
INSERT INTO cs_action (id, name, privilege, context_type) values (15, 'delete_project', 3, 1);
INSERT INTO cs_action (id, name, privilege, context_type) values (16, 'get_projects', 2, 3);
INSERT INTO cs_action (id, name, privilege, context_type) values (17, 'get_project_by_lead', 2, 3);
INSERT INTO cs_action (id, name, privilege, context_type) values (18, 'lookup_project', 2, 3);
INSERT INTO cs_action (id, name, privilege, context_type) values (19, 'update_project', 3, 1);
INSERT INTO cs_action (id, name, privilege, context_type) values (20, 'change_lead', 3, 1);

-- PORTAL 'admin' actions: These are catch-all privileges in a particular context
-- and should go away when we refactor
INSERT INTO cs_action(id, name, privilege, context_type) VALUES (21, 'administer_resources', 3, 3);
INSERT INTO cs_action(id, name, privilege, context_type) VALUES (22, 'administer_services', 3, 4);
INSERT INTO cs_action(id, name, privilege, context_type) VALUES (23, 'administer_members', 3, 5);

-- Define initial set of policies
-- A LEAD of a context_type has DELEGATE, READ AND WRITE PRIVILEGE in that context
INSERT INTO cs_policy (signer,  attribute, context_type, privilege, policy_cert) values
       (null, 1, 1, 1, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 1, 1, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 1, 1, 3, null);
INSERT INTO cs_policy (signer,  attribute, context_type, privilege, policy_cert) values
       (null, 1, 2, 1, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 1, 2, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 1, 2, 3, null);
INSERT INTO cs_policy (signer,  attribute, context_type, privilege, policy_cert) values
       (null, 1, 3, 1, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 1, 3, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 1, 3, 3, null);
INSERT INTO cs_policy (signer,  attribute, context_type, privilege, policy_cert) values
       (null, 1, 4, 1, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 1, 4, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 1, 4, 3, null);
INSERT INTO cs_policy (signer,  attribute, context_type, privilege, policy_cert) values
       (null, 1, 5, 1, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 1, 5, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 1, 5, 3, null);
-- An ADMIN of a context_type has DELEGATE, READ AND WRITE PRIVILEGE in that context
INSERT INTO cs_policy (signer,  attribute, context_type, privilege, policy_cert) values
       (null, 2, 1, 1, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 1, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 1, 3, null);
INSERT INTO cs_policy (signer,  attribute, context_type, privilege, policy_cert) values
       (null, 2, 2, 1, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 2, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 2, 3, null);
INSERT INTO cs_policy (signer,  attribute, context_type, privilege, policy_cert) values
       (null, 2, 3, 1, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 3, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 3, 3, null);
INSERT INTO cs_policy (signer,  attribute, context_type, privilege, policy_cert) values
       (null, 2, 4, 1, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 4, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 4, 3, null);
INSERT INTO cs_policy (signer,  attribute, context_type, privilege, policy_cert) values
       (null, 2, 5, 1, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 5, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 5, 3, null);

-- An ADMIN of a context_type has DELEGATE, READ AND WRITE PRIVILEGE in that context
INSERT INTO cs_policy (signer,  attribute, context_type, privilege, policy_cert) values
       (null, 2, 1, 1, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 1, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 1, 3, null);
INSERT INTO cs_policy (signer,  attribute, context_type, privilege, policy_cert) values
       (null, 2, 2, 1, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 2, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 2, 3, null);
INSERT INTO cs_policy (signer,  attribute, context_type, privilege, policy_cert) values
       (null, 2, 3, 1, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 3, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 3, 3, null);
INSERT INTO cs_policy (signer,  attribute, context_type, privilege, policy_cert) values
       (null, 2, 4, 1, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 4, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 4, 3, null);
INSERT INTO cs_policy (signer,  attribute, context_type, privilege, policy_cert) values
       (null, 2, 5, 1, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 5, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 2, 5, 3, null);
-- A MEMBER of a context_type has READ AND WRITE PRIVILEGE in that context
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 3, 1, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 3, 1, 3, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 3, 2, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 3, 2, 3, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 3, 3, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 3, 3, 3, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 3, 4, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 3, 4, 3, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 3, 5, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 3, 5, 3, null);
-- An AUDITOR of a context_type has READ PRIVILEGE in that context
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 4, 1, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 4, 2, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 4, 3, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 4, 4, 2, null);
INSERT INTO cs_policy (signer, attribute, context_type, privilege, policy_cert) values
       (null, 4, 5, 2, null);



