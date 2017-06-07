<?php
//----------------------------------------------------------------------
// Copyright (c) 2016 Raytheon BBN Technologies
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

/* When a user wants to join a project they type in a project name.
 * This script is invoked by AJAX and looks up the project by name,
 * returning a JSON object for the project containing basic
 * project information.
 *
 * If no project by the given name exists, an empty JSON object
 * is returned.
 */

require_once("sr_client.php");
require_once("sr_constants.php");
require_once("pa_client.php");
require_once("pa_constants.php");

$user = geni_loadUser();

// If not a known user, redirect to onboard.
if (!isset($user)) {
  // signal an HTTP user error
}

function result($c, $v, $o) {
  return array("code" => $c,
               "value" => $v,
               "output" => $o);
}

// There should be a project name in the request
if (! array_key_exists("name", $_REQUEST)) {
    // No name argument, return empty JSON object.
    // print json_encode($emptyObject);
    $result = result(1, null, "No project name specified.");
} else {
    $project_name = $_REQUEST["name"];

    // Look up the project by name at the slice authority
    $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
    $signer = $user;
    $project = lookup_project_by_name($sa_url, $signer, $project_name);

    if (is_null($project)) {
        // No project with the given name exists.
        // Return empty JSON object.
        // print json_encode($emptyObject);
        $result = result(1, null, "Project $project_name not found.");
    } else {
        $result = result(0, $project, "");
    }
}

print json_encode($result);
?>
