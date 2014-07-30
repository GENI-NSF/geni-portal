-- -------------------
-- Apply changes to add unique and no null and foreign key constraints, and related indices
-- See chapi #174, proto-ch #943, proto-ch #1081
-- -------------------

ALTER TABLE account
  ALTER COLUMN username SET NOT NULL;

-- Unused
DROP VIEW IF EXISTS active_account;
DROP VIEW IF EXISTS disabled_account;

ALTER TABLE identity
   ALTER COLUMN eppn SET NOT NULL;

ALTER TABLE identity_attribute
   ALTER COLUMN name SET NOT NULL;

-- Unused
DROP TABLE IF EXISTS account_slice CASCADE;
