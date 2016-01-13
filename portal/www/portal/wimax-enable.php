<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2016 Raytheon BBN Technologies
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
require_once("am_client.php");
require_once("ma_client.php");
require_once("sr_client.php");
require_once('util.php');
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
$wimax_url = get_first_service_of_type(SR_SERVICE_TYPE::WIMAX_SITE);

// error_log("WIMAX_URL " . print_r($wimax_url, true));


show_header('GENI Portal: Wireless Account Setup');
include('tool-breadcrumbs.php');
include("tool-showmessage.php");

if ($wimax_url == NULL) {
  echo "This Portal is not configured to enable or manage wireless-enabled projects.<br>";
  echo "See system administrator to enable management of wireless-enabled projects on this Portal.<br>";
  echo"<button onClick=\"history.back(-1)\">Back</button>\n";
  return;
}


?>

<script>

function set_error_text(responseTxt, project_id)
{
  if(responseTxt.code == 0) {
    $('#error-' + project_id).text('');
  } else {
    $('#error-' + project_id).text('Sync Error: ' + responseTxt.code).css('color', 'red');
  }
}

function do_enable_wireless(project_id, project_name) 
{
  $('#enable-' + project_id).attr('disabled', true);
  $.getJSON("wireless_operations.php", {operation : 'enable', 
	project_id : project_id, project_name : project_name},
    function(responseTxt, statusTxt, xhr) {
      $('#enable-' + project_id).removeAttr('disabled');
      $('#enable-' + project_id).hide();
      $('#disable-' + project_id).show();
      $('#sync-' + project_id).show();
      $('#sync-' + project_id).removeAttr('disabled');
      $('#group-' + project_id).text('geni-' + project_name);
      set_error_text(responseTxt, project_id);
    })
    .fail(function(xhr, ts, et) {
	console.log("ERROR FROM ENABLE " + xhr);
      });
}
function do_disable_wireless(project_id, project_name) 
{
  $('#disable-' + project_id).attr('disabled', true);
  $.getJSON("wireless_operations.php", {operation : 'disable', 
	project_id : project_id, project_name : project_name},
    function(responseTxt, statusTxt, xhr) {
      $('#disable-' + project_id).removeAttr('disabled');
      $('#enable-' + project_id).show();
      $('#disable-' + project_id).hide();
      $('#sync-' + project_id).hide();
      $('#sync-' + project_id).attr('disabled', true);
      $('#group-' + project_id).text("");
      set_error_text(responseTxt, project_id);
    })
    .fail(function(xhr, ts, et) {
      console.log("ERROR FROM DISABLE " + xhr);
      });
}
function do_wireless_sync(project_id, project_name) 
{
  $('#sync-' + project_id).attr('disabled', true);
  $.getJSON("wireless_operations.php", {operation : 'sync', 
	project_name : project_name,
	project_id : project_id},
    function(responseTxt, statusTxt, xhr) {
      $('#sync-' + project_id).removeAttr('disabled');
      set_error_text(responseTxt, project_id);
    })
    .fail(function(xhr, ts, et) {
      console.log("ERROR FROM SYNC");
      });
}
</script>

<?php

function is_wimax_enabled($project_id, $attribs_by_project) {
  $project_attribs = $attribs_by_project[$project_id];
  $enabled = false;
  foreach($project_attribs as $project_attrib) {
    $name = $project_attrib[PA_ATTRIBUTE::NAME];
    $value = $project_attrib[PA_ATTRIBUTE::VALUE];
    //     error_log("PID $project_id NAME $name VALUE $value " . PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
    if($name == PA_ATTRIBUTE_NAME::ENABLE_WIMAX) {
      $enabled = true;
      break;
    }
  }
  return $enabled;
}

