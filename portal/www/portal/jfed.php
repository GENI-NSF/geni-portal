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

require_once("user.php");
require_once("header.php");
require_once('util.php');
require_once('tool-jfed.php');

$user = geni_loadUser();
if (! $user) {
  relative_redirect('home.php');
  exit;
}

$slice = NULL;
$slice_urn = '';
$slice_name = '';
if (array_key_exists("slice_id", $_REQUEST)) {
  $slice_id = $_REQUEST['slice_id'];
  if (uuid_is_valid($slice_id)) {
    $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
    $slice = lookup_slice($sa_url, $user, $slice_id);
    $slice_urn = $slice[SA_ARGUMENT::SLICE_URN];
    $slice_name = $slice[SA_ARGUMENT::SLICE_NAME];
  }
}


$browser = getBrowser();
$browser_name = strtolower($browser["name"]);
$do_launch = true;
$body_text = 'Launching jFed ...';

if (strpos($browser_name, "chrom") !== false) {
  $do_launch = false;
  $body_text = ('jFed cannot currently be launched from Chrome.'
                . ' Please try a different browser.');
} else if ($slice) {
  $body_text = "Launching jFed on slice $slice_name ...";
}


$jfedret = get_jfed_strs($user);
$jfed_script_text = $jfedret[0];
$jfed_button_start = $jfedret[1];
$jfed_button_part2 = $jfedret[2];

show_header('GENI Portal: Launch jFed', true, true);

if (! is_null($jfed_button_start)) {
    print $jfed_script_text;
}

if (! is_null($jfed_button_start)) {
?>
<div class="card">
<h1>jFed</h1>
  <p>
    <?php echo $body_text; ?>
  </p>

<?php
if ($do_launch) {
?>
  <script>
  $( document ).ready(function() {
    slice_urn = <?php echo "'$slice_urn';\n"; ?>
    launchjFed();
  });
  </script>
<?php
}
?>

</div>

<?php
}
include("footer.php");
?>