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

require_once("header.php");
require_once("settings.php");
require_once("user.php");
require_once("file_utils.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("am_client.php");
require_once("sa_client.php");
require_once("am_map.php");
require_once("json_util.php");

$user = geni_loadUser();
if (! $user->isActive()) {
  relative_redirect('home.php');
}

function no_slice_error() {
  header('HTTP/1.1 404 Not Found');
  print 'No slice id specified.';
  exit();
}

 if (! count($_GET)) {
  // No parameters. Return an error result?
  // For now, return nothing.
  no_slice_error();
}
unset($slice);
include("tool-lookupids.php");
if (! isset($slice)) {
  no_slice_error();
}

if (!$user->isAllowed(SA_ACTION::LOOKUP_SLICE, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
  relative_redirect('home.php');
}

if (array_key_exists("pretty", $_REQUEST)){
  $pretty = $_REQUEST['pretty'];
  if (strtolower($pretty) == "false") {
    $pretty = False;
  } else {
    $pretty = True;
  }
} else {
  $pretty=True;
}

$header = "Status of Slivers on slice: $slice_name";

show_header('GENI Portal: Slices',  $TAB_SLICES);
include("tool-breadcrumbs.php");

?>

<script src="amstatus.js"></script>
<script>
var slice= "<?php echo $slice_id ?>";
// var am_id= "<?php echo $am_id ?>";
$(document).ready(build_agg_table_on_sliverstatuspg);
</script>

<?php
print "<h2>$header</h2>\n";
        
if ($pretty) {
  echo "<div id='sliverstatus'><table id='sliverstatus'></table></div>";	
} else {
  echo "<div class='xml'>\n";
  /* json_encode accepts JSON_PRETTY_PRINT in PHP 5.4, but
   * we've got 5.3. Use a third-party utility instead.
   */
//FIXME add back    echo "<pre>\n" . json_indent(json_encode($obj)) . "\n</pre>\n";
  echo "\n</div>\n";
}

print "<div id='slivererror'></div>";
print "<table id='slivererror'></table></div>";

  if (isset($am_id) && $am_id ) {
    $am_id_str = "&am_id=$am_id";
  } else {
    $am_id_str = "";
  }
  print "<a href='sliverstatus.php?pretty=False&slice_id=".$slice_id.$am_id_str."'>Raw SliverStatus</a>";
  print "<br/>";
  print "<br/>";

print "<a href='slices.php'>Back to All slices</a>";
print "<br/>";
print "<a href='slice.php?slice_id=$slice_id'>Back to Slice $slice_name</a>";
include("footer.php");

?>
