<?php
//----------------------------------------------------------------------
// Copyright (c) 2015 Raytheon BBN Technologies
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
require_once("header.php");
require_once('util.php');
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("pa_client.php");
require_once("pa_constants.php");
require_once('rq_client.php');
require_once("sa_client.php");
require_once("cs_client.php");
require_once("proj_slice_member.php");
require_once("services.php");
require_once("user-preferences.php");


$user = geni_loadUser();

if (!isset($user) || is_null($user) || ! $user->isActive()) {
  $msg = "Unable to load user record.";
  relative_redirect('error-text.php?error=' . urlencode($msg));
}

if (! $user->portalIsAuthorized()) {
  $km_url = get_first_service_of_type(SR_SERVICE_TYPE::KEY_MANAGER);
  $params['redirect'] = selfURL();
  $query = http_build_query($params);
  $km_url = $km_url . "?" . $query;
  print "<h2>Portal authorization</h2>";
  print "<p>";
  print "The GENI Portal is not authorized by you as a client tool. If you would like";
  print " the GENI Portal to help you manage your projects and slices, you can";
  print " <a href=\"$km_url\">authorize the portal</a> to do so.";
  print "</p>";
  return 0;
}

show_header('GENI Portal: Home', true, true);
include("tool-showmessage.php");

  $tab_names_to_div_ids = array(
    "Slices" => "#slices",
    "Projects" => "#projects",
    "Logs" => "#logs",
    "Map" => "#map");

$default_slice_tab = $tab_names_to_div_ids[get_preference($user->urn(), "homepage_tab")];

echo "<script type='text/javascript'>GENI_USERNAME = '{$user->username}';";
echo "DEFAULT_TAB = '$default_slice_tab';</script>";

?>

<script src='cards.js'></script>
<script src='dashboard.js'></script>

<?php
  
// Get user's slices and projects
$retVal  = get_project_slice_member_info( $sa_url, $ma_url, $user, True);
$project_objects = $retVal[0];
$slice_objects = $retVal[1];
$member_objects = $retVal[2];
$project_slice_map = $retVal[3];
$project_activeslice_map = $retVal[4];

// Split up expired projects and unexpired projects
$expired_projects = array();
$unexpired_projects = array();
$num_projects = count($project_objects);
foreach($project_objects as $project) {
  $project_id = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
  $expired = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRED];
  if (convert_boolean($expired)) {
    $expired_projects[$project_id] = $project;
  } else {
    $unexpired_projects[$project_id] = $project;
  }
}
$all_projects = $project_objects;
$project_objects = $unexpired_projects;

