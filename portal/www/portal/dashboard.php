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
?>


<h2 class="dashtext">Slices</h2><br>
<?php
  if (count($project_objects) == 0){
    print "no projects";
  } else {
    $lead_names = lookup_member_names_for_rows($ma_url, $user, $project_objects, 
                                                PA_PROJECT_TABLE_FIELDNAME::LEAD_ID);
    $project_options = "<ul class='selectorcontainer'><li class='has-sub selector' style='float:none;' id='projectswitch'>";
    $project_options .= "<span class='selectorshown'>Filters</span><ul class='submenu'>";
    $project_info = "";
    $show_info = "";
    foreach ($project_objects as $project) {
      $project_id = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
      $project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
      $lead_id = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
      $lead_name = $lead_names[$lead_id];
      $create_slice_button = "<button onClick='window.location=\"createslice.php?project_id=$project_id\"'><b>New slice</b></button>";
      $project_options .= "<li value='{$project_name}'>Project: $project_name</li>";
      $project_info .= "<div $show_info class='projectinfo' id='{$project_name}info'>";
      $project_info .= "project lead: $lead_name | $create_slice_button</div>";
      $show_info = "style='display:none;'";
    }
    $project_options .= "<li value='-MY-'>All slices I lead</li>";
    $project_options .= "<li value='-THEIR-'>All slices I don't lead</li>";
    $project_options .= "<li value='-ALL-'>All slices</li>";
    $project_options .= "</ul></li></ul>";
    print "<div id='projectcontrols'><h4 class='dashtext'>Filter by:</h4>$project_options"; 
    print "<h4 class='dashtext' style='margin-left: 15px !important;'>Sort by:</h4>";
    print "<ul class='selectorcontainer'><li class='has-sub selector' style='float:none;' id='sortby'>";
    print "<span class='selectorshown'>Sorts</span><ul class='submenu'>";
    print "<li value='slicename'>Slice name</li><li value='sliceexp'>Slice expiration</li>";
    print "<li value='resourceexp'>Resource expiration</li></ul></li></ul>";
    print "<input type='checkbox' id='ascendingcheck' value='ascending' checked>Sort ascending<br></div>";
    print $project_info;
  }
?>

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

  function make_slice_box($slice_name, $whose_slice, $slice_url, $lead_name, $project_name, $resource_count, 
                          $slice_exp, $resource_exp, $add_url, $remove_url) {
    print "<div class='floatleft slicebox $whose_slice {$project_name}slices' slicename='$slice_name' sliceexp='$slice_exp' resourceexp='$resource_exp'>";
    print "<table>";
    $resource_exp_icon = "";
    if ($resource_count > 0){
      $plural = $resource_count == 1 ? "" : "s";
      $resource_exp_string = get_time_diff_string($resource_exp);
      $resource_exp_color = get_urgency_color($resource_exp);
      $resource_info = "<b>$resource_count</b> resource{$plural}, next expiration in <b style='color: #{$resource_exp_color}'>$resource_exp_string</b>";
      $resource_exp_icon = "<img class='expirationicon' alt='$resource_exp_color resource expiration icon' src='/common/${resource_exp_color}.png'/>";
    } else {
      $resource_info = "<i>No resources for this slice</i>";
    }
    $slice_exp_string = get_time_diff_string($slice_exp);
    $slice_exp_color = get_urgency_color($slice_exp);
    $slice_info = "Slice expires in <b style='color: #{$slice_exp_color}'>$slice_exp_string</b>";
    $slice_exp_icon = "<img class='expirationicon' alt='slice expiration icon' src='/common/${slice_exp_color}.png'/>";
    print "<tr><td class='slicetopbar' colspan='2' style='text-align:center; background-color: #F57F21; height: 30px; line-height: 30px;'>";
    print "<span class='dashtext' style='font-weight: normal; color:white !important; font-size: 16px' onclick='window.location=\"$slice_url\"'>$slice_name</span></td></tr>";
    print "<tr><td colspan='2' style='width:200px;'>Lead: $lead_name</td>";
    print "<td rowspan='3' style='text-align:center; border-left:1px solid #C2C2C2; border-bottom:none; display:none;' class='slicebuttons'>";
    print "<button onclick='window.location=\"$add_url\"'>Add resources</button><br><button onclick='window.location=\"$remove_url\"'>Remove resources</button></td></tr>";
    print "<tr><td style='width:200px;'>$slice_info</td><td style='vertical-align: center; style='width:30px;'>$slice_exp_icon</td></tr>";
    print "<tr><td style='border-bottom:none; height:55px;'>$resource_info</td><td style='vertical-align: center; border-bottom:none'>$resource_exp_icon</td></tr>";
    print "</table></div>";
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
      return "FFD92A";
    } else {
      return "339933";
    }
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
      $resource_exp = -999;
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

  if(count($slice_objects) == 0 ){
    print "<h4 class='dashtext'>No slices</h4>";
  }
?>

</div>

<div style="clear:both;">&nbsp;</div>
<h2 class="dashtext">Messages</h2><br>
<div style='text-align: left;'>
<h4 class='dashtext' style='margin-top: 20px !important;'>Showing logs for the last</h4>
<ul class="selectorcontainer"> 
  <li class='has-sub selector' style='float:none;'><span class='selectorshown'>Day</span>
  <ul class='submenu' id='loglength'>
    <li value="24" onclick="getLogs(24);">day</li>
    <li value="48" onclick="getLogs(48);">2 days</li>
    <li value="72" onclick="getLogs(72);">3 days</li>
    <li value="168" onclick="getLogs(168);">week</li>
  </ul>
  </li>
</ul>
</div>
<script type="text/javascript">
  $(document).ready(function(){
    if(localStorage.loghours){
      $("#loglength").val(localStorage.loghours);
      getLogs(localStorage.loghours);
    } else {
      getLogs("24");
    }
  });
  function getLogs(hours){
    localStorage['loghours'] = hours;
    $.get("do-get-logs.php?hours="+hours, function(data) {
      $('#logtable').html(data);
    });
  }
</script>
<div class="tablecontainer">
  <table id="logtable"></table>
</div>

<?php

include("footer.php");

?>
