<?php

/**
 * Home of GENI key management tool
 */

require_once('signer.php');
require_once('sr_constants.php');
require_once("sr_client.php");
require_once("user.php");
require_once('km_utils.php');

$sr_url = get_sr_url();
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);

// error_log("MA = " . print_r($ma_url, true));

$mycertfile = "/usr/share/geni-ch/km/km-cert.pem";
$mykeyfile = "/usr/share/geni-ch/km/km-key.pem";
$mycert = file_get_contents($mycertfile);
$mykey = file_get_contents($mykeyfile);

// error_log("CERT = $mycert");
// error_log("KEY = $mykey");

$user = Geni_loadUser();
// error_log("USER = " . print_r($user, true));

$candidate_tools = get_candidate_tools();
$authorized_tools_for_user = get_authorized_tools_for_user($user);

print "<h2>GENI Key Management Tool</h2><br><br>\n";

print "By clicking on an \"Authorize\" button in the table below, " . 
      "you are allowing the given tool to sign statements " . 
"on your behalf in interactions with the GENI Clearinghouse.<br><br>";

print "By clicking on a \"Deauthorize\" in the table below, " . 
      "you are removing permission fromthe given tool to sign statements " . 
      "on your behalf in interactions with the GENI Clearinghouse. <br><br>";

$username = $user->prettyName();

// If invoked with a ?redirect=url argument, grab that 
// argument and go there from the 'continue' button
$redirect_key = "redirect";

$redirect_address = "home.php";

if(array_key_exists($redirect_key, $_GET)) {
  $redirect_address = $_GET[$redirect_key];
}

// error_log("REDIRECT = " . $redirect_address);



// Create table with entry for each tool
print("<table>\n");
print("<tr><th>Tool</th><th>Authorized</th></tr>\n");
foreach($candidate_tools as $toolname => $toolurl) {
  $enabled = array_key_exists($toolname, $authorized_tools_for_user);
  //  error_log("EN = " . print_r($enabled, true) . " " . print_r($toolname, true));
  $authorize_url = "km_authorize_tool.php"; 
  $authorize_label = "Authorize";
  $authorize_sense = "true";
  if ($enabled) { 
    $authorize_label = "Deauthorize"; 
  $authorize_sense = "false";
  }
  $authorize_url = "$authorize_url?authorize_sense=$authorize_sense&authorize_toolname=$toolname&authorize_toolurl=$toolurl&authorize_user=$username&redirect_address=$redirect_address";
  $enable_cell = "<button onClick=\"window.location='" . 
    $authorize_url . "'\">$authorize_label</button>";
  print("<tr><td>$toolname</td><td>$enable_cell</td></tr>\n");
}
print("</table>");
print("<br><br>");

print"<button onclick=\"window.location='" . 
    $redirect_address . "'" . "\"<b>Continue</b></button>";


/**
 * Need to make this redirect to ../$redirect_address
 * Need to put up a table with check boxes for all the tools
 * Need to find the user's information
 * Get the KM's keys to sign any CM interactiosn
 * WIthin portal, need to tell if you don't have keys and then direct here
 */


?>