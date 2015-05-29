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

// A Single Slice

require_once("user.php");
require_once("header.php");
require_once("portal.php");
require_once('util.php');
require_once('pa_constants.php');
require_once('pa_client.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once("sa_constants.php");
require_once("sa_client.php");
require_once("settings.php");
require_once('logging_constants.php');
require_once('logging_client.php');
require_once('am_map.php');
require_once('status_constants.php');
require_once('maintenance_mode.php');
require_once('am_client.php');
require_once("tool-jfed.php");

function cmp($a,$b) {
  return strcmp(strtolower($a['name']),strtolower($b['name']));
}

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
unset($slice);
include("tool-lookupids.php");

$disable_buttons_str = "";

if (isset($slice_expired) && convert_boolean($slice_expired) ) {
  $disable_buttons_str = " disabled";
}

if (! isset($all_ams)) {
  $am_list = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
  $all_ams = array();
  foreach ($am_list as $am)
    {
      $single_am = array();
      $service_id = $am[SR_TABLE_FIELDNAME::SERVICE_ID];
      $single_am['name'] = $am[SR_TABLE_FIELDNAME::SERVICE_NAME];
      $single_am['url'] = $am[SR_TABLE_FIELDNAME::SERVICE_URL];
      $single_am['urn'] = $am[SR_TABLE_FIELDNAME::SERVICE_URN];
      $all_ams[$service_id] = $single_am;
    }
}

// print_r( $all_ams);

// For comparing member records by role (low roles come before high roles)
function compare_members_by_role($mem1, $mem2)
{
  $role1 = $mem1[SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE];
  $role2 = $mem2[SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE];
  if ($role1 < $role2)
    return -1;
  else if ($role1 > $role2) 
    return 1;
  else return 0;
  
}

function compare_last_names($mem1,$mem2)
{
  $parts1 = explode(" ",$mem1);
  $name1 = array_pop($parts1);
  $parts2 = explode(" ",$mem2);
  $name2 = array_pop($parts2);
  return strcmp($name1,$name2);
}

function build_agg_table_on_slicepg() 
{
     global $am_list;
     global $slice;
     global $slice_id;
     global $renew_slice_privilege;
     global $slice_expiration;
     global $slice_date_expiration;
     global $delete_slivers_disabled;
     global $slice_name;
     global $disable_buttons_str;
     global $get_slice_credential_disable_buttons;
     global $add_slivers_disabled;

     $sliver_expiration = "NOT IMPLEMENTED YET";
     $slice_status = "";

     $add_url = 'slice-add-resources-jacks.php?slice_id=' . $slice_id;
     $listres_url = 'listresources.php?slice_id='.$slice_id;

     $updating_text = "Updating status...";
     $initial_text = "Status not retrieved";

     $output = "";
     $output .= "<table id='actions_table'>";
     $output .= "<tr id='manage'><th colspan='3'>Manage Resources</th></tr>";
     $output .= "<tr class='statusButtons'><td><button onClick=\"selectAll()\">Select All</button>";
     $output .= "<button onClick=\"deselectAll()\">Deselect All</button></td>";
     $output .= "<td colspan='2'>";
     $output .= "<button title='Get summary status for resources at selected aggregates.' onClick=\"getCheckedStatus();\"><b>Ready?</b></button>";
     $output .= "<button title='Login info, etc. for resources at selected aggregates.' onClick=\"doOnChecked('$listres_url');\"><b>Resource Details</b></button>";
     $output .= "<button title='Delete resources at selected aggregates.' onClick=\"doOnChecked('confirm-sliverdelete.php?slice_id=" . $slice_id . "', true)\"><b>Delete Resources</b></button>";
     $output .= "</td></tr>\n";

     $output .= "<tr><td><span style='margin: 0 5px;'>Select Only: </span><select id='checkGroups'>";
     $output .= "<option style='display:none;'> </option>";
     $output .= "<option value='op_my_slice' class='op_my_slice'>This Slice</option>";
     $output .= "<option class='op_".SERVICE_ATTRIBUTE_COMPUTE_CAT."'>Compute</option>";
     $output .= "<option class='op_".SERVICE_ATTRIBUTE_NETWORK_CAT."'>Network</option>";
     $output .= "<option class='op_".SERVICE_ATTRIBUTE_STITCHABLE_CAT."'>Stitchable</option>";
     $output .= "<option class='op_".SERVICE_ATTRIBUTE_PROD_CAT."'>Production</option>";
     $output .= "<option class='op_".SERVICE_ATTRIBUTE_DEV_CAT."'>Development</option>";
     $output .= "<option class='op_".SERVICE_ATTRIBUTE_EXPERIMENTAL_CAT."'>Experimental</option>";
     $output .= "<option class='op_".SERVICE_ATTRIBUTE_FEDERATED_CAT."'>Federated</option>";
     $output .= "</select>";
     $output .= "</td>";
     $output .= "<td colspan='2'>";
    if ($renew_slice_privilege) {
      $output .= "<input id='renew_field_check' class='date' type='text' name='sliver_expiration' placeholder='Select Date...' ";
      $size = strlen($slice_date_expiration) + 3;
      $output .= "size=\"$size\"/>\n";
      $output .= "<button id='renew_button_check' title='Renew resource reservation at selected aggregates until the specified date' ";
      $output .= "onClick=\"confirmQuerySelected('".$slice_id."');\""; 
      $output .= "$disable_buttons_str><b>Renew Resources</b></button>\n";
    }
     $output .= "</td></tr>\n";

       $output .= "<tr><td id='am_name_list'><ul id='am_names'>";
        $output .= "<li id='g_exogeni' class='am_group'><div class='collapsable'></div><input type='checkbox' id='exogenibox' class='outer' checked='checked'><span class='checkSib'>ExoGENI<span class='countSelected'> (0)</span></span><ul style='display:none;'></ul></li>";
        $output .= "<li id='g_foam' class='am_group'><div class='collapsable'></div><input type='checkbox' id='foambox' class='outer' checked='checked'><span class='checkSib'>FOAM<span class='countSelected'> (0)</span></span><ul style='display:none;'></ul></li>";
        $output .= "<li id='g_instageni' class='am_group'><div class='collapsable'></div><input type='checkbox' id='instagenibox' class='outer' checked='checked'><span class='checkSib'>InstaGENI<span class='countSelected'> (0)</span></span><ul style='display:none;'></ul></li>";
        $output .= "<li id='g_other' class='am_group'><div class='collapsable'></div><input type='checkbox' id='otherbox' class='outer' checked='checked'><span class='checkSib'>Other<span class='countSelected'> (0)</span></span><ul style='display:none;'></ul></li>";
       $output .= "</ul>";
     $output .= "</td>";
     
     // (2) create an HTML table with one row for each aggregate
     $output .= "<td colspan='2'><div id='status_table_cont'>";
     $output .= "<div id='none-selected'>You do not have any aggregates selected.<br /> Please select aggregates on the left.</div>";
     $output .= "<table id='status_table' cols='3'>";
     
     foreach ($am_list as $am) {
	    $name = $am[SR_TABLE_FIELDNAME::SERVICE_NAME];
            $am_id = $am[SR_TABLE_FIELDNAME::SERVICE_ID];
            $am_type = lookup_attribute($am[SR_TABLE_FIELDNAME::SERVICE_URL], SERVICE_ATTRIBUTE_AM_TYPE);
            if ($am_type) {
              $output .= "<tbody id='t_".$am_id."' class='".$am_type;
            }
            else {
              $output .= "<tbody id='t_".$am_id."' class='ui_other_am";
            }
            $am_cat = lookup_attribute($am[SR_TABLE_FIELDNAME::SERVICE_URL], SERVICE_ATTRIBUTE_AM_CAT);
            if ($am_cat) {
              $output .= " ".$am_cat;
            }
            $output .= "' tabindex='-1'>";
            $output .= "<tr id='".$am_id."'>";
	    $output .= "<td colspan='1' class='am_name_field'><b>";  
      $output .= $name;
      $output .= "</b></td>"; // sliver expiration
      $output .= "<td colspan='2' class='hide status_buttons'><div>";
      $output .= "<button  id='add_button_".$am_id."' title='Add resources at this aggregate.' onClick=\"window.location='".$add_url."&am_id=".$am_id."'\" $add_slivers_disabled $disable_buttons_str><b>Add</b></button>\n";
	    $output .= "<button  id='details_button_".$am_id."' title='Login info, etc. for resources at this aggregate.' onClick=\"window.location='".$listres_url."&am_id=".$am_id."'\" $get_slice_credential_disable_buttons><b>Details</b></button>\n";
	    $output .= "<button  id='delete_button_".$am_id."' title='Delete resources at this aggregate.' onClick=\"window.location='confirm-sliverdelete.php?slice_id=".$slice_id."&am_id=".$am_id."'\" ".$delete_slivers_disabled." $disable_buttons_str><b>Delete</b></button>\n";
      $output .= "</div></td></tr>";
      

      $output .= "<tr class='notqueried'><td colspan='1' id='status_".$am_id."' class='notqueried'>";  
      $output .= $initial_text;
      $output .= "</td>";
      $output .= "<td class='hide' colspan='2'><div>";
      $output .= "<button id='reload_button_".$am_id."' title='Get summary status for resources at this aggregate.' type='button' onclick='refresh_agg_row(".$am_id.")' class='getButton' $get_slice_credential_disable_buttons>Ready?</button>";
      $output .= "<div class='renewForm'><div class='expireText'>Expires on <b><span class='renew_date' id='renew_sliver_".$am_id."'>".$initial_text."</span></b></div>";
      if ($renew_slice_privilege) {
        $output .= "<form  method='GET' action=\"do-renew.php\">";
        $output .= "<input type=\"hidden\" name=\"slice_id\" value=\"".$slice_id."\"/>\n";
        $output .= "<input type=\"hidden\" name=\"am_id\" value=\"".$am_id."\"/>\n";
        $output .= "<input type=\"hidden\" name=\"renew\" value=\"sliver\"/>\n";
        $output .= "<input id='renew_field_".$am_id."' class='date' type='text' placeholder='Select Date...' name='sliver_expiration' ";
        $size = strlen($slice_date_expiration) + 3;
        $output .= "size=\"$size\"/>\n";
        $output .= "<button id='renew_button_".$am_id."' onclick='confirmQueryTable(".$am_id.")' type='button' $disable_buttons_str title='Renew resource reservation at this aggregate until the specified date'>Renew</button>\n";
        $output .= "</form></div>";
      }   

      $output .= "</div></td></tr>";

            // (3) Get the status for this slice at this aggregate
//	    update_agg_row( am_id );
     }	
     $output .= "</table></div></td>";
     $output .= "</tr></table>";
     return $output;
}

if (! isset($sa_url)) {
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
}

if (! isset($ma_url)) {
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
}


include("jacks-app.php");
setup_jacks_slice_context();

$edit_url = 'edit-slice.php?slice_id='.$slice_id;
$add_url = 'slice-add-resources-jacks.php?slice_id=' . $slice_id;
$res_url = 'sliceresource.php?slice_id='.$slice_id;
$proj_url = 'project.php?project_id='.$slice_project_id;
$slice_own_url = "mailto:$owner_email";
//$slice_own_url = 'slice-member.php?member_id='.$slice_owner_id . "&slice_id=" . $slice_id;
$omni_url = "tool-omniconfig.php";
$flack_url = "flack.php?slice_id=".$slice_id;
$gemini_url = "gemini.php?slice_id=" . $slice_id;
$labwiki_url = 'http://labwiki.casa.umass.edu/?slice_id=' . $slice_id;

$listres_url = 'listresources.php?slice_id='.$slice_id;
$edit_slice_members_url = 'edit-slice-member.php?slice_id='.$slice_id."&project_id=".$slice_project_id;

// String to disable button or other active element
$disabled = "disabled = " . '"' . "disabled" . '"'; 

$add_slivers_privilege = $user->isAllowed(SA_ACTION::ADD_SLIVERS,
				    CS_CONTEXT_TYPE::SLICE, $slice_id);
$add_slivers_disabled = "";
if(!$add_slivers_privilege) { $add_slivers_disabled = $disabled; }

$get_slice_credential_privilege = $user->isAllowed(SA_ACTION::GET_SLICE_CREDENTIAL, 
						   CS_CONTEXT_TYPE::SLICE, $slice_id);
$get_slice_credential_disable_buttons = "";
if(!$get_slice_credential_privilege) {$get_slice_credential_disable_buttons = $disabled; }

// String to disable button or other active element
$disabled = "disabled = " . '"' . "disabled" . '"'; 

$delete_slivers_privilege = $user->isAllowed(SA_ACTION::DELETE_SLIVERS,
				    CS_CONTEXT_TYPE::SLICE, $slice_id);
$delete_slivers_disabled = "";
if(!$delete_slivers_privilege) { $delete_slivers_disabled = $disabled; }

$renew_slice_privilege = $user->isAllowed(SA_ACTION::RENEW_SLICE,
				    CS_CONTEXT_TYPE::SLICE, $slice_id);
$renew_disabled = "";
if(!$renew_slice_privilege) { $renew_disabled = $disabled; }

$lookup_slice_privilege = $user->isAllowed(SA_ACTION::LOOKUP_SLICE, 
				    CS_CONTEXT_TYPE::SLICE, $slice_id);

if(!$lookup_slice_privilege) {
  $_SESSION['lasterror'] = 'User has no privileges to view slice ' . $slice_name;
  relative_redirect('home.php');
}

// determine maximum date of slice renewal
$renewal_days = $portal_max_slice_renewal_days;
$project_expiration = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRATION];
if ($project_expiration) {
  $project_expiration_dt = new DateTime($project_expiration);
  $now_dt = new DateTime();
  $difference = $project_expiration_dt->diff($now_dt);
  $renewal_days = $difference->days;
  // take the minimum of the two as the constraint
  $renewal_days = min($renewal_days, $portal_max_slice_renewal_days);
}

