<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2015 Raytheon BBN Technologies
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

/* Set of known attribute or role types */
class CS_ATTRIBUTE_TYPE {
  const LEAD = 1;
  const ADMIN = 2;
  const MEMBER = 3;
  const AUDITOR = 4;
  const OPERATOR = 5;
}

// Names of attribute / role types
$CS_ATTRIBUTE_TYPE_NAME = array(
				CS_ATTRIBUTE_TYPE::LEAD => "Lead", 
				CS_ATTRIBUTE_TYPE::ADMIN =>"Admin", 
				CS_ATTRIBUTE_TYPE::MEMBER =>"Member", 
				CS_ATTRIBUTE_TYPE::AUDITOR =>"Auditor",
        CS_ATTRIBUTE_TYPE::OPERATOR => "Operator"
        );

class CS_CONTEXT_TYPE
{
  // These have contex_types have contexts: PROJECT_ID and SLICE_ID respectively
  const PROJECT = 1; // Manage project properties using the Project Authority
  const SLICE = 2; // Manage slice properties using the Slice Authority
  // These are context free: you either have the attribute wrt. this context type or you don't
  const RESOURCE = 3; // Generic actions with respect to resources (aggregates, e.g.)
  const SERVICE = 4; // Manage service properties using the Service Registory
  const MEMBER = 5; // Manage member privileges using the Member Authority
}

/* Set of known context types for services within GENI CH credential store */
/* We store/retrieve by index into this array, but print the strings */
$CS_CONTEXT_TYPE_NAME = array(
			      CS_CONTEXT_TYPE::PROJECT => "PROJECT",
			      CS_CONTEXT_TYPE::SLICE =>  "SLICE",
			      CS_CONTEXT_TYPE::RESOURCE =>  "RESOURCE",
			      CS_CONTEXT_TYPE::SERVICE => "SERVICE", 
			      CS_CONTEXT_TYPE::MEMBER => "MEMBER");

// Is the context type one for a specific object (true) or general to a class of operations (false)?
function is_context_type_specific($context_type) 
{
  return $context_type == CS_CONTEXT_TYPE::PROJECT || $context_type == CS_CONTEXT_TYPE::SLICE;
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

/* Name of table mapping CS attributes to names */
$CS_ATTRIBUTE_TABLENAME = "cs_attribute";
/* Name of table to which the CS persists/retrieves credentials */
$CS_ASSERTION_TABLENAME = "cs_assertion";
/* Name of table to which the CS persists/retrieves policies */
$CS_POLICY_TABLENAME = "cs_policy";

/* CS Attribute table */
class CS_ATTRIBUTE_TABLE_FIELDNAME {
  const ID = "id";
  const NAME = "name";
}

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

/* CS Actions on which privileges are enabled/disabled */
/* Should match the set of privileges in the cs action table */
class CS_ACTION {
  const ADMINISTER_RESOURCES = 'administer_resources';
  const ADMINISTER_MEMBERS = 'administer_members';
  const ADMINISTER_SERVICES = 'administer_services';
}


?>
