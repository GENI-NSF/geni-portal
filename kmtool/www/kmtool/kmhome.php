<?php

/**
 * Home of GENI key management tool
 */

require_once('km_utils.php');
require_once('ma_client.php');

print '<link type="text/css" href="/common/css/kmtool.css" rel="Stylesheet"/>';

$member_id_key = 'eppn';
$member_id_value = $_SERVER[$member_id_key];
$members = ma_lookup_member_id($ma_url, $km_signer, 
			       $member_id_key, $member_id_value);
$member = $members[0];
$member_id = $member->member_id;

$username = '*** Undefined ***';
if (array_key_exists('sn', $_SERVER) && array_key_exists('givenName', $_SERVER)){
  $username = $_SERVER['givenName'] . " " . $_SERVER['sn'];
} else if (array_key_exists('eppn', $_SERVER)) {
  $username = $_SERVER['eppn'];
}

$candidate_tools = ma_list_clients($ma_url, $km_signer);
$authorized_tools_for_user = 
  ma_list_authorized_clients($ma_url, $km_signer, $member_id);

// error_log("CT = " . print_r($candidate_tools, true));
// error_log("AT4U = " . print_r($authorized_tools_for_user, true));

print "<h2>GENI Key Management Tool</h2><br><br>\n";

print "By clicking on an \"Authorize\" button in the table below, " . 
      "you are allowing the given tool to sign statements " . 
"on your behalf in interactions with the GENI Clearinghouse.<br><br>";

print "By clicking on a \"Deauthorize\" in the table below, " . 
      "you are removing permission fromthe given tool to sign statements " . 
      "on your behalf in interactions with the GENI Clearinghouse. <br><br>";

// If invoked with a ?redirect=url argument, grab that 
// argument and go there from the 'continue' button
$redirect_key = "redirect";

$redirect_address = "home.php";

if(array_key_exists($redirect_key, $_GET)) {
  $redirect_address = $_GET[$redirect_key];
}

// error_log("REDIRECT = " . $redirect_address);

// Create table with entry for each tool
print("<table class=\"gridtable\">\n");
print("<tr><th>Tool</th><th>URN</th><th>Authorized</th></tr>\n");
foreach($candidate_tools as $toolname => $toolurn) {
  $enabled = in_array($toolurn, $authorized_tools_for_user);
  //  error_log("EN = " . print_r($enabled, true) . " " . print_r($toolname, true));
  $authorize_url = "km_authorize_tool.php"; 
  $authorize_label = "Authorize";
  $authorize_sense = "true";
  if ($enabled) { 
    $authorize_label = "Deauthorize"; 
    $authorize_sense = "false";
  }
  $toolurn_encoded = urlencode($toolurn);
  $authorize_url = "$authorize_url?authorize_sense=$authorize_sense&authorize_toolname=$toolname&authorize_toolurn=$toolurn_encoded&authorize_userid=$member_id&authorize_username=$username&redirect_address=$redirect_address";
  $enable_cell = "<button onClick=\"window.location='" . 
    $authorize_url . "'\">$authorize_label</button>";
  print("<tr><td>$toolname</td><td>$toolurn</td><td>$enable_cell</td></tr>\n");
}
print("</table>");
print("<br><br>");

print"<button onclick=\"window.location='" . 
    $redirect_address . "'" . "\"<b>Continue</b></button>";


?>