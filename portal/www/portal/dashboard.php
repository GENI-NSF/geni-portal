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

include("tool-showmessage.php");

?>
<style>
  .dashtext {
    padding: 0px;
    border: none !important;
    margin: 0px !important;
    color: #5F584E !important;
    text-shadow: none !important;
    display: inline;
  }

  .slicebox {
    width: 330px;
    padding: 0px;
    margin: 15px 30px 15px 0px;
    border-radius: 3px;
    border: 2px solid #5F584E;
    float: left;
  }

  .sliceboxinside {
    width: 100%;
  }

  .sliceboxinside table {
    margin: 0px !important;
    box-shadow: none !important;
  }

  #dashboardtools {
    background-color: #F57F21;
    border-radius: 3px;
    padding: 10px;
  }

  select {
    font-family: 'Open Sans';
    font-size: 16px;
    color: #fff;
    background-color: #5F584E;
    padding: 5px;
    border-radius: 0px;
    margin-top: 15px;
  }

  select:focus {
    outline-width: 0;
  }

  option {
    font-family: 'Open Sans';
    font-size: 16px;
    color: #ffffff;
    background-color: #5F584E;
  }

  option:focus {
      outline-width: 0;
  }

</style>

<script>
  $(document).ready(function(){
    $(".slicebox").hide();
    if (localStorage.lastproj){
      if (localStorage.lastproj == "__ALL") {
        show_all_slices();
      } else {
        $("#projectswitch").val(localStorage.lastproj);
        if($("." + localStorage.lastproj + "slice").length == 0) {
          $("#slicearea").append("<h3 class='dashtext noslices'>No slices for project " + localStorage.lastproj + ".</h3>");
        }
      }
    }

    $("." + $("#projectswitch").val() + "slice").show();

    $("#projectswitch").change(function(){
      $('#showallslices').removeAttr('disabled');
      $('#showallslices').html("Show all slices");
      $(".noslices").remove();
      $("#whatslices").html("Slices for project:");
      $(".projectinfo").hide();
      new_name = $(this).val()
      $("#" + new_name + "info").show();
      $("#" + new_name + "info").css("float", "right");
      $(".slicebox").hide();
      $("." + new_name + "slice").show();
      localStorage.setItem("lastproj", new_name);
      if($("." + new_name + "slice").length == 0) {
        $("#slicearea").append("<h3 class='dashtext noslices'>No slices for project " + new_name + ".</h3>");
      }
    });

    $("#showallslices").click(function() {
      localStorage.setItem("lastproj", "__ALL");
      show_all_slices();
    });

    function show_all_slices() {
      $(".noslices").remove();
      $("#showallslices").attr('disabled', 'disabled');
      $("#showallslices").html("Showing all slices");
      $(".slicebox").show();
      $(".projectinfo").hide();
      $("#whatslices").html("Filter by project: ");
    }
  });
</script>

<?php

  $retVal  = get_project_slice_member_info( $sa_url, $ma_url, $user, True);
  $project_objects = $retVal[0];
  $slice_objects = $retVal[1];
  $member_objects = $retVal[2];
  $project_slice_map = $retVal[3];
  $project_activeslice_map = $retVal[4];

?>

<div id="dashboardheader">
  <h1 class="dashtext" style="float:left; ">GENI Dashboard</h1>
  <div id="dashboardtools" style="float:right; vertical-align: middle;">
    <h3 class="dashtext" style="color:white !important;">Manage: </h3>
    <button onclick="window.location='profile.php#accountsummary'">Account</button>
    <button onclick="window.location='profile.php#ssh'">SSH Keys</button>
    <button onclick="window.location='profile.php#omni'">Omni</button>
    <button onclick="window.location='profile.php#outstandingrequests'">Requests</button>
  </div>
</div>

<div style="clear:both; margin-top: 50px;">

<?php
  if (count($project_objects) == 0){
    print "no projects";
  } else {
    $lead_names = lookup_member_names_for_rows($ma_url, $user, $project_objects, 
                                                PA_PROJECT_TABLE_FIELDNAME::LEAD_ID);
    $project_options = "<select id='projectswitch' style='margin: 0px 15px;'>";
    $project_info = "";
    $show_info = "";
    foreach ($project_objects as $project) {
      $project_id = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
      $project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
      $lead_id = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
      $lead_name = $lead_names[$lead_id];
      $create_slice_button = "<button onClick='window.location=\"createslice.php?project_id=$project_id\"'><b>new slice</b></button>";
      $project_options .= "<option value='$project_name'>$project_name</option>";
      $project_info .= "<div $show_info class='projectinfo' id='{$project_name}info' style='float:right; vertical-align: bottom !important;'>";
      $project_info .= "<p style='font-weight: bold;'><b>Project Lead:</b> $lead_name | $create_slice_button</p></div>";
      $show_info = "style='display:none;'";
    }
    $project_options .= "</select>";
    print "<div style='float:left;'><h3 class='dashtext' id='whatslices'>Slices for project:</h3>$project_options<button id='showallslices'>Show all</button></div>";
    print $project_info;
  }