// Code to set up jfed button
$jfedret = get_jfed_strs($user);
$jfed_script_text = $jfedret[0];
$jfed_button_start = $jfedret[1];
$jfed_button_part2 = $jfedret[2];

show_header('GENI Portal: Slices', $TAB_SLICES);
include("tool-breadcrumbs.php");
include("tool-showmessage.php");

include("tabs.js");

// Finish jFed setup
print $jfed_script_text;

?>

<!-- Jacks JS and App CSS -->

<!-- This belongs in the header, probably -->
<script>
var jacks_app_expanded = false;
var slice_uid = "<?php echo $slice_id ?>";
var renew_slice_privilege= "<?php echo $renew_slice_privilege?>";
var slice_expiration= "<?php echo $slice_expiration?>";
var slice_date_expiration= "<?php echo $slice_date_expiration?>";
var sliver_expiration= "NOT IMPLEMENTED YET";
var delete_slivers_disabled= "<?php echo $delete_slivers_disabled ?>";
var slice_status= "";
var slice_name= "<?php echo $slice_name?>";
var all_ams= '<?php echo json_encode($all_ams) ?>';
var slice_ams= <?php echo json_encode($slice_ams) ?>;
var max_slice_renewal_days = "+" + "<?php echo $renewal_days ?>" + "d";
var ui_exogeni_am = "<?php echo SERVICE_ATTRIBUTE_EXOGENI_AM ?>";
var ui_foam_am = "<?php echo SERVICE_ATTRIBUTE_FOAM_AM ?>";
var ui_instageni_am = "<?php echo SERVICE_ATTRIBUTE_INSTAGENI_AM ?>";
var ui_other_am = "<?php echo SERVICE_ATTRIBUTE_OTHER_AM ?>";
<?php include('status_constants_import.php'); ?>

