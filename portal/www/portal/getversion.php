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
?>
<?php
require_once("settings.php");
require_once("user.php");
require_once("file_utils.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("am_client.php");
require_once("sa_client.php");
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive() ) {
  relative_redirect('home.php');
}
?>
<?php
// Get an AM
$am_url = get_first_service_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
// error_log("AM_URL = " . $am_url);

$result = get_version($am_url, $user);
// error_log("VERSION = " . $result);


error_log("GetVersion output = " . $result);

$header = "GetVersion";
$text = $result;

require_once("header.php");
show_header('GENI Portal: Debug',  $TAB_DEBUG);
print "<h2>$header</h2>\n";

$text2 = explode("\n",$text);
foreach ($text2 as $line_num => $line) {
    echo htmlspecialchars($line) . "<br />\n";
}

print "\n";
include("footer.php");

?>
