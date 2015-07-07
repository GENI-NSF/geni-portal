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
include("services.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
show_header('GENI Portal: Dashboard', $TAB_HOME);
?>

<script src='dashboard.js'></script>

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

  if (count($project_objects) == 0) {
    if ($user->isAllowed(PA_ACTION::CREATE_PROJECT, CS_CONTEXT_TYPE::RESOURCE, null)) {
      if ($num_projects==0) { //you're not in any projects at all, even expired 
        print "<p class='instruction'>";
        print "Congratulations! Your GENI Portal account is now active.<br/><br/>";
        print "You have been made a 'Project Lead', meaning you can create GENI Projects, as well as create slices in projects and reserve resources.<br/><br/>";
        print "A project is a group of people and their research, led by a single responsible individual - the project lead.";
        print "See the <a href=\"http://groups.geni.net/geni/wiki/GENIGlossary\">Glossary</a>.</p>\n";
        print "<p class='warn'>";
        print "You are not a member of any projects. You need to Create or Join a Project.";
        print "</p>";
      } else { // you have some projects that are expired, none active
        print "<p class='instruction'>You have no active projects at this time</p>";
      }
      print "<button $disable_create_project onClick=\"window.location='edit-project.php'\">Create New Project</button>\n";
      print "<button $disable_join_project onClick=\"window.location='join-project.php'\">Join a Project</button>\n";
      print "<button $disable_join_project onClick=\"window.location='ask-for-project.php'\">Ask Someone to Create a Project</button>\n";
    } else {
      if ($num_projects==0) {
        print "<p class='instruction'>Congratulations! Your GENI Portal account is now active.<br/><br/>";
        print "You can now participate in GENI research, by joining a 'Project'.<br/>";
        print "Note that your account is not a 'Project Lead' account, meaning you must join a project created by someone else, ";
        print "before you can create slices or use GENI resources.<br/><br/>";
        print "A project is a group of people and their research, led by a single responsible individual - the project lead.";
        print " See the <a href=\"http://groups.geni.net/geni/wiki/GENIGlossary\">Glossary</a>.</p>\n";
        print "<p class='warn'>";
        print "You are not a member of any projects. Please join an
           existing Project, ask someone to create a Project for you, or ask
           to be a Project Lead.</p>";
      }
      print "<p><button $disable_join_project onClick=\"window.location='join-project.php'\">Join a Project</button>\n";
      print "<button $disable_join_project onClick=\"window.location='ask-for-project.php'\">Ask Someone to Create a Project</button>\n";
      print "<button $disable_project_lead onClick=\"window.location='modify.php?belead=belead'\">Ask to be a Project Lead</button></p>\n";
    }
  } else { //you have some projects or slices 
    print "<h2 class='dashtext' id='sectionswitch'>";
    print "<a id='Slicesbutton' class='dashtext activesection'>Slices</a> | ";
    print "<a class='dashtext' id='Projectsbutton'>Projects</a></h2><br>";

    // Start making the slice and project sections

    $lead_names = lookup_member_names_for_rows($ma_url, $user, $project_objects, PA_PROJECT_TABLE_FIELDNAME::LEAD_ID);
    $project_options = "<ul class='selectorcontainer'><li class='has-sub selector' style='float:none;' id='slicefilterswitch'>";
    $project_options .= "<span class='selectorshown'>Filters</span><ul class='submenu'><li value='-ALL-' class='selectorlabel'>Projects</li>";
    $project_info = "";
    $show_info = "";
    $project_boxes = "";
    $user_id = $user->account_id;
    foreach ($project_objects as $project) {
      if (!$project[PA_PROJECT_TABLE_FIELDNAME::EXPIRED]) {
        $project_id = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
        $project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
        $lead_id = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
        $purpose = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
        $expiration = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRATION];
        $lead_name = $lead_names[$lead_id];
        $create_slice_button = "<a class='button' href='createslice.php?project_id=$project_id'>New slice</a>";
        $manage_project_button = "<a class='button' href='project.php?project_id=$project_id'>Manage project</a>";
        $project_options .= "<li value='{$project_name}'>$project_name</li>";
        $project_info .= "<div $show_info class='projectinfo' id='{$project_name}info'>";
        $project_info .= "Project lead: $lead_name | $create_slice_button $manage_project_button</div>";
        $show_info = "style='display:none;'";
        if ($lead_id == $user_id) {
          $whose_proj = "-MY-PROJECTS- -ALL-PROJECTS-";
        } else {
          $whose_proj = "-THEIR-PROJECTS- -ALL-PROJECTS-";
        }
        $project_boxes .= make_project_box($project_id, $project_name, $lead_id, $lead_name, $purpose, $expiration, $whose_proj);
      }
    }
    $project_options .= "<li class='selectorlabel'>Categories</li>";
    $project_options .= "<li value='-MY-'>Slices I lead</li>";
    $project_options .= "<li value='-THEIR-'>Slices I don't lead</li>";
    $project_options .= "<li value='-ALL-'>All slices</li>";
    $project_options .= "</ul></li></ul>";

    print "<div id='Projectsection' class='dashsection' style='display:none;'>";
    print "<h4 class='dashtext'>Filter by:</h4><ul class='selectorcontainer'><li class='has-sub selector' style='float:none;' id='projectfilterswitch'>";
    print "<span class='selectorshown'>Filters</span><ul class='submenu'>";
    print "<li value='-ALL-PROJECTS-'>All projects</li>";
    print "<li value='-MY-PROJECTS-'>Projects I lead</li>";
    print "<li value='-THEIR-PROJECTS-'>Projects I don't lead</li>";
    print "</ul></li></ul><br>";
    print "<div style='margin: 20px; clear: both;'>";
    if ($user->isAllowed(PA_ACTION::CREATE_PROJECT, CS_CONTEXT_TYPE::RESOURCE, null)) {
      print "<a class='button' href='edit-project.php'>Create New Project</a>";
      print "<a class='button' href='join-project.php'>Join a Project</a></div>";
    } else {
      print "<p><a href='join-project.php'><b>Join a Project</b></a>";
      print "<a href='ask-for-project.php'><b>Ask Someone to Create a Project</b></a>";
      print "<a href='window.location='modify.php?belead=belead'><b>Ask to be a Project Lead</b></a></p>";
    }
    // TODO: sort projects
    // print "<h4 class='dashtext' style='margin-left: 15px !important;'>Sort by:</h4>";
    // print "<ul class='selectorcontainer'><li class='has-sub selector' style='float:none;' id='slicesortby'>";
    // print "<span class='selectorshown'>Sorts</span><ul class='submenu'>";
    // print "<li value='slicename'>Project name</li><li value='sliceexp'>Project expiration</li>";
    // print "<input type='checkbox' id='projascendingcheck' value='ascending' checked>Sort ascending<br></div>";

    print "<div id='projectarea'>$project_boxes</div></div>";

    print "<div id='Slicesection' class='dashsection'>";
    print "<div id='projectcontrols'><h4 class='dashtext'>Filter by:</h4>$project_options<br class='mobilebreak'>"; 
    print "<h4 class='dashtext' style='margin-left: 15px !important;'>Sort by:</h4>";
    print "<ul class='selectorcontainer'><li class='has-sub selector' style='float:none;' id='slicesortby'>";
    print "<span class='selectorshown'>Sorts</span><ul class='submenu'>";
    print "<li value='slicename'>Slice name</li><li value='sliceexp'>Slice expiration</li>";
    print "<li value='resourceexp'>Resource expiration</li></ul></li></ul><br class='mobilebreak'>";
    print "<input type='checkbox' id='sliceascendingcheck' value='ascending' checked>Sort ascending<br></div>";
    print $project_info;

    print "<div id='slicearea' style='clear:both;'>";
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
      $slice_url = "slice.php?" . $query;
      $slice_project_id = $slice[SA_SLICE_TABLE_FIELDNAME::PROJECT_ID];
      if (!array_key_exists($slice_project_id, $project_objects)) {
        $slice_project_name = "-Expired Project-";
      } else {
        $project = $project_objects[ $slice_project_id ];
        $slice_project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
      }
      $add_slivers_privilege = $user->isAllowed(SA_ACTION::ADD_SLIVERS,
                CS_CONTEXT_TYPE::SLICE, 
                $slice_id);

      $slivers = lookup_sliver_info_by_slice($sa_url, $user, $slice_urn);
      $slice_exp = get_time_diff($slice_exp_date);

      if (count($slivers) == 0) {
        $resource_exp = 1000000000;
      } else {
        $first_sliver = reset($slivers);
        $next_exp = new DateTime($first_sliver[SA_SLIVER_INFO_TABLE_FIELDNAME::SLIVER_INFO_EXPIRATION]);
        foreach ($slivers as $sliver) {
          $this_date = new DateTime($sliver[SA_SLIVER_INFO_TABLE_FIELDNAME::SLIVER_INFO_EXPIRATION]);
          if ($next_exp > $this_date) {
            $next_exp = $this_date;
          }
        }
        $resource_exp = get_time_diff(dateUIFormat($next_exp)); 
      }
      make_slice_box($slice_name, $whose_slice, $slice_url, $slice_owner_names[$slice_owner_id], $slice_project_name,
                     count($slivers), $slice_exp, $resource_exp, $add_resource_url, $delete_resource_url);
    }

    print "</div></div>";
  }

  function make_slice_box($slice_name, $whose_slice, $slice_url, $lead_name, $project_name, $resource_count, 
                          $slice_exp, $resource_exp, $add_url, $remove_url) {
    print "<div class='floatleft slicebox $whose_slice {$project_name}slices' slicename='$slice_name' sliceexp='$slice_exp' resourceexp='$resource_exp'>";
    print "<table>";
    $resource_exp_icon = "";
    if ($resource_count > 0){
      $plural = $resource_count == 1 ? "" : "s";
      $resource_exp_string = get_time_diff_string($resource_exp);
      $resource_exp_color = get_urgency_color($resource_exp);
      $resource_exp_icon = get_urgency_icon($resource_exp);
      $resource_info = "<b>$resource_count</b> resource{$plural}, next expiration in <b style='color: #{$resource_exp_color}'>$resource_exp_string</b>";
      $resource_exp_icon = "<img class='expirationicon' alt='resource expiration icon' src='/images/${resource_exp_icon}.png'/>";
    } else {
      $resource_info = "<i>No resources for this slice</i>";
    }
    $slice_exp_string = get_time_diff_string($slice_exp);
    $slice_exp_color = get_urgency_color($slice_exp);
    $slice_exp_icon = get_urgency_icon($slice_exp);
    $slice_info = "Slice expires in <b style='color: #{$slice_exp_color}'>$slice_exp_string</b>";
    $slice_exp_icon = "<img class='expirationicon' alt='slice expiration icon' src='/images/{$slice_exp_icon}.png'/>";
    print "<tr><td class='slicetopbar' style='text-align:left;' onclick='window.location=\"$slice_url\"'>";
    print "<span style='font-weight: normal; font-size: 16px'>$slice_name</span></td>";
    print "<td class='slicetopbar sliceactions' style='text-align:right;'><ul><li class='has-sub'>actions<ul class='submenu'>";
    print "<li onclick='window.location=\"$slice_url\"'>Manage slice</li>";
    print "<li onclick='window.location=\"$add_url\"'>Add resources</li>";
    print "<li onclick='window.location=\"$remove_url\"'>Remove resources</li></ul></li></ul></td></tr>";
    print "<tr><td colspan='2' style='width:200px;'>Lead: $lead_name</td>";
    print "<tr><td style='width:200px;'>$slice_info</td><td style='vertical-align: middle; style='width:30px;'>$slice_exp_icon</td></tr>";
    print "<tr><td style='border-bottom:none; height:55px;'>$resource_info</td>";
    print "<td style='vertical-align: middle; border-bottom:none'>$resource_exp_icon</td></tr>";
    print "</table></div>";
  }
  
  function make_project_box($project_id, $project_name, $lead_id, $lead_name, $purpose, $expiration, $whose_proj) {
    $box = '';
    $box .= "<div class='floatleft slicebox $whose_proj'>";
    $box .= "<table><tr class='slicetopbar'>";
    $box .= "<td class='slicetopbar' style='text-align: left;' onclick='window.location=\"project.php?project_id=$project_id\"'>$project_name</td>";
    $box .= "<td class='slicetopbar sliceactions' style='text-align: right;'><ul><li class='has-sub'>actions<ul class='submenu'>";
    $box .= "<li onclick='window.location=\"project.php?project_id=$project_id\"'>Manage slice</li>";
    $box .= "<li onclick='window.location=\"createslice.php?project_id=$project_id\"'>New slice</li>";
    $box .= "<li onclick='window.location=\"edit-project.php?project_id=$project_id\"'>Edit project</li>";
    $box .= "<li onclick='window.location=\"edit-project-member.php?project_id=$project_id\"'>Edit membership</li>";
    $box .= "</ul></li></ul></td></tr>";
    $box .= "<tr><td colspan='2'>Lead: $lead_name</td></tr>";
    $box .= "<tr><td colspan='2'>";
    $box .= "<div class='purposebox'>Purpose: $purpose</div></td></tr>";
    if($expiration) {
      $exp_diff = get_time_diff($expiration);
      $expiration_string = get_time_diff_string($exp_diff);
      $expiration_color = get_urgency_color($exp_diff);
      $expiration_string = "Project expires in <b style='color: $expiration_color'>$expiration_string</b>";
      $expiration_icon = get_urgency_icon($exp_diff);
      $expiration_icon = "<img class='expirationicon' alt='project expiration icon' src='/images/{$expiration_icon}.png'/>";
    } else {
      $expiration_string = "<i>No expiration</i>";
      $expiration_icon = "";
    }
    $box .= "<tr><td style='border-bottom:none'>$expiration_string</td>";
    $box .= "<td style='vertical-align: middle; border-bottom:none'>$expiration_icon</td></tr>";
    $box .= "</table></div>";
    return $box;
  }

  function get_time_diff_string($num_hours) {
    if ($num_hours < 48) {
      return "$num_hours hours";
    } else {
      $num_days =  $num_hours / 24;
      $num_days = (int) $num_days;
      return "$num_days days";
    }
  }
  
  function get_time_diff($exp_date) {
    $now = new DateTime('now');
    $exp_datetime = new DateTime($exp_date);
    $interval = date_diff($exp_datetime, $now);
    $num_hours = $interval->days * 24 + $interval->h;
    return $num_hours;
  }

  function get_urgency_color($num_hours) {
    if ($num_hours < 24) { 
      return "EE583A";
    } else if ($num_hours < 48) {
      return "FBC02D";
    } else {
      return "339933";
    }
  }

  function get_urgency_icon($num_hours) {
    if ($num_hours < 24) { 
      return "red";
    } else if ($num_hours < 48) {
      return "yellow";
    } else {
      return "green";
    }
  }
?>

<div style="clear:both;">&nbsp;</div>
<h2 class="dashtext">Messages</h2><br>
<div style='text-align: left;'>
<h4 class='dashtext' style='margin-top: 20px !important;'>Showing logs for the last</h4>
<ul class="selectorcontainer"> 
  <li class='has-sub selector' style='float:none;'><span class='selectorshown'>day</span>
  <ul class='submenu' id='loglength'>
    <li value="24" onclick="getLogs(24);">day</li>
    <li value="48" onclick="getLogs(48);">2 days</li>
    <li value="72" onclick="getLogs(72);">3 days</li>
    <li value="168" onclick="getLogs(168);">week</li>
  </ul>
  </li>
</ul>
</div>

<div class="tablecontainer">
  <table id="logtable"></table>
</div>

<?php

include("footer.php");

?>