function confirmQuery() {
  if ($('#datepicker').val()) {
    var count = slice_ams.length;
    var i;
    // Modifying the form html resets the values to default. Need this saved for later.
    var dateVal = $('#datepicker').val();

    if ($("#sliceslivers").is(':checked') && count > 0) {
      var result = true;
      if (count > 10) {
        result = confirm("This action will renew resources at "
                         + count
                         + " aggregates and may take several minutes.");
      }

      if (result) {
        var myform = $("#renewform");
        $.each(slice_ams, function(index, value) {
          myform.html(myform.html()+'<input type="hidden" name="am_id[]" value="'+value+'"/>');
          $('#datepicker').val(dateVal);
        });
        myform.submit();
      }
    } else {
      $('#sliceonly').prop('checked',true);
      var myform = $("#renewform");
      myform.submit();
    }
  }
}

function confirmQueryTable(am_id) {
  myform = $('#t_'+am_id+' .renewForm form');
  if ($('#renew_field_'+am_id).val()) {
    myform.submit();
  }
}

function confirmQuerySelected(slice_id) {
  if ($('#renew_field_check').val()) {
    doOnRenew('do-renew.php?slice_id='+slice_id+'&renew=sliver&Renew=Renew');
  }
}
</script>
<script src="amstatus.js"></script>
<!--<script>
$(document).ready(build_agg_table_on_slicepg());
</script>
-->

