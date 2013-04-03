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
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("pa_client.php");
require_once("pa_constants.php");
require_once('rq_client.php');
require_once("sa_client.php");
require_once("cs_client.php");
require_once("proj_slice_member.php");
include("services.php");


if(isset($expired_projects) && count($expired_projects) > 0) {

  print "<h2>Expired Projects</h2>\n";

  $lead_names = lookup_member_names_for_rows($ma_url, $user, $expired_projects,
					     PA_PROJECT_TABLE_FIELDNAME::LEAD_ID);
  print "\n<table>\n";
  print ("<tr><th>Project Name</th><th>Project Lead</th><th>Purpose</th><th>Slice Count</th>" . 
	 //	 "<th>Create Slice</th>" . 
	 "</tr>\n");
  // name, lead_id, purpose
  foreach ($expired_projects as $project) {
    $project_id = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
    $handle_req_str = "";
    if ($user->isAllowed(PA_ACTION::ADD_PROJECT_MEMBER, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
      if (array_key_exists($project_id , $project_request_map )) {
	$reqcnt = count($project_request_map[$project_id]);
      } else {
         $reqcnt = 0;
      }
      //      error_log("REQCNT " . print_r($reqcnt, true) . " " . $project_id);
      if ($reqcnt == 0) {
	$handle_req_str = "";
      } elseif ($reqcnt == 1) {
	$rid = $project_request_map[$project_id][0][RQ_REQUEST_TABLE_FIELDNAME::ID];
	$handle_req_str = "(<b>$reqcnt</b> Join Request(s) to <a href=\"handle-project-request.php?request_id=$rid\">Handle</a>) ";
      } else {
	$handle_req_str = "(<b>$reqcnt</b> Join Request(s) to <a href=\"project.php?project_id=$project_id\">Handle</a>) ";
      }
    }

    //    error_log("Before load user " . time());
    $lead_id = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
    $lead_name = $lead_names[$lead_id];
    //    $lead_obj = $member_objects[ $lead_id ];
    //    $lead = new Member();
    //    $lead->init_from_record($lead_obj);

    // print "\nMEMBERS: ". print_r($member_objects, true);
    // print "\nLEAD ID: ". print_r($lead_id, true);
    // print "\nLEAD Object: ". print_r($member_objects[ $lead_id ], true);
    // print "\nLEAD USER: ". print_r($lead, true);
    // print "\nLEAD AccountID: ". print_r($lead->member_id, true);

    //    error_log("After load user " . time());
    //<button style="width:65;height:65" onClick="window.location='http://www.javascriptkit.com'"><b>Home</b></button>
    // http://www.javascriptkit.com/howto/button.shtml
    $create_slice_link = "<button style=\"\" onClick=\"window.location='" . "createslice.php?project_id=$project_id" . "'\">" . 
      "<b>Create Slice</b></button>";
    if(!$user->isAllowed(SA_ACTION::CREATE_SLICE, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
      $create_slice_link = "";
    }
    print ("<tr><td> <a href=\"project.php?project_id=$project_id\">" . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME] . 
	   "</a> $handle_req_str</td><td> <a href=\"project-member.php?project_id=$project_id&member_id=" .
	   $lead_id . "\">" . $lead_name . "</a> </td> " .
	   "<td> " . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE] . 
	   " </td><td align=\"center\"> " . count($project_slice_map[$project_id]) . " </td>" . 
	   //	   "<td> " .  $create_slice_link . "</td>" . 
	   "</tr>\n");
    // FIXME: Button to invite people to the project?
  }
  print "</table>\n";
} else {
  print "<i> No expired projects.</i><br/>\n";
}

print "<br/>\n";

