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
?>

<script src='dashboard.js'></script>
<?php include "cards.js"; ?>

<script>
function info_set_location(slice_id, url)
{
  $.getJSON("aggregates.php", { slice_id: slice_id },
    function (responseTxt, statusTxt, xhr) {
        var json_agg = responseTxt;
        var agg_ids = Object.keys(json_agg);
        var agg_count = agg_ids.length;
        if (agg_count > 0) {
            for (var i = 0; i < agg_count; i++) {
                url += "&am_id[]=" + agg_ids[i];
            }
            window.location = url;
        } else {
            alert("This slice has no known resources. \n\nSomething missing? Select aggregates manually on the slice page.");
        }
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
    if ($user->isAllowed(PA_ACTION::CREATE_PROJECT, CS_CONTEXT_TYPE::RESOURCE, null)) {
      if ($num_projects==0) { 
        // You're not in any projects at all, even expired
        print "<p class='instruction'>";
        print "Congratulations! Your GENI Portal account is now active.<br/><br/>";
        print "You have been made a 'Project Lead', meaning you can create GENI Projects, as well as create slices in projects and reserve resources.<br/><br/>";
        print "A project is a group of people and their research, led by a single responsible individual - the project lead.";
        print "See the <a href=\"http://groups.geni.net/geni/wiki/GENIGlossary\">Glossary</a>.</p>\n";
        print "<p class='warn'>";
        print "You are not a member of any projects. You need to Create or Join a Project.";
        print "</p>";
      } else { 
        // You have some projects that are expired, none active
        print "<p class='instruction'>You have no active projects at this time</p>";
      }
      print "<button $disable_create_project onClick=\"window.location='edit-project.php'\"><i class='material-icons'>add</i> New Project</button>\n";
      print "<button $disable_join_project onClick=\"window.location='join-project.php'\">Join a Project</button>\n";
      print "<button $disable_join_project onClick=\"window.location='ask-for-project.php'\">Ask Someone to Create a Project</button>\n";
    } else {
      if ($num_projects==0) {
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
      }
      print "<button $disable_join_project onClick=\"window.location='join-project.php'\">Join a Project</button>\n";
      print "<button $disable_join_project onClick=\"window.location='ask-for-project.php'\">Ask Someone to Create a Project</button>\n";
      // print "<button $disable_project_lead onClick=\"window.location='modify.php?belead=belead'\">Ask to be a Project Lead</button>\n";
    }
  } else { 
    // You have some projects or slices 
    print "<div class='nav2'><ul class='tabs'>";
    // include("tool-breadcrumbs.php");
    print "<li><a class='tab' data-tabindex=1 data-tabname='slices' href='#slices'>Slices</a></li>";
    print "<li><a class='tab' data-tabindex=2 data-tabname='projects' href='#projects'>Projects</a></li>";
    print "<li><a class='tab' data-tabindex=3 data-tabname='logs' href='#logs'>Logs</a></li>";
    print "</ul></div>";

    // Start making the slice and project sections
    $lead_names = lookup_member_names_for_rows($ma_url, $user, $project_objects, PA_PROJECT_TABLE_FIELDNAME::LEAD_ID);
    $project_options = "<ul class='selectorcontainer'><li class='has-sub selector' style='float:none;' id='slicefilterswitch'>";
    $project_options .= "<span class='selectorshown'>Filters</span><ul class='submenu'>";
    $project_options .= "<li data-value='-ALL-' class='selectorlabel'>Categories</li>";
    $project_options .= "<li data-value='-ALL-'>All slices</li>";
    $project_options .= "<li data-value='-MY-'>Slices I lead</li>";
    // $project_options .= "<li data-value='-THEIR-'>Slices I don't lead</li>";
    $project_options .= "<li data-value='-has-resources-'>Has resources</li>";
    // $project_options .= "<li data-value='-no-resources-'>Has no resources</li>";
    $project_options .= "<li data-value='-ALL-' class='selectorlabel'>Projects</li>";
    $project_info = "";
    $show_info = "";
    $project_boxes = "";
    $user_id = $user->account_id;
    foreach ($project_objects as $project) {
      $project_id = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
      $project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
      $lead_id = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
      $purpose = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
      $expiration = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRATION];
      $lead_name = $lead_names[$lead_id];
      $create_slice_button = "<a class='button' href='createslice.php?project_id=$project_id'><i class='material-icons'>add</i> New slice</a><br class='mobilebreak'>";
      $manage_project_button = "<a class='button' href='project.php?project_id=$project_id'>Manage project</a>";
      $project_options .= "<li data-value='{$project_name}'>$project_name</li>";
      $project_info .= "<div $show_info class='projectinfo' id='{$project_name}info'>";
      $project_info .= "Project lead: $lead_name | $create_slice_button $manage_project_button</div>";
      $show_info = "style='display:none;'";

      $expired_project_class = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRED] ? "-EXPIRED-PROJECTS-" : "-ACTIVE-PROJECTS-";
      $project_lead_class = $lead_id == $user_id ? "-MY-PROJECTS" : "-THEIR-PROJECTS-";
      $categories = "$expired_project_class $project_lead_class";

      $project_boxes .= make_project_box($project_id, $project_name, $lead_id, $lead_name, $purpose, $expiration, $categories);
    }
    $project_options .= "</ul></li></ul>";
    
    print "<div class='dashsection card' data-cardname='projects' id='projects'>";
    print "<h3 class='dashtext'>Projects</h3><br>";

    // project filters
    print "<h6 class='dashtext'>Filter by:</h6><ul class='selectorcontainer'>";
    print "<li class='has-sub selector' style='float:none;' id='projectfilterswitch'>";
    print "<span class='selectorshown'>Filters</span><ul class='submenu'>";
    print "<li data-value='-ACTIVE-PROJECTS-'>Active projects</li>";
    print "<li data-value='-MY-PROJECTS-'>Projects I lead</li>";
    // print "<li data-value='-THEIR-PROJECTS-'>Projects I don't lead</li>";
    print "<li data-value='-EXPIRED-PROJECTS-'>Expired projects</li>";
    print "</ul></li></ul><br class='mobilebreak'>";

    // project sorts
    print "<h6 class='dashtext'>Sort by:</h6>";
    print "<ul class='selectorcontainer'><li class='has-sub selector' style='float:none;' id='projectsortby'>";
    print "<span data-value='projname' class='selectorshown'>Sorts</span><ul class='submenu'>";
    print "<li data-value='projname'>Project name</li><li data-value='projexp'>Project expiration</li></ul></li></ul><br class='mobilebreak'>";
    print "<input type='checkbox' id='projectascendingcheck' data-value='ascending' checked><span style='font-size: 13px;'>Sort ascending</span><br>";
    
    print "<div style='margin: 15px 0px; clear: both;'>";
    if ($user->isAllowed(PA_ACTION::CREATE_PROJECT, CS_CONTEXT_TYPE::RESOURCE, null)) {
      print "<a class='button' href='edit-project.php'><i class='material-icons'>add</i>New Project</a>";
      print "<a class='button' href='join-project.php'>Join a Project</a></div>";
    } else {
      print "<a class='button' href='join-project.php'><b>Join a Project</b></a><br class='mobilebreak'>";
      print "<a class='button' href='ask-for-project.php'><b>Ask Someone to Create a Project</b></a><br class='mobilebreak'>";
      print "<a class='button' href='modify.php?belead=belead'><b>Ask to be a Project Lead</b></a></div>";
    }

    print "<div id='projectarea'>$project_boxes</div></div>";

    print "<div class='dashsection card' data-cardname='slices' id='slices'>";
    print "<h3 class='dashtext'>Slices</h3><br>";
    // Slice filters
    print "<div id='projectcontrols'><h6 class='dashtext'>Filter by:</h6>$project_options<br class='mobilebreak'>"; 
    print "<h6 class='dashtext'>Sort by:</h6>";
    print "<ul class='selectorcontainer'><li class='has-sub selector' style='float:none;' id='slicesortby'>";
    // Slice sorts
    print "<span class='selectorshown'>Sorts</span><ul class='submenu'>";
    print "<li data-value='slicename'>Slice name</li><li data-value='sliceexp'>Slice expiration</li>";
    print "<li data-value='resourceexp'>Resource expiration</li></ul></li></ul><br class='mobilebreak'>";
    print "<input type='checkbox' id='sliceascendingcheck' data-value='ascending' checked><span style='font-size: 13px;'>Sort ascending</span><br></div>";
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
      $listres_url = "listresources.php?";
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
      make_slice_box($slice_name, $slice_id, $whose_slice, $slice_url, $slice_owner_names[$slice_owner_id], $slice_project_name,
                     count($slivers), $slice_exp, $resource_exp, $add_resource_url, $delete_resource_url, $listres_url);
    }

    print "</div></div>";
  }

  function make_slice_box($slice_name, $slice_id, $whose_slice, $slice_url, $lead_name, $project_name, $resource_count, 
                          $slice_exp, $resource_exp, $add_url, $remove_url, $listres_url) {
    $has_resources = $resource_count > 0 ? "-has-resources-" : "-no-resources-";
    print "<div class='floatleft slicebox $whose_slice {$project_name}slices $has_resources' 
                data-resourcecount='$resource_count' data-slicename='$slice_name' 
                data-sliceexp='$slice_exp' data-resourceexp='$resource_exp'>";
    print "<table>";
    $resource_exp_icon = "";
    if ($resource_count > 0){
      $plural = $resource_count == 1 ? "" : "s";
      $resource_exp_string = get_time_diff_string($resource_exp);
      $resource_exp_color = get_urgency_color($resource_exp);
      $resource_exp_icon = get_urgency_icon($resource_exp);
      $resource_info = "<b>$resource_count</b> resource{$plural}, next expiration in <b style='color: #{$resource_exp_color}'>$resource_exp_string</b>";
    } else {
      $resource_info = "<i>No resources for this slice</i>";
    }
    $slice_exp_string = get_time_diff_string($slice_exp);
    $slice_exp_color = get_urgency_color($slice_exp);
    $slice_exp_icon = get_urgency_icon($slice_exp);
    $slice_info = "Slice expires in <b style='color: #{$slice_exp_color}'>$slice_exp_string</b>";
    print "<tr><td class='slicetopbar' title='Manage slice $slice_name' style='text-align:left;' onclick='window.location=\"$slice_url\"'>";
    print "$slice_name</td>";
    print "<td class='slicetopbar sliceactions' style='text-align:right;'><ul><li class='has-sub' style='color: #ffffff;'>";
    print "<i class='material-icons' style='font-size: 22px;'>more_horiz</i><ul class='submenu'>";
    print "<li><a href='$slice_url'>Manage slice</a></li>";
    print "<li><a href='$add_url'>Add resources</a></li>";
    if ($resource_count > 0) {
      print "<li><a onclick='info_set_location(\"$slice_id\", \"$listres_url\")'>Resource details</a></li>";
      print "<li><a href='$remove_url'>Remove resources</a></li>";
    }
    print "</ul></li></ul></td></tr>";
    // print "<tr><td colspan='2' style='width:200px;'>Project: $slice_project_name</td></tr>";
    print "<tr><td colspan='2' style='width:200px;'>Lead: $lead_name</td></tr>";
    print "<tr><td style='width:200px;'>$slice_info</td><td style='vertical-align: middle; width:30px;'>";
    print "<i class='material-icons' style='color:$slice_exp_color;'>$slice_exp_icon</i></td></tr>";
    print "<tr><td style='border-bottom:none; height:55px;'>$resource_info</td>";
    print "<td style='vertical-align: middle; border-bottom:none'>";
    print "<i class='material-icons' style='color: $resource_exp_color;'>$resource_exp_icon</i></td></tr>";
    print "</table></div>";
  }
  
  function make_project_box($project_id, $project_name, $lead_id, $lead_name, $purpose, $expiration, $categories) {
    if ($expiration) {
      $exp_diff = get_time_diff($expiration);
    } else {
      $exp_diff = 100000000;
    }

    $box = '';
    $box .= "<div class='floatleft slicebox $categories' data-projname='$project_name' data-projexp='$exp_diff'>";
    $box .= "<table><tr class='slicetopbar'>";
    $box .= "<td class='slicetopbar' style='text-align: left;' onclick='window.location=\"project.php?project_id=$project_id\"'>$project_name</td>";
    $box .= "<td class='slicetopbar sliceactions' style='text-align: right;'><ul><li class='has-sub'><i class='material-icons' style='font-size: 22px;'>more_horiz</i><ul class='submenu'>";
    $box .= "<li onclick='window.location=\"project.php?project_id=$project_id\"'>Manage project</li>";
    $box .= "<li onclick='window.location=\"createslice.php?project_id=$project_id\"'>New slice</li>";
    $box .= "<li onclick='window.location=\"edit-project.php?project_id=$project_id\"'>Edit project</li>";
    $box .= "<li onclick='window.location=\"edit-project-member.php?project_id=$project_id\"'>Edit membership</li>";
    $box .= "</ul></li></ul></td></tr>";
    $box .= "<tr><td colspan='2'>Lead: $lead_name</td></tr>";
    // $box .= "<tr><td colspan='2'>Lead: $lead_name</td></tr>"; 5 slices, next expiration: 
    $box .= "<tr><td colspan='2'>";
    $purpose = !$purpose ? "<i>None</i>" : $purpose;
    $box .= "<div class='purposebox'>Purpose: $purpose</div></td></tr>";
    if($expiration) {
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

<?php

include("footer.php");

?>
