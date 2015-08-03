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


$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
show_header('GENI Portal: Dashboard', $TAB_HOME, true, true);
include("tool-showmessage.php");

?>

<script src='dashboard.js'></script>
<?php include "cards.js"; ?>

<script>
function info_set_location(slice_id, url, stop_if_none)
{
  $.getJSON("aggregates.php", { slice_id: slice_id },
    function (responseTxt, statusTxt, xhr) {
        var json_agg = responseTxt;
        var agg_ids = Object.keys(json_agg);
        var agg_count = agg_ids.length;
        for (var i = 0; i < agg_count; i++) {
            url += "&am_id[]=" + agg_ids[i];
        }
        window.location = url;
    })
    .fail(function() {
        alert("Unable to locate sliver information for this slice.");
    });
}
</script>
<?php
  $retVal  = get_project_slice_member_info( $sa_url, $ma_url, $user, True);
  $project_objects = $retVal[0];
  $slice_objects = $retVal[1];
  $member_objects = $retVal[2];
  $project_slice_map = $retVal[3];
  $project_activeslice_map = $retVal[4];

if(array_key_exists("showmap", $_REQUEST)) {
  echo "<h2 class='dashtext'>Current GENI Resources</h2><br>";
  echo "<table id='resourcemap'><tr><td>";
  include("map.html");
  echo "</td></tr></table>";  
}

if (! $user->portalIsAuthorized()) {
  $km_url = get_first_service_of_type(SR_SERVICE_TYPE::KEY_MANAGER);
  $params['redirect'] = selfURL();
  $query = http_build_query($params);
  $km_url = $km_url . "?" . $query;
  print "<h2>Portal authorization</h2>\n";
  print "<p>";
  print "The GENI Portal is not authorized by you as a client tool. If you would like";
  print " the GENI Portal to help you manage your projects and slices, you can";
  print " <a href=\"$km_url\">authorize the portal</a> to do so.";
  print "</p>";
  return 0;
}

?>

