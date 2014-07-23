<?php
//----------------------------------------------------------------------
// Copyright (c) 2011-2014 Raytheon BBN Technologies
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
require_once("proj_slice_member.php");
require_once("print-text-helpers.php");
require_once("logging_client.php");
require_once("tool-rspec-parse.php");

/*
    STEP 1: VERIFY
    Verify that incoming data can be used to create a sliver
*/

// redirect if user doesn't exist, isn't logged in, etc.
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

// redirect if no parameters were added
if (! count($_REQUEST)) {
  // No parameters. Return an error result?
  // For now, return nothing.
  relative_redirect('home.php');
}

// reset and set slice, AM, and RSpec
unset($slice);
unset($rspec);
unset($am);
include("tool-lookupids.php");
if (! isset($slice)) {
  no_slice_error();
}

// print header/breadcrumbs since we know slice information
show_header('GENI Portal: Slices',  $TAB_SLICES);
include("tool-breadcrumbs.php");
echo "<h1>Add Resources to GENI Slice: $slice_name</h1>";

// get RSpec if tool-lookupids.php hasn't already gotten it
// both will store contents of RSpec in $rspec
if(array_key_exists('rspec_selection', $_FILES)) {
  $local_rspec_file = $_FILES['rspec_selection']['tmp_name'];
  $local_rspec_file = trim($local_rspec_file);
  $temp_rspec_file = null;
  if(strlen($local_rspec_file) > 0) {
    $rspec = file_get_contents($local_rspec_file);
  }
}
else if(array_key_exists('rspec_jacks', $_REQUEST)) {
	$temp_rspec_file = null;
  $local_rspec_file = $_REQUEST['rspec_jacks'];
  if(strlen($local_rspec_file) > 0) {
    $rspec = $local_rspec_file;
  }
}

// redirect if no RSpec is specified
if (! isset($rspec) || is_null($rspec)) {
  error_log("RSPEC is not set or null");
  no_rspec_error();
  //  $rspec = fetchRSpecById(1);
}

// redirect if slice has expired
if (isset($slice_expired) && convert_boolean($slice_expired)) {
  if (! isset($slice_name)) {
    $slice_name = "";
  }
  $_SESSION['lasterror'] = "Slice " . $slice_name . " is expired.";
  relative_redirect('slices.php');
}

// redirect if user isn't allowed to look up slice
if(!$user->isAllowed(SA_ACTION::LOOKUP_SLICE, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
  relative_redirect('home.php');
}

// check stitching to see if AM is required to be specified
$bound_rspec = 0;
$stitch_rspec = 0;
$parse_results = parseRequestRSpecContents($rspec);
// is_bound is located in parse_results[1]
if($parse_results[1] === true) {
    $bound_rspec = 1;
}
// is_stitch is located in parse_results[2]
if($parse_results[2] === true) {
    $stitch_rspec = 1;
}
// FIXME: list of AMs is in parse_results[3] in case that needs to be passed in the future
if (!$stitch_rspec && (! isset($am) || is_null($am))) {
      no_am_error();
}

// Get an AM for non-bound RSpecs
if($stitch_rspec) {
    $am_url = "";
    $AM_name = "";
}
else {
    $am_url = $am[SR_ARGUMENT::SERVICE_URL];
    $AM_name = am_name($am_url);
    // error_log("AM_URL = " . $am_url);
    //$result = get_version($am_url, $user);
    // error_log("VERSION = " . $result);
}

/*
    STEP 2: PREPARE
    At this point, we can assume that verification is done and that the
    sliver is ready to be created.
*/

// prepare temporary directory to hold all files related to invocation
$omni_invocation_dir = prepare_temp_dir($user->username);
if(is_null($omni_invocation_dir)) {
    // FIXME: What to do if directory can't be created?
    error_log("Could not create temporary directory for omni session: $omni_invocation_dir");
    create_sliver_error("Could not create temporary directory for omni session: $omni_invocation_dir");
}

// Get the slice credential from the SA
$slice_credential = get_slice_credential($sa_url, $user, $slice_id);

// Get the slice URN via the SA
$slice_urn = $slice[SA_ARGUMENT::SLICE_URN];

// FIXME: This is the RSpec that will be used to call omni/stitcher.
// See proto-ch ticket #164 for storing all request RSpecs
$rspec_file = writeDataToTempDir($omni_invocation_dir, $rspec, "rspec");

$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$slice_users = get_all_members_of_slice_as_users( $sa_url, $ma_url, $user, $slice_id);

// write out metadata file
$metadata = array(
    'User name' => $user->prettyName(),
    'User username' => $user->username,
    'User EPPN' => $user->eppn,
    'User e-mail' => $user->mail,
    'User UUID' => $user->account_id,
    'Slice UUID' => $slice_id,
    'Slice URN' => $slice_urn,
    'Slice name' => $slice_name,
    'Project UUID' => $project_id,
    'Project name' => $project_name,
    'Aggregate manager URL' => $am_url,
    'Aggregate manager name' => $AM_name,
    'Request IP' => $_SERVER['REMOTE_ADDR'],
    'Request browser' => $_SERVER['HTTP_USER_AGENT'],
    'Request submitted' => date('r')
    );
$metadata_file = writeDataToTempDir($omni_invocation_dir, json_encode($metadata), "metadata");

/*
    STEP 3: CALL AM CLIENT
    Call create_sliver() in am_client.php and get a return code back.
    $retVal is non-null if successful, null if failed
*/
$retVal = create_sliver($am_url, $user, $slice_users, $slice_credential,
			$slice_urn, $omni_invocation_dir, $slice['slice_id'], $bound_rspec, 
			$stitch_rspec);

if($retVal) {
    // FIXME: Write URL to 'Recent slice events' log
    create_sliver_success($omni_invocation_dir, $user->username, $slice_id);
}
else {
    create_sliver_error("Failed to start an <tt>omni</tt> process.");
}

function no_slice_error() {
  header('HTTP/1.1 404 Not Found');
  print 'No slice id specified.';
  exit();
}

function no_rspec_error() {
  if (array_key_exists("rspec_id", $_REQUEST)) {
    $rspec_id = $_REQUEST['rspec_id'];
    create_sliver_error("Invalid resource specification id \"$rspec_id\" specified.");
  } else {
    create_sliver_error("No resource specification specified.");
  }
}
function no_am_error() {
  if (array_key_exists("am_id", $_REQUEST)) {
    $am_id = $_REQUEST['am_id'];
    create_sliver_error("Invalid aggregate manager id \"$am_id\" specified.");
  } else {
    create_sliver_error("No aggregate manager id specified.");
  }
}

function create_sliver_error($error) {
    echo "<p class='error'>$error</p>";
    echo '<form method="GET" action="back">';
    echo '<input type="button" value="Back" onClick="history.back(-1)"/>';
    echo '</form>';
    include("footer.php");
    exit;
}

function create_sliver_success($omni_invocation_dir, $username, $slice_id) {
    $invoke_id = array_pop(explode("-", $omni_invocation_dir));
    
    $link = "sliceresource.php?invocation_user=$username" .
        "&invocation_id=$invoke_id&slice_id=$slice_id";
        
    $string = "<p class='instruction'>Resource request submitted. If you are ";
    $string .= "not automatically redirected, <a href='$link'>click here</a> ";
    $string .= "to view request progress and results.</p>";
    echo $string;
    include("footer.php");
    
    // FIXME: We probably want to redirect the user as quickly as possible, but
    // is this the best way of doing it?
    relative_redirect($link);
    
    exit;
}

function prepare_temp_dir($identifier) {
    return createTempDir($identifier);
}

?>