<!--// deals with the checkboxes to display which aggregates we want to see
    // use a javascript class selector (".class") - looks funny because I
    // am concatenating a js constant to create the class name
    // there is a function for every checkbox
-->
<script>
$(document).ready(function() {
    prepareList();
    prepareEvents();
    $.each(slice_ams, function(index, value) {

      $('#t_'+value).addClass('my_slice');
    });
    $('.op_my_slice').prop('selected',true);
    $('#checkGroups').trigger('change');
});
</script>


<?php 
print "<h1>GENI Slice: " . "<i>" . $slice_name . "</i>" . " </h1>\n";
print "<div style=''><p id='portalhelp'>Need help? Look at the <a href='help.php'>Portal Help</a> or <a href='http://groups.geni.net/geni/wiki/GENIGlossary'>GENI Glossary</a>.</p></div>";
if (isset($slice_expired) && convert_boolean($slice_expired) ) {
   print "<p class='warn'>This slice is expired!</p>\n";
}

// FIXME: Set add_slivers_disabled if in_lockdown_mode or otherwise disable 'Add Resources'?
// FIXME: Disable launch flack if in lockdown mode?

print "<table id='sliceActions' cols='4'>\n";
print "<tr><th colspan='4'>Slice Actions</th></tr>\n";
print "<tr><td colspan='4'>\n";
print "<button onClick=\"window.location='$add_url'\" $add_slivers_disabled $disable_buttons_str><b>Add Resources</b></button>\n";
print "<a href='#manage' style='border-bottom:0;'><button><b>Manage Resources</b></button></a>\n";
print "<a href=\"#members\" style=\"border-bottom:0;\"><button><b>Manage Slice Members</b></button></a>";
print "<a href=\"#identifiers\" style=\"border-bottom:0;\"><button><b>Slice Identifiers</b></button></a>";

