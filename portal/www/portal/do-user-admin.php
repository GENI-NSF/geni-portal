<?php
//----------------------------------------------------------------------
// Copyright (c) 2015-2016 Raytheon BBN Technologies
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

require_once("user.php");
require_once("util.php");
require_once("sa_client.php");
require_once("pa_client.php");
require_once("ma_client.php");
require_once('cs_constants.php');
require_once('sr_constants.php');

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  exit();
}

// This admin functionality is for OPERATORS only
if (!$user->isAllowed(CS_ACTION::ADMINISTER_MEMBERS, CS_CONTEXT_TYPE::MEMBER, null)) {
  exit();
}

$sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$signer = $user;

// Handle the request, determine which action to perform
if (array_key_exists('action', $_REQUEST)) {
  $action = $_REQUEST['action'];
  if ($action == "remove"){
    if (array_key_exists('project_id', $_REQUEST) && array_key_exists('member_id', $_REQUEST)) {
      modify_project_membership($sa_url, $signer, $_REQUEST['project_id'], array(), array(), array($_REQUEST['member_id']));
    } else {
      print "Insufficient information given to remove user";
      exit();
    }
  } else if ($action == "disable") {
      if (array_key_exists('member_urn', $_REQUEST)) {
        disable_user($ma_url, $signer, $_REQUEST['member_urn']);        
      }
  } else {
      print "No action requested";
      exit();
  }
}

?>
