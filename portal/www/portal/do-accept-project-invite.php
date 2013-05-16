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

require_once('header.php');
require_once("user.php");
require_once('sr_constants.php');
require_once('sr_client.php');
require_once('pa_constants.php');
require_once('pa_client.php');

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

if (! isset($sa_url)) {
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
}

include("tool-lookupids.php");

error_log("DAPI.request = " . print_r($_REQUEST, true));

$invite_id = null;
if (array_key_exists('invite_id', $_REQUEST)) {
  $invite_id = $_REQUEST['invite_id'];
}

$project_name = null;
if (array_key_exists('project_name', $_REQUEST)) {
  $project_name = $_REQUEST['project_name'];
}

if($invite_id == null) {
  $_SESSION['lasterror'] = "Ill-formed request to do-accept-project-invite.php";
  relative_redirect("home.php");
}

accept_invitation($sa_url, $user, $invite_id);

$_SESSION['lastmessage'] = "Successfully joined project $project_name";
relative_redirect('home.php');