<?php
//----------------------------------------------------------------------
// Copyright (c) 2013 Raytheon BBN Technologies
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

// Search for an iRODS account for this user under their portal username. Display it if found.
// Otherwise, Create an account for the user at iRODS with a temporary password

/* TODO:
 * Get U/P from settings only
 * S/MIME
 * test with the iEnv and WebURL bits
 * Put up a contact point for resetting your PW
 * Other info to the user?
 * Enable for all users
 * When server has a real cert, take out disabling VERIFYPEER and VERIFYHOST
 * Are temporary failure sleep/retry counts good?
 * irodsHost/port/resource default values - get them from a config?
 * Username has a prefix? HRN like prefix? None? Currently prefix is geni-
 */

require_once('user.php');
require_once('ma_client.php');
require_once('header.php');
require_once('smime.php');
require_once('portal.php');
require_once('settings.php');
require_once('sr_client.php');
include_once('/etc/geni-ch/settings.php');
include_once('irods_utils.php');


if (! isset($ma_url)) {
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  if (! isset($ma_url) || is_null($ma_url) || $ma_url == '') {
    error_log("Found no MA in SR!");
    relative_redirect("error-text.php?error=" . urlencode("No Member Authority configured."));
  }
}


if (! isset($irods_url) || is_null($irods_url) || $irods_url == '') {
  error_log("Found no iRODS server in SR!");
  relative_redirect("error-text.php?error=" . urlencode("No iRODS servers configured."));
}

if (!isset($user)) {
  $user = geni_loadUser();
}
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

// FIXME: Only let this page run for testers for now
if (! $user->hasAttribute('enable_irods')) {
  error_log("User " . $user->prettyName() . " not enabled for iRODS");
  relative_redirect('profile.php');
}

$username = base_username($user);
$baseusername = $username;

$certStruct = openssl_x509_parse($user->certificate());
$subjectDN = $certStruct['name'];
//$userurn = $user->urn();

///* Sign the outbound message with the portal cert/key */
//$portal = Portal::getInstance();
//$portal_cert = $portal->certificate();
//$portal_key = $portal->privateKey();

$didCreate = False; // Did we create the account on this page
$userExisted = False; // Did the user already exist
$usernameTaken = False; // Is the basic username taken
$irodsError = ""; // Error from iRODS server
$tempPassword = "";
$createTime = "";
$zone = "";
$userDN = ""; // DN from iRODS
$irodsUsername = ""; // username from iRODS
$irodsEnv = null; // iEnv file contents from iRODS
$irodsWebURL = null; // iRDODS web URL to show user

$userinfo = array();

// Did GET fail in a way that we shouldn't try the PUT
$permError = false;

