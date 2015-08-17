-- -------------------
-- Drop abac, identity, account, abac, shib portal tables
-- See geni-portal #299
-- -------------------

-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

-- Tell psql to stop on an error. Default behavior is to proceed.
\set ON_ERROR_STOP 1


drop table if exists abac_assertion;
drop table if exists abac;
drop view  if exists requested_account;
drop table if exists identity_attribute;
drop table if exists identity;
drop table if exists account_privilege;
drop table if exists slice;
drop table if exists account;
drop table if exists shib_attribute;
