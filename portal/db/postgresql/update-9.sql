-- -------------------
-- Apply changes to rename abac, identity, account portal tables
-- (make inaccessible from existing code, but archive)
-- See geni-portal #299
-- -------------------

alter table abac_assertion rename to abac_assertion_299;
alter table abac rename to abac_299;
alter table account_privilege rename to account_privilege_299;
alter table account rename to account_299;
alter table identity_attribute rename to identity_attribute_299;
alter table identity rename to identity_299;
alter table requested_account rename to requested_account_299;