// First we try to GET the username: if there, the user already has an account. Remind them.
try {
  while(True) {
    $userxml = doGET($irods_url . IRODS_GET_USER_URI . $username, $portal_irods_user, $portal_irods_pw, $irods_cert);
    //  error_log(print_r($pestget->last_response, TRUE));
    //  error_log("Got userxml: " . $userxml);
    if (! isset($userxml) || strncmp($userxml, "<?xml", 5)) {
      throw new Exception("Error looking up " . $username . " at iRODS: " . $userxml);
    } 
    $xml = simplexml_load_string($userxml);
    if (!$xml) {
      error_log("Failed to parse XML from GET of iRODS user " . $username);
    } else {
      $userxml = $xml;
      if (! is_null($userxml) && $userxml->getName() == "user") {
	foreach ($userxml->attributes() as $a=>$b) {
	  if ($a == "name") {
	    $irodsUsername = $b;
	    if ($irodsUsername != $username) {
	      error_log("GET for iRODS username " . $username . " got a different username " . $irodsUsername);
	      throw new PermFailException("Lookup of iRODS username " . $username . " got a different username " . $irodsUsername);
	    }
	  } elseif ($a == IRODS_ENV) {
	    $irodsEnv = $b;
	  } elseif ($a == IRODS_URL) {
	    $irodsWebURL = $b;
	  }
	  //      	error_log($a . '=' . $b);
	}

	foreach ($userxml->children() as $child) {
	  $name = $child->getName();
	  //	error_log($child->getName() . '=' . $child);
	  if ($name == IRODS_CREATE_TIME) {
	    $createTime = strval($child);
	  } elseif ($name == IRODS_ZONE) {
	    $zone = strval($child);
	  } elseif ($name == IRODS_USERDN) {
	    $userDN = strval($child);
	    if ($userDN === "") {
	      error_log("userDN empty from iRODS for user " . $username);
	    } elseif ($userDN !== $subjectDN) {
	      error_log("GET for username " . $username . " got DN from iRODS " . $userDN . " != user's: " . $subjectDN);
	      if (!strncmp($subjectDN, $userDN, strlen($userDN))) {
		error_log("But iRODS DN is just the start of our DN, so treat as same");
		$userExisted = True;
	      } else {
		$usernameTaken = True;
	      }
	    } else {
	      $userExisted = True;
	    }
	  } elseif ($name == IRODS_ENV) {
	    $irodsEnv = strval($child);
	  } elseif ($name == IRODS_URL) {
	    $irodsWebURL = strval($child);
	  }
	}
      } // End of block where we got a valid GET result
      if ($usernameTaken) {
	error_log("Since old username was taken, finding a new one");
	$username = derive_username($baseusername, $username);
	$usernameTaken = False;
      }
      if ($userExisted) {
	error_log("User " . $username . " found in iRODS");
	break;
      } else {
	error_log("User not found, and no exception. Should be a usernameTaken kind of thing");
      }
    } // End of block to parse GET result 
  } // end of while to either find the user or raise an exception (IE found a free username)
}
catch (PermFailException $e) 
{
  error_log("Error checking if iRODS account $username exists: " . $e->getMessage());
  $irodsError = htmlentities($e->getMessage());
  $permError = true;
}
catch (Exception $e) 
{
  error_log("Error checking if iRODS account $username exists - probably it does not"); //: " . $e->getMessage());
  $irodsError = htmlentities($e->getMessage());
}

