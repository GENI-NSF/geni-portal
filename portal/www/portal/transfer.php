<?php
//----------------------------------------------------------------------
// Copyright (c) 2017 Raytheon BBN Technologies
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
require_once("header.php");

// Get the authenticated user for logging
$user = geni_loadUser();
if (! isset($user)) {
        header('X-PHP-Response-Code: 400', true, 400);
        exit;
}

show_header('GENI Portal: Transfer Identity');
include("tool-breadcrumbs.php");
include("tool-showmessage.php");
?>
<script src="transfer.js"></script>
<h1>Transfer Identity</h1>
<p>
This is where you will transfer your identity.
</p>

<label for="gpo_user">User:</label>
<input type="text" name="gpo_user" id="gpo_user"><br/>
<label for="gpo_pass">Password:</label>
<input type="text" name="gpo_pass" id="gpo_pass"><br/>
<button id="gpo_verify">Verify User</button>

<?php
include("footer.php");
?>