print "<a href=\"#recent_actions\" style=\"border-bottom:0;\"><button><b>Recent Actions</b></button></a>";
print "</td></tr>\n";

print "<tr><th colspan='4'>Renew Slice</th></tr>\n";

print "<tr>\n";
/* Renew */
if ($project_expiration) {
  $project_exp_print = dateUIFormat($project_expiration);
  $project_line = "Project expires on <b>$project_exp_print</b><br>";
} else {
  $project_line = "Project does not have an expiration date<br>";
}
if ($renew_slice_privilege) {
  print "<td id='renewtext' colspan='1' style=\"width:320px;\">\n";
} else {
  print "<td colspan='4'>\n";
}
print $project_line;
print "Slice expires on <b>$slice_expiration</b>";
print "</td>\n";


if ($renew_slice_privilege) {
  print "<td id='renewcell' colspan='3'>\n";
  print "<form id='renewform' method='GET' action=\"do-renew.php\">";
  print "<table id='renewtable'><tr><td>";
  print "Renew ";
  print "</td><td>";
  print "<div>";
  print "<input type='radio' id='sliceonly' name='renew' value='slice'>slice only<br>";
  print "<input type='radio' id='sliceslivers' name='renew' value='slice_sliver' checked>slice & known resources";
  print "</div>";
  print "</td><td>";
  print " until <br/>";
  print "</td>";
  print "<td id='renewbutton'>";
  print "<input type=\"hidden\" name=\"slice_id\" value=\"$slice_id\"/>\n";
  print "<input class='date' type='text' name='sliver_expiration' id='datepicker'";
  $size = strlen($slice_date_expiration) + 3;
  print " size=\"$size\" value=\"$slice_date_expiration\"/>\n";
  print "<button type='button' onclick='confirmQuery()' name= 'Renew' value='Renew' title='Renew until the specified date' $disable_buttons_str>Renew</button>\n";
  print "</td></tr></table>";
  print "</form>\n";
  print "</td>\n";
}
print "</tr>\n";

