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
 * GENI Clearinghouse Project Authority
 */

/* Set of arguments in calls to PA interface */
class PA_ARGUMENT 
{
  const PROJECT_ID = "project_id";
  const PROJECT_NAME = "project_name";
  const LEAD_ID = "lead_id";
  const PREVIOUS_LEAD_ID = "previous_lead_id";
  const PROJECT_EMAIL = "project_email";
  const PROJECT_PURPOSE  = "project_purpose";
  const MEMBER_ID = "member_id";
  const IS_MEMBER = "is_member";
  const ROLE_TYPE = "role_type";
  const CREATION = "creation";
}

/* Name of table containing per-project info */
$PA_PROJECT_TABLENAME = "pa_project";

class PA_PROJECT_TABLE_FIELDNAME 
{
  const NAME = "name";
  const PROJECT_ID = "project_id";
  const PROJECT_NAME = "project_name";
  const LEAD_ID = "lead_id";
  const PROJECT_EMAIL = "project_email";
  const PROJECT_PURPOSE = "project_purpose";
  const CREATION = "creation";
}

/* Name of table containing project membership info */
$PA_PROJECT_MEMBER_TABLENAME = "pa_project_member";

class PA_PROJECT_MEMBER_TABLE_FIELDNAME
{
  const PROJECT_ID = "project_id";
  const MEMBER_ID = "member_id";
  const ROLE = "role";
}

/* Name of table containing pending project membership requests */
$PA_PROJECT_MEMBER_REQUEST_TABLENAME = 'pa_project_member_request';

?>
