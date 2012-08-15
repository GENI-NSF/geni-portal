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

require_once("user.php");
require_once("util.php");
require_once("pa_constants.php");
require_once("pa_client.php");
require_once("sr_constants.php");
require_once('sr_client.php');
require_once("sa_constants.php");
require_once("sa_client.php");

/*----------------------------------------------------------------------
 *
 * NOTE: $user must be set before including or requiring this file.
 *
 *----------------------------------------------------------------------
 */

// TODO: load slice_owner? project_lead?
// We're loading members from geni_loadUser - should this be from an MA instead?

// Name of actual script being used here
$file = $_SERVER["SCRIPT_NAME"];
$pinfo = pathinfo($file);
$script = $pinfo['basename'];

if (! isset($pa_url)) {
  $pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
  if (! isset($pa_url) || is_null($pa_url) || $pa_url == '') {
    error_log("Found no PA in SR!'");
  }
}

if (! isset($sa_url)) {
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
  if (! isset($sa_url) || is_null($sa_url) || $sa_url == '') {
    error_log("Found no SA in SR!'");
  }
}

if (array_key_exists("project_id", $_REQUEST)) {
  $project_id = $_REQUEST['project_id'];
  if (uuid_is_valid($project_id)) {
    $project = lookup_project($pa_url, $user, $project_id);
    if (isset($project) && is_array($project) && array_key_exists(PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME, $project)) {
      $project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
    }
  } else {
    if ($project_id != '') {
      error_log($script . ": invalid project_id (not valid uuid) from REQUEST: $project_id");
      $project_id = "none";
    }
  }
}

if (array_key_exists("slice_id", $_REQUEST)) {
  $slice_id = $_REQUEST['slice_id'];
  if (uuid_is_valid($slice_id)) {
    $slice = lookup_slice($sa_url, $user, $slice_id);
    if (isset($slice) && is_array($slice) && array_key_exists(SA_SLICE_TABLE_FIELDNAME::SLICE_NAME, $slice)) {
      $slice_name = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_NAME];
    }
    $slice_project_id = $slice[SA_ARGUMENT::PROJECT_ID];
    if (! uuid_is_valid($slice_project_id)) {
      error_log($script . ": invalid slice_project_id from DB for slice_id $slice_id");
      $slice_project_id = "none";
    } else {
      if (! isset($project_id)) {
	$project_id = $slice_project_id;
	if (! isset($pa_url)) {
	  $pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
	}
	$project = lookup_project($pa_url, $user, $project_id);
	if (isset($project) && is_array($project) && array_key_exists(PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME, $project)) {
	  $project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
	}
      } else {
	if ($project_id != $slice_project_id) {
	  error_log($script . ": slice_id $slice_id has project id $slice_project_id != REQUEST project_id $project_id");
	}
      }
    }
  } else {
    if ($slice_id != '') {
      error_log($script . ": invalid slice_id from REQUEST");
      $slice_id = "none";
    }
  }
}

if (array_key_exists("member_id", $_REQUEST)) {
  $member_id = $_REQUEST['member_id'];
  if (uuid_is_valid($member_id)) {
    $member = geni_loadUser($member_id);
    $member_name = $member->prettyName();
  } else {
    if ($member_id != '') {
      error_log($script . ": invalid member_id from REQUEST");
      $member_id = "none";
    }
  }
}

if (array_key_exists("rspec_id", $_REQUEST)) {
  $rspec_id = $_REQUEST['rspec_id'];
  $rspec = fetchRSpecById($rspec_id);
  if (is_null($rspec)) {
    if ($rspec_id != '') {
      error_log($script . ": invalid rspec_id $rspec_id from REQUEST");
      $rspec_id = "none";
    }
  }
}

// May be 1 or more am_id arguments. Instantiate them all, if many given
// To give many, name the arg am_id[]
if (array_key_exists("am_id", $_REQUEST)) {
  $am_id = $_REQUEST['am_id'];
  if (is_array($am_id)) {
    $am_ids = $am_id;
    foreach ($am_ids as $am_id) {
      $ams[] = get_service_by_id($am_id);
    }
    $am_id = $am_ids[0];
    $am = $ams[0];
  } else {
    $am = get_service_by_id($am_id);
  }
  if (is_null($am)) {
    if ($am_id != '') {
      error_log($script . ": invalid am_id $am_id from REQUEST");
      $am_id = null;
    }
  }
}
