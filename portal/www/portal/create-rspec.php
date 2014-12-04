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
require_once("settings.php");
require_once 'geni_syslog.php';

function cmp($a,$b) {
  return strcmp(strtolower($a['name']),strtolower($b['name']));
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
?>
<script>
function validateSubmit()
{
  f1 = document.getElementById("f1");
  rspecField = document.getElementById("rspec_raw");
  am = document.getElementById("agg_chooser");
  
  if (rspecField.value && am.value) {
    f1.submit();
    return true;
  } else if (rspecField.value) {
    alert("Please select an Aggregate.");
    return false;
  }
  alert ("Please select a Resource Specification (RSpec).");
  return false;
}
</script>
<script src="<?php echo $jacks_stable_url;?>"></script>
<script>
var thisInstance;
var jacksInput;
var readyToSubmit = false;

function grabRspec()
{
	if (thisInstance != null && jacksInput != null) {
    jacksInput.trigger('fetch-topology');
	}
}
function finalizeRspec() 
{
  if (thisInstance != null && jacksInput != null) {
    readyToSubmit = true;
    jacksInput.trigger('fetch-topology');
  }
}
function postRspec(rspec) 
{
  if (!readyToSubmit) {
    $('.rawRspec').text(rspec);
    $('.rawRspec').attr('style','');
  }
  else {
    $('#rspec_raw').val(rspec);
    validateSubmit();
    readyToSubmit = false;
  }
}
</script>
<?php
$rspec = '<rspec></rspec>';


if (array_key_exists('rspec_id', $_REQUEST)) {
  $rspec = fetchRSpecById($rspec_id);
  $rspec = str_replace(array("\n", "\r", "\t"), '', $rspec);
  $rspec = trim(str_replace('"', "'", $rspec));

}
print "<script>var rspec=\"$rspec\"; console.log(rspec);</script>";

include("tool-breadcrumbs.php");
include("tool-showmessage.php");
print "<h1>Add resources to GENI Slice: " . "<i>" . $slice_name . "</i>" . "</h1>\n";
print "<p>To add resources, draw your topology below using Jacks.</p>";
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

print '<div id="jacksContainer" class="jacks"></div>';

print "<script>
		$(document).ready(function() {
      thisInstance = new window.Jacks({
        mode: 'editor',
        source: 'rspec',
        size: { x: 793, y: 403},
        root: '#jacksContainer',
        canvasOptions: {
        images: [{
          name: 'foo',
          id: 'bar'
        },
        {
          name: 'UBUNTU12-64-STD',
          id: 'urn:publicid:IDN+emulab.net+image+emulab-ops//UBUNTU12-64-STD'
        }]
      },
        readyCallback: function (input, output) {
        	jacksInput = input;
          input.trigger('change-topology',
                        [{ rspec: rspec }]);
        	output.on('fetch-topology', function(rspecs) {
        		if (rspecs.length > 0 && rspecs[0].rspec)
        		  {
        		    postRspec(rspecs[0].rspec);
        		  }
        	}); 
        }
      });
    });
</script>";

print '<button onClick="grabRspec();">Grab Rspec</button>';
print '<p class="rawRspec" style="display:none"></p>';
print '<form id="f1" action="createsliver.php" method="post" enctype="multipart/form-data">';
show_am_chooser();
print '<input type="hidden" name="slice_id" value="' . $slice_id . '"/>';
print '<input type="hidden" name="rspec_jacks" id="rspec_raw"/>';
print '</form>';

print ("<p><button onClick=\"");
print ("finalizeRspec();\">"
       . "<b>Reserve Resources</b></button>\n");
print "<button onClick=\"history.back(-1)\">Cancel</button>\n";
print '</p>';

include("footer.php");
?>
