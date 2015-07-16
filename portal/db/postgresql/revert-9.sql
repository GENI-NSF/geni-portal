-- -------------------
-- Apply changes to rename abac, identity, account portal tables
-- (make inaccessible from existing code, but archive)
-- See geni-portal #299
-- -------------------

alter table abac_assertion_299 rename to abac_assertion;
alter table abac_299 rename to abac;
alter table account_299 rename to account;
alter table account_privilege_299 rename to account_privilege;
alter table identity_attribute_299 rename to identity_attribute;
alter table identity_299 rename to identity;
alter table requested_account_299 rename to requested_account;
alter table shib_attribute_299 rename to shib_attribute;
alter table slice_299 rename to slice;

