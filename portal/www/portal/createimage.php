<?php
//----------------------------------------------------------------------
// Copyright (c) 2015-2016 Raytheon BBN Technologies
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

// Create image UI.
// Given am_id, slice_id and sliver_id
// Computes slice_name and project_name
// Solicits image_name and public from user
// Then calls image_operations?operation=createimage...
// and 
// If successful, displays the image ID and image URN
// If not, displays error message

?>

<?php

require_once("user.php");
require_once("response_format.php");
require_once("header.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

if(array_key_exists('sliver_id', $_REQUEST)) {
  $sliver_id = $_REQUEST['sliver_id'];
} else {
  error_log("Illegal invocation of createimage.php: no sliver_id provided");
}

require_once("tool-lookupids.php");

?>

<script>

var AM_ID = "<?php echo $am_id ?>";
var SLICE_NAME = "<?php echo $slice_name ?>";
var PROJECT_NAME = "<?php echo $project_name ?>";
var SLIVER_ID = window.location.href.split('sliver_id=')[1];
var AM_URL = "<?php echo $am['service_url'] ?>";

function add_image_info(image_urn, image_id)
{
  var div = $('#create_image_text_div');
  div.empty();

  var line0 = '<p>Your image is being created. You will receive an email ' + 
    'from the aggregate administrator when the image is ready.</p>';
  div.append($(line0));

  var line_table = '<table>' + 
    '<tr><th>Image URN</th><td>' + image_urn + '</td></tr>' + 
    '<tr><th>Image ID</th><td>' + image_id + '</td></tr>' + 
    '</table>';
  div.append($(line_table));
  var line3a = '<p>To use this image at this aggregate in Jacks, ' + 
    '\n\tselect <i>Other...</i> under the Disk Image pulldown and enter the image URN.</pr>';
  div.append($(line3a));
  var line3 = '<p>To delete this image, run the following command from a command line:</p>';
  div.append($(line3));
  var line4 = '<pre><i>omni -a ' + AM_URL + ' deleteimage ' + image_urn + '</i></pre>';
  div.append($(line4));
  var line5 = '<p>To list your images at this aggregate, run the following command from a command line:</p>';
  div.append($(line5));
  var line6 = '<pre><i>omni -a ' + AM_URL + ' listimages </i></pre>';
  div.append($(line6));
  var line7 = '<p>More information about managing images is available ' + 
    '<a href="http://groups.geni.net/geni/wiki/HowTo/ManageCustomImagesInstaGENI">here</a>.</p>';
  div.append($(line7));

}

function add_image_error(msg)
{
  var div = $('#create_image_text_div');
  div.empty();

  var line1 = '<code><b>Error creating image: </b>' + msg + '</code>';
  div.append($(line1));
}

function do_create_image()
{
  image_name = ($('#create_image_name'))[0].value;
  image_public = ($('#create_image_public'))[0].checked;
  var div = $('#create_image_text_div');
  div.empty();
  div.append($('<i>Creating image....</i>'));
  console.log("do_image_create " + image_name + " " + image_public);
  $.getJSON('image_operations.php',
	    {
	    am_id : AM_ID, 
		operation : 'createimage',
		project_name : PROJECT_NAME,
		slice_name : SLICE_NAME,
		sliver_id : SLIVER_ID,
		image_name : image_name,
		public: image_public
		},
	    function (rt, st, xhr) {
	      if (rt.code == 0) {
		var image_urn = rt.value[0];
		var image_id = rt.value[1];
		add_image_info(image_urn, image_id);
	      } else {
		// On failure the return may be a string, so no 'output'
		if (typeof(rt.output) !== 'undefined') {
		  add_image_error(rt.output);
		} else {
		  add_image_error(rt);
		}
	      }
	    })
	    .fail(function (xhr, ts, et) {
		add_image_error(et);
	      }
	    );
}
</script>

<?php

show_header('GENI Portal: Slices');

// error_log("GET = " . print_r($_GET, true));
// error_log("AM = " . print_r($am, true));

print "<h1>Create Image on Selected Node</h1>";

print "<form method='POST' >";

print "<table><tr><th>Image Name</th><td><input type='text' id='create_image_name' size'30' required></td></tr>";
print "<tr><th>Image Visibility</th><td>Public <input type='radio' checked='checked' name='create_image_visibility' id='create_image_public'> Private <input type='radio' name='create_image_visibility' id='create_image_private'></td></tr>";
print "</table>";
print "<p>Note: Image name must be non-empty and alphanumeric only.</p>\n";

print "<input type='button' value='Create' onclick='do_create_image()' />";
print "<input type='button' value='Back' onclick='history.back(-1)'/>";

print "</form>";

print "<div id='create_image_text_div' style='display:block' >";
print "</div>";


?>

<?php
include("footer.php");
?>