function draw_table($projects, $lead_names, $attribs_by_project, $show_actions)
{

  if (count($projects) == 0) return;

  $show_purpose = false;

  echo "<table>";
  $actions_entry = "";
  if ($show_actions) $actions_entry = "<th>Actions</th>";

  $purpose_entry = "";
  if ($show_purpose) $purpose_entry = "<tr>Purpose</th>";

  echo "<tr><th>Project Name</th><th>Wireless Group</th><th>Project Lead</th>$purpose_entry $actions_entry<th></th></tr>";

  foreach ($projects as $project) {
    $lead_id = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
    $proj_id = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
    $proj_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
    $proj_purpose = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
    $lead_name = $lead_names[$lead_id];
    $expired = convert_boolean($project[PA_PROJECT_TABLE_FIELDNAME::EXPIRED]);

    //    error_log("PN = $proj_name EXP = $expired");


    $group_name = "";
    $wimax_enabled = is_wimax_enabled($proj_id, $attribs_by_project);
    if ($wimax_enabled) $group_name = "geni-" . $proj_name;

    if($expired) continue;

    $proj_href = "<a href='project.php?project_id=$proj_id'>{$proj_name}</a>";

    $actions_entry = "";
    if ($show_actions) {
      
      $enable_button_name = "Enable project";
      $enable_button_action = "do_enable_wireless('$proj_id', '$proj_name');";
      $enable_button_hidden = "";
      $enable_button_id = "enable-$proj_id";

      $disable_button_name = "Disable project";
      $disable_button_action = "do_disable_wireless('$proj_id', '$proj_name');";
      $disable_button_hidden = 'hidden="hidden"';
      $disable_button_id = "disable-$proj_id";

      $sync_button_name = "Sync Project";
      $sync_button_action = "do_wireless_sync('$proj_id', '$proj_name');";
      $sync_button_disabled = "disabled=\"disabled\"";
      $sync_button_id = "sync-$proj_id";

      if($wimax_enabled) {
	$enable_button_hidden = 'hidden="hidden"';
	$disable_button_hidden = "";
	$sync_button_disabled = "";
      }



      $enable_button = "<button id=\"$enable_button_id\" $enable_button_hidden onClick=\"$enable_button_action\">$enable_button_name</button>";
      $disable_button = "<button id=\"$disable_button_id\" $disable_button_hidden onClick=\"$disable_button_action\">$disable_button_name</button>";
      $sync_button = "<button id=\"$sync_button_id\" $disable_button_hidden $sync_button_disabled onClick=\"$sync_button_action\">$sync_button_name</button>";

      $actions = $enable_button . $disable_button . $sync_button;
      $actions_entry = "<td>$actions</td>";
    }

    $purpose_entry = "";
    if ($show_purpose) {
      $purpose_entry = "<td>$proj_purpose</td>";
    }

    $error_entry_id = "error-" . $proj_id;
    $error_entry = "<td id=\"$error_entry_id\"></td>";

    echo "<tr><td>$proj_href</td><td id=\"group-$proj_id\">$group_name</td><td>$lead_name</td>$purpose_entry $actions_entry $error_entry</tr>";

  }

  echo "</table>";

}

echo "<h1>Wireless Account Setup</h1>";

$project_ids = get_projects_for_member($sa_url, $user, 
				       $user->account_id, true);

// Gather project attributes for each project
$attribs = lookup_project_attributes($sa_url, $user, $project_ids);
$attribs_by_project = array();
foreach($project_ids as $project_id) { 
  $attribs_by_project[$project_id] = array(); 
}

foreach($attribs as $attrib) {
  $project_id = $attrib[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
  $attribs_by_project[$project_id][] = $attrib;
}

$projects = lookup_project_details($sa_url, $user, $project_ids);
$num_projects = count($project_ids);

$lead_names = lookup_member_names_for_rows($ma_url, $user, $projects,
					   PA_PROJECT_TABLE_FIELDNAME::LEAD_ID);
// error_log("LEADS = " . print_r($lead_names, true));


// Get the user's SSH keys to make sure they'll be able to log in
$sshkeys = $user->sshKeys();

// Break up projects into those I can modify and those I cannot.
$my_projects = array();
$other_projects = array();
foreach($projects as $proj) {
  $proj_id = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
  if($user->isAllowed(PA_Action::UPDATE_PROJECT, CS_CONTEXT_TYPE::PROJECT, $proj_id)) {
    $my_projects[] = $proj;
  } else {
    $other_projects[] = $proj;
  }
}

// error_log("MINE = " . print_r($my_projects, true));
// error_log("OTHERS = " . print_r($other_projects, true));

echo "<h2>Projects You Manage</h2>";

if (count($my_projects) == 0) {
  echo "You have no projects of which you are lead or admin.<br>";
} else {
  echo "This table shows all projects of which you are a lead or admin.<br>";
  echo "If a project has not been enabled for wireless access, the <b>Enable project</b> button will enable it.<br>";
  echo "If a project has already been enabled for wireless access, the <b>Disable project</b> button will disable it.<br>";
  echo "Wireless-enabled projects allow for synchronizing GENI and Wireless project and member state with the <b>Sync Project</b> button.<br>";
}

draw_table($my_projects, $lead_names, $attribs_by_project, true);

if(count($other_projects) > 0) {
  echo "<h2>Projects Others Manage</h2>";
  echo "This table shows all projects to which you belong but do not have management privileges.<br>";
  echo "The projects with a 'Wireless Group' indicated are already wireless-enabled.<br>";
  echo "Contact the project lead if you would like to modify wireless settings for a given project.<br>";
  draw_table($other_projects, $lead_names, $attribs_by_project, false);
}


include("footer.php");
?>
