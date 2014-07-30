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
?>
<?php
require_once("settings.php");
require_once("user.php");
require_once("file_utils.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("am_client.php");
require_once("sa_client.php");
require_once("print-text-helpers.php");
$user = geni_loadUser();
if (! $user->isActive()) {
  relative_redirect("home.php");
}
?>
<?php
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
$am_id = "";  // initialize this before going into tools
include("tool-lookupids.php");
if (! isset($slice)) {
  no_slice_error();
}

if (!$user->isAllowed(SA_ACTION::LIST_RESOURCES, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
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

$header = "Resources on slice: $slice_name";

show_header('GENI Portal: Slices',  $TAB_SLICES);
?>

<script src="https://www.emulab.net/protogeni/jacks-stable/js/jacks"></script>
<script src="amstatus.js"></script>
<script>
  var thisInstance;
  var slice= "<?php echo $slice_id ?>";
  var am_id= <?php echo json_encode($am_ids) ?>;
  var pretty= "<?php echo $pretty ? 'true' : 'false';?>";
  $(document).ready(build_details_table);
  //$(document).ready(add_all_logins_to_manifest_table);
  $(document).ready(function() {
  	$('#rawResource').click(function() {
      $('.rawRSpec').each(function() {
        $(this).attr('style', '');
      });
      $(this).parent().attr('style', 'display: none;');
      $('#hideRawResource').parent().attr('style', '');
    });

    $('#hideRawResource').click(function() {
      $('.rawRSpec').each(function() {
        $(this).attr('style', 'display: none;');
      });
      $(this).parent().attr('style', 'display: none;');
      $('#rawResource').parent().attr('style', '');
    });
  });
</script>

<?php

include("tool-breadcrumbs.php");


print "<h2>$header</h2>\n";

print "<div class='aggregate' id='prettyxml'>";
print "<p id='query' style='display:block;'><i>Querying aggregates for details about resources...</i></p>";

print "<p id='summary' style='display:none;'><i>Queried <span id='numagg'>0</span> of <span id='total'>0</span> aggregates. </i><br/>";
print "<p id='noresources' style='display:none;'><i>You have no resources</i><br/>";
// print "<button id='reload_all_button' type='button' onclick='location.reload(true)' $get_slice_credential_disable_buttons>Refresh</button></p>";
print "</p>";
print "</div>\n";

echo "<div id='details'>
</div>";	

print "<div id='slivererror'>";
print "<table id='slivererror'></table>
</div>";

if ($pretty) {
  if (isset($am_id) && $am_id ){
    $am_id_str = "&am_id=$am_id";
  } else {
    $am_id_str = "";
  }

}

print "<p><a id='rawResource' style='cursor: pointer;'>Show Raw XML Resource Specification (Manifest)</a></p>";
print "<p style='display:none;'><a id='hideRawResource' style='cursor: pointer;'>Hide Raw XML Resource Specification (Manifest)</a></p>";
print "<hr/><p>";
print "<a href='slices.php'>Back to All slices</a>";
print "<br/>";
print "<a href='slice.php?slice_id=$slice_id'>Back to Slice <i>$slice_name</i></a></p>";
include("footer.php");

?>
