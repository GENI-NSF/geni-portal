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

require_once('user.php');
require_once('sr_client.php');
require_once('sr_constants.php');
require_once('ma_client.php');
require_once('ma_constants.php');
require_once('util.php');
require_once('tool-jfed.php');

if (!isset($user)) {
  $user = geni_loadUser();
}
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

/*
 * jFed will use the outside cert and key.
 * However, if the portal does not have an outside key, send nothing,
 * and jFed will prompt the user.
 * If the user has no outside cert, redirect with an error.
 */
// Look up the key...
if (! isset($ma_url)) {
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  if (! isset($ma_url) || is_null($ma_url) || $ma_url == '') {
    error_log("Found no MA in SR!'");
    relative_redirect("error-text.php");
  }
}

// Code to set up jfed button
$jfedret = get_jfed_strs($user);
$jfed_script_text = $jfedret[0];
$jfed_button_start = $jfedret[1];
  // End of jFed section


// FIXME: Could make this simply produce the HTML for the button? Or make this a page you launch in a new window that auto calls launchjFed()?

// FIXME: Chrome on Mac is not supported - it's a 32bit browser, and Java7 needs 64bit.. Warn user in advance?
// FIXME: Java on FF on Mac has to be updated for jFed to work (to Java7)
// Mac OSX 10.6 and below you use Software Update to update Java
// It's Apple Java 6 vs Oracle Java 7. Can't have both.
// Mac OSX 10.7+ does not come with Java
// Java Webstart doesn't work using Apple Java
// Once you install Oracla Java 7, then Apple Java 6 won't run, so no more Java from Chrome.

// Also, you'll be prompted if you want to let this applet run. Then you'll get a security warning potentially (or is that just our dev server).


print "<html><head>\n";
print $jfed_script_text;
?>
</head>
<body>
<h1>Test Launch jFed with predefined credential</h1>
<?php
  // Show a jfed button if there wasn't an error generating it
  if (! is_null($jfed_button_start)) {
    print $jfed_button_start . "><b>Start jFed</b></button>";
  }
?>
</body>
</html>
