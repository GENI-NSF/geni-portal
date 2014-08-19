<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2014 Raytheon BBN Technologiesc
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
require_once('sr_constants.php');
require_once('sr_client.php');
require_once("sa_constants.php");
require_once("sa_client.php");
require_once 'geni_syslog.php';

function cmp($a,$b) {
  return strcmp(strtolower($a['name']),strtolower($b['name']));
}

function show_rspec_chooser($user) {
  $all_rmd = fetchRSpecMetaData($user);
  usort($all_rmd,"cmp");
  print "<p><b>Choose Resources:</b> \n" ;
  print "<select name=\"rspec_id\" id=\"rspec_select\""
    . " onchange=\"rspec_onchange()\""
    . ">\n";
  echo '<option value="" title="Choose RSpec" selected="selected">Choose RSpec...</option>';
  echo '<option value="PRIVATE" disabled>---Private RSpecs---</option>';
  foreach ($all_rmd as $rmd) {
    if ($rmd['visibility']==="private") {
      $rid = $rmd['id'];
      $rname = $rmd['name'];
      $rdesc = $rmd['description'];
      //    error_log("BOUND = " . $rmd['bound']);
      $enable_agg_chooser=1;
      //UNCOMMENT NEXT LINE WHEN WE WANT BOUND RSPEC TO BE HANDLED CORRECTLY AGAIN
      //-->    if ($rmd['bound'] == 't') { $enable_agg_chooser = 0; }
      //    error_log("BOUND = " . $enable_agg_chooser);
      print "<option value=\"$rid\" title=\"$rdesc\" bound=\"$enable_agg_chooser\">$rname</option>\n";
    }
  }
  echo '<option value="PUBLIC" disabled>---Public RSpecs---</option>';
  foreach ($all_rmd as $rmd) {
    if ($rmd['visibility']==="public") {
      $rid = $rmd['id'];
      $rname = $rmd['name'];
      $rdesc = $rmd['description'];
      //    error_log("BOUND = " . $rmd['bound']);
      $enable_agg_chooser=1;
      //UNCOMMENT NEXT LINE WHEN WE WANT BOUND RSPEC TO BE HANDLED CORRECTLY AGAIN
      //-->    if ($rmd['bound'] == 't') { $enable_agg_chooser = 0; }
      //    error_log("BOUND = " . $enable_agg_chooser);
      print "<option value=\"$rid\" title=\"$rdesc\" bound=\"$enable_agg_chooser\">$rname</option>\n";
    }
  }
  
  //  print "<option value=\"paste\" title=\"Paste your own RSpec\">Paste</option>\n";
  //  print "<option value=\"upload\" title=\"Upload an RSpec\">Upload</option>\n";
  print "</select>\n";

  print '<br><button onClick="sendRspecToJacks();">Edit Selected RSpec With Jacks</button>';
  print "<br>or <a href=\"rspecupload.php\">upload your own RSpec to the above list</a>.";
//  print " or <button onClick=\"window.location='rspecupload.php'\">";
//  print "upload your own RSpec</button>.";
  // RSpec entry area
  print '<span id="paste_rspec" style="display:none;vertical-align:top;">'
    . PHP_EOL;
  print '<label for="paste_rspec2">Resource Specification (RSpec):</label>' . PHP_EOL;
  print "<textarea id=\"paste_rspec2\" name=\"rspec\" rows=\"10\" cols=\"40\""
    //. " style=\"display: none;\""
    . "></textarea>\n";
  print '</span>' . PHP_EOL;

  // RSpec upload
  print '<span id="upload_rspec" style="display:none;">'
    . PHP_EOL;
  print '<label for="rspec_file">Resource Specification (RSpec) File:</label>' . PHP_EOL;
  print '<input type="file" name="rspec_file" id="rspec_file" />' . PHP_EOL;
  print '</span>' . PHP_EOL;
  
  print "</p>";
}

function show_am_chooser() {
  $all_aggs = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
  print "<p><b>Choose Aggregate:</b> \n";
  print '<select name="am_id" id="agg_chooser">\n';
  echo '<option value="" title = "Choose an Aggregate" selected="selected">Choose an Aggregate...</option>';
  foreach ($all_aggs as $agg) {
    $aggid = $agg['id'];
    $aggname = $agg['service_name'];
    $aggdesc = $agg['service_description'];
    print "<option value=\"$aggid\" title=\"$aggdesc\">$aggname</option>\n";
  }
  print "</select>\n";
  
  print "</p>";
}

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$mydir = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME);
add_js_script($mydir . '/slice-add-resources.js');

$slice_id = "None";
$slice_name = "None";
include("tool-lookupids.php");

if (isset($slice)) {
  $slice_urn = $slice[SA_Argument::SLICE_URN];
}

if (isset($slice_expired) && convert_boolean($slice_expired)) {
  if (! isset($slice_name)) {
    $slice_name = "";
  }
  $_SESSION['lasterror'] = "Slice " . $slice_name . " is expired.";
  relative_redirect('slices.php');
}

