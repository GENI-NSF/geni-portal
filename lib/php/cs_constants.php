<?php
//----------------------------------------------------------------------
// Copyright (c) 2012 Raytheon BBN Technologies
//
// Permission is hereby granted, free of charge, to any person obtaining
// a copy of this software and/or hardware specification (the "Work") to
// deal in the Work without restriction, including without limitation the
// rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Work, and to permit persons to whom the Work
// is furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be
// included in all copies or substantial portions of the Work.
//
// THE WORK IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
// NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
// HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS
// IN THE WORK.
//----------------------------------------------------------------------

/* Set of constants for managing calls and data records for 
 * GENI Clearinghouse Credential Store
 */

/* Set of known attribute types */
class CS_ATTRIBUTE_TYPE {
  const REGISTRAR = 1;
  const ADMIN = 2;
  const PROJECT_LEAD = 3;
  const PROJECT_MEMBER = 4;
  const PROJECT_AUDITOR = 5;
  const SLICE_LEAD = 6;
  const SLICE_MEMBER = 7;
  const SLICE_AUDITOR = 8;
  const SLIVER_LEAD = 9;
  const SLIVER_MEMBER = 10;
  const SLIVER_AUDITOR = 11;

}

/* Set of known privilege types */
class CS_PRIVILEGE_TYPE {
  const DELEGATE = 1;
  const READ = 2;
  const WRITE = 3;
}

/* Set of known context types for services within GENI CH credential store */
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
  const PRIVILEGE = "privilege";
  const CONTEXT_TYPE = "context_type";
  const CONTEXT = "context";
  const RENEWAL_TIME = "renewal_time";
  const ACTION = "action";
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
  const PRIVILEGE = "privilege";
  const POLICY_CERT = "policy_cert";
}

?>