?>
</div>
<div id="slicearea" style="clear:both;">

<?php  

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
  if (count($unexpired_slices) > 0) {
    $slice_owner_names = lookup_member_names_for_rows($ma_url, $user, $slice_objects, 
                                                      SA_SLICE_TABLE_FIELDNAME::OWNER_ID);
  }

  function make_slice_box($slice_name, $lead_name, $project_name, $resource_count, $slice_exp, $resource_exp, 
                          $add_url, $remove_url) {
    print "<div class='slicebox {$project_name}slice'><div class='sliceboxinside'><table>";
    if ($resource_count > 0){
      $resource_info = "$<b>resource_count</b> resources, next one expires in <b>$resource_exp</b>";
    } else {
      $resource_info = "<i>No resources for this slice</i>";
    }
    $slice_info = "Slice expires in <b>$slice_exp</b>";
    print "<tr><td colspan='2' style='text-align:center; background-color: #F57F21;'><h5 class='dashtext' style='color:white !important;'>$slice_name <br> lead: $lead_name</h5></td><tr>";
    print "<tr><td>$slice_info</td>";
    print "<td rowspan='2' style='text-align:center; border-left: 1px solid white;'>";
    
    print "<button onclick='window.location=\"$add_url\"'>Add resources</button><br><button onclick='window.location=\"$remove_url\"'>Remove resources</button></td></tr>";
    print "<tr><td style='border-left: 1px solid white;'>$resource_info</td></tr>";
    print "</table></div></div>";
  }
  
  function get_time_diff_string($exp_date) {
    $now = new DateTime('now');
    $exp_datetime = new DateTime($exp_date);
    if ($exp_datetime < $now) {
      return "This is expired";
    } 
    $interval = date_diff($exp_datetime, $now);
    $num_hours = $interval->days * 24 + $interval->h;
    if ($num_hours < 48) {
      return "$num_hours hours";
    } else {
      return "{$interval->days} days";
    }
  }

  foreach ($slice_objects as $slice) {
    $slice_id = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
    $slice_urn = $slice[SA_ARGUMENT::SLICE_URN];
    $slice_name = $slice[SA_ARGUMENT::SLICE_NAME];
    $slice_owner_id = $slice[SA_ARGUMENT::OWNER_ID];
    $slice_exp_date = $slice[SA_ARGUMENT::EXPIRATION];
    $args['slice_id'] = $slice_id;
    $query = http_build_query($args);
    $add_resource_url = "slice-add-resources-jacks.php?" . $query;
    $delete_resource_url = "confirm-sliverdelete.php?" . $query;
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
    $slice_exp_info = get_time_diff_string($slice_exp_date);

    if (count($slivers) == 0) {
      $resource_exp_info = "";
    } else {
      $first_sliver = reset($slivers);
      $next_exp = new DateTime($first_sliver[SA_SLIVER_INFO_TABLE_FIELDNAME::SLIVER_INFO_EXPIRATION]);
      foreach ($slivers as $sliver) {
        $this_date = new DateTime($sliver[SA_SLIVER_INFO_TABLE_FIELDNAME::SLIVER_INFO_EXPIRATION]);
        if ($next_exp > $this_date) {
          $next_exp = $this_date;
        }
      }
      $resource_exp_info = get_time_diff_string($next_exp); 
    }
    make_slice_box($slice_name, $slice_owner_names[$slice_owner_id], $slice_project_name, count($slivers), $slice_exp_info, $resource_exp_info, $add_resource_url, $delete_resource_url);
  }
  if(count($slice_objects) == 0 ){
    print "<h4 class='dashtext'>No slices</h4>";
  }
?>

</div>

<div style="clear:both;">&nbsp;</div>
<h2 class="dashtext">GENI Messages</h2>
<p>Showing logs for the last 
<select id="loglength" onchange="getLogs(this.value);">
  <option value="24">day</option>
  <option value="48">2 days</option>
  <option value="72">3 days</option>
  <option value="168">week</option>
</select></p>

<script type="text/javascript">
  $(document).ready(function(){
    if(localStorage.loghours){
      $("#loglength").val(localStorage.logHours);
      getLogs(localStorage.loghours);
    } else {
      getLogs("24");
    }
  });
  function getLogs(hours){
    localStorage['loghours'] = hours;
    $.get("do-get-logs.php?hours="+hours, function(data) {
      $('#log_table').html(data);
    });
  }
</script>
<div class="tablecontainer">
  <table id="log_table" style="border: 2px solid #5F584E;"></table>
</div>

<?php

include("footer.php");

?>
