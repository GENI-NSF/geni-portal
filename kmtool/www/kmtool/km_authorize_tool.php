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

require_once('util.php');
require_once('km_utils.php');
require_once('ma_client.php');

$sense = $_GET["authorize_sense"];
$toolname = $_GET["authorize_toolname"];
$toolurn  = $_GET["authorize_toolurn"];
$toolusername = $_GET["authorize_username"];
$tooluserid = $_GET["authorize_userid"];
$redirect_address = $_GET['redirect_address'];

$sense_text = "authorized";
if ($sense == "false") { $sense_text = "deauthorized"; }

// error_log("ARGS = " . print_r($_GET, true));


$result = ma_authorize_client($ma_url, $km_signer, $tooluserid, $toolurn, $sense);

if ($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
  print "Tool $toolname has been $sense_text for user $toolusername<br><br>\n";
}

print "<button onclick=\"window.location='" . 
    "kmhome.php?redirect=$redirect_address" . "'\">Back</button>";

?>