?>
<script>
  $(function() {
    function focusParent(am_id) {
      $('#t_'+am_id.data['am_id']).addClass('activeBody');
    }
    function removeActiveBodies(a, b) {
      $('tbody.activeBody').focus().removeClass('activeBody');
    }
    // minDate = 1 will not allow today or earlier, only future dates.
    $( "#datepicker" ).datepicker({ dateFormat: "yy-mm-dd", minDate: slice_date_expiration, maxDate: max_slice_renewal_days });
    $( "#renew_field_check" ).datepicker({ dateFormat: "yy-mm-dd", minDate: 1, maxDate: slice_date_expiration });
<?php
     foreach ($am_list as $am) {
      $name = $am[SR_TABLE_FIELDNAME::SERVICE_NAME];
            $am_id = $am[SR_TABLE_FIELDNAME::SERVICE_ID];
    //    $( ".date" ).datepicker({ dateFormat: "yy-mm-dd", minDate: 1,  maxDate: slice_date_expiration });
      print "    $( \"#renew_field_$am_id\" ).datepicker({ dateFormat: \"yy-mm-dd\", minDate: 1,  maxDate: slice_date_expiration, onClose: removeActiveBodies });\n";
      print "    $( \"#status_table #renew_field_$am_id\" ).bind('click', { am_id: '$am_id' }, focusParent);\n";
     }
?>
  });

</script>
<?php



print "<tr><th colspan='4'>Slice Tools</th></tr>\n";
/* Tools */
print "<tr><td colspan='4'>\n";
/* print "To use a command line tool:<br/>"; */
$hostname = $_SERVER['SERVER_NAME'];
print "<button $add_slivers_disabled onClick=\"window.open('$flack_url')\" $disable_buttons_str><b>Flack <br/>(deprecated)</b> </button>\n";

  print "<button $add_slivers_disabled onClick=\"window.open('$gemini_url')\" $disable_buttons_str><b>GENI Desktop</b></button>\n";

  print "<button $add_slivers_disabled onClick=\"window.open('$labwiki_url')\" $disable_buttons_str><b>LabWiki</b></button>\n";

print "<button onClick=\"window.location='$omni_url'\" $add_slivers_disabled $disable_buttons_str><b>Omni</b></button>\n";
//print "<button disabled='disabled'><b>Download GUSH Config</b></button>\n";

// Show a jfed button if there wasn't an error generating it
if (! is_null($jfed_button_start)) {
  print $jfed_button_start . getjFedSliceScript($slice_urn) . $jfed_button_part2 . " $disable_buttons_str><b>jFed</b></button>";
}

$map_url = "slice-map-view.php?slice_id=$slice_id";
print "<button onClick=\"window.location='$map_url'\" $disable_buttons_str><b>Geo Map</b></button>\n";

print "</td>\n";
print "</tr>\n";



print "</table>\n";

/* print "<h2>Slice Operational Monitoring</h2>\n"; */
/* print "<table>\n"; */
/* print "<tr><td><b>Slice data</b></td><td><a href='https://gmoc-db.grnoc.iu.edu/protected-openid/index.pl?method=slice_details;slice=".$slice_urn."'>Slice $slice_name</a></td></tr>\n"; */
/* print "</table>\n"; */


// ----
// Now show slice / sliver status

print "<h2></h2>\n";

?>


<div id='tablist'>
  <ul class='tabs'>
    <li><a href='#jacks-app'>Graphical View</a></li>
    <li><a href='#status_table_div'>Aggregate View</a></li>
    <li><a href='#geo_view_div'>Geographic View</a></li>
  </ul>
</div>

<?php

// Grab all rspecs 
$all_rspecs = fetchRSpecMetaData($user);
usort($all_rspecs, "cmp");

// JACKS-APP STUFF //
print "<div id='jacks-app-div'>";
print "<table id='jacks-app'><tbody><tr>";
print "<th>Manage Resources</th></tr><tr><td><div id='jacks-app-container'>";
print build_jacks_viewer();
print "</div></td></tr></tbody></table>";
print "</div>";

//include("jacks-editor-app.php");
//print "<table id='jacks-editor-app'><tbody><tr>";
//print "<th>Add Resources</th></tr><tr><td><div id='jacks-editor-app-container'>";
//print build_jacks_editor();
//print "</div></td></tr></tbody></table>";

?>

<link rel="stylesheet" type="text/css" href="slice-table.css" />
<link rel="stylesheet" type="text/css" href="jacks-app.css" />
<link rel="stylesheet" type="text/css" href="jacks-editor-app.css" />

<script src="jacks-lib.js"></script>
<script src="portal-jacks-app.js"></script>
<script src="portal-jacks-editor-app.js"></script>
<script src="<?php echo $jacks_stable_url;?>"></script>

