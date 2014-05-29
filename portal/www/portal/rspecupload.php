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

require_once("settings.php");
require_once("user.php");
require_once("header.php");
require_once 'geni_syslog.php';
require_once 'db-util.php';

// This module is called in a number of contexts.
// 1) It is called directly from profile.php
//     a) To upload a new RSPEC
//     b) To update an existing rspec
// 2) It is called as a callback from this module when the form is submitted
//     a) To upload a new RSPEC
//     b) to update an existing RSPEC
//
// We can differentiate between these cases:
//  1a) The module is invoked with empty $_REQUEST/$_POST arguments
//  1b) The module is invoked with a full set of rspec_id, group1, 
//       description, name $_REQUEST arguments (not $_POST)
//       (on the URL rspecupload.php?rspec_id=x&....
//  2a) The group1, description name fields are set in $_REQUEST and $_POST
//      but the rspec_id is blank 
//  2b) The group1, description name fields are set in $_REQUEST and $_POST
//      and the rspec_id is set as well
// Note, if the $_FILES variable contains no files 
//    (an error = UPLOAD_ERR_NO_FILE, it is an error for uploading a new
//    rspec but allowed for updating an existing rpsec 
//    (leave the existing RSPEC alone)


/**
 * Validate the uploaded RSpec.
 *
 * @return boolean -- true for valid, false if invalid
 */
function validateRSpec($rspec_filename, &$error_msg)
{
  $error = null;

  //--------------------------------------------------
  // is it parseable as XML?
  $rspec = file_get_contents($rspec_filename);
  $xml_parser = xml_parser_create();
  $parse_result = xml_parse($xml_parser, $rspec, true);
  if ($parse_result === 0) {
    $xml_error = xml_error_string(xml_get_error_code($xml_parser));
    $line = xml_get_current_line_number($xml_parser);
    $column = xml_get_current_column_number($xml_parser);
    $error_msg = "$xml_error at line $line, column $column";
    xml_parser_free($xml_parser);
    return false;
  }
  /* TODO: do some more checks on the rspec.
   * Does it pass "rspeclint"?
   * Is it a request RSpec (not ad or manifest)?
   */
  return true;
}

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

  // Handle case wherein no file provided (for update, not upload)
  if ($rspec_filename == NULL || $rspec_filename == "") {
    return array(NULL, False, False, array());
  }

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

$rspec_id = "";
$rspec_visibility = "";
$rspec_desc = "";
$rspec_sn = "";
if (array_key_exists('rspec_id', $_REQUEST)) {
  $rspec_id = $_REQUEST['rspec_id'];
  $rspec_sn = $_REQUEST['name'];
  $rspec_desc = $_REQUEST['description'];
  $rspec_visibility = $_REQUEST['group1'];
}

//error_log("REQUEST = " . print_r($_REQUEST, True));
//error_log("POST = " . print_r($_POST, True));
//error_log("FILES = " . print_r($_FILES, True));

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$error = NULL;
$errorcode = 0;
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
    $rspec_filename = $_FILES["file"]["tmp_name"];
    if (! validateRSpec($rspec_filename, $msg)) {
      $error = 'Uploaded RSpec is invalid: ' . $msg;
    }
  }
}

/*
 * If we weren't given a file but we're in update  mode (not upload a new rspec mode), there is no error
 */
if (($rspec_id != "") && ($errorcode == UPLOAD_ERR_NO_FILE)) {
  $error = NULL;
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

  $msg = "Upload experiment Resource Specification (RSpec)";
  if($rspec_id != "") 
    $msg = "Update experiment Resource Specification (RSpec)";
  print("<h1 style=\"line-height: 1.2em\">$msg</h1>\n");

  if ($error != NULL) {
    echo "<div id=\"error-message\""
      . " style=\"background: #dddddd;font-weight: bold\">\n";
    echo "$error";
    echo "</div>\n";
  }
  echo '<form action="rspecupload.php" method="post" enctype="multipart/form-data">';
  echo '  <input type="hidden" value="' . $rspec_id . '" name="rspec_id"/>';
  echo '  <p><label for="file">RSpec File:</label>';
  echo '  <input type="file" name="file" id="file" /></p>';
  echo '  <p>';
  echo '  <label for="file">Short Name:</label>';
  echo '  <input type="text" name="name" value="' . $rspec_sn . '"/> - Required</p>';
  $public_checked = "";
  $private_checked = "checked";
  if ($rspec_visibility == "public") {
    $public_checked = "checked";
    $private_checked = "";
  }
  echo '  <p>Type: ';
  echo '  <input type="radio" name="group1" value="public" ' . $public_checked . '> public';
  echo '  <input type="radio" name="group1" value="private" ' . $private_checked . '> private</p>';
  echo '  <p>';
  echo '  <label for="file">Description:</label>';
  echo '  <input type="text" name="description" value="' . $rspec_desc . '"/> - Required</p>';
  echo '  <p>';
  $button_label = "Upload";
  if ($rspec_id != "") $button_label = "Update";
  echo '  <input type="submit" name="submit" value="' . $button_label . '"/>';
  echo '  <input type="hidden" name="referer" value="' . $referer . '"/></p>';
  echo '</form>';
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
$rspec_id = $_POST['rspec_id'];

$am_urns_image = "";
foreach($am_urns as $am_urn) {
  $am_urns_image = $am_urns_image . $am_urn . " ";
}

//error_log("PARSE : " . $is_bound . " " . $is_stitch . " " . $am_urns_image);

// FIXME: Need a utility that determines schema and version
// from the RSpec itself.
$schema = "GENI";
$schema_version = "3";

if ($rspec_id != "") {
  $uploaded_rspec = ($errorcode != UPLOAD_ERR_NO_FILE);
  $result = db_update_rspec($rspec_id, $user, $name, $description, $contents,
			    $schema, $schema_version, $visibility, $is_bound,
			    $is_stitch, $am_urns_image, $uploaded_rspec
			 );
} else {
  $result = db_add_rspec($user, $name, $description, $contents,
			 $schema, $schema_version, $visibility, $is_bound,
			 $is_stitch, $am_urns_image
			 );
}
geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, "db_add_rspec: " . print_r($result, true));
//error_log("db_add_rspec: " . print_r($result, true));
// FIXME: check result
if (! $result) {
  $_SESSION['lasterror'] = "ERROR. Failed to upload Resource Specification " . $name;
} elseif ($rspec_id != "") {
  $_SESSION['lastmessage'] = "Updated Resource Specification " . $name;
} else {
  $_SESSION['lastmessage'] = "Uploaded Resource Specification " . $name;
}

relative_redirect('profile#rspecs');
?>
