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

require_once('user.php');
require_once('header.php');

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
unset($slice);
include("tool-lookupids.php");

echo '<div id="content">';
echo '<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/humanity/jquery-ui.css" rel="Stylesheet" />';
echo '<link type="text/css" href="/common/css/portal.css" rel="Stylesheet"/>';
echo '<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700|PT+Serif:400,400italic|Droid+Sans+Mono" rel="stylesheet" type="text/css">';
  


?>

<script>
var slice_id = <?php echo json_encode($slice_id); ?>
</script>

<?php

echo "<table style=\"margin-left: 0px;width:100%;height:20px\"><tr><th>Geographic View for Slice $slice_name</th></tr></table'>";
echo "<table style=\"margin-left: 0px;width:100%;height:75%\"><tr><td style=\"padding: 0px;margin: 0px\" class='map'>";
include('slice_map.html');
echo "</td></tr></table>";
echo '</div>';

include('footer.php');

?>