<script>

  // AMs that the Portal says there are resources at.
  var jacks_slice_ams = <?php echo json_encode($slice_ams) ?>;
  var jacks_all_ams = <?php echo json_encode($all_ams) ?>;
  var jacks_slice_id = <?php echo json_encode($slice_id) ?>;
  var jacks_slice_name = <?php echo json_encode($slice_name) ?>;
  var jacks_slice_urn= <?php echo json_encode($slice_urn) ?>;
  var jacks_slice_expiration = <?php echo json_encode($slice_expiration) ?>;

  var jacks_slice_info = {slice_id : jacks_slice_id, 
			  slice_name : jacks_slice_name,
			  slice_urn : jacks_slice_urn, 
			  slice_expiration : jacks_slice_expiration};

  var jacks_user_name = <?php echo json_encode($user->username) ?>;
  var jacks_user_urn = <?php echo json_encode($user->urn) ?>;
  var jacks_user_id = <?php echo json_encode($user->account_id) ?>;

  var jacks_user_info = {user_name : jacks_user_name,
			 user_urn : jacks_user_urn,
			 user_id : jacks_user_id};

  var jacks_all_rspecs = <?php echo json_encode($all_rspecs) ?>;

  var slice_id = <?php echo json_encode($slice_id) ?>;

  var jacks_enable_buttons = true;
  var jacksEditorApp = null;

  $(document).ready(function() {

      // This funciton will start up a Jacks viewer, get the status bar going
      // and set up all of the button clicks.
      var jacksApp = new JacksApp('#jacks-pane', '#jacks-status', 
				  '#jacks-status-history', '#jacks-buttons',
				  jacks_slice_ams, jacks_all_ams, 
				  jacks_slice_info,
				  jacks_user_info,
				  portal_jacks_app_ready);
 
      jacksApp.hideStatusHistory();
    });


//function  portal_jacks_combo_app_ready(ja, ja_input, ja_output) {
//  portal_jacks_app_ready(ja, ja_input, ja_output);
//  // This funciton will start up a Jacks viewer, get the status bar going
//  // and set up all of the button clicks.
//  jacksEditorApp = new JacksEditorApp('#jacks-editor-pane', 
//				      '#jacks-editor-status', 
//				      '#jacks-editor-buttons',
//				      jacks_slice_ams, jacks_all_ams, 
//				      jacks_all_rspecs,
//				      jacks_slice_info,
//				      jacks_user_info,
//				      jacks_enable_buttons, 
//				      null, null,
//				      portal_jacks_editor_app_ready,
//				      JacksEditorApp.prototype.postRspec
//				      );
//
//  jacksEditorApp.setJacksViewer(ja);
//  ja.setJacksEditor(jacksEditorApp);
//  if(jacks_slice_ams.length == 0) {
//    // Initially hide the viewer if there are no resources
//    ja.hide();
//  } else {
//    // Initally hide the editor if there are resources
//    jacksEditorApp.hide(); 
//  }
//};
//

</script>

<?php

// END JACKS-APP STUFF //

  $slice_status='';

  print "<div id='status_table_div'>\n";
  print build_agg_table_on_slicepg();
  print "</div>\n";


  // Slice geo view
  print "<div id='geo_view_div' >\n";
  echo "<table style=\"margin-left: 0px;width:100%\"><tr><td style=\"padding: 0px;margin: 0px\" class='map'>";
  include('slice_map.html');
  echo "</td></tr></table>";
  print "</div>";

?>

<script>
// Make sure the height is not 100% but an actual size
// Make sure width is set to 100% at load time 
 //   (don't know, some times it isn't)
$(document).ready(function() {
    $("#map1").height(400); 
    $("#map1").width('100%'); 
  });

</script>


<?php

// --- End of Slice and Sliver Status table

print "<h2 id='members'>Slice Members</h2>";
?>


<p>Slice members will be able to login to resources reserved <i>in the future</i> if:</p>
<ul>
 <li>The resources were reserved directly through the portal (by clicking <b>Add Resources</b> on the slice page) or using omni 2.5 or newer, and</li>
 <li>The slice member has uploaded an ssh public key.</li>
</ul>

<table>
	<tr>
		<th>Slice Member</th>
		<th>Role</th>
	</tr>
	<?php
usort($members, 'compare_members_by_role');
// Write each row in the project member table
// Sort alphabetically by role

$member_lists = array();
$member_lists[1] = array();
$member_lists[2] = array();
$member_lists[3] = array();
$member_lists[4] = array();

