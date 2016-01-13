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

function project_name_compare($p1, $p2)
{
  $pn1 = $p1[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]; 
  $pn2 = $p2[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]; 
  if ($pn1 == $pn2) {
    return 0;
  } else {
    return ($pn1 < $pn2) ? -1 : 1;
  }
}

if(!isset($project_objects) || !isset($slice_objects) || 
   !isset($member_objects) || !isset($project_slice_map)) 
{
  $retVal  = get_project_slice_member_info( $sa_url, $ma_url, $user, True);
  $project_objects = $retVal[0];
  $slice_objects = $retVal[1];
  $member_objects = $retVal[2];
  $project_slice_map = $retVal[3];
  $project_activeslice_map = $retVal[4];
}

usort($project_objects, "project_name_compare");

/* foreach($project_objects as $project) { */
/*   error_log("PROJ (orig) = " . print_r($project, true)); */
/* } */

$expired_projects = array();
$unexpired_projects = array();
foreach($project_objects as $project) {
  //  error_log("PROJ = " . print_r($project, true));
  $project_id = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
  $expired = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRED];
  if(convert_boolean($expired)) 
    $expired_projects[$project_id] = $project;
  else
    $unexpired_projects[$project_id] = $project;
}

$project_objects = $unexpired_projects;

/* foreach($project_objects as $project) { */
/*   error_log("PROJ (unexp) = " . print_r($project, true)); */
/* } */

// $tmp = "PROJECTS = " . print_r($project_objects, true) . "\nSLICES = " . print_r($slice_objects, true) . "\nMEMBERS = " . print_r($member_objects, true);

// print "alert(".$tmp.")";

$num_projects = count($project_objects);

$disable_create_project = "";
$disable_join_project = "";
$disable_project_lead = "";
if ($in_lockdown_mode) {
  $disable_create_project = "disabled";
  $disable_join_project = "disabled";
  $disable_project_lead = "disabled";
}

print "<h2>Projects</h2>\n";

// The idea here was to show this table only if the user is a lead or admin on _some_ project
// But we don't have an easy way to check that
/* if ($user->isAllowed(PA_ACTION::ADD_PROJECT_MEMBER, CS_CONTEXT_TYPE::PROJECT, null)) { */
/*   error_log("user is allowed to add project members"); */
/*   // Show outstanding project requests for this user to handle */
/*   $reqs = get_pending_requests_for_user($sa_url, $user, $user->account_id, CS_CONTEXT_TYPE::PROJECT, null); */
/*   if (isset($reqs) && count($reqs) > 0) { */
/*     print "Found " . count($reqs) . " outstanding project join requests for you to handle:<br/>\n"; */
/*     print "<table>\n"; */
/*     print "<tr><th>Project Name</th><th>Project Lead</th><th>Request Created</th><th>Requestor</th><th>Handle Request</th></tr>\n"; */
/*     foreach ($reqs as $request) { */
/*       // Print it out */
/*       $project = lookup_project($sa_url, $user, $request['context_id']); */
/*       $project_id = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID]; */
/*       $project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]; */
/*       $purpose = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE]; */
/*       $reason = $request['request_text']; */
/*       $req_date_db = $request['creation_timestamp']; */
/*       $req_date = dateUIFormat($req_date_db); */
/*       $lead = $user->fetchMember($project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID]); */
/*       $lead_name = $lead->prettyName(); */
/*       $requestor = $user->fetchMember($request[RQ_ARGUMENTS::REQUESTOR]); */
/*       $requestor_name = $requestor->prettyName(); */
/*       $handle_url="handle-project-request.php?request_id=" . $request['id']; // *** */
/*       $handle_button = "<button style=\"\" onClick=\"window.location='" . $handle_url . "'\"><b>Handle Request</b></button>"; */
/*       print "<tr><td><a href=\"project.php?project_id=$project_id\">$project_name</a></td><td>$lead_name</td><td>$req_date</td><td>$requestor_name</td><td>$handle_button</td></tr>\n"; */
/*     } */
/*     print "</table>\n"; */
/*     print "<br/><br/>\n"; */
/*   } else { */
/*     print "<div class='announce'>No outstanding project join requests to handle.</div><br/><br/>\n"; */
/*   } */
/* } else { */
/*   error_log("user not allowed to add project members"); */
/* } */

if ($user->isAllowed(PA_ACTION::CREATE_PROJECT, CS_CONTEXT_TYPE::RESOURCE, null)) {
  print "<a class='button' href='edit-project.php'><i class='material-icons'>add</i>New Project</a>";
  print "<a class='button' href='join-project.php'>Join a Project</a>";
} else {
  print "<a class='button' href='join-project.php'>Join a Project</a><br class='mobilebreak'>";
  print "<a class='button' href='modify.php?belead=belead'>Ask to be a Project Lead</a>";
}

