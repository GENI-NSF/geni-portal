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

$prev_name = session_id('AUTHZ-SESSION');

require_once('message_handler.php');
require_once('db_utils.php');
require_once('cs_constants.php');

/**
 * GENI Clearinghouse Authorization Service (AUTHZ) controller interface
 * The Authorization Service determines whether a given principal
 * has a given privilege to take a given action in a given context.
 *
 * The Credential Store contains attributes and policies.
 *    Attributes map principals to attributes possibly in contexts
 *    Policies map attributes to privileges.
 * The AuthZ service maps these privileges to specific actions.
 *    This map is contained in private database tables
 *
 * Supports 1 'read' interfaces:
 * success/failure <= request_authorization(principal, action, 
 *     context_type, context);
 **/

function request_authorization($principal, $action, $context_type, $context)
{
  $result = 1;
  return $result;
}

handle_message("AUTHZ");

?>