if (count($all_projects) == 0) { // You are in no active projects, active or expired
  make_no_projects_greeting($user, true);
} else { 
  if(count($project_objects) == 0) { // You are in no active projects
    make_no_projects_greeting($user, false);
    print "<script type='text/javascript'>";
    print "$(document).ready(function(){";
    print 'update_selector($("#projectfilterswitch"), "-EXPIRED-PROJECTS-");';
    print 'switch_to_card("#projects");';
    print 'update_projects();';
    print "});";
    print "</script>";
  }

  make_navigation_tabs();

  if (get_preference($user->urn(), "homepage_view") == "cards") {
    // Get the user's unexpired slices
    $unexpired_slices = array();
    foreach($slice_objects as $slice) {
      $slice_id = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
      $expired = $slice[SA_SLICE_TABLE_FIELDNAME::EXPIRED];
      if (!convert_boolean($expired)) {
        $unexpired_slices[$slice_id] = $slice;
      }
    }
    $slice_objects = $unexpired_slices;

    // Get all of the slice owner names
    $slice_owner_names = array();
    if (count($slice_objects) > 0) {
      $slice_owner_names = lookup_member_names_for_rows($ma_url, $user, $slice_objects, 
                                                        SA_SLICE_TABLE_FIELDNAME::OWNER_ID);
    }
    $user_id = $user->account_id;
    $project_slice_counts = array();
    $slice_boxes = '';

    // Make slice box and get slice counts for making the project boxes
    foreach ($slice_objects as $slice) {
      // Get all the basic slice info for making the slice box
      $slice_id = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
      $slice_urn = $slice[SA_ARGUMENT::SLICE_URN];
      $slice_name = $slice[SA_ARGUMENT::SLICE_NAME];
      $slice_owner_id = $slice[SA_ARGUMENT::OWNER_ID];
      $slice_owner_name = $slice_owner_names[$slice_owner_id];
      $slice_exp_date = $slice[SA_ARGUMENT::EXPIRATION];
      $slice_project_id = $slice[SA_SLICE_TABLE_FIELDNAME::PROJECT_ID];

      // We'll use these counts later for saying a project "Has N slices"
      if (array_key_exists($slice_project_id, $project_slice_counts)) {
        $project_slice_counts[$slice_project_id]++;
      } else {
        $project_slice_counts[$slice_project_id] = 1;
      }

      if (!array_key_exists($slice_project_id, $project_objects)) { // Slice is in an expired project
        $project_expiration = "";
        $project_expiration_dt = new DateTime();
        $slice_project_name = "-Expired Project-";
      } else { // Slice is in an active project
        $project = $project_objects[$slice_project_id];
        $project_expiration = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRATION];
        $project_expiration_dt = new DateTime($project_expiration);
        $slice_project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
      }

      // For printing out "next resource expires in N days"
      $slivers = lookup_sliver_info_by_slice($sa_url, $user, $slice_urn);
      $next_resource_exp_date = get_next_resource_expiration($slivers);

      $max_renewal_days = get_max_renewal_days($project_expiration);

      $slice_boxes .= make_slice_box($slice_name, $slice_id, $slice_owner_id, $slice_owner_name,
                                     $slice_project_name, $slice_project_id, count($slivers), 
                                     $slice_exp_date, $next_resource_exp_date, $max_renewal_days);
    }

    // Get names of all the project leads for the user's projects
    $lead_names = lookup_member_names_for_rows($ma_url, $user, $all_projects, 
                                               PA_PROJECT_TABLE_FIELDNAME::LEAD_ID);

    // Make and fill dictionary of project join requests
    // Do this for ACTIVE projects only
    $project_request_map = array();
    if (count($project_objects) > 0) {
      $reqlist = get_pending_requests_for_user($sa_url, $user, $user->account_id, 
                  CS_CONTEXT_TYPE::PROJECT);
      foreach ($reqlist as $req) {
        if ($req[RQ_REQUEST_TABLE_FIELDNAME::STATUS] != RQ_REQUEST_STATUS::PENDING) {
          continue;
        }
        $context_id = $req[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID];
        if (array_key_exists($context_id , $project_request_map )) {
          $project_request_map[$context_id][] = $req;
        } else {
          $project_request_map[$context_id] = array($req);
        }
      }                       
    }

    // Make filters for slice section, we'll add project names to this as we go through projects
    $slice_filters = "<ul class='selectorcontainer'>";
    $slice_filters .= "<li class='has-sub selector' style='float:none;' id='slicefilterswitch'>";
    $slice_filters .= "<span class='selectorshown'>Filters</span><ul class='submenu'>";
    $slice_filters .= "<li data-value='-ALL-' class='selectorlabel'>Categories</li>";
    $slice_filters .= "<li data-value='-ALL-'>All slices</li>";
    $slice_filters .= "<li data-value='-MY-'>Slices I lead</li>";
    $slice_filters .= "<li data-value='-has-resources-'>Has resources</li>";
    $slice_filters .= "<li data-value='-ALL-' class='selectorlabel'>Projects</li>";

    $slice_card_project_buttons = "";
    $project_boxes = "";

    // Make project box for all projects 
    // Make slice filter-by-project dropdown, and "manage proj" and "new slice buttons" for active projects
    foreach ($all_projects as $project) {
      // Gather basic project info
      $project_id = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
      $project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
      $lead_id = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
      $purpose = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
      $expiration = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRATION];
      $lead_name = $lead_names[$lead_id];
      $expired = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRED];

      if (!$expired) {
        // The new slice button and manage project buttons specific to each project
        if ($user->isAllowed(SA_ACTION::CREATE_SLICE, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
          $create_slice_button = "<a class='button' href='createslice.php?project_id=$project_id'>";
          $create_slice_button .= "<i class='material-icons'>add</i> New slice</a><br class='mobilebreak'>";
        } else {
          $create_slice_button = "";
        }
        $manage_project_button = "<a class='button' href='project.php?project_id=$project_id'>Manage project</a>";
        $slice_card_project_buttons .= "<div style='display:none;' class='projectinfo' id='{$project_name}info'>";
        $slice_card_project_buttons .= "$create_slice_button $manage_project_button</div>";
        $slice_filters .= "<li data-value='{$project_name}'>$project_name</li>";
      }
      
      $handle_req_str = get_pending_requests($project_id, $project_request_map);
      $project_boxes .= make_project_box($project_id, $project_name, $user_id, $lead_id, $lead_name, 
                                         $purpose, $expiration, $expired, $handle_req_str);
    }

    $slice_filters .= "</ul></li></ul><br class='mobilebreak'>";

    // Make slice section
    $slice_card = "<div class='card' id='slices'>";
    $slice_card .= "<h3 class='dashtext'>Slices</h3><br>";
    // Slice filters
    $slice_card .= "<div id='projectcontrols'>";
    $slice_card .= "<h6 class='dashtext'>Filter by:</h6>";
    $slice_card .= $slice_filters; 
    
    // Slice sorts
    $slice_sorts = "<ul class='selectorcontainer'>";
    $slice_sorts .= "<li class='has-sub selector' style='float:none;' id='slicesortby'>";
    $slice_sorts .= "<span class='selectorshown'>Sorts</span><ul class='submenu'>";
    $slice_sorts .= "<li data-value='slicename'>Slice name</li>";
    $slice_sorts .= "<li data-value='sliceexp'>Slice expiration</li>";
    $slice_sorts .= "<li data-value='resourceexp'>Resource expiration</li>";
    $slice_sorts .= "<li data-value='projname'>Project name</li>";
    $slice_sorts .= "</ul></li></ul><br class='mobilebreak'>";
    $slice_sorts .= "<input type='checkbox' id='sliceascendingcheck' data-value='ascending' checked>";
    $slice_sorts .= "<span style='font-size: 13px;'>Sort ascending</span><br></div>";

    $slice_card .= "<h6 class='dashtext'>Sort by:</h6>";
    $slice_card .= $slice_sorts;

    $slice_card_project_buttons .= "<div class='projectinfo' id='categoryinfo'>";
    $slice_card_project_buttons .= "<a class='button' href='createslice.php'><i class='material-icons'>add</i> New slice</a></div>";

    $slice_card .= $slice_card_project_buttons;
    $slice_card .= "<div id='slicearea' style='clear:both;'>$slice_boxes</div></div>";
    print $slice_card;

    // Make projects section
    $project_card = "<div class='card' id='projects'>";
    $project_card .= "<h3 class='dashtext'>Projects</h3><br>";

    // Project filters
    $project_filters = "<ul class='selectorcontainer'>";
    $project_filters .= "<li class='has-sub selector' style='float:none;' id='projectfilterswitch'>";
    $project_filters .= "<span class='selectorshown'>Filters</span><ul class='submenu'>";
    $project_filters .= "<li data-value='-ACTIVE-PROJECTS-'>Active projects</li>";
    $project_filters .= "<li data-value='-MY-PROJECTS-'>Projects I lead</li>";
    $project_filters .= "<li data-value='-HAS-SLICES-'>Has slices</li>";
    $project_filters .= "<li data-value='-EXPIRED-PROJECTS-'>Expired projects</li>";
    $project_filters .= "</ul></li></ul><br class='mobilebreak'>";

    $project_card .= "<h6 class='dashtext'>Filter by:</h6>";
    $project_card .= "$project_filters";

    // Project sorts
    $project_sorts = "<ul class='selectorcontainer'>";
    $project_sorts .= "<li class='has-sub selector' style='float:none;' id='projectsortby'>";
    $project_sorts .= "<span data-value='projname' class='selectorshown'>Sorts</span><ul class='submenu'>";
    $project_sorts .= "<li data-value='projname'>Project name</li>";
    $project_sorts .= "<li data-value='projexp'>Project expiration</li>";
    $project_sorts .= "<li data-value='slicecount'>Slice count</li>";
    $project_sorts .= "</ul></li></ul><br class='mobilebreak'>";
    $project_sorts .= "<input type='checkbox' id='projectascendingcheck' data-value='ascending' checked>";
    $project_sorts .= "<span style='font-size: 13px;'>Sort ascending</span><br>";

    $project_card .= "<h6 class='dashtext'>Sort by:</h6>";
    $project_card .= $project_sorts;

    
    $project_card .= "<div style='margin: 15px 0px; clear: both;'>";
    if ($user->isAllowed(PA_ACTION::CREATE_PROJECT, CS_CONTEXT_TYPE::RESOURCE, null)) {
      $project_card .= "<a class='button' href='edit-project.php'><i class='material-icons'>add</i>New Project</a>";
      $project_card .= "<a class='button' href='join-project.php'>Join a Project</a></div>";
    } else {
      $project_card .= "<a class='button' href='join-project.php'>Join a Project</a><br class='mobilebreak'>";
      $project_card .= "<a class='button' href='modify.php?belead=belead'>Ask to be a Project Lead</a></div>";
    }

    $project_card .= "<div id='projectarea'>$project_boxes</div></div>";    
    print $project_card;   
  } else {
    print "<div class='dashsection card' id='slices'>";
    unset($project_id);
    print "<h3 class='dashtext'>Slices</h3><br><br>";
    print "<a href='createslice.php' class='button'><i class='material-icons'>add</i>New slice</a>";
    include("tool-slices.php");
    print "</div>";
    print "<div class='dashsection card' id='projects'>";
    include("tool-projects.php");
    include("tool-expired-projects.php");
    print "</div>";
  }
} 

