<?php
//----------------------------------------------------------------------
// Copyright (c) 2011 Raytheon BBN Technologies
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

require_once("settings.php");
require_once("user.php");
require_once("header.php");
require_once 'geni_syslog.php';
require_once 'db-util.php';

// Parse XML rspec contained in given file
// Is rspec in given file bound (i.e. does it have any nodes with 
//      component_manager_id tags)?
// Is it for stitching (i.e. does any link have two different 
//      component_manager_id tags)?
// Return array of four values: 
//    parsed_rspec (text), 
//    is_bound (boolean), 
//    is_stitch (boolean),
//    a list of AM URN's (component_manager_id's) of requested nodes
function parseRequestRSpec($rspec_filename)
{
  $am_urns = array();
  $is_bound = false;
  $is_stitch = false;

  $dom_document = new DOMDocument();
  $rspec = file_get_contents($rspec_filename);
  $dom_document->loadXML($rspec);
  $root = $dom_document->documentElement;

  // Go over each node and link child
  // If ANY node child has a component_manager_id, is_bound is true
  // If ANY link has two componennt_manager children of 
  //       different names, is_stitch is true
  foreach ($root->childNodes as $child) {
    if ($child->nodeType == XML_TEXT_NODE) { continue; }
    // Pull out 'client_id' and 'component_manager_id' attributes
    if ($child->nodeName == 'node') {
      $node = $child;
      $client_id = NULL;
      if ($node->hasAttribute('client_id')) { 
	$client_id = $node->getAttribute('client_id'); 
      }
      $component_manager_id = NULL;
      if ($node->hasAttribute('component_manager_id')) { 
	$component_manager_id = $node->getAttribute('component_manager_id'); 
	$is_bound = true;
	$am_urns[] = $component_manager_id;
      }
      //      error_log("Node "  . print_r($node, true) . " " . 
      //		print_r($client_id, true) . " " . 
      //		print_r($component_manager_id, true));
    } elseif ($child->nodeName == 'link') {
      $link_urns = array();
      // Extract the client_id attribute and 
      // component_manager_id child name attribute
      $link = $child;
      $link_client_id = NULL;
      if ($link->hasAttribute('client_id')) { 
	$link_client_id = $link->getAttribute('client_id'); 
      }
      //      error_log("Link "  . print_r($link, true) . " " . 
      //		print_r($link_client_id, true));
      foreach($link->childNodes as $link_child) {
	if ($link_child->nodeType == XML_TEXT_NODE) { continue; }
	if ($link_child->nodeName == 'component_manager') {
	  $cm_id = $link_child;
	  $cmid_component_manager_id = NULL;
	  if ($cm_id->hasAttribute('name')) { 
	    $cmid_component_manager_id = $cm_id->getAttribute('name'); 
	    $link_urns[$cmid_component_manager_id] = true;
	  }
	  //	  error_log("   CMID:" . print_r($cm_id, true) . " " . 
	  //		    print_r($cmid_component_manager_id, true));
	} 
      }
      // We have a link with more than 1 different aggregate URN
      if (count(array_keys($link_urns)) > 1) {
	$is_stitch = true;
      }
    }
  }

  return array($rspec, $is_bound, $is_stitch, $am_urns);
}


$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$error = NULL;
if (array_key_exists('file', $_FILES)) {
  $errorcode = $_FILES['file']['error'];
  if ($errorcode != 0) {
    // An error occurred with the upload.
    if ($errorcode == UPLOAD_ERR_NO_FILE) {
      $error = "No file was uploaded.";
    } else {
      $error = "Unknown upload error (code = $errorcode).";
    }
  } else {
    /*
     * Upload was successful, do some basic checks on the contents.
     */
    /* TODO: do some sort of check on the rspec.
     * Is it valid XML? Does it pass "rspeclint"?
     * Is it a request RSpec (not ad or manifest)?
     */
  }
}

/* Set up the referer, which is used to
 * redirect after upload.
 */
$referer_key = 'HTTP_REFERER';
if (array_key_exists($referer_key, $_SERVER)) {
  $referer = $_SERVER['HTTP_REFERER'];
} else {
  $referer = relative_url('profile.php');
}
if ($error != NULL || count($_POST) == 0) {
  // Display the form and exit
  show_header('GENI Portal: Profile', $TAB_PROFILE, 0); // 0=Don't load user to show header
  include("tool-breadcrumbs.php");
  include("tool-showmessage.php");
  print("<h2>Upload experiment Resource Specification (RSpec)</h2>\n");
  if ($error != NULL) {
    echo "<div id=\"error-message\""
      . " style=\"background: #dddddd;font-weight: bold\">\n";
    echo "$error";
    echo "</div>\n";
  }
  echo '<form action="rspecupload.php" method="post" enctype="multipart/form-data">';
  echo '  <label for="file">RSpec File:</label>';
  echo '  <input type="file" name="file" id="file" />';
  echo '  <br/><br/>';
  echo '  <label for="file">Short Name:</label>';
  echo '  <input type="text" name="name"/> - Required';
  echo '  <br/><br/>';
  echo '  <input type="radio" name="group1" value="public" checked> public';
  echo '  <input type="radio" name="group1" value="private"> private';
  echo '  <br/><br/>';
  echo '  <label for="file">Description:</label>';
  echo '  <input type="text" name="description"/> - Required';
  echo '  <br/><br/>';
  echo '  <input type="submit" name="submit" value="Upload"/>';
  echo '  <input type="hidden" name="referer" value="' . $referer . '"/>';
  echo '</form>';
  echo '<br/>';
  include("footer.php");
  exit;
}

// The rspec is in $_FILES["file"]["tmp_name"]
$actual_filename = $_FILES["file"]["tmp_name"];
$parse_results = parseRequestRSpec($actual_filename);
$contents = $parse_results[0];
$is_bound = $parse_results[1];
$is_stitch = $parse_results[2];
$am_urns = $parse_results[3];

$filename = $_FILES["file"]["name"];
$description = NULL;
$name = $_POST["name"];
$visibility = $_POST["group1"];
$description = $_POST["description"];

$am_urns_image = "";
foreach($am_urns as $am_urn) {
  $am_urns_image = $am_urns_image . $am_urn . " ";
}

//error_log("PARSE : " . $is_bound . " " . $is_stitch . " " . $am_urns_image);

// FIXME: Need a utility that determines schema and version
// from the RSpec itself.
$schema = "GENI";
$schema_version = "3";

$result = db_add_rspec($user, $name, $description, $contents,
		       $schema, $schema_version, $visibility, $is_bound,
		       $is_stitch, $am_urns_image
		       );
geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, "db_add_rspec: " . print_r($result, true));
//error_log("db_add_rspec: " . print_r($result, true));
// FIXME: check result
if (! $result) {
  $_SESSION['lasterror'] = "ERROR. Failed to upload Resource Specification " . $name;
} else {
  $_SESSION['lastmessage'] = "Uploaded Resource Specification " . $name;
}

// redirect to referer if available.
if (array_key_exists('referer', $_POST)) {
  header("Location: " . $_POST['referer']);
  exit;
} else {
  relative_redirect('profile');
}
?>