// If the iRODS server is working but the user didn't exist, create them
if (! $permError && ! $userExisted) {

  // Create a temp password of 10 characters
  $hash = strtoupper(md5(rand()));
  $tempPassword = substr($hash, rand(0, 22), 10);

  // Construct the array of stuff to send iRODS
  $irods_info = array();
  $irods_info[IRODS_USER_PASSWORD] = $tempPassword;
  $irods_info[IRODS_USER_DN] = $subjectDN;

  
  // Now do the REST PUT
  try {
    $keepTrying = True;
    $tempFailTries = 0;
    while ($keepTrying) {

      $irods_info[IRODS_USER_NAME] = $username;

      // Note: in PHP 5.4, use JSON_UNESCAPED_SLASHES.
      //   we have PHP 5.3, so we have to remove those manually.
      $irods_json = json_encode($irods_info);
      $irods_json = str_replace('\\/','/', $irods_json);

      //      error_log("Trying to create iRODS account with values: " . $irods_json);

      ///* Sign the data with the portal certificate (Is that correct?) */
      //$irods_signed = smime_sign_message($irods_json, $portal_cert, $portal_key);

      ///* Encrypt the signed data for the iRODS SSL certificate */
      //$irods_blob = smime_encrypt($irods_signed, $irods_cert);


      $addstruct = doPUT($irods_url . IRODS_PUT_USER_URI . IRODS_SEND_JSON, $portal_irods_user, $portal_irods_pw, $irods_json, "application/json", $irods_cert);
      //error_log("PUT raw result: " . print_r($addstruct, true));

      // look for (\r or \n or \r\n){2} and move past that
      preg_match("/(\r|\n|\r\n){2}([^\r\n].+)$/", $addstruct, $m);
      if (! array_key_exists(2, $m)) {
	error_log("Malformed PUT result to iRODS - error? Got: " . $addstruct);
	throw new Exception("Failed to create iRODS account - server error: " . $addstruct);
      }

      //      error_log("PUT result content: " . $m[2]);

      $addjson = json_decode($m[2], true);

      // Parse the result. If code 0, show username and password. Else show the error for now.
      // Later if username taken, find another.
      if (! is_null($addjson) && is_array($addjson) && array_key_exists(IRODS_ADD_RESPONSE_CODE, $addjson)) {
	if ($addjson[IRODS_ADD_RESPONSE_CODE] == IRODS_ERROR::SUCCESS) {
	  $didCreate = True;
	  if (array_key_exists(IRODS_ENV, $addjson)) 
	    $irodsEnv = $addjson[IRODS_ENV];
	  if (array_key_exists(IRODS_URL, $addjson)) 
	    $irodsWebURL = $addjson[IRODS_URL];
	  $keepTrying = False;
	} elseif ($addjson[IRODS_ADD_RESPONSE_CODE] == IRODS_ERROR::TRY_AGAIN) {
	  $tempFailTries = $tempFailTries + 1;
	  if ($tempFailTries > 5) { // FIXME: Is 5 times enough?
	    error_log("iRODS got temporary error on PUT. But retried a bunch already. " . $addjson[IRODS_MESSAGE]);
	    $irodsError = $IRODS_ERROR_NAMES[$addjson[IRODS_ADD_RESPONSE_CODE]] . ": " . $addjson[IRODS_MESSAGE];
	    $keepTrying = False;
	  } else {
	    error_log("iRODS got temporary error on PUT. Sleep and retry. " . $addjson[IRODS_MESSAGE]);
	    sleep(10); // FIXME: Long enough?
	  }
	  // now allow to repeat
	} elseif ($addjson[IRODS_ADD_RESPONSE_CODE] == IRODS_ERROR::USERNAME_TAKEN) {
	  // Really I should check the userDN via a GET
	  // But that presumes another portal page created the user while this was querying. Unlikely.
	  error_log("iRODS says username " . $username . " is now taken. " . $addjson[IRODS_MESSAGE]);
	  $username = derive_username($baseusername, $username);
	} else {
	  $keepTrying = False;
	  // Get the various messages
	  
	  // Which error description do we use? The one they sent? Or ours?
	  $irodsError = $IRODS_ERROR_NAMES[$addjson[IRODS_ADD_RESPONSE_CODE]] . ": " . $addjson[IRODS_MESSAGE];
	  //	$irodsError = $addjson[IRODS_ADD_RESPONSE_DESCRIPTION] . ": " . $addjson[IRODS_MESSAGE];
	  error_log("iRODS returned an error creating account for " . $username . ": " . $irodsError);
	}
      } else {
	// malformed return struct
	error_log("Malformed return from iRODS PUT to create iRODS account for " . $username . ": " . $addjson);
      }
    } // end of while loop to retry PUT
  } catch (Exception $e) {
    error_log("Error doing iRODS put to create iRODS account for " . $username . ": " . $e->getMessage());
    $irodsError = htmlentities($e->getMessage());
  }
}

// Now that the user has an account, make sure there is an irods group for all their projects and they are in those groups
// FIXME: In future take out that $userExisted piece. That is just to help bootstrap group creation but slow and not needed in the long run
if ($didCreate or $userExisted) {
  if (! isset($sa_url)) {
    $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
    if (! isset($sa_url) || is_null($sa_url) || $sa_url == '') {
      error_log("Found no SA in SR!'");
    }
  }

  // Use $username
  // Get users projects
  $project_ids = get_projects_for_member($sa_url, $user, $user->account_id, true);
  $num_projects = count($project_ids);
  // for each project
  foreach ($project_ids as $project_id) {
    $project = lookup_project($sa_url, $user, $project_id);
    if (convert_boolean($project[PA_PROJECT_TABLE_FIELDNAME::EXPIRED])) {
      // Don't create groups and members for expired projects
      continue;
    }

    // FIXME: If I had attributes, I could skip trying to recreate the group here if it already exists

    // create group
    $created = irods_create_group($project_id, $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME], $user);
    $group_name = group_name($project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]);
    if ($created === 0) {
      error_log("irods.php created group for already existing project $group_name cause of page load by " . $user->prettyName());
    }

    // If the group was created, then this user was added
    // But if the group already existed and we just created their iRODS account, then we must add them to the group
    if ($created === 1 and $didCreate) {
      // add user to group
      $added = addToGroup($project_id, $group_name, $user->account_id, $user);
      if ($added === -1) {
	error_log("FAILed to add $username to iRODS group $group_name");
      }
    }
  }
}

// Now show a page with the result

show_header('GENI Portal: Profile', $TAB_PROFILE);
include("tool-breadcrumbs.php");
include("tool-showmessage.php");