$project_request_map = array();
if (count($project_objects) > 0) {
  $reqlist = get_pending_requests_for_user($sa_url, $user, $user->account_id, 
							CS_CONTEXT_TYPE::PROJECT);
  foreach ($reqlist as $req) {
     // print "\nREQ: ". print_r($req, true);	
     // print "\nREQ context ID: ". print_r($req[RQ_ARGUMENTS::CONTEXT_ID], true);	
     if ($req[RQ_REQUEST_TABLE_FIELDNAME::STATUS] != RQ_REQUEST_STATUS::PENDING){
        // print "\nNOT PENDING: ".$req[RQ_REQUEST_TABLE_FIELDNAME::STATUS];
        continue;
     }
     $context_id = $req[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID];
     if (array_key_exists($context_id , $project_request_map )) {
       $project_request_map[$context_id][] = $req;
     } else {
       $project_request_map[$context_id] = array($req);
     }
 }							
  // print "\nREQ LIST: ". print_r($reqlist, true);	
  // print "\nPROJ REQ MAP: ". print_r($project_request_map, true);							
}

if (count($project_objects) > 0) {
  $lead_names = lookup_member_names_for_rows($ma_url, $user, $project_objects, 
					     PA_PROJECT_TABLE_FIELDNAME::LEAD_ID);
    print "<div class='tablecontainer'>";
  print "\n<table>\n";
  print ("<tr><th>Project Name</th><th>Project Lead</th><th>Purpose</th><th>Slice Count</th><th>Create Slice</th></tr>\n");
  // name, lead_id, purpose
  foreach ($project_objects as $project) {
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
	$handle_req_str = "(<b>$reqcnt</b> Join Request(s) to <a href=\"handle-project-request.php?project_id=$project_id\">Handle</a>) ";
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
    $create_slice_link = "<button style=\"\" onClick=\"window.location='" . "createslice.php?project_id=$project_id" . "'\"><b>Create Slice</b></button>";
    if(!$user->isAllowed(SA_ACTION::CREATE_SLICE, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
      $create_slice_link = "";
    }
    print ("<tr><td> <a href=\"project.php?project_id=$project_id\">" . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME] . 
	   "</a> $handle_req_str</td><td> <a href=\"project-member.php?project_id=$project_id&member_id=" .
	   $lead_id . "\">" . $lead_name . "</a> </td> " .
	   "<td> " . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE] . 
	   " </td>");
    print ("<td align=\"center\"> " . count($project_activeslice_map[$project_id]) . " </td>");
    print ("<td> " .
	   $create_slice_link . "</td></tr>\n");
    // FIXME: Button to invite people to the project?
  }
  print "</table>\n";
  print "</div>";
} else {
  print "<i> No projects.</i><br/>\n";
}

// Show outstanding project requests BY this user - projects you asked to join
$reqs = get_requests_by_user($sa_url, $user, $user->account_id, CS_CONTEXT_TYPE::PROJECT, null, RQ_REQUEST_STATUS::PENDING);
if (isset($reqs) && count($reqs) > 0) {

  $project_ids = array();
  foreach($reqs as $req) {
    $project_id = $req['context_id'];
    if (!in_array($project_id, $project_ids))
      $project_ids[] = $project_id;
  }
  $projects = lookup_project_details($sa_url, $user, $project_ids);
  $project_lead_names = lookup_member_names_for_rows($ma_url, $user, $projects, 
						PA_PROJECT_TABLE_FIELDNAME::LEAD_ID);

  print "<br/>\n";
  print "<h3>Projects you Asked to Join</h3>\n";
  print "Found " . count($reqs) . " outstanding project join request(s) by you:<br/>\n";
  print "<div class='tablecontainer'>";
  print "<table>\n";
  // Could add a cancel button?
  print "<tr><th>Project Name</th><th>Project Lead</th><th>Project Purpose</th><th>Request Created</th><th>Request Reason</th><th>Cancel Request</th></tr>\n";
  foreach ($reqs as $request) {
    // Print it out
    $project_id = $request['context_id'];
    $project = $projects[$project_id];
    $project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
    $purpose = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
    $reason = $request['request_text'];
    if (strlen($reason) > 45) {
      $reason = substr($reason, 0, 40) . '...';
    }
    $req_date_db = $request['creation_timestamp'];
    $req_date = dateUIFormat($req_date_db);
    $lead_id = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
    $lead_name = $project_lead_names[$lead_id];
    $cancel_url="cancel-join-project.php?request_id=" . $request[RQ_REQUEST_TABLE_FIELDNAME::ID];
    $cancel_button = "<button style=\"\" onClick=\"window.location='" . $cancel_url . "'\"><b>Cancel Request</b></button>";
    print "<tr><td>$project_name</td><td>$lead_name</td><td>$purpose</td><td>$req_date</td><td>$reason</td><td>$cancel_button</td></tr>\n";
  }
  print "</table>\n";
  print "</div>";
  print "<br/><br/>\n";
} else {
  print "<div class='announce'><p>No outstanding project join requests by you.</p></div>\n";
}


