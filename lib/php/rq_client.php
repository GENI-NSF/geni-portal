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


// Interface for managing queues of pending reuqests for actions of one operator relative
// to the status of another (joining slices or projects, approving accounts, changing attributes)

require_once('rq_constants.php');

/*
 * The interface is as follows:

 * // Create a request of given content
 * // request_details is a dictionary of attributes, to be turned a string when stored/retrieved from database
 * request_id <= create_request(context, context_id, request_type, request_text, request_details=null)

 * // Set disposition of pending request to APPROVED, REJECTED or CANCELED
 * // (setting status, timestamp, approver)
 * resolve_pending_request(request_id, resolution_status, resolution_description)

 * // Get list of requests for given context
 * get_requests_for_context(context_type, context_id)

 * // Get list of requests made by given user
 * // Optionally, limit by given context type
 * get_requests_by_user(account_id, context_type=null, context_id=null)

 * // Get list of requests pending for given user
 * // Optionally, limit by given context
 * get_pending_requests_for_user(account_id, context_type=null, context_id=null)

 * // Get number of pending requests for a given user
 * // Optionally, limit by given context
 * get_number_of_pending_requests_for_user(account_id, context_type=null, context_id=null)

 * // Get request info for a single request id
 * get_request_by_id(request_id)

 */

// Create a request of given content
// request_details is a dictionary of attributes, to be turned a string when stored/retrieved from database
function create_request($url, $signer, $context_type, $context_id, $request_type, 
			$request_text, $request_details = '')
{
  $request_message['operation'] = 'create_request';
  $request_message[RQ_ARGUMENTS::CONTEXT_TYPE] = $context_type;
  $request_message[RQ_ARGUMENTS::CONTEXT_ID] = $context_id;
  $request_message[RQ_ARGUMENTS::REQUEST_TYPE] = $request_type;
  $request_message[RQ_ARGUMENTS::REQUEST_TEXT] = $request_text;
  $request_message[RQ_ARGUMENTS::REQUESTOR] = $signer->account_id;
  $request_message[RQ_ARGUMENTS::REQUEST_DETAILS] = $request_details;
  $result = put_message($url, $request_message, $signer->certificate(), $signer->privateKey());
  return $result;
}

// Set disposition of pending request to APPROVED, REJECTED or CANCELED
// Approve given request (setting status, timestamp, approver)
function resolve_pending_request($url, $signer, $request_id, 
				 $resolution_status, $resolution_description)
{
  $request_message['operation'] = 'resolve_pending_request';
  $request_message[RQ_ARGUMENTS::REQUEST_ID] = $request_id;
  $request_message[RQ_ARGUMENTS::RESOLVER] = $signer->account_id;
  $request_message[RQ_ARGUMENTS::RESOLUTION_STATUS] = $resolution_status;
  $request_message[RQ_ARGUMENTS::RESOLUTION_DESCRIPTION] = $resolution_description;
  $result = put_message($url, $request_message, $signer->certificate(), $signer->privateKey());
  return $result;
}

// Get list of requests for given context
function get_requests_for_context($url, $signer, $context_type, $context_id)
{
  $request_message['operation'] = 'get_requests_for_context';
  $request_message[RQ_ARGUMENTS::CONTEXT_TYPE] = $context_type;
  $request_message[RQ_ARGUMENTS::CONTEXT_ID] = $context_id;
  $result = put_message($url, $request_message, $signer->certificate(), $signer->privateKey());
  return $result;
}

// Get list of requests made by given user
// Optionally, limit by given context type
function get_requests_by_user($url, $signer, $account_id, $context_type=null, $context_id=null)
{
  $request_message['operation'] = 'get_requests_by_user';
  $request_message[RQ_ARGUMENTS::ACCOUNT_ID] = $account_id;
  $request_message[RQ_ARGUMENTS::CONTEXT_TYPE] = $context_type;
  $request_message[RQ_ARGUMENTS::CONTEXT_ID] = $context_id;
  $result = put_message($url, $request_message, $signer->certificate(), $signer->privateKey());
  return $result;
}

// Get list of requests pending for given user
// Optionally, limit by given context
function get_pending_requests_for_user($url, $signer, $account_id, 
				       $context_type=null, $context_id=null)
{
  $request_message['operation'] = 'get_pending_requests_for_user';
  $request_message[RQ_ARGUMENTS::ACCOUNT_ID] = $account_id;
  $request_message[RQ_ARGUMENTS::CONTEXT_TYPE] = $context_type;
  $request_message[RQ_ARGUMENTS::CONTEXT_ID] = $context_id;
  $result = put_message($url, $request_message, $signer->certificate(), $signer->privateKey());
  return $result;
}

// Get number of pending requests for a given user
// Optionally, limit by given context
function get_number_of_pending_requests_for_user($url, $signer, $account_id, 
						 $context_type=null, $context_id=null)
{
  $request_message['operation'] = 'get_number_of_pending_requests_for_user';
  $request_message[RQ_ARGUMENTS::ACCOUNT_ID] = $account_id;
  $request_message[RQ_ARGUMENTS::CONTEXT_TYPE] = $context_type;
  $request_message[RQ_ARGUMENTS::CONTEXT_ID] = $context_id;
  $result = put_message($url, $request_message, $signer->certificate(), $signer->privateKey());
  return $result;
}

// Get request info for a single request id
function get_request_by_id($url, $signer, $request_id)
{
  $request_message['operation'] = 'get_request_by_id';
  $request_message[RQ_ARGUMENTS::REQUEST_ID] = $request_id;
  $result = put_message($url, $request_message, $signer->certificate(), $signer->privateKey());
  return $result;
}

?>