?>
<h1>iRODS Account</h1>
<p>iRODS is a server for storing data about your experiments. It is used by the GIMI and GEMINI Instrumentation and Measurement systems.</p>
<?php

if ($didCreate) {
  print "<p>Your GENI iRODS account has been created.</p>";
  print "<table><tr><td class='label'><b>Username</b></td><td>$username</td></tr>\n";
  print "<tr><td class='label'><b>Temporary Password</b></td><td>$tempPassword</td></tr></table>\n";
  print "<p><b>WARNING: Write down your password. It is not recorded anywhere. You will need to change it after accessing iRODS.</b></p>\n";
  if ($username != $baseusername) 
    print "<p><b>NOTE</b>: Your username is not the same as your portal username (which was taken). Write it down!</p>\n";
  print "<br/>\n";
  if (! is_null($irodsWebURL))
    print "<p>To act on your iRODS account, go to <a href=\"$irodsWebURL\">$irodsWebURL</a>.</p>\n";
  print "<p>To learn more about using iRODS, go to <a href=\"http://groups.geni.net/geni/wiki/HowToUseiRODS\">http://groups.geni.net/geni/wiki/HowToUseiRODS</a>.</p>\n";
  print "To use iRODS tools you will need the following data. For commandline tools you will need to create the file '~/.irods/.irodsEnv':<br/>\n";
  if ($zone === "")
    $zone = $default_zone;
  if (is_null($irodsEnv)) {
      print "<pre>irodsHost=$irods_host\n";
      print "irodsPort=$irods_port\n";
      print "irodsDefResource=$irods_resource\n";
      print "irodsHome=/$zone/home/$username\n";
      print "irodsCwd=/$zone/home/$username\n";
      print "irodsUserName=$username\n";
      print "irodsZone=$zone</pre>\n";
    } else {
      $irodsEnv = preg_replace("/\n/", "<br/>\n", $irodsEnv);
      print "<p>$irodsEnv</p\n";
    }
} elseif ($userExisted) {
  $isDiffDN = false;
  print "<p>Your GENI iRODS account has already been created.</p>";
  print "<table><tr><td class='label'><b>Username</b></td><td>$username</td></tr>";
  if ($createTime !== "")
    print "<tr><td class='label'><b>Created</b></td><td>$createTime</td></tr>\n";
  if ($zone !== "")
    print "<tr><td class='label'><b>irodsZone</b></td><td>$zone</td></tr>\n";
  if ($userDN !== "") {
    print "<tr><td class='label'><b>User DN</b></td><td>$userDN</td></tr>\n";
    if ($userDN != $subjectDN)
      $isDiffDN = true;
  }
  print "</table>\n";
  print "<p><b>WARNING: Only you know your iRODS password.</b></p>\n";
  if ($isDiffDN)
    print "<p><b>WARNING: This iRODS user has your username but a different DN. Is this you? Your DN is: " . $subjectDN . "</b></p>\n";
  if (! is_null($irodsWebURL))
    print "<p>To act on your iRODS account, go to <a href=\"$irodsWebURL\">$irodsWebURL</a>.</p>\n";
  print "<p>To learn more about using iRODS, go to <a href=\"http://groups.geni.net/geni/wiki/HowToUseiRODS\">http://groups.geni.net/geni/wiki/HowToUseiRODS</a>.</p>\n";
  print "To use iRODS tools you will need the following data. For commandline tools you will need to create the file '~/.irods/.irodsEnv':<br/>\n";
  if ($zone === "")
    $zone = $default_zone;
  if (is_null($irodsEnv)) {
      print "<pre>irodsHost=$irods_host\n";
      print "irodsPort=$irods_port\n";
      print "irodsDefResource=$irods_resource\n";
      print "irodsHome=/$zone/home/$username\n";
      print "irodsCwd=/$zone/home/$username\n";
      print "irodsUserName=$username\n";
      print "irodsZone=$zone</pre>\n";
    } else {
      $irodsEnv = preg_replace("/\n/", "<br/>\n", $irodsEnv);
      print "<p>$irodsEnv</p\n";
    }
} else {
  // Some kind of error
  print "<div id=\"error-message\">";
  print "<p class='warn'>There was an error talking to iRODS.<br/><br/>";
  print "$irodsError</p>\n";
  print "</div>\n";
}
include("footer.php");
?>
