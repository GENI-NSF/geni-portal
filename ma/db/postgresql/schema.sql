-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

-- Tables for the MA (Member Authority)
-- ----------------------------------------------------------------------
--
-- ----------------------------------------------------------------------
-- Drop an obsolete table if it exists.
DROP TABLE IF EXISTS ma_member;
