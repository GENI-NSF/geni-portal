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

require_once('pa_constants.php');
require_once('sa_constants.php');

// Routines to help support pending users requests on either projects or slices

function user_context_query($account_id, $context_type)
{
  if ($context_type == CS_CONTEXT_TYPE::SLICE)
    return slice_user_context_query($account_id);
  else
    return project_user_context_query($account_id);
}

function get_request_tablename($context_type)
{
  global $SA_SLICE_MEMBER_REQUEST_TABLENAME;
  global $PA_PROJECT_MEMBER_REQUEST_TABLENAME;

  if ($context_type == CS_CONTEXT_TYPE::SLICE)
    return $SA_SLICE_MEMBER_REQUEST_TABLENAME;
  else
    return $PA_PROJECT_MEMBER_REQUEST_TABLENAME;
}

// Define the specific function to call to determine what projects a given user can act on.
// (That is, requests about these projects can be handled by this user.)
function project_user_context_query($account_id)
{
  global $PA_PROJECT_MEMBER_TABLENAME;
  $conn = db_conn();
  return "select " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID
  . " FROM " . $PA_PROJECT_MEMBER_TABLENAME
  . " WHERE " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE
  . " IN (" . $conn->quote(CS_ATTRIBUTE_TYPE::LEAD, 'integer') . ", " . 
    $conn->quote(CS_ATTRIBUTE_TYPE::ADMIN, 'integer') . ")"
    . " AND " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " = " . 
    $conn->quote($account_id, 'text');

}

// Define the specific function to call to determine what slices are relevant to a given user
// (That is, requests about that slice are for this user)
function slice_user_context_query($account_id)
{
  global $SA_SLICE_MEMBER_TABLENAME;
  return "select " . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID 
    . " FROM " . $SA_SLICE_MEMBER_TABLENAME
    . " WHERE " . SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE 
    . " IN (" . $conn->quote(CS_ATTRIBUTE_TYPE::LEAD, 'integer') . ", " . $conn->quote(CS_ATTRIBUTE_TYPE::ADMIN, 'integer') . ")"
    . " AND " . SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " = " . $conn->quote($account_id, 'text');
  
}



?>
