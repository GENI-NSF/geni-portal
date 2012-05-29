<?php

/*
 * Set of constants associated with making and satisfying requests
 * to operators (to approve or reject in the course of operations)
 * E.g. approving new accounts, approving new provileges, 
 * approving new memberships
 */

// We use the same table structure for three different kinds of requests
// controlled by three different controllers (SA for slice membership, PA for project membership
// and MA for account approval). So all these controllers use tables of different names (which
// will be set by a global variable REQUEST_TABLE_FIELDNAME before including the rq_controller
// file

/* Set of arguments to call to RQ interfaces */
class RQ_ARGUMENTS {
  const CONTEXT_TYPE = 'context_type';
  const CONTEXT_ID = 'context_id';
  const REQUEST_ID = 'request_id';
  const REQUESTOR = 'requestor';
  const RESOLVER = 'resolver';
  const REQUEST_DISPOSITION = 'request_disposition';
  const REQUEST_TYPE = 'request_type';
  const REQUEST_TEXT = 'request_text';
  const REQUEST_DETAILS = 'request_details';
  const ACCOUNT_ID = 'account_id';
  const RESOLUTION_STATUS = 'resolution_status';
  const RESOLUTION_DESCRIPTION = 'resolution_description';
}

// Types of requests
class REQUEST_TYPE
{
  const JOIN = 0;
  const UPDATE_ATTRIBUTES = 1;
}

// Statuses of requests
class REQUEST_STATUS
{
  const PENDING = 0;
  const APPROVED = 1;
  const CANCELLED = 2;
  const REJECTED = 3;
};

// Common field structure for all request tables
class RQ_REQUEST_TABLE_FIELDNAME {
  const ID = "id";
  const CONTEXT_TYPE = 'context_type';
  const CONTEXT_ID = 'context_id';
  const REQUEST_TEXT = 'request_text';
  const REQUEST_TYPE = 'request_type';
  const REQUEST_DETAILS = 'request_details';
  const REQUESTOR = 'requestor';
  const STATUS = 'status';
  const CREATION_TIMESTAMP = 'creation_timestamp';
  const RESOLVER = 'resolver';
  const RESOLUTION_TIMESTAMP = 'resolution_timestamp';
  const RESOLUTION_DESCRIPTION = 'resolution_description';
};

?>
