<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2014 Raytheon BBN Technologies
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

// Upload list of candidate members to add to project
// from a CSV file
// Format: email, name

require_once("user.php");
require_once("header.php");
require_once("ma_client.php");
require_once("pa_client.php");
require_once('cs_constants.php');


show_header('GENI Portal: Projects', $TAB_PROJECTS);
include("tool-lookupids.php");
include("tool-breadcrumbs.php");
include("tool-showmessage.php");


if (! isset($ma_url)) {
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
}

function compute_member_options($email, $member_id, $member_name, $requested_role, $is_member)
{

  global $CS_ATTRIBUTE_TYPE_NAME;

  // No options if you are already a member
  if ($is_member) return "";

  $requested_role_index = -1;
  foreach($CS_ATTRIBUTE_TYPE_NAME as $value => $name) {
    if (($requested_role != null) && (strcasecmp($name, $requested_role) == 0)) {
      $requested_role_index = $value;
      break;
    }
  }

  //  error_log("Requested role : " . $member_name . " | " . $requested_role . " | " . $requested_role_index . " | " . $member_id . " | " . $email);

  $options = "";
  $label = "Invite";
  if($member_id != null) $label = "Add";
  $options = $options . "<option value=0,$member_id>Do not " . strtolower($label) . "</option>";
  foreach($CS_ATTRIBUTE_TYPE_NAME as $role_index => $role_label) {
    $selected = "";
    if($role_index == CS_ATTRIBUTE_TYPE::OPERATOR || $role_index == CS_ATTRIBUTE_TYPE::LEAD)
      continue;
    if($role_index == $requested_role_index) $selected = "selected";
    // Default (if not specified) is MEMBER
    if($requested_role_index == -1 && $role_index == CS_ATTRIBUTE_TYPE::MEMBER) $selected = "selected";
    //    error_log("Selected = " . $selected . " ROLE " . $role_label . " RI " . $role_index . " RRI " . $requested_role_index);
    $options = $options . "<option $selected value=$role_index,$member_id>$label as $role_label</option>";
  }
  return $options;
}

// error_log("IN UPM");

if($project_id == '' || $project_id == 'none') {
  error_log("PROJECT_ID not set");
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
  $referer = relative_url("project.php?project_id=$project_id");
}

//error_log("Error = " . $error);
//error_log("POST = " . print_r($_POST, true));
//error_log("FILES = " . print_r($_FILES, true));
//error_log("PID = " . $project_id);

if ($error != NULL || count($_POST) == 0) {
  echo "<div id=\"error-message\""
    . " style=\"background: #dddddd;font-weight: bold\">\n";
  echo "$error";
  echo "</div>\n";
  print "<h2>Upload Project Members</h2>";
  print "<p>You can upload a CSV (comma-separated-values) file of candidates members for your project or enter candidate information in the text box below. Use the following format:</p>";
  print "<p>";
  print "Format:</p>";
  print "<pre style='margin-left:80px;'>candidate_email, candidate_name, [optional: role = Admin, Member (default), Auditor]</pre>";
  print "<p>Example:</p>";
  print "<pre style='margin-left:80px;'>jsmith@geni.net, Joe Smith, Admin\n";
  print "mbrown@geni.net, Mary Brown</pre>";

  print "<p>For an explanation of the different project roles, see the <a href='http://groups.geni.net/geni/wiki/GENIGlossary#Project'>GENI Glossary</a>.</p>";

  print '<form action="upload-project-members.php?project_id=' . $project_id . '" method="post" enctype="multipart/form-data">';
  print '  <p><b><label for="file">Upload CSV File:</label></b>';  
  print '  <input type="file" name="file" id="file" />';
  print '  </p>';
  print '  <p><input type="submit" name="submit" value="Upload"/></p>';
  print '  <input type="hidden" name="referer" value="' . $referer . '"/>';
  print '</form>';

  print "<p>";
  print '<form action="upload-project-members.php?project_id=' . $project_id . '" method="post" enctype="multipart/form-data">';
  print '  <p><b><label>Or enter candidates to add:</label></b>';
  print "<p><textarea name='candidates' cols=\"60\" rows=\"4\"></textarea></p>\n";
  print '  </p>';

  print "<p><button type=\"submit\" value=\"submit\"><b>Add</b></button>\n";
  print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/></p>\n";
  print "</form>\n";

  include("footer.php");
  exit;
}
$error = NULL;
$content = NULL;
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
    $actual_filename = $_FILES['file']['tmp_name'];
    $contents = file_get_contents($actual_filename);
    /*
     * Upload was successful, do some basic checks on the contents.
     */
    /* TODO: do some sort of check on the rspec.
     * Is it valid XML? Does it pass "rspeclint"?
     * Is it a request RSpec (not ad or manifest)?
     */
  }
} else if (array_key_exists('candidates',$_REQUEST)) {
  $contents = $_REQUEST['candidates'];
}