foreach($members as $member) {

  $member_id = $member[SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
  //  error_log("MEMBER = " . print_r($member_user, true));
  $member_name = $member_names[$member_id];
  $member_ids[$member_name] = $member_id;
  $member_role_index = $member[SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE];
  $member_role = $CS_ATTRIBUTE_TYPE_NAME[$member_role_index];
  $member_lists[$member_role_index][] = $member_name;

}

// Keep member ID's by name (inverting $member_names array)
$member_ids_by_name = array();
foreach ($member_names as $member_id => $member_name) {
  $member_ids_by_name[$member_name] = $member_id;
}

// Lookup all members by ID (getting MA Member objects back)
$member_ids = array();
foreach ($members as $member) {
  $member_id = $member[SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
  $member_ids[] = $member_id;
}
$members_info = $user->fetchMembersNoIdentity($member_ids);

// Keep member info by ID
$members_info_by_id = array();
foreach ($members_info as $member_info) {
  $member_id = $member_info->member_id;
  $members_info_by_id[$member_id] = $member_info;
}

foreach ($member_lists as $member_role_index => $member_names) {
  usort($member_names, 'compare_last_names');
  foreach ($member_names as $member_name) {
    $member_role = $CS_ATTRIBUTE_TYPE_NAME[$member_role_index];
    $member_id = $member_ids_by_name[$member_name];
    $member_info = $members_info_by_id[$member_id];
    $member_email = $member_info->email_address;
    $member_url = "mailto:$member_email";

    print "<tr><td><a href=$member_url>$member_name</a></td><td>$member_role</td></tr>\n";

    /*
    print "<tr><td><a href=\"slice-member.php?slice_id=" . $slice_id . 
      "&member_id=$member_id\">$member_name</a></td>" . 
      "<td>$member_role</td></tr>\n";
    */
  }
}

?>
</table>

<?php
$edit_members_disabled = "";
if (!$user->isAllowed(SA_ACTION::ADD_SLICE_MEMBER, CS_CONTEXT_TYPE::SLICE, $slice_id) || $in_lockdown_mode) {
  $edit_members_disabled = $disabled;
}
echo "<p><button $edit_members_disabled onClick=\"window.location='$edit_slice_members_url'\"><b>Edit Slice Membership</b></button></p>";

print "<h2 id='identifiers'>Slice Identifiers</h2>";
// Slice Identifers table
print "<table>\n";
print "<tr><th colspan='2'>Slice Identifiers (public)</th></tr>\n";
print "<tr><td class='label'><b>Name</b></td><td>$slice_name</td></tr>\n";
print "<tr><td class='label'><b>Project</b></td><td><a href='$proj_url'>$project_name</a></td></tr>\n";
print "<tr><td class='label deemphasize'><b>URN</b></td><td  class='deemphasize'>$slice_urn</td></tr>\n";
print "<tr><td class='label'><b>Creation</b></td><td>$slice_creation</td></tr>\n";
print "<tr><td class='label'><b>Description</b></td><td>$slice_desc ";
// If this is always disabled, just don't show it.
//echo "<button disabled=\"disabled\" onClick=\"window.location='$edit_url'\"><b>Edit</b></button>";
print "</td></tr>\n";
print "<tr><th colspan='2'>Contact Information</th></tr>\n";
print "<tr><td class='label'><b>Slice Owner</b></td><td><a href=$slice_own_url>$slice_owner_name</a></td></tr>\n";
//print "<tr><td class='label'><b>Slice Owner</b></td><td><a href=$slice_own_url>$slice_owner_name</a> <a href='mailto:$owner_email'>e-mail</a></td></tr>\n";
print "</table>\n";
// ---

?>

<h2 id="recent_actions">Recent Slice Actions</h2>
<p>Showing logs for the last 
<select onchange="getLogs(this.value);">
  <option value="24">day</option>
  <option value="48">2 days</option>
  <option value="72">3 days</option>
  <option value="168">week</option>
</select>
</p>

<script type="text/javascript">
  $(document).ready(function(){ getLogs(24); });
  function getLogs(hours){
    url = "do-get-logs.php?hours="+hours+"&slice_id=" + <?php echo "\"" . $slice_id . "\""; ?>; 
    $.get(url, function(data) {
      $('#log_table').html(data);
    });
  }
</script>

<table id="log_table"></table>

<?php
include("footer.php");
?>
