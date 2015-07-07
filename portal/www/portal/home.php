<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2015 Raytheon BBN Technologies
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
require_once('portal.php');
require_once('cs_constants.php');
require_once('maintenance_mode.php');

/* $GENI_TITLE = "GENI Portal Home"; */
/* $ACTIVE_TAB = "Home"; */
require_once("header.php");
$user = geni_loadUser();

relative_redirect("dashboard.php");
show_header('GENI Portal Home', $TAB_HOME);

?>
<div id="home-body">
<?php

include("tool-showmessage.php");
if (is_null($user)) {
  // TODO: Handle unknown state
  print "Unable to load user record.<br/>";
} else {
  if ($user->isRequested()) {
    include("home-requested.php");
  } else if ($user->isDisabled()) {
    print "User $user->eppn has been disabled.";
  } else if ($user->isActive()) {
    include("home-active.php");
  } else {
    // TODO: Handle unknown state
    print "Unknown account state: $user->status<br/>";
  }
}
?>
</div>
<?php
include("footer.php");
?>
