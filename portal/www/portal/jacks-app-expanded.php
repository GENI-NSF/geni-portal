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

require_once("header.php");
require_once("settings.php");
require_once("user.php");
require_once("jacks-app.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

setup_jacks_slice_context();

echo '<html><body><meta charset="utf-8">';
echo '<div id="content" >';

echo '<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/humanity/jquery-ui.css" rel="Stylesheet" />';
echo '<link type="text/css" href="/common/css/portal.css" rel="Stylesheet"/>';
echo '<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700|PT+Serif:400,400italic|Droid+Sans+Mono" rel="stylesheet" type="text/css">';
// echo '<script src="' . $jacks_stable_url . '"></script>';


?>

<link rel="stylesheet" type="text/css" href="slice-jacks.css" />
<link rel="stylesheet" type="text/css" href="jacks-app.css" />
<link rel="stylesheet" type="text/css" href="jacks-editor-app.css" />
<link rel="stylesheet" type="text/css" href="slice-table.css" />

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>

<script>var jacks_app_expanded = true;</script>

<script src="jacks-lib.js"></script>
<script src="jacks-app.js"></script>
<script src="portal-jacks-app.js"></script>
<script src="portal-jacks-editor-app.js"></script>
<script src="<?php echo $jacks_stable_url;?>"></script>

<script>
  var slice_id = <?php echo json_encode($slice_id); ?>;
  var slice_name = <?php echo json_encode($slice_name); ?>;

  // AMs that the Portal says there are resources at.
  var jacks_slice_ams = <?php echo json_encode($slice_ams) ?>;
  var jacks_all_ams = <?php echo json_encode($all_ams) ?>;
  var jacks_slice_id = <?php echo json_encode($slice_id) ?>;
  var jacks_slice_name = <?php echo json_encode($slice_name) ?>;
  var jacks_slice_urn= <?php echo json_encode($slice_urn) ?>;
  var jacks_slice_expiration = <?php echo json_encode($slice_expiration) ?>;

  var jacks_slice_info = {slice_id : jacks_slice_id, 
			  slice_name : jacks_slice_name,
			  slice_urn : jacks_slice_urn, 
			  slice_expiration : jacks_slice_expiration};

  var jacks_user_name = <?php echo json_encode($user->username) ?>;
  var jacks_user_urn = <?php echo json_encode($user->urn) ?>;
  var jacks_user_id = <?php echo json_encode($user->account_id) ?>;

  var jacks_user_info = {user_name : jacks_user_name,
			 user_urn : jacks_user_urn,
			 user_id : jacks_user_id};

  // AMs that the Portal says there are resources at.
  var jacks_slice_ams = <?php echo json_encode($slice_ams) ?>;
  var jacks_all_ams = <?php echo json_encode($all_ams) ?>;

  var jacks_slice_id = <?php echo json_encode($slice_id) ?>;
  var jacks_slice_name = <?php echo json_encode($slice_name) ?>;

  var jacks_slice_info = {slice_id : jacks_slice_id, 
			  slice_name : jacks_slice_name};

  var jacks_user_name = <?php echo json_encode($user->username) ?>;
  var jacks_user_urn = <?php echo json_encode($user->urn) ?>;
  var jacks_user_id = <?php echo json_encode($user->account_id) ?>;

  var jacks_user_info = {user_name : jacks_user_name,
			 user_urn : jacks_user_urn,
			 user_id : jacks_user_id};

  var jacks_enable_buttons = true;

</script>


<?php

echo "<table style=\"margin-left: 0px;width:95%;height:20px\"><tr><th>Resources for Slice $slice_name</th></tr></table>";

print "<table style=\"margin-left: 0px; width:95%; height:75%\" id='jacks-app'><tbody>";
print "<tr><td><div id='jacks-app-container' style='width:100%; height:100%'>";
print build_jacks_viewer();

?>

<script>

$(document).ready(function() {

    // This function will start up a Jacks viewer, get the status bar going
    // and set up all of the button clicks.
    var jacksApp = new JacksApp('#jacks-pane', '#jacks-status', 
				'#jacks-status-history', '#jacks-buttons',
				jacks_slice_ams, jacks_all_ams, 
				jacks_slice_info,
				jacks_user_info,
				portal_jacks_app_ready);
 
    jacksApp.hideStatusHistory();

    var pane = $("#jacks-pane")[0];
    pane.style.height ="90%";
    pane.style.width ="100%";
  });

</script>

<?php

print "</div></td></tr></tbody></table>";

echo '</div>';

include("footer.php");

?>
