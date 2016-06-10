<?php
//----------------------------------------------------------------------
// Copyright (c) 2016 Raytheon BBN Technologies
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

$user = geni_loadUser();
if (! isset($user)) {
  relative_redirect("home.php");
}

function no_slice_error() {
  header('HTTP/1.1 404 Not Found');
  print 'No slice id specified.';
  exit();
}

include("tool-lookupids.php");

if (! isset($slice)) {
  no_slice_error();
}

// TODO: What permission check should be made here?
if (!$user->isAllowed(SA_ACTION::RENEW_SLICE, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
  relative_redirect('home.php');
}

show_header('GENI Portal: Update Keys');
include("tool-breadcrumbs.php");
?>

<script src="updatekeys.js"></script>
<script>
function startUpdateKeys() {
  updatekeys("<?php echo $slice_id ?>");
}
$(document).ready(startUpdateKeys);
</script>

<h2>Update SSH Keys</h2>
<div class='resources' id='prettyxml'>
<p id='status_working' style='display:block;'>
  <i>Updating SSH keys on slice <?php echo $slice_name; ?>...</i>
  <br>
  <span style='font-size:75%;margin-left:10px;'>
    (This could take a while if the slice is large)
  </span>
</p>
<p id='status_complete' style='display:none;'>
  SSH key update complete on slice <?php echo $slice_name; ?>
</p>
<p id='status_failed' style='display:none;'>
  Error: Unable to update SSH keys on slice <?php echo $slice_name; ?>
</p>
</div>

<hr/>
<p>
  <a href='dashboard.php#slices'>Back to All slices</a>
  <br/>
  <a href='slice.php?slice_id=<?php echo $slice_id; ?>'>
    Back to Slice <i><?php echo $slice_name; ?></i>
  </a>
</p>

<?php
include("footer.php");
?>
