<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2014 Raytheon BBN Technologies
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
require_once('sr_client.php');
require_once('sr_constants.php');
require_once('ma_client.php');
require_once('ma_constants.php');

// Return is an array
// First return element is text to print on the page - preferably in the header
// second is the start of the jFed button - add the close brace,
// label, and the closing </button>
// But check for that 2nd arg being null - if so, avoid printing the button at all
// See tool-slices for sample usage
function get_jfed_strs($user) {
  if (!isset($user)) {
    $user = geni_loadUser();
  }

  if (! isset($ma_url)) {
    $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
    if (! isset($ma_url) || is_null($ma_url) || $ma_url == '') {
      error_log("Found no MA in SR!'");
      return array('', null);
    }
  }

  // Code to set up jfed button
  $jfed_button_start = null;
  $jfed_script_text = '';
  $certresult = ma_lookup_certificate($ma_url, $user, $user->account_id);
  $expiration_key = 'expiration';
  $has_certificate = False;
  $has_key = False;
  $expired = False;
  $expiration = NULL;
  if (! is_null($certresult)) {
    $has_certificate = True;
    $has_key = array_key_exists(MA_ARGUMENT::PRIVATE_KEY, $certresult);
    if (array_key_exists($expiration_key, $certresult)) {
      $expiration = $certresult[$expiration_key];
      $now = new DateTime('now', new DateTimeZone("UTC"));
      $expired = ($expiration < $now);
    }
  }
  if (! $has_certificate or $expired) {
    $jfed_button_start = "<button type='button' onclick='alert(\"<a href=\'profile.php#ssl\'>Generate a key pair</a> to use jFed.\")'";
  } else {
    // Print the 2 script tags needed
    $params = '';
    if ($has_key) {
      $certstring = $certresult[MA_ARGUMENT::PRIVATE_KEY] . "\n" . $certresult[MA_ARGUMENT::CERTIFICATE];
      $params = ", params: {'login-certificate-string' : '" . base64_encode($certstring) . "' }";
    }
    $jfed_script_text = "
	<script src=\"dtjava_orig.js\"></script>
	<script>
		function launchjFed() {
                dtjava.launch( { url : 'http://jfed.iminds.be/jfed-geni.jnlp'
" . $params . "
		      }, { javafx : '2.2+' }, {} );
                return false;
	}
	</script>
";

    $jfed_button_start = "<button id='jfed' type='button' onclick='launchjFed()'";
  }
  return array($jfed_script_text, $jfed_button_start);
}
