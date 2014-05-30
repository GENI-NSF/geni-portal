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

require_once 'sr_constants.php';
require_once 'sr_client.php';
require_once 'ma_client.php';
require_once 'portal.php';
require_once 'util.php';
require_once 'km_utils.php';
require_once 'maintenance_mode.php';

$redirect_address = "";

if(array_key_exists('HTTP_REFERER', $_SERVER)) {
  $redirect_address = $_SERVER['HTTP_REFERER'];
}

// If no eppn, go directly to InCommon error page
if (! key_exists('eppn', $_SERVER)) {
  $feh_url = incommon_feh_url();
  header("Location: $feh_url");
  exit;
}

if($in_lockdown_mode) {

  $new_portal = "https://portal.geni.net";
  print "This GENI Clearinghouse is currently transitioning offline.";
  print "</br>";
  print "Creation of new accounts on this Clearinghouse is not allowed.";
  print "</br>";
  print "Please go to the GENI Portal pointing to the new Clearinghouse at " 
    . $new_portal;
  print "</br>";
  print "</br>";
  print "<button onClick=\"window.location='$new_portal'\"><b>New Portal</b></button>";
  return;
}

if ($in_maintenance_mode) {
  print "This GENI Clearinghouse is currently in maintenance mode and cannot register new users.";
  print "<br>";
  print "<button onClick=\"history.back(-1)\"><b>Back</b></button>";
  return;
}



// Get the EPPN now that we know it's there.
$eppn = strtolower($_SERVER['eppn']);

// If no email address and no preasserted email
//    Then redirect to kmnoemail.php
if (! key_exists('mail', $_SERVER)) {
  $asserted_attrs = get_asserted_attributes($eppn);
  if (! key_exists('mail', $asserted_attrs)) {
    relative_redirect('kmnoemail.php');
  }
}

// Avoid double registration by checking if this is a valid
// user before displaying the page. If this user is already
// registered, redirect to the home page.
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$member = ma_lookup_member_by_eppn($ma_url, Portal::getInstance(), $eppn);
  //$attrs = array('eppn' => $eppn);
  //$ma_members = ma_lookup_members($ma_url, Portal::getInstance(), $attrs);
  //$count = count($ma_members);
  //if ($count !== 0) {
if (!is_null($member)) {
  // Existing account, go to home page or to referer
  if ($redirect_address != '') {
    relative_redirect($redirect_address);
  } else {
    relative_redirect("kmhome.php");
  }
}

include("kmheader.php");
print "<h2> GENI Account Activation Page </h2>\n";
include("tool-showmessage.php");
?>

<br/>
In order to activate your GENI account, you must first agree to GENI
policies:<br/>
<ul>
  <li><a href="http://groups.geni.net/geni/attachment/wiki/RUP/RUP.pdf">GENI resource Recommended Use Policy</a>: GENI participants must follow these guidelines in using resources.</li>
   <li>Ethics: Be respectful of other GENI experimenters - these are shared resources.</li>
   <li><a href="../policy/privacy.html">Privacy</a>: Some personal information, including that provided from InCommon, may be shared among GENI operators.</li>
   <li>Cite GENI: If you use GENI in your research or classroom, you must say so in your published papers or other documents. You may make this acknowledgement by citing the following paper:<br/>
 <a href="http://www.sciencedirect.com/science/article/pii/S1389128613004507">GENI: A federated testbed for innovative network experiments</a>.<br/>
 This <a href="http://groups.geni.net/geni/attachment/wiki/GENIBibliography/geni-overview-paper.bib?format=raw">BibTeX entry</a> may be used to cite GENI.<br/>
<br/>
 If you send us a citation for your paper, we'll include it in the <a href="http://groups.geni.net/geni/wiki/GENIBibliography">GENI Bibliography</a>.</li>
</ul>
<br/>
   You must also acknowledge that the GENI Clearinghouse and experimenter portal are currently in a beta release stage. There are bugs and missing features. Let us know, and we will try to address the issues.
<br/>
<br/>
<form method="POST" action="do-register.php">
<input type="checkbox" name="agree" value="agree">I agree to the GENI policies.<br/>
<?php
  /* Do not provide the checkbox in the speaks-for world. */
  if (!$speaks_for_enabled) {
?>
<br>
If authorized to do so, the GENI portal can help you reserve and
manage GENI resources, and is recommended for most GENI users.<br/><br/>
<input type="checkbox" name="portal" value="portal" checked="checked">I authorize the GENI Portal to act on my behalf in GENI.<br/>
<br/>
<?php
  }
?>
<input type="submit" value="Activate"/>
</form>
<?php
include("kmfooter.php");
?>