// Print the tab switching navigation bar 
function make_navigation_tabs() {
  print "<div class='nav2'><ul class='tabs'>";
  print "<li><a class='tab' data-tabindex=1 data-tabname='slices' href='#slices'>Slices</a></li>";
  print "<li><a class='tab' data-tabindex=2 data-tabname='projects' href='#projects'>Projects</a></li>";
  print "<li><a class='tab' data-tabindex=3 data-tabname='logs' href='#logs'>Logs</a></li>";
  print "<li><a class='tab' data-tabindex=4 data-tabname='map' href='#map' id='maptab'>Map</a></li>";
  print "</ul></div>";
}

// Print the message that shows up for $user when they have no projects or slices
function make_no_projects_greeting($user, $new_user) {
  global $in_lockdown_mode;
  $disable_create_project = "";
  $disable_join_project = "";
  $disable_project_lead = "";
  if ($in_lockdown_mode) {
    $disable_create_project = "disabled";
    $disable_join_project = "disabled";
    $disable_project_lead = "disabled";
  }
  if ($user->isAllowed(PA_ACTION::CREATE_PROJECT, CS_CONTEXT_TYPE::RESOURCE, null)) {
    if ($new_user) {
      print "<p class='instruction'>";
      print "Congratulations! Your GENI Portal account is now active.<br/><br/>";
      print "You have been made a 'Project Lead', meaning you can create GENI Projects, "; 
      print "as well as create slices in projects and reserve resources.<br/><br/>";
      print "A project is a group of people and their research, ";
      print "led by a single responsible individual - the project lead.";
      print "See the <a href=\"http://groups.geni.net/geni/wiki/GENIGlossary\">Glossary</a>.</p>";
      print "<p class='warn'>";
      print "You are not a member of any projects. You need to Create or Join a Project.";
      print "</p>";
      print "<button $disable_create_project onClick=\"window.location='edit-project.php'\"><i class='material-icons'>add</i> New Project</button>";
      print "<button $disable_join_project onClick=\"window.location='join-project.php'\">Join a Project</button>";
      print "<button $disable_join_project onClick=\"window.location='ask-for-project.php'\">Ask Someone to Create a Project</button>";
    } else {
      print "<p class='warn'>";
      print "You are not a member of any active projects. You need to create, join, or renew an expired project you lead.";
      print "</p>";
    }
  } else {
    if ($new_user) {
      print "<p class='instruction'>Congratulations! Your GENI Portal account is now active.<br/><br/>";
      print "You can now participate in GENI research, by joining a 'Project'.<br/>";
      print "Note that your account is not a 'Project Lead' account, meaning you must join a project created by someone else, ";
      print "before you can create slices or use GENI resources.<br/><br/>";
      print "A project is a group of people and their research, led by a single responsible individual - the project lead.";
      print " See the <a href='http://groups.geni.net/geni/wiki/GENIGlossary'>Glossary</a>.</p>"; 
      print "<p class='warn'>";
      print "You are not a member of any projects. Please join an
         existing Project, ask someone to create a Project for you, or ask
         to be a Project Lead.</p>";
      print "<button $disable_join_project onClick=\"window.location='join-project.php'\">Join a Project</button>";
      print "<button $disable_join_project onClick=\"window.location='ask-for-project.php'\">Ask Someone to Create a Project</button>";
      print "<button $disable_project_lead onClick=\"window.location='modify.php?belead=belead'\">Ask to be a Project Lead</button>";
    } else {
      print "<p class='warn'>";
      print "You are not a member of any active projects. Please join an
         existing Project, ask someone to create a Project for you, ask
         to be a Project Lead, or ask someone to renew an expired project.</p>";
    }
  }
}