// show_header('GENI Portal: Project', $TAB_PROJECT, 0); // 
// include("tool-breadcrumbs.php");
// include("tool-showmessage.php");
  print("<h2>Upload Project Members</h2>\n");
  print "<p>Add or invite project members. For an explanation of the different roles, see the <a href='http://groups.geni.net/geni/wiki/GENIGlossary#Project'>GENI Glossary</a>.</p>";
  print "<p><b>Action Legend</b><br/>";
  print "<b>Add as ...</b> Candidates who already use the portal will be added to your project with the specified role immediately.<br/>";
  print "<b>Invite as ...</b> Others will receive an invitation email with instructions on joining your project.</p>";


$project_members = get_project_members($sa_url, $user, $project_id);
$project_member_ids = array();
foreach($project_members as $project_member) {
  $project_member_id = $project_member[PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
  $project_member_ids[] = $project_member_id;
}
// error_log("PM = " . print_r($project_members, true));

// error_log("CONTENTS = " . $contents);

print '<form method="POST" action="do-upload-project-members.php">';
print '<table>';

print '<tr><th>Candidate Name</th><th>Candidate Email</th>';
// <th>Can Add <br/>Immediately?</th>
print '<th>Action</th></tr>';
print "<input type=\"hidden\" name=\"project_id\" value=\"$project_id\"/>\n";


//$lines = explode("\n", $contents); // See http://stackoverflow.com/questions/3997336/explode-php-string-by-new-line
$lines = preg_split('/\r\n|\n|\r/', $contents, -1, PREG_SPLIT_NO_EMPTY);
$names_by_email = array();
$roles_by_email = array();
$skips = "";
foreach($lines as $line) {
  $parts = explode(",", $line);
  if (count($parts) < 2) continue;
  $email = trim($parts[0]);
  $email = filter_var($email, FILTER_SANITIZE_EMAIL);
  $email = strtolower($email);
  if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
    error_log("Uploaded file of candidate members had invalid email address: " . $line);
    if ($skips !== "")
      $skips = $skips . ", ";
    $skips = $skips . $email;
    continue;
  }
  $name = trim($parts[1]);
  $role = null;
  if (count($parts) > 2) $role = trim($parts[2]);
  $names_by_email[$email] = $name;
  $roles_by_email[$email] = $role;
}

if (count(array_keys($names_by_email)) > 0) {
  $members_by_email = lookup_members_by_email($ma_url, $user, array_keys($names_by_email));
  $members_by_email = array_change_key_case($members_by_email,CASE_LOWER);
}

//error_log("NBE = " . print_r($names_by_email, true));
//error_log("RBE = " . print_r($roles_by_email, true));

foreach($names_by_email as $email => $name) {
  $member_id = null;
  $recognized = "No";
  $role = null;
  if (array_key_exists(strtolower($email), $members_by_email) && count($members_by_email[strtolower($email)] == 1))  {
    $member_id = $members_by_email[strtolower($email)][0];
    $recognized = "Yes";
  }
  if (array_key_exists(strtolower($email), array_change_key_case($roles_by_email)) && count($roles_by_email[strtolower($email)] == 1)) {
    $role = $roles_by_email[$email];
  }

  $is_member = ($member_id != null && in_array($member_id, $project_member_ids));
  if ($is_member) {
    $member_actions = "Already Member";
  } else {
    $member_options = compute_member_options($email, $member_id, $name,$role, $is_member);
    $email_name = $email . ":" . $name;
    // Convert spaces to tabs, convert periods to commas
    $email_name = str_replace(".", ",", $email_name);
    $email_name = str_replace(" ", "\t", $email_name);
    $member_actions = "<select name=\"$email_name\">$member_options</select>";
  }
    
  //  error_log("MA = " . $member_actions);
  print "<tr><td>$name</td><td>$email</td>";
  //<td>$recognized</td>
  print "<td>$member_actions</td></tr>\n";
  //  error_log("EMAIL = " . $email . " NAME = " . $name);
}

print '</table>';
if ($skips !== "") {
  print "<p class='warn'>Skipped invalid email addresses: $skips</p>\n";
}
print "<br/>\n";
if (count(array_keys($names_by_email)) > 0) {
  print "<input type=\"submit\" value=\"Invite Selected Members\"/>\n";
}
print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
print '</form>';


include("footer.php");


?>

