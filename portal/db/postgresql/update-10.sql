-- -------------------
-- Drop abac, identity, account, abac, shib portal tables
-- See geni-portal #299
-- -------------------

drop table abac_assertion;
drop table abac;
drop table account;
drop table account_privilege;
drop table identity_attribute;
drop table identity;
drop view requested_account;
drop table shib_attribute;
drop table slice;