// Return the maximum amount of time you can renew a slice in a project with
// expiration date $project_expiration for
function get_max_renewal_days($project_expiration) {
  global $portal_max_slice_renewal_days;
  $dashboard_max_renewal_days = 7;
  $renewal_days = min($dashboard_max_renewal_days, $portal_max_slice_renewal_days);
  if ($project_expiration) {
    $project_expiration_dt = new DateTime($project_expiration);
    $now_dt = new DateTime();
    $difference = $project_expiration_dt->diff($now_dt);
    $renewal_days = $difference->days;
    $renewal_days = min($renewal_days, $portal_max_slice_renewal_days, $dashboard_max_renewal_days);
  }
  return $renewal_days;
}

// Return the expiration date of the next expiring sliver in $slivers.
// If $slivers is empty, return "", representing there being no next expiration
function get_next_resource_expiration($slivers) {
  if (count($slivers) == 0) {
    $next_exp = "";
  } else {
    $first_sliver = reset($slivers);
    $next_exp = new DateTime($first_sliver[SA_SLIVER_INFO_TABLE_FIELDNAME::SLIVER_INFO_EXPIRATION]);
    foreach ($slivers as $sliver) {
      $this_date = new DateTime($sliver[SA_SLIVER_INFO_TABLE_FIELDNAME::SLIVER_INFO_EXPIRATION]);
      if ($next_exp > $this_date) {
        $next_exp = $this_date;
      }
    }
  }
  return $next_exp == "" ? "" : dateUIFormat($next_exp);
}

