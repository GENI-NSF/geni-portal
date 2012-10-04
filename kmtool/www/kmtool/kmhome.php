<?php
//----------------------------------------------------------------------
// Copyright (c) 2012 Raytheon BBN Technologies
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

/**
 * Home of GENI key management tool
 */

require_once('km_utils.php');
require_once('ma_client.php');

$member_id_key = 'eppn';
$member_id_value = null;
$members = array();
$member = null;
$member_id = null;
$authorized_tools_for_user = array();
if (array_key_exists($member_id_key, $_SERVER)) {
  $member_id_value = $_SERVER[$member_id_key];
  $members = ma_lookup_member_id($ma_url, $km_signer, 
				 $member_id_key, $member_id_value);
} else if (array_key_exists("member_id", $_REQUEST)) {
  $member_id = $_REQUEST["member_id"];
} else {
  error_log("No member_id_key $member_id_key given to kmhome");
}

if (count($members) > 0 && ! isset($member_id)) {
  $member = $members[0];
  $member_id = $member->member_id;
} else if (! isset($member_id)) {
  error_log("kmhome: No members found for member_id $member_id_value");
}

$username = '*** Undefined ***';
if (array_key_exists('sn', $_SERVER) && array_key_exists('givenName', $_SERVER)){
  $username = $_SERVER['givenName'] . " " . $_SERVER['sn'];
} else if (array_key_exists('eppn', $_SERVER)) {
  $username = $_SERVER['eppn'];
} else if (array_key_exists("username", $_REQUEST)) {
  $username = $_REQUEST["username"];
}

$candidate_tools = ma_list_clients($ma_url, $km_signer);

// error_log("CT = " . print_r($candidate_tools, true));
// error_log("AT4U = " . print_r($authorized_tools_for_user, true));

// If invoked with a ?redirect=url argument, grab that 
// argument and go there from the 'continue' button
$redirect_key = "redirect";

//$redirect_address = "home.php";
$redirect_address = "";

if(array_key_exists($redirect_key, $_GET)) {
  $redirect_address = $_GET[$redirect_key];
}

$toolname_auth = "[no name]";
if (array_key_exists("authorize_toolname", $_GET)) {
  $toolname_auth = $_GET["authorize_toolname"];
}

$sense = "false";
if (array_key_exists("authorize_sense", $_GET)) {
  $sense = $_GET["authorize_sense"];
}
$sense_text = "authorized";
if ($sense == "false") { $sense_text = "deauthorized"; }

$toolurn_auth = "";
if (array_key_exists("authorize_toolurn", $_GET)) {
  $toolurn_auth  = $_GET["authorize_toolurn"];
}

// error_log("REDIRECT = " . $redirect_address);

$auth_success = false;
$auth_error = "";

if (array_key_exists("authorize_toolname", $_GET)) {
  $result = ma_authorize_client($ma_url, $km_signer, $member_id, $toolurn_auth, $sense);
  //  error_log("auth res = " . print_r($result, true));
  if ($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    $auth_success = true;
  } else {
    $auth_error = $result[RESPONSE_ARGUMENT::OUTPUT];
    error_log("KM: Error changing authorization for $toolname_auth for $username to $sense: " . $result[RESPONSE_ARGUMENT::OUTPUT]);
  }
}

if (isset($member_id)) {
  $authorized_tools_for_user = 
    ma_list_authorized_clients($ma_url, $km_signer, $member_id);
  //error_log("auth list: " . print_r($authorized_tools_for_user, true));
}

include('kmheader.php');
print "<h2>GENI Key Management Tool</h2><br>\n";
include("tool-showmessage.php");

print "This tool manages what clients can act on your behalf when " .
"working with the GENI Clearinghouse. If you authorize a tool, you " .
"are responsible for what it does on your behalf.<br><br>";

if (! isset($member_id)) {
  print "You must first activate your GENI account <a href=\"kmactivate.php\">here</a>.<br\>\n";
  include("footer.php");
  return;
}

print "Click the \"Authorize\" button  " . 
      "to allow the given tool to sign statements " . 
"on your behalf in interactions with the GENI Clearinghouse.<br><br>";

print "Click \"Deauthorize\" in the table below " . 
      "to remove permission for the given tool to sign statements " . 
      "on your behalf when working with the GENI Clearinghouse. <br><br>";

if (array_key_exists("authorize_toolname", $_GET)) {
  if ($auth_success) {
    print "<h3>Tool '$toolname_auth' has been <b>$sense_text</b> for user $username.</h3><br>\n";
  } else {
    print "<h4>Error changing authorization for $toolname_auth.</h4>\n";
  }
}

// Create table with entry for each tool
print("<table class=\"gridtable\">\n");
print("<tr><th>Tool</th><th>URN</th><th>Authorized</th></tr>\n");
foreach($candidate_tools as $toolname => $toolurn) {
  $enabled = in_array($toolurn, $authorized_tools_for_user);
  //  error_log("EN = " . print_r($enabled, true) . " " . print_r($toolname, true));

  // If you only have the portal and it is authorized and have no
  // redirect_address, set it here
  if (empty($redirect_address) && $toolname == "portal" && $enabled) {
    $redirect_address = "home.php";
  }

  $authorize_url = "kmhome.php"; 
  $authorize_label = "Authorize";
  $authorize_sense = "true";
  if ($enabled) { 
    $authorize_label = "Deauthorize"; 
    $authorize_sense = "false";
  }
  if (isset($toolname_auth) && $toolname_auth == $toolname) {
    $authorize_label = "<b>$authorize_label</b>";
  }

  $toolurn_encoded = urlencode($toolurn);
  $authorize_url = "$authorize_url?authorize_sense=$authorize_sense&authorize_toolname=$toolname&authorize_toolurn=$toolurn_encoded&username=$username&member_id=$member_id&redirect=$redirect_address";
  $enable_cell = "<button onClick=\"window.location='" . 
    $authorize_url . "'\">$authorize_label</button>";
  print("<tr><td>$toolname</td><td>$toolurn</td><td>$enable_cell</td></tr>\n");
}
print("</table>");
print("<br>\n");


// Include this only if the redirect address is a web address
if (! empty($redirect_address)) {
  print"<button onclick=\"window.location='" . 
    $redirect_address . "'" . "\"<b>Continue</b></button> back to your " .
    "Clearinghouse tool.<br/>";
}

include("footer.php");
?>