<?php

  $expired_projects = array();
  $unexpired_projects = array();
  $num_projects = count($project_objects);
  foreach($project_objects as $project) {
    $project_id = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
    $expired = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRED];
    if(convert_boolean($expired)) 
      $expired_projects[$project_id] = $project;
    else
      $unexpired_projects[$project_id] = $project;
  }

  $project_objects = $unexpired_projects;

  $disable_create_project = "";
  $disable_join_project = "";
  $disable_project_lead = "";
  if ($in_lockdown_mode) {
    $disable_create_project = "disabled";
    $disable_join_project = "disabled";
    $disable_project_lead = "disabled";
  }

  if (count($project_objects) == 0) {
    make_new_user_greeting($user);
  } else { 
    // you have some projects or slices
    make_navigation_tabs();

    // Start making the slice and project sections
    $lead_names = lookup_member_names_for_rows($ma_url, $user, $project_objects, PA_PROJECT_TABLE_FIELDNAME::LEAD_ID);
    $slice_filters = "<ul class='selectorcontainer'><li class='has-sub selector' style='float:none;' id='slicefilterswitch'>";
    $slice_filters .= "<span class='selectorshown'>Filters</span><ul class='submenu'>";
    $slice_filters .= "<li data-value='-ALL-' class='selectorlabel'>Categories</li>";
    $slice_filters .= "<li data-value='-ALL-'>All slices</li>";
    $slice_filters .= "<li data-value='-MY-'>Slices I lead</li>";
    $slice_filters .= "<li data-value='-has-resources-'>Has resources</li>";
    $slice_filters .= "<li data-value='-ALL-' class='selectorlabel'>Projects</li>";
    $slice_project_info = "";
    $show_info = "";
    $project_boxes = "";
    $user_id = $user->account_id;

    // Make and fill dictionary of project join requests
    $project_request_map = array();
    if (count($project_objects) > 0) {
      $reqlist = get_pending_requests_for_user($sa_url, $user, $user->account_id, 
                  CS_CONTEXT_TYPE::PROJECT);
      foreach ($reqlist as $req) {
         if ($req[RQ_REQUEST_TABLE_FIELDNAME::STATUS] != RQ_REQUEST_STATUS::PENDING){
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

    // Get the user's unexpired slices
    $unexpired_slices = array();
    foreach($slice_objects as $slice) {
      $slice_id = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
      $expired = $slice[SA_SLICE_TABLE_FIELDNAME::EXPIRED];
      if(!convert_boolean($expired)) {
        $unexpired_slices[$slice_id] = $slice;
      }
    }
    $slice_objects = $unexpired_slices;

    $slice_owner_names = array();
    if (count($slice_objects) > 0) {
      $slice_owner_names = lookup_member_names_for_rows($ma_url, $user, $slice_objects, SA_SLICE_TABLE_FIELDNAME::OWNER_ID);
    }

    $user_id = $user->account_id;
    $project_slice_counts = array();
    $slice_boxes = '';
    foreach ($slice_objects as $slice) {
      $slice_id = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
      $slice_urn = $slice[SA_ARGUMENT::SLICE_URN];
      $slice_name = $slice[SA_ARGUMENT::SLICE_NAME];
      $slice_owner_id = $slice[SA_ARGUMENT::OWNER_ID];
      if ($slice_owner_id == $user_id) {
        $whose_slice = "-MY- -ALL-";
      } else {
        $whose_slice = "-THEIR- -ALL-";
      }
      $slice_exp_date = $slice[SA_ARGUMENT::EXPIRATION];
      $args['slice_id'] = $slice_id;
      $query = http_build_query($args);
      $add_resource_url = "slice-add-resources-jacks.php?" . $query;
      $delete_resource_url = "confirm-sliverdelete.php?" . $query;
      $listres_url = "listresources.php?" . $query;
      $slice_url = "slice.php?" . $query;
      $slice_project_id = $slice[SA_SLICE_TABLE_FIELDNAME::PROJECT_ID];
      if (array_key_exists($slice_project_id, $project_slice_counts)) {
        $project_slice_counts[$slice_project_id]++;
      } else {
        $project_slice_counts[$slice_project_id] = 1;
      }
      if (!array_key_exists($slice_project_id, $project_objects)) {
        $slice_project_name = "-Expired Project-";
        $project = array();
      } else {
        $project = $project_objects[ $slice_project_id ];
        $slice_project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
      }

      $slivers = lookup_sliver_info_by_slice($sa_url, $user, $slice_urn);
      $slice_exp = get_time_diff($slice_exp_date);
      // determine maximum date of slice renewal
      $renewal_days = min($portal_max_slice_renewal_days, 7);
      $project_expiration = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRATION];
      if ($project_expiration) {
        $project_expiration_dt = new DateTime($project_expiration);
        $now_dt = new DateTime();
        $difference = $project_expiration_dt->diff($now_dt);
        $renewal_days = $difference->days;
        // take the minimum of the two as the constraint
        $renewal_days = min($renewal_days, $portal_max_slice_renewal_days, 7);
      }

      if (count($slivers) == 0) {
        $resource_exp = 1000000000;
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
        if ($next_exp != "") {
          $resource_exp = get_time_diff(dateUIFormat($next_exp)); 
        } else {
          $resource_exp = "";
        }
      }
      $slice_boxes .= make_slice_box($slice_name, $slice_id, $whose_slice, $slice_url, $slice_owner_names[$slice_owner_id], $slice_project_name,
                                     count($slivers), $slice_exp, $resource_exp, $add_resource_url, $delete_resource_url, $listres_url, $renewal_days, 
                                     dateUIFormat($slice_exp_date), dateUIFormat($next_exp));
    }

    // populate slice filters with project names, make project boxes
    foreach ($project_objects as $project) {
      $project_id = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
      $project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
      $lead_id = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
      $purpose = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
      $expiration = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRATION];
      $lead_name = $lead_names[$lead_id];
      if ($user->isAllowed(SA_ACTION::CREATE_SLICE, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
        $create_slice_button = "<a class='button' href='createslice.php?project_id=$project_id'><i class='material-icons'>add</i> New slice</a><br class='mobilebreak'>";
      } else {
        $create_slice_button = "";
      }
      $manage_project_button = "<a class='button' href='project.php?project_id=$project_id'>Manage project</a>";
      $slice_filters .= "<li data-value='{$project_name}'>$project_name</li>";
      $slice_project_info .= "<div $show_info class='projectinfo' id='{$project_name}info'>";
      $slice_project_info .= "$create_slice_button $manage_project_button</div>";
      $show_info = "style='display:none;'";
      $expired = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRED];
      $expired_project_class = $expired ? "-EXPIRED-PROJECTS-" : "-ACTIVE-PROJECTS-";
      $project_lead_class = $lead_id == $user_id ? "-MY-PROJECTS-" : "-THEIR-PROJECTS-";
      $categories = "$expired_project_class $project_lead_class";
      $handle_req_str = get_pending_requests($project_id, $project_request_map);
      $project_boxes .= make_project_box($project_id, $project_name, $lead_id, $lead_name, $purpose, $expiration, $expired, $categories, $handle_req_str);
    }
    foreach ($expired_projects as $project) {
      $project_id = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
      $project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
      $lead_id = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
      $purpose = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
      $expiration = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRATION];
      $lead_name = $lead_names[$lead_id];
      if ($user->isAllowed(SA_ACTION::CREATE_SLICE, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
        $create_slice_button = "<a class='button' href='createslice.php?project_id=$project_id'><i class='material-icons'>add</i> New slice</a><br class='mobilebreak'>";
      } else {
        $create_slice_button = "";
      }
      $expired = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRED];
      $expired_project_class = $expired ? "-EXPIRED-PROJECTS-" : "-ACTIVE-PROJECTS-";
      $project_lead_class = $lead_id == $user_id ? "-MY-PROJECTS-" : "-THEIR-PROJECTS-";
      $categories = "$expired_project_class $project_lead_class";
      $handle_req_str = get_pending_requests($project_id, $project_request_map);
      $project_boxes .= make_project_box($project_id, $project_name, $lead_id, $lead_name, $purpose, $expiration, $expired, $categories, $handle_req_str);
    }
    $slice_filters .= "</ul></li></ul>";

    // Make slice section
    $slice_card = "<div class='dashsection card' data-cardname='slices' id='slices'>";
    $slice_card .= "<h3 class='dashtext'>Slices</h3><br>";
    // Slice filters
    $slice_card .= "<div id='projectcontrols'><h6 class='dashtext'>Filter by:</h6>$slice_filters<br class='mobilebreak'>"; 
    $slice_card .= "<h6 class='dashtext'>Sort by:</h6>";
    $slice_card .= "<ul class='selectorcontainer'><li class='has-sub selector' style='float:none;' id='slicesortby'>";
    // Slice sorts
    $slice_card .= "<span class='selectorshown'>Sorts</span><ul class='submenu'>";
    $slice_card .= "<li data-value='slicename'>Slice name</li><li data-value='sliceexp'>Slice expiration</li>";
    $slice_card .= "<li data-value='resourceexp'>Resource expiration</li></ul></li></ul><br class='mobilebreak'>";
    $slice_card .= "<input type='checkbox' id='sliceascendingcheck' data-value='ascending' checked><span style='font-size: 13px;'>Sort ascending</span><br></div>";
    $slice_project_info .= "<div $show_info class='projectinfo' id='categoryinfo'>";
    $slice_project_info .= "<a class='button' href='createslice.php'><i class='material-icons'>add</i> New slice</a></div>";

    $slice_card .= $slice_project_info;

    $slice_card .= "<div id='slicearea' style='clear:both;'>$slice_boxes</div></div>";

    // Make projects section
    $project_card = "<div class='dashsection card' data-cardname='projects' id='projects'>";
    $project_card .= "<h3 class='dashtext'>Projects</h3><br>";
    // Project filters
    $project_card .= "<h6 class='dashtext'>Filter by:</h6><ul class='selectorcontainer'>";
    $project_card .= "<li class='has-sub selector' style='float:none;' id='projectfilterswitch'>";
    $project_card .= "<span class='selectorshown'>Filters</span><ul class='submenu'>";
    $project_card .= "<li data-value='-ACTIVE-PROJECTS-'>Active projects</li>";
    $project_card .= "<li data-value='-MY-PROJECTS-'>Projects I lead</li>";
    $project_card .= "<li data-value='-HAS-SLICES-'>Has slices</li>";
    $project_card .= "<li data-value='-EXPIRED-PROJECTS-'>Expired projects</li>";
    $project_card .= "</ul></li></ul><br class='mobilebreak'>";
    // Project sorts
    $project_card .= "<h6 class='dashtext'>Sort by:</h6>";
    $project_card .= "<ul class='selectorcontainer'><li class='has-sub selector' style='float:none;' id='projectsortby'>";
    $project_card .= "<span data-value='projname' class='selectorshown'>Sorts</span><ul class='submenu'>";
    $project_card .= "<li data-value='projname'>Project name</li>";
    $project_card .= "<li data-value='projexp'>Project expiration</li>";
    $project_card .= "<li data-value='slicecount'>Slice count</li>";
    $project_card .= "</ul></li></ul><br class='mobilebreak'>";
    $project_card .= "<input type='checkbox' id='projectascendingcheck' data-value='ascending' checked><span style='font-size: 13px;'>Sort ascending</span><br>";
    
    $project_card .= "<div style='margin: 15px 0px; clear: both;'>";
    if ($user->isAllowed(PA_ACTION::CREATE_PROJECT, CS_CONTEXT_TYPE::RESOURCE, null)) {
      $project_card .= "<a class='button' href='edit-project.php'><i class='material-icons'>add</i>New Project</a>";
      $project_card .= "<a class='button' href='join-project.php'>Join a Project</a></div>";
    } else {
      // TODO: are all of these always worthwhile?
      $project_card .= "<a class='button' href='join-project.php'>Join a Project</a><br class='mobilebreak'>";
      $project_card .= "<a class='button' href='ask-for-project.php'><b>Ask Someone to Create a Project</b></a><br class='mobilebreak'>";
      $project_card .= "<a class='button' href='modify.php?belead=belead'><b>Ask to be a Project Lead</b></a></div>";
    }

    $project_card .= "<div id='projectarea'>$project_boxes</div></div>";

    // Finally, print the slice and project cards to the dashboard
    print $slice_card;
    print $project_card;
  }

  // Print the tab switching navigation bar 
  function make_navigation_tabs() {
    print "<div class='nav2'><ul class='tabs'>";
    print "<li><a class='tab' data-tabindex=1 data-tabname='slices' href='#slices'>Slices</a></li>";
    print "<li><a class='tab' data-tabindex=2 data-tabname='projects' href='#projects'>Projects</a></li>";
    print "<li><a class='tab' data-tabindex=3 data-tabname='logs' href='#logs'>Logs</a></li>";
    print "<li><a class='tab' data-tabindex=4 data-tabname='map' href='#map'>Map</a></li>";
    print "</ul></div>";
  }

  // Print the message that shows up for $user when they have no projects or slices
  function make_new_user_greeting($user) {
    if ($user->isAllowed(PA_ACTION::CREATE_PROJECT, CS_CONTEXT_TYPE::RESOURCE, null)) {
      // You're not in any projects at all, even expired
      print "<p class='instruction'>";
      print "Congratulations! Your GENI Portal account is now active.<br/><br/>";
      print "You have been made a 'Project Lead', meaning you can create GENI Projects, as well as create slices in projects and reserve resources.<br/><br/>";
      print "A project is a group of people and their research, led by a single responsible individual - the project lead.";
      print "See the <a href=\"http://groups.geni.net/geni/wiki/GENIGlossary\">Glossary</a>.</p>\n";
      print "<p class='warn'>";
      print "You are not a member of any projects. You need to Create or Join a Project.";
      print "</p>";
      print "<button $disable_create_project onClick=\"window.location='edit-project.php'\"><i class='material-icons'>add</i> New Project</button>\n";
      print "<button $disable_join_project onClick=\"window.location='join-project.php'\">Join a Project</button>\n";
      print "<button $disable_join_project onClick=\"window.location='ask-for-project.php'\">Ask Someone to Create a Project</button>\n";
    } else {
      print "<p class='instruction'>Congratulations! Your GENI Portal account is now active.<br/><br/>";
      print "You can now participate in GENI research, by joining a 'Project'.<br/>";
      print "Note that your account is not a 'Project Lead' account, meaning you must join a project created by someone else, ";
      print "before you can create slices or use GENI resources.<br/><br/>";
      print "A project is a group of people and their research, led by a single responsible individual - the project lead.";
      print " See the <a href='http://groups.geni.net/geni/wiki/GENIGlossary'>Glossary</a>.</p>\n";
      print "<p class='warn'>";
      print "You are not a member of any projects. Please join an
         existing Project, ask someone to create a Project for you, or ask
         to be a Project Lead.</p>";
      print "<button $disable_join_project onClick=\"window.location='join-project.php'\">Join a Project</button>\n";
      print "<button $disable_join_project onClick=\"window.location='ask-for-project.php'\">Ask Someone to Create a Project</button>\n";
      print "<button $disable_project_lead onClick=\"window.location='modify.php?belead=belead'\">Ask to be a Project Lead</button>\n";
    }
  }

  function make_slice_box($slice_name, $slice_id, $whose_slice, $slice_url, $lead_name, $project_name, $resource_count, 
                          $slice_exp, $resource_exp, $add_url, $remove_url, $listres_url, $renewal_days, $slice_exp_date, $next_exp) {
    global $user;
    $has_resources = $resource_count > 0 ? "-has-resources-" : "-no-resources-";
    $box = "<div class='floatleft slicebox $whose_slice {$project_name}slices $has_resources' 
                data-resourcecount='$resource_count' data-slicename='$slice_name' 
                data-sliceexp='$slice_exp' data-resourceexp='$resource_exp'>";
    $box .= "<table>";
    $resource_exp_icon = "";
    if ($resource_count > 0){
      $plural = $resource_count == 1 ? "" : "s";
      $resource_exp_string = get_time_diff_string($resource_exp);
      $resource_exp_color = get_urgency_color($resource_exp);
      $resource_exp_icon = get_urgency_icon($resource_exp);
      $resource_info = "<b>$resource_count</b> resource{$plural}, next exp. in <b title='$next_exp'>$resource_exp_string</b>";
      $resource_exp_icon = "<i class='material-icons' style='color: $resource_exp_color;'>$resource_exp_icon</i>";
    } else {
      $resource_info = "<i>No resources for this slice</i>";
      $resource_exp_icon = "<i></i>";
    }
    $slice_exp_string = get_time_diff_string($slice_exp);
    $slice_exp_color = get_urgency_color($slice_exp);
    $slice_exp_icon = get_urgency_icon($slice_exp);
    $slice_info = "Slice expires in <b title='$slice_exp_date'>$slice_exp_string</b>";
    $box .= "<tr><td class='slicetopbar' title='Manage slice $slice_name' style='text-align:left;'>";
    $box .= "<a class='slicename' href='$slice_url'>$slice_name</a></td>";
    $box .= "<td class='slicetopbar sliceactions' style='text-align:right;'><ul><li class='has-sub' style='color: #ffffff;'>";
    $box .= "<i class='material-icons' style='font-size: 22px;'>more_horiz</i><ul class='submenu'>";
    $box .= "<li><a href='$slice_url'>Manage slice</a></li>";
    if ($user->isAllowed(SA_ACTION::ADD_SLIVERS, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
      $box .= "<li><a href='$add_url'>Add resources</a></li>";
    }
    if ($resource_count > 0) {
      $box .= "<li><a onclick='renew_slice(\"$slice_id\", $renewal_days, $resource_count, \"$slice_exp\");'>Renew resources ($renewal_days days)</a></li>";
      if ($user->isAllowed(SA_ACTION::GET_SLICE_CREDENTIAL, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
        $box .= "<li><a onclick='info_set_location(\"$slice_id\", \"$listres_url\")'>Resource details</a></li>";
      }
      if ($user->isAllowed(SA_ACTION::DELETE_SLIVERS, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
        $box .= "<li><a onclick='info_set_location(\"$slice_id\", \"$remove_url\")'>Delete resources</a></li>";
      }
    }
    $box .= "</ul></li></ul></td></tr>";
    $box .= "<tr><td colspan='2'><b>Project:</b> $project_name </td></tr>";
    $box .= "<tr><td colspan='2'><b>Owner:</b> $lead_name</td></tr>";
    $box .= "<tr style='height:40px;'><td>$slice_info</td><td style='vertical-align: middle; width:30px;'>";
    $box .= "<i class='material-icons' style='color:$slice_exp_color;'>$slice_exp_icon</i></td></tr>";
    $box .= "<tr style='height:40px;'><td style='border-bottom:none;'>$resource_info</td>";
    $box .= "<td style='vertical-align: middle; border-bottom:none'>";
    $box .= "$resource_exp_icon</td></tr>";
    $box .= "</table></div>";

    return $box;
  }
  
  function make_project_box($project_id, $project_name, $lead_id, $lead_name, $purpose, $expiration, $expired, $categories, $handle_req_str) {
    global $project_slice_counts, $user;
    if (array_key_exists($project_id, $project_slice_counts)) {
      $slice_count = $project_slice_counts[$project_id];
    } else {
      $slice_count = 0;
    }
    $has_slices = $slice_count == 0 ? '' : "-HAS-SLICES-";

    if ($expiration) {
      $exp_diff = get_time_diff($expiration);
    } else {
      $exp_diff = 100000000;
    }

    $box = '';
    $box .= "<div class='floatleft slicebox $categories $has_slices' data-projname='$project_name' data-projexp='$exp_diff' data-slicecount='$slice_count'>";
    $box .= "<table><tr class='slicetopbar'>";
    $box .= "<td class='slicetopbar' style='text-align: left;'>";
    $box .= "<a href='project.php?project_id=$project_id' class='projectname'>$project_name</a></td>";
    $box .= "<td class='slicetopbar sliceactions' style='text-align: right;'>";
    $box .= "<ul><li class='has-sub'><i class='material-icons' style='font-size: 22px;'>more_horiz</i><ul class='submenu'>";
    $box .= "<li><a href='project.php?project_id=$project_id'>Manage project</a></li>";
    if ($user->isAllowed(SA_ACTION::CREATE_SLICE, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
      $box .= "<li><a href='createslice.php?project_id=$project_id'>New slice</a></li>";
    }
    if ($user->isAllowed(PA_ACTION::UPDATE_PROJECT, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
      $box .= "<li><a href='edit-project.php?project_id=$project_id'>Edit project</a></li>";
    }
    if ($user->isAllowed(PA_ACTION::ADD_PROJECT_MEMBER, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
      $box .= "<li><a href='edit-project-member.php?project_id=$project_id'>Edit membership</a></li>";
    }
    $box .= "</ul></li></ul></td></tr>";
    $box .= "<tr><td colspan='2'><b>Lead:</b> $lead_name $handle_req_str</td></tr>";
    $box .= $slice_count == 0 ? "<tr><td colspan='2'><i> No slices</i></td></tr>" : "<tr><td colspan='2'>Has <b>$slice_count</b> slices</td></tr>";
    if ($expiration) {
      if (!$expired) {
        $expiration_string = get_time_diff_string($exp_diff);
        $expiration_color = get_urgency_color($exp_diff);
        $expiration_string = "Project expires in <b title='$expiration'>$expiration_string</b>";
        $expiration_icon = get_urgency_icon($exp_diff);
        $expiration_icon = "<i class='material-icons' style='color: $expiration_color'>$expiration_icon</i>";
      } else {
        $expiration_string = "<b>Project is expired</i>";
        $expiration_icon = "<i class='material-icons' style='color: #EE583A;'>report</i>";
      }
    } else {
      $expiration_string = "<i>No expiration</i>";
      $expiration_icon = "";
    }
    $box .= "<tr><td style='border-bottom:none'>$expiration_string</td>";
    $box .= "<td style='vertical-align: middle; border-bottom:none; height: 35px;'>$expiration_icon</td></tr>";
    $box .= "</table></div>";
    return $box;
  }

  // Convert $num_hours into days if $num_hours is large
  function get_time_diff_string($num_hours) {
    if ($num_hours < 1) {
      return "less than 1 hour";
    } else if ($num_hours == 1 ) {
      return "1 hour";
    } else if ($num_hours < 48) {
      return "$num_hours hours";
    } else {
      $num_days =  $num_hours / 24;
      $num_days = (int) $num_days;
      return "$num_days days";
    }
  }
  
  // Return the difference in hours between now and $exp_date
  function get_time_diff($exp_date) {
    $now = new DateTime('now');
    $exp_datetime = new DateTime($exp_date);
    $interval = date_diff($exp_datetime, $now);
    $num_hours = $interval->days * 24 + $interval->h;
    return $num_hours;
  }

  // Return a red if $num_hours is small, an orange if medium, a green if large 
  function get_urgency_color($num_hours) {
    if ($num_hours < 24) { 
      return "#EE583A";
    } else if ($num_hours < 48) {
      return "#FBC02D";
    } else {
      return "#339933";
    }
  }

  // Return name of icons by same ideas as get_urgency_color
  function get_urgency_icon($num_hours) {
    if ($num_hours < 24) { 
      return "report";
    } else if ($num_hours < 48) {
      return "warning";
    } else {
      return "check_circle";
    }
  }

  function get_pending_requests($project_id, $project_request_map) {
    global $user;
    $handle_req_str = "";
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
        $handle_req_str = "(<a href=\"handle-project-request.php?request_id=$rid\"><b>$reqcnt</b> join request</a>) ";
      } else {
        $handle_req_str = "(<a href=\"handle-project-request.php?project_id=$project_id\"><b>$reqcnt</b> join requests</a>) ";
      }
    }
    return $handle_req_str;
  }

?>

<div class='card' data-cardname='logs' id='logs'>
<h3 class="dashtext">Logs</h3><br>
<div style='text-align: left;'>
<h6 class='dashtext' style='margin-top: 20px;'>Showing logs for the last</h6>
<ul class="selectorcontainer"> 
  <li class='has-sub selector' id='loglengthselector' style='float:none;'><span class='selectorshown'>day</span>
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

<div class='card' data-cardname='map' id='map'>
<table style="max-width: 1000px;"><th>Current GENI Resources</th><tr>
<td style="padding: 0px;">
<?php include("map.html"); ?>
</td></tr></table>
</div>

<?php include("footer.php"); ?>
