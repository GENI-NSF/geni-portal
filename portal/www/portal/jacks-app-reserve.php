 <?php
//----------------------------------------------------------------------
// Copyright (c) 2011-2015 Raytheon BBN Technologies
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
?>
<?php
require_once("settings.php");
require_once('portal.php');
require_once("user.php");
require_once("file_utils.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("am_client.php");
require_once("am_map.php");
require_once("sa_client.php");
require_once("print-text-helpers.php");
require_once("logging_client.php");
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}


function no_slice_error() {
  header('HTTP/1.1 404 Not Found');
  print 'No slice id specified.';
  exit();
}
function no_rspec_error() {
  header('HTTP/1.1 404 Not Found');
  if (array_key_exists("rspec_id", $_REQUEST)) {
    $rspec_id = $_REQUEST['rspec_id'];
    print "Invalid resource specification id \"$rspec_id\" specified.";
  } else {
    print 'No resource specification id specified.';
  }
  exit();
}
function no_am_error() {
  header('HTTP/1.1 404 Not Found');
  if (array_key_exists("am_id", $_REQUEST)) {
    $am_id = $_REQUEST['am_id'];
    print "Invalid aggregate manager id \"$am_id\" specified.";
  } else {
    print 'No aggregate manager id specified.';
  }
  exit();
}

if (! count($_REQUEST)) {
  // No parameters. Return an error result?
  // For now, return nothing.
  no_slice_error();
}
unset($slice);
unset($rspec);
unset($am);
include("tool-lookupids.php");
if (! isset($slice)) {
  no_slice_error();
}

if(array_key_exists('rspec_selection', $_FILES)) {
  $local_rspec_file = $_FILES['rspec_selection']['tmp_name'];
  $local_rspec_file = trim($local_rspec_file);
  $temp_rspec_file = null;
  if(strlen($local_rspec_file) > 0) {
    $rspec = file_get_contents($local_rspec_file);
    $temp_rspec_file = writeDataToTempFile($rspec, 'rspec-');
  }
}
else if(array_key_exists('rspec_jacks', $_REQUEST)) {
	$temp_rspec_file = null;
  $local_rspec_file = $_REQUEST['rspec_jacks'];
  if(strlen($local_rspec_file) > 0) {
    $rspec = $local_rspec_file;
    $temp_rspec_file = writeDataToTempFile($rspec, 'rspec-');
  }
}

if(array_key_exists('ji', $_REQUEST)) {
	$ji = $_REQUEST['ji'];
}

if (isset($slice_expired) && convert_boolean($slice_expired)) {
  if (! isset($slice_name)) {
    $slice_name = "";
  }
  $_SESSION['lasterror'] = "Slice " . $slice_name . " is expired.";
  relative_redirect('slices.php');
}

if(!$user->isAllowed(SA_ACTION::LOOKUP_SLICE, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
  relative_redirect('home.php');
}

if (! isset($am) || is_null($am)) {
  no_am_error();
}

// Get an AM
$am_url = $am[SR_ARGUMENT::SERVICE_URL];
$AM_name = am_name($am_url);

$header = "Creating Sliver on slice: $slice_name";
?>

<script type="text/javascript" id="runscript">
var slicet= "<?php echo $slice_id ?>";
var am_idt= "<?php echo $am_id ?>";
var rspec_filet = "<?php echo $temp_rspec_file ?>";
var jit = "<?php echo $ji ?>";

$.post("createsliver.php", { slice_id:slicet, am_id:am_idt, rspec_file:rspec_filet }, function() {
  requestResourceDetails(am_idt, jit);
}).fail(function() { requestResourceFailed(am_idt, jit)});
</script>

<?php

?>
