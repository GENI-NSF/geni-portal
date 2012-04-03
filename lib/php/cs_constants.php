<?php

/* Set of constants for managing calls and data records for 
 * GENI Clearinghouse Credential Store
 */

/* Set of known context types for services within GENI CH credential store */`
/* We store/retrieve by index into this array, but print the strings */
$CS_CONTEXT_TYPE_NAMES = array("NONE", 
			       "PROJECT",
			       "SLICE",
			       "SLIVER");

class CS_CONTEXT_TYPE
{
  const NONE = 0;
  const PROJECT = 1;
  const SLICE = 2;
  const SLIVER = 3;
}

/* Set of arguments in calls to the SR interface */
class CS_ARGUMENT
{
  const ID = "id";
  const SIGNER = "signer";
  const PRINCIPAL = "principal";
  const ATTRIBUTE = "attribute";
  const ACTION = "action";`
  const CONTEXT_TYPE = "context_type";
  const CONTEXT = "context";
  const RENEWAL_TIME = "renewal_time";
}

/* Name of table to which the CS persists/retrieves credentials */
$CS_ASSERTION_TABLENAME = "cs_assertion";
/* Name of table to which the CS persists/retrieves policies */
$CS_POLICY_TABLENAME = "cs_policy";

/* CS Credential table has the following fields */
class CS_ASSERTION_TABLE_FIELDNAME {
  const ID = "id";
  const SIGNER = "signer";
  const PRINCIPAL = "principal";
  const ATTRIBUTE = "attribute";
  const CONTEXT_TYPE = "context_type";
  const CONTEXT = "context";
  const EXPIRATION = "expiration";
  const ASSERTION_CERT = "assertion_cert";
}

/* CS Policy table has the following fields */
class CS_POLICY_TABLE_FIELDNAME {
  const ID = "id";
  const SIGNER = "signer";
  const ATTRIBUTE = "attribute";
  const CONTEXT_TYPE = "context_type";
  const ACTION = "action";
  const POLICY_CERT = "policy_cert";
}

?>
