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

/* Set of constants for managing attributes of members in the 
 * GENI Clearinghouse Member Authority (MA)
 */

/* Set of arguments in calls to MA interface */
class MA_ARGUMENT {
  const MEMBER_ID = "member_id";
  const ROLE_TYPE = "role_type";
  const CONTEXT_TYPE = "context_type";
  const CONTEXT_ID = "context_id";
}

/* Defined sets of role types */
class ROLE_TYPE {
  const OWNER = 0; // Has all privileges to entity, and is single POC
  const ADMIN = 1; // Has all privileges to entity
  const USER = 2; // Has read-write privileges to entity
  const AUDITOR = 3; // Has read-only privileges to entity
}

/* Defined sets of context types */
class CONTEXT_TYPE {
  const PROJECT = 0; // Context is a project, context_id is a project_id
  const SLICE = 1;   // Context is a slice, context_id is a slice_idp
  const SERVICE = 2; // This is a context-free type : no context_id supplied
  const MEMBER = 3;  // This is a context-free type: no context_id supplied
}

/* Name of table containing per member attribute info */
$MA_MEMBER_TABLENAME = "ma_member";

/* Name of fields for member table */
class MA_MEMBER_TABLE_FIELDNAME {
  const MEMBER_ID = "member_id";
  const ROLE_TYPE = "role_type";
  const CONTEXT_TYPE = "context_type";
  const CONTEXT_ID = "context_id";
}

?>