// Return a string representing the pending requests for project given by $project_id 
// and with a link to handle them
function get_pending_requests($project_id, $project_request_map) {
  global $user;
  $handle_req_str = "";
  $handle_req_url = "handle-project-request.php?";
  if ($user->isAllowed(PA_ACTION::ADD_PROJECT_MEMBER, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
    if (array_key_exists($project_id , $project_request_map )) {
      $reqcnt = count($project_request_map[$project_id]);
    } else {
      $reqcnt = 0;
    }
    if ($reqcnt == 0) {
      $handle_req_str = "";
    } elseif ($reqcnt == 1) {
      $rid = $project_request_map[$project_id][0][RQ_REQUEST_TABLE_FIELDNAME::ID];
      $handle_req_str = "(<a href='{$handle_req_url}request_id=$rid'><b>$reqcnt</b> join request</a>) ";
    } else {
      $handle_req_str = "(<a href='{$handle_req_url}project_id=$project_id'><b>$reqcnt</b> join requests</a>) ";
    }
  }
  return $handle_req_str;
}

// Return the slice box (card) for the slice with these attributes
function make_slice_box($slice_name, $slice_id, $owner_id, $owner_name, $project_name, $project_id,
                        $resource_count, $slice_exp_date, $resource_exp_date, $max_renewal_days) {
  global $user;

  $has_resources = $resource_count > 0 ? "-has-resources-" : "-no-resources-";
  $whose_slice = $owner_id == $user->account_id ? "-MY- -ALL-" : "-THEIR- -ALL-";
  $slice_exp_hours = get_time_diff($slice_exp_date);
  // if slice has no slivers, next resource expiration is (effectively) never
  $resource_exp_hours = $resource_exp_date == "" ? 10000000 : get_time_diff($resource_exp_date);


  // Give div specific classes for filtering purposes, data-* attributes for sorting purposes
  $box = "<div class='floatleft slicebox $whose_slice {$project_name}slices $has_resources' 
          data-resourcecount='$resource_count' data-slicename='$slice_name' 
          data-sliceexp='$slice_exp_hours' data-resourceexp='$resource_exp_hours'
          data-projname='$project_name'>";
  $box .= "<table>";
  
  $slice_url = "slice.php?slice_id=" . $slice_id;
  $box .= "<tr><td class='slicetopbar' title='Manage slice $slice_name' style='text-align:left;'>";
  $box .= "<a class='slicename' href='$slice_url'>$slice_name</a></td>";


  $box .= "<td class='slicetopbar sliceactions' style='text-align:right;'>";
  $box .= make_slice_actions_dropdown($slice_id, $max_renewal_days, $slice_exp_hours, $resource_exp_hours, $resource_count);
  $box .= "</td></tr>";
  $project_url = "project.php?project_id=" . $project_id;
  $box .= "<tr><td colspan='2'>";
  $box .= "<span class='leadname'><b>Project:</b> <a href='$project_url'>$project_name</a></span>";
  $box .= "</td></tr>";
  $box .= "<tr><td colspan='2'>";
  $box .= "<span class='leadname'><b>Owner:</b> $owner_name</span>";
  $box .= "</td></tr>";

  $resource_exp_icon = "";
  if ($resource_count > 0) {
    $plural = $resource_count == 1 ? "" : "s";
    $resource_exp_string = get_time_diff_string($resource_exp_hours);
    $resource_exp_color = get_urgency_color($resource_exp_hours);
    $resource_exp_icon = get_urgency_icon($resource_exp_hours);
    $resource_info = "<b>$resource_count</b> resource{$plural}, next exp. in ";
    $resource_info .= "<b title='$resource_exp_date'>$resource_exp_string</b>";
    $resource_exp_icon = "<i class='material-icons' style='color: $resource_exp_color;'>$resource_exp_icon</i>";
  } else {
    $resource_info = "<i>No resources for this slice</i>";
    $resource_exp_icon = "<i></i>";
  }

  $slice_exp_string = get_time_diff_string($slice_exp_hours);
  $slice_exp_color = get_urgency_color($slice_exp_hours);
  $slice_exp_icon = get_urgency_icon($slice_exp_hours);

  $slice_info = "Slice expires in <b title='$slice_exp_date'>$slice_exp_string</b>";
  $box .= "<tr style='height:40px;'><td style='padding: 0px 10px;'>$slice_info</td>";
  $box .= "<td style='vertical-align: middle; width:30px;'>";
  $box .= "<i class='material-icons' style='color:$slice_exp_color;'>$slice_exp_icon</i></td></tr>";
  $box .= "<tr style='height:40px;'><td style='border-bottom:none; padding: 0px 10px;'>$resource_info</td>";
  $box .= "<td style='vertical-align: middle; border-bottom:none'>$resource_exp_icon</td></tr>";
  $box .= "</table></div>";

  return $box;
}

// Return the dropdown (what you get when you hover over ...) for the slice with id $slice_id
function make_slice_actions_dropdown($slice_id, $max_renewal_days, $slice_exp_hours, 
                                     $resource_exp_hours, $resource_count) {
  global $user;
  $add_resource_url = "slice-add-resources-jacks.php?slice_id=" . $slice_id;
  $delete_resource_url = "confirm-sliverdelete.php?slice_id=" . $slice_id;
  $listres_url = "listresources.php?slice_id=" . $slice_id;
  $slice_url = "slice.php?slice_id=" . $slice_id;

  $dropdown = "<ul><li class='has-sub' style='color: #ffffff;'>";
  $dropdown .= "<i class='material-icons' style='font-size: 22px;'>more_horiz</i><ul class='submenu'>";
  $dropdown .= "<li><a href='$slice_url'>Manage slice</a></li>";
  if ($user->isAllowed(SA_ACTION::ADD_SLIVERS, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
    $dropdown .= "<li><a href='$add_resource_url'>Add resources</a></li>";
  }

  $renewal_hours = 24 * $max_renewal_days;
  $disable_renewal = "";
  if ($slice_exp_hours > $renewal_hours && $resource_exp_hours > $renewal_hours) {
    $disable_renewal = "class='disabledaction'";
  }

  if ($resource_count > 0) {
    $dropdown .= "<li><a $disable_renewal onclick='renew_slice(\"$slice_id\", $max_renewal_days, $resource_count,
                                                               $slice_exp_hours, $resource_exp_hours);'>";
    $dropdown .= "Renew resources ($max_renewal_days days)</a></li>";
    if ($user->isAllowed(SA_ACTION::GET_SLICE_CREDENTIAL, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
      $dropdown .= "<li><a onclick='info_set_location(\"$slice_id\", \"$listres_url\")'>Resource details</a></li>";
    }
    if ($user->isAllowed(SA_ACTION::DELETE_SLIVERS, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
      $dropdown .= "<li><a onclick='info_set_location(\"$slice_id\", \"$delete_resource_url\")'>Delete resources</a></li>";
    }
  }
  $dropdown .= "</ul></li></ul>";

  return $dropdown;
}

// Return the project box (card) for the project with these attributes
function make_project_box($project_id, $project_name, $user_id, $lead_id, $lead_name, $purpose,
                          $expiration, $expired, $handle_req_str) {
  global $project_slice_counts, $user;
  if (array_key_exists($project_id, $project_slice_counts)) {
    $slice_count = $project_slice_counts[$project_id];
  } else {
    $slice_count = 0;
  }
  $has_slices = $slice_count == 0 ? '' : "-HAS-SLICES-";
  $expired_project_class = $expired ? "-EXPIRED-PROJECTS-" : "-ACTIVE-PROJECTS-";
  $project_lead_class = $lead_id == $user_id ? "-MY-PROJECTS-" : "-THEIR-PROJECTS-";

  if ($expiration) {
    $exp_diff = get_time_diff($expiration);
  } else {
    $exp_diff = 100000000; // a very far away time indicating this will never expire
  }

  $box = '';
  // Give div specific classes for filtering purposes, data-* attributes for sorting purposes
  $box .= "<div class='floatleft slicebox $expired_project_class $project_lead_class $has_slices'
                data-projname='$project_name' data-projexp='$exp_diff' data-slicecount='$slice_count'>";
  $box .= "<table><tr class='slicetopbar'>";
  $box .= "<td class='slicetopbar' style='text-align: left;'>";
  $box .= "<a href='project.php?project_id=$project_id' class='projectname'>$project_name</a></td>";

  $box .= "<td class='slicetopbar sliceactions' style='text-align: right;'>";
  $box .= make_project_actions_dropdown($project_id, $expired);
  print "</td></tr>";

  if ($handle_req_str) {
    $box .= "<tr><td colspan='2'>";
    $box .= "<span class='smallleadname'><b>Lead:</b> $lead_name </span>";
    $box .= "<span class='requeststring'>$handle_req_str</span>";
    $box .= "</td></tr>";
  } else {
    $box .= "<tr><td colspan='2'>";
    $box .= "<span class='leadname'><b>Lead:</b> $lead_name </span>";
    $box .= "</td></tr>";
  }

  if ($slice_count == 0) {
    $box .= "<tr><td colspan='2'>";
    $box .= "<i> No slices</i>";
  } else {
    $slice_word = $slice_count > 1 ? "slices" : "slice";
    $box .= "<tr><td colspan='2'>Has <a onclick='show_slices_for_project(\"$project_name\");'>";
    $box .= "<b>$slice_count</b> $slice_word</a>";
  }                        
  $box .= "</td></tr>";
  
  if ($expiration) {
    if (!$expired) {
      $expiration_string = get_time_diff_string($exp_diff);
      $expiration_color = get_urgency_color($exp_diff);
      $expiration_string = "Project expires in <b title='$expiration'>$expiration_string</b>";
      $expiration_icon = get_urgency_icon($exp_diff);
      $expiration_icon = "<i class='material-icons' style='color: $expiration_color'>$expiration_icon</i>";
    } else {
      $expiration_string = "<b>Project is expired</b>";
      $expiration_icon = "<i class='material-icons' style='color: #EE583A;'>report</i>";
    }
  } else {
    $expiration_string = "<i>No expiration</i>";
    $expiration_icon = "";
  }
  $box .= "<tr style='height: 40px;'><td style='border-bottom:none;'>$expiration_string</td>";
  $box .= "<td style='vertical-align: middle; border-bottom:none;'>$expiration_icon</td></tr>";
  $box .= "</table></div>";
  return $box;
}

// Make the dropdown (what you get when you hover over ...) for project with id $project_id
function make_project_actions_dropdown($project_id, $expired) {
  global $user;
  $dropdown = "<ul><li class='has-sub'>";
  $dropdown .= "<i class='material-icons' style='font-size: 22px;'>more_horiz</i><ul class='submenu'>";
  
  $dropdown .= "<li><a href='project.php?project_id=$project_id'>Manage project</a></li>";
  if ($user->isAllowed(SA_ACTION::CREATE_SLICE, CS_CONTEXT_TYPE::PROJECT, $project_id) && !$expired) {
    $dropdown .= "<li><a href='createslice.php?project_id=$project_id'>New slice</a></li>";
  }
  if ($user->isAllowed(PA_ACTION::UPDATE_PROJECT, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
    $dropdown .= "<li><a href='edit-project.php?project_id=$project_id'>Edit project</a></li>";
  }
  if ($user->isAllowed(PA_ACTION::ADD_PROJECT_MEMBER, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
    $dropdown .= "<li><a href='edit-project-member.php?project_id=$project_id'>Edit membership</a></li>";
  }
  $dropdown .= "</ul></li></ul>";
  return $dropdown;
}

?>

<div class='card' id='logs'>
  <h3 class="dashtext">Logs</h3><br>
  <div style='text-align: left;'>
    <h6 class='dashtext' style='margin-top: 20px;'>Showing logs for the last</h6>
    <ul class="selectorcontainer"> 
      <li class='has-sub selector' id='loglengthselector' style='float:none;'>
        <span class='selectorshown'>day</span>
        <ul class='submenu' id='loglength'>
          <li data-value="24">day</li>
          <li data-value="48">2 days</li>
          <li data-value="72">3 days</li>
          <li data-value="168">week</li>
        </ul>
      </li>
    </ul>
  </div>

  <div class="tablecontainer">
    <table id="logtable"></table>
  </div>
</div>

<div class='card' id='map'>
  <table style="max-width: 1000px;">
    <th>Current GENI Resources</th>
    <tr><td style="padding: 0px;">
      <?php include("map.html"); ?>
    </td></tr>
  </table>
</div>

<?php include("footer.php"); ?>
