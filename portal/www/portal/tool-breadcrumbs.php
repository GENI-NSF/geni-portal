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

require_once("user.php");
require_once("util.php");
require_once("pa_constants.php");
require_once("pa_client.php");
require_once("sr_constants.php");
require_once('sr_client.php');
require_once("sa_constants.php");
require_once("sa_client.php");

// Name of actual script being used here
$file = $_SERVER["SCRIPT_NAME"];
$pinfo = pathinfo($file);
$script = $pinfo['basename'];
$referrer_file = $_SERVER["HTTP_REFERER"];
$pinfo = pathinfo($referrer_file);
$referrer_uri = $pinfo['basename'];
$referrer_script = strstr($referrer_uri, "?", true);

/* FIXME: for immediate parent, do onclick history.back(-1) ?*/
// Problem with that is that you may be going down in the hierarchy
/* <A HREF="javascript:history.back()">Back</A> */

// Array from script name to parent script name.
// We define a fixed hierarchy
// FIXME: DB of script names?
// Note ?project_id in script name in some places
$parents = array("profile.php" => "home.php",
		"help.php" => "home.php",
		"admin.php" => "home.php",
		"projects.php" => "home.php",
		"slices.php" => "home.php",
		"project.php" => "home.php",
		 "home.php" => "",
		"slice.php" => "project.php",
		"edit-project.php?project_id" => "project.php", // If supplied pid, then go back to project
		"edit-project.php" => "home.php", // No pid so doing new, go back to home
		"project-member.php" => "project.php",
		 "disable-project.php" => "project.php",
		 "createslice.php" => "project.php",
		 "edit-slice.php" => "slice.php",
		 "getversion.php" => "slice.php",
		 "listresources.php" => "slice.php",
		 "listresources_plain.php" => "slice.php",
		 "modify.php" => "profile.php",
		 "slice-add-resources.php" => "slice.php",
		 "slice-member.php" => "slice.php",
		 "sliceabac.php" => "slice.php",
		 "slicecred.php" => "slice.php",
		 "sliceresource.php" => "slice.php",
		 "sliverdelete.php" => "slice.php",
		 "sliverstatus.php" => "slice.php",
		 "uploadkey.php" => "profile.php",
		 "uploadsshkey.php" => "profile.php");

// Array from script name to a pretty name
// FIXME: From a DB that the script uses too?
// Note %project_name, %slice_name, %member_name will be filled in later
// Note ?project_id in script name in some places
$names = array("home.php" => $TAB_HOME,
	      "profile.php" => $TAB_PROFILE,
	      "admin.php" => $TAB_ADMIN,
	      "projects.php" => $TAB_PROJECTS,
	      "slices.php" => $TAB_SLICES,
	      "help.php" => $TAB_HELP,
	      "slice.php" => "Slice %slice_name",
	      "project.php" => "Project %project_name",
	      "edit-project.php?project_id" => "Edit Project %project_name",
	      "edit-project.php" => "New Project",
	      "project-member.php" => "Project %project_name Member %member_name",
	       "disable-project.php" => "Disable Project %project_name",
		 "createslice.php" => "Create Slice in project %project_name",
		 "edit-slice.php" => "Edit Slice %slice_name",
		 "getversion.php" => "Get Version",
		 "listresources.php" => "List Resources in Slice %slice_name",
		 "listresources_plain.php" => "List Resources",
		 "modify.php" => "Modify Your Account",
		 "slice-add-resources.php" => "Add Resources to %slice_name",
		 "slice-member.php" => "Member %member_name in Slice %slice_name",
		 "sliceabac.php" => "Get Slice %slice_name ABAC Credentials",
		 "slicecred.php" => "Get Slice %slice_name slice credential",
		 "sliceresource.php" => "Add resources to Slice %slice_name",
		 "sliverdelete.php" => "Delete Sliver from %slice_name",
		 "sliverstatus.php" => "Sliver Status for %slice_name",
		 "uploadkey.php" => "Upload Key",
		 "uploadsshkey.php" => "Upload SSH Key");

// Look up in the 2 arrays above
// Carefully checking for the project_id variant
function getNameAndParent($script)
{
  global $names;
  global $parents;
  if (! isset($script) || is_null($script) || $script == '') {
    return array ('', '');
  }
  if (array_key_exists($script, $names)) {
    $thisname = $names[$script];
  } else {
    $thisname = $script;
    error_log("breadcrumbs: No $script in names");
  }
  if (array_key_exists($script, $parents)) {
    $thisparent = $parents[$script];
  } else {
    error_log("breadcrumbs: No $script in parents");
    $thisparent = "home.php";
  }
  if (array_key_exists("project_id", $_REQUEST)) {
    $script2 = $script . "?project_id";
    if (array_key_exists($script2, $names)) {
      $thisname = $names[$script2];
    }
    if (array_key_exists($script2, $parents)) {
      $thisparent = $parents[$script2];
    }
  }
  return array($thisname, $thisparent);
}

// Produce Href part of string, filling in
// the project_id et al from globals
function getHref($script)
{
  global $project_id;
  global $slice_id;
  global $member_id;
  return "<a href=\"$script?project_id=$project_id&slice_id=$slice_id&member_id=$member_id\">";
}

// substitute globals for %foo_nam
function insertName($name)
{
  global $project_name;
  global $slice_name;
  global $member_name;
  return str_replace(array("%project_name", "%slice_name", "%member_name"), array($project_name, $slice_name, $member_name), $name);
}

// Complete the crumb addition for this bit
function getCrumbString($href, $thisname)
{
  $thisname = insertName($thisname);
  return "$href$thisname</a>";
}

// fancy name and parent script for the thing being called here
$scriptPair = getNameAndParent($script);

$parent = $scriptPair[1];
//$back1 = $parent;
// Start out the crumb where we are - no link
$crumb = insertName($scriptPair[0]);

// If there is no parent, we're done
if ($parent != '') {
  // Loop until we reach home or nothing
  do {
    // Go back 1
    $cur = $parent;
    $thisPair = getNameAndParent($cur);
    $parent = $thisPair[1];
    /* if ($parent == $back1) { */
    /*   $thisScript = "<a href=\"#\" onclick=\"history.back(-1);return false\">"; */
    /* } else { */
    $thisScript = getHref($cur);
    /* } */
    // Crumb is link to script with right name
    $crumb = getCrumbString($thisScript, $thisPair[0]) . " -> " . $crumb;
  } while ($cur != "home.php" && $cur != '');
}

print $crumb . "<br/>\n";

