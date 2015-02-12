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
require_once("proj_slice_member.php");
require_once("print-text-helpers.php");
require_once("logging_client.php");
require_once("tool-rspec-parse.php");
require_once("omni_invocation_constants.php");

#error_log("REQUEST = " . print_r($_REQUEST, true));
#error_log("POST = " . print_r($_POST, true));
#error_log("FILES = " . print_r($_FILES, true));

$background = (array_key_exists("background", $_REQUEST));

// Don't print to output if running in background
if ($background) {
  ob_start(null, 0, true);
}

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

// redirect if slice has expired
if (isset($slice_expired) && convert_boolean($slice_expired)) {
  if (! isset($slice_name)) {
    $slice_name = "";
  }
  $_SESSION['lasterror'] = "Slice " . $slice_name . " is expired.";
  relative_redirect('slices.php');
}

// redirect if user isn't allowed to add slivers
if(!$user->isAllowed(SA_ACTION::ADD_SLIVERS, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
  relative_redirect('home.php');
}

// print header/breadcrumbs since we know slice information
show_header('GENI Portal: Add Resources to Slice (Results)',  $TAB_SLICES);
include("tool-breadcrumbs.php");
echo "<h1>Add Resources to GENI Slice <i>$slice_name</i> (Results)</h1>";

// get RSpec if tool-lookupids.php hasn't already gotten it
// both will store contents of RSpec in $rspec
if(array_key_exists('rspec_selection', $_FILES)) {
  $local_rspec_file = $_FILES['rspec_selection']['tmp_name'];
  $local_rspec_file = trim($local_rspec_file);
  $temp_rspec_file = null;
  if(strlen($local_rspec_file) > 0) {
    $rspec = file_get_contents($local_rspec_file);
  }
} else if (array_key_exists('current_rspec_text', $_REQUEST)) {
  $rspec = $_REQUEST['current_rspec_text'];
} else if(array_key_exists('rspec_jacks', $_REQUEST)) {
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

// check stitching to see if AM is required to be specified
$bound_rspec = 0;
$stitch_rspec = 0;
$partially_bound_rspec = 0;
$am_urns = array();
$parse_results = parseRequestRSpecContents($rspec);
if (is_null($parse_results)) {
    error_log("Invalid request RSpec");
    no_rspec_error();
} else {
    // is_bound is located in parse_results[1]
    if($parse_results[1] === true) {
      $bound_rspec = 1;
    }

    // is_stitch is located in parse_results[2]
    if($parse_results[2] === true) {
      $stitch_rspec = 1;
    }

    // List of AMs is in parse_results[3] for bound rspecs
    $am_urns = $parse_results[3];

    // is_partially_bound is located in parse_results[4]
    if($parse_results[4] == true) {
      $partially_bound_rspec = 1;
    }
}

// If a bound rspec, we should get the AM's from the rspec itself 
// in parse_results[3]
// If not a bound rspec, need to get it from the selected AM
if ($bound_rspec && count($am_urns) == 0) {
  error_log("Bound RSpec but 0 AMs");
  no_am_error();
} else if ($partially_bound_rspec) {
  error_log("Partially bound RSpec with AMs: " . print_r($am_urns, true));
  create_sliver_error("Partially bound RSpecs are not supported. All nodes must be assigned to an aggregate.");
} else if (!$bound_rspec && (!isset($am) || is_null($am))) {
  error_log("Unbound RSpec and no AM specified");
  no_am_error();
}

// Get an AM for non-bound RSpecs
if($bound_rspec) {
  $all_aggs = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
  $am_urls = array();
  $am_names = array();
  foreach($am_urns as $am_urn) {
    $knownAM = False;
    //    error_log("AM_URN = " . print_r($am_urn, true));
    foreach($all_aggs as $agg) {
      if ($agg[SR_ARGUMENT::SERVICE_URN] == $am_urn) {
	//	error_log("MATCH " . print_r($agg, true));
	$am_name = $agg[SR_ARGUMENT::SERVICE_NAME];
	$am_url = $agg[SR_ARGUMENT::SERVICE_URL];
	array_push($am_urls, $am_url);
	array_push($am_names, $am_name);
	$knownAM = True;
	break;
      }
    }
    if (! $knownAM) {
      error_log("Bound RSpec uses unknown AM $am_urn!");
      // This is going to fail later - almost certainly (all my tests do).
      // Some cases get you a results page with a clear error message.
      // Others give an SCS error about failing to find a path.
      // Doing this error message here cuts off that later work, but also stops
      // before the submit problem report page, which might be useful.
      //create_sliver_error("Your RSpec requires using an unknown aggregate: $am_urn.");
    }
  }
}
else {
    $am_url = $am[SR_ARGUMENT::SERVICE_URL];
    $am_name = am_name($am_url);
    $am_urls = array($am_url);
    $am_names = array($am_name);
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
$rspec_file = writeDataToTempDir($omni_invocation_dir, $rspec, OMNI_INVOCATION_FILE::REQUEST_RSPEC_FILE);

$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$slice_users = get_all_members_of_slice_as_users( $sa_url, $ma_url, $user, $slice_id);

// write out metadata file
$metadata = array(
    'Omni invocation ID' => get_invocation_id_from_dir($omni_invocation_dir),
    'User name' => $user->prettyName(),
    'User username' => $user->username,
    'User EPPN' => $user->eppn,
    'User e-mail' => $user->email(),
    'User UUID' => $user->account_id,
    'Slice UUID' => $slice_id,
    'Slice URN' => $slice_urn,
    'Slice name' => $slice_name,
    'Project UUID' => $project_id,
    'Project name' => $project_name,
    'Aggregate manager URLs' => $am_urls,
    'Aggregate manager names' => $am_names,
    'Request IP' => $_SERVER['REMOTE_ADDR'],
    'Request browser' => $_SERVER['HTTP_USER_AGENT'],
    'Request submitted' => date('r')
    );
$metadata_file = writeDataToTempDir($omni_invocation_dir,
        json_encode($metadata), OMNI_INVOCATION_FILE::METADATA_FILE);

/* write out metadata file that will be included in the body of a bug
   report e-mail - adjust this as necessary */
$metadata_email_report = array(
    'User name' => $user->prettyName(),
    'User username' => $user->username,
    'Slice URN' => $slice_urn,
    'Slice name' => $slice_name,
    'Project name' => $project_name,
    'Aggregate manager URLs' => $am_urls,
    'Aggregate manager names' => $am_names,
    'Request submitted' => date('r')
    );
$metadata_email_report_file = writeDataToTempDir($omni_invocation_dir,
        json_encode($metadata_email_report),
        OMNI_INVOCATION_FILE::METADATA_BUG_REPORT_EMAIL_FILE);

/*
    STEP 3: CALL AM CLIENT
    Call create_sliver() in am_client.php and get a return code back.
    $retVal is non-null if successful, null if failed
*/
$retVal = create_sliver($am_urls, $user, $slice_users, $slice_credential,
			$slice_urn, $omni_invocation_dir, $slice['slice_id'], $bound_rspec, 
			$stitch_rspec);

if($retVal and $retVal != "Invalid AM URL" and $retVal != "Missing AM URL" and $retVal != "Missing slice credential") {
  // Really we want to come here if we spawned the process OK only
    // Set up link to results page
    $invoke_id = get_invocation_id_from_dir($omni_invocation_dir);
    $link = "sliceresource.php?invocation_user=" . $user->username .
        "&invocation_id=$invoke_id&slice_id=$slice_id";
    
    // if am_id specified, append it to link
    if (isset($am_id) && $am_id) {
        $link .= "&am_id=$am_id";
    }
    
    $full_link = relative_url($link);

    // Write URL to 'Recent slice events' log
    $log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);
    $project_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, 
						   $slice['project_id']);
    $slice_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::SLICE, 
						 $slice['slice_id']);
    $log_attributes = array_merge($project_attributes, $slice_attributes);
    if($stitch_rspec) {
        log_event($log_url, $user,
            "Add resource request submitted for slice " . $slice_name . " from " .
            "stitching RSpec.<br><a href='$full_link'>Click here</a> for results.",
            $log_attributes);
    }
    else {
        log_event($log_url, $user,
            "Add resource request submitted for slice " . $slice_name . 
		  " at " . implode(", ", $am_names) . 
             ".<br><a href='$full_link'>Click here</a> for results.",
            $log_attributes);
    }
    
    // Do redirection
    create_sliver_success($link, $full_link);
}
else {
  $msg = "Failed to start an <tt>omni</tt> process.";
  if ($retVal == "Invalid AM URL" or $retVal == "Missing AM URL" or $retVal == "Missing slice credential") {
    $msg = $msg . " $retVal";
  }
  create_sliver_error($msg);
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

    global $background;
    if($background) {
      ob_end_clean(); // Flush all previous output
      $result = $error;
      print_r($result, true);
    } else {
      exit;
    }
}

function create_sliver_success($link, $full_link) {
    $string = "<p class='instruction'>Resource request submitted. If you are ";
    $string .= "not automatically redirected, <a href='$full_link'>click here</a> ";
    $string .= "to view request progress and results.</p>";
    echo $string;
    include("footer.php");

    global $background;
    if($background) {
      ob_end_clean();
      print_r($link);
    }  else {
    
      // FIXME: We probably want to redirect the user as 
      // quickly as possible, but
      // is this the best way of doing it?
      relative_redirect($link);
    
      exit;
    }
}

function prepare_temp_dir($identifier) {
    return createTempDir($identifier);
}

?>
