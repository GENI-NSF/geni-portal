<?php
//----------------------------------------------------------------------
// Copyright (c) 2011-2015 Raytheon BBN Technologies
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

function do_create_image()
{
  image_name = ($('#create_image_name'))[0].value;
  image_public = ($('#create_image_public'))[0].checked;
  var div = $('#create_image_text_div');
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
	      div.empty();
	      if (rt.code == 0) {
		var image_urn = rt.value[0];
		var image_id = rt.value[1];
		var line1 = '<p><b>Image ID: </b>' + image_id + '</p>';
		div.append($(line1));
		var line2 = '<p><b>Image URN: </b>' + image_urn + '</p>';
		div.append($(line2));
		var line3 = '<br><p>To delete this image, run the following command from a UNIX shell:</p>';
		div.append($(line3));
		var line4 = '<p><i>omni.py -a ' + AM_URL + ' deleteimage ' + image_urn + '</i></p>';
		div.append($(line4));
		var line5 = '<br><p>To list your images at this aggregate, run the following command from a UNIX shell:</p>';
		div.append($(line5));
		var line6 = '<p><i>omni.py -a ' + AM_URL + ' listimages </i></p>';
		div.append($(line6));
	      } else {
		var line1 = '<p><b>Error creating image: </b>' + rt.output + '</p>';
		div.append($(line1));
	      }
	    })
	    .fail(function (xhr, ts, et) {
		div.empty();
		var line1 = '<p><b>Error creating image: </b>' + et + '</p>';
		div.append($(line1));
	      }
	    );
}
</script>

<?php

show_header('GENI Portal: Slices', $TAB_SLICES);

// error_log("GET = " . print_r($_GET, true));
// error_log("AM = " . print_r($am, true));

print "<h1>Create Image on Selected Node</h1>";

print "<form method='POST' >";
print "Image Name: <input name='ImageName' type='text' id='create_image_name' size='30'><br>";
print "Public <input name='ImagePublic' type='radio' checked='checked' id='create_image_public'/>";
print "Private <input name='ImagePrivate' type='radio' id='create_image_private'><br>";

print "<input type='button' value='Create' onclick='do_create_image()' />";
print "<input type='button' value='Back' onclick='history.back(-1)'/>";

print "</form>";

print "<div id='create_image_text_div' class='xml' style='display:block' >";
print "</div>";


?>

<?php
include("footer.php");
?>