if (!$user->isAllowed(SA_ACTION::ADD_SLIVERS, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
  relative_redirect('home.php');
}
$keys = $user->sshKeys();

show_header('GENI Portal: Slices', $TAB_SLICES);
include("tool-breadcrumbs.php");
include("tool-showmessage.php");

// Define list of all known AM's
if (! isset($all_ams)) {
  $am_list = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
  $all_ams = array();
  foreach ($am_list as $am) 
  {
    $single_am = array();
    $service_id = $am[SR_TABLE_FIELDNAME::SERVICE_ID];
    $single_am['name'] = $am[SR_TABLE_FIELDNAME::SERVICE_NAME];
    $single_am['url'] = $am[SR_TABLE_FIELDNAME::SERVICE_URL];
    $all_ams[$service_id] = $single_am;
  }   
}

$slivers = lookup_sliver_info_by_slice($sa_url, $user, $slice_urn);
//find aggregates to be able to return just am_id
$all_aggs = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
$aggs_with_resources = Array();

//do the comparison and find ams
foreach($slivers as $sliver)
  {
    foreach($all_aggs as $agg)
      {
	if($sliver[SA_SLIVER_INFO_TABLE_FIELDNAME::SLIVER_INFO_AGGREGATE_URN] == $agg[SR_TABLE_FIELDNAME::SERVICE_URN])
	  {
	    $aggs_with_resources[] = $agg[SR_TABLE_FIELDNAME::SERVICE_ID];
	    break;
	  }
      }
  }
//return unique ids
$slice_ams = array_unique($aggs_with_resources, SORT_REGULAR);

// Grab all rspecs 
$all_rspecs = fetchRSpecMetaData($user);
usort($all_rspecs, "cmp");

// JACKS-APP stuff
include("jacks-editor-app.php");
print build_jacks_editor();

?>


<!-- Jacks JS and App CSS -->
<link rel="stylesheet" type="text/css" href="jacks-editor-app.css" />
<script src="//www.emulab.net/protogeni/jacks-stable/js/jacks"></script>

<script src="portal-jacks-editor-app.js"></script>
<script>
  // AMs that the Portal says there are resources at.
  var jacks_slice_ams = <?php echo json_encode($slice_ams) ?>;
  var jacks_all_ams = <?php echo json_encode($all_ams) ?>;

  var jacks_all_rspecs = <?php echo json_encode($all_rspecs) ?>;

  var jacks_slice_id = <?php echo json_encode($slice_id) ?>;
  var jacks_slice_name = <?php echo json_encode($slice_name) ?>;

  var jacks_slice_info = {slice_id : jacks_slice_id, 
			  slice_name : jacks_slice_name};

  var jacks_user_name = <?php echo json_encode($user->username) ?>;
  var jacks_user_urn = <?php echo json_encode($user->urn) ?>;
  var jacks_user_id = <?php echo json_encode($user->account_id) ?>;

  var jacks_user_info = {user_name : jacks_user_name,
			 user_urn : jacks_user_urn,
			 user_id : jacks_user_id};

  // This funciton will start up a Jacks viewer, get the status bar going
  // and set up all of the button clicks.
  var jacksEditorApp = new JacksEditorApp('#jacks-pane', '#jacks-status', 
					  '#jacks-buttons',
					  jacks_slice_ams, jacks_all_ams, 
					  jacks_all_rspecs,
					  jacks_slice_info,
					  jacks_user_info,
					  portal_jacks_editor_app_ready);
   portal_jacks_editor_app_verbose=true;
   jacksEditorApp.verbose=true;

</script>


<?php
// END JACKS-APP STUFF //

  /*
print "<h1>Add resources to GENI Slice: " . "<i>" . $slice_name . "</i>" . "</h1>\n";
print "<p>To add resources you need to choose a Resource Specification file (RSpec).</p>";
// Put up a warning to upload SSH keys, if not done yet.
if (count($keys) == 0) {
  // No ssh keys are present.
  print "<p class='warn'>No ssh keys have been uploaded. ";
  print ("Please <button onClick=\"window.location='uploadsshkey.php'\">"
         . "Upload an SSH key</button> or <button " .
	 "onClick=\"window.location='generatesshkey.php'\">Generate and "
	 . "Download an SSH keypair</button> to enable logon to nodes.</p>\n");
  print "<br/>\n";
}

print "<p><button onClick=\"window.location='create-rspec.php?slice_id=" . $slice_id . "'\">"
    . "Create New RSpec</button></p>\n";
print "<p><button onClick=\"window.location='rspecs.php'\">"
    . "View Available RSpecs</button></p>\n";

print '<form id="f1" action="" method="post" enctype="multipart/form-data">';
show_rspec_chooser($user);
print  '<p><label for="file">or import RSpec from file:</label>';
print  '<input type="file" name="rspec_selection" id="rspec_selection" /></p>';
print "<p><i>Note: This RSpec selection is for this reservation only and will not be saved by the portal.</i></p>";
show_am_chooser();
print '<input type="hidden" name="slice_id" value="' . $slice_id . '"/>';
print '</form>';

print ("<p><button onClick=\"");
print ("validateSubmit();\">"
       . "<b>Reserve Resources</b></button>\n");
print "<button onClick=\"history.back(-1)\">Cancel</button>\n";
print '</p>';

  */


include("footer.php");
?>
