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

$user = geni_loadUser();
if (! $user) {
  relative_redirect('home.php');
  exit;
}

// Variables that may change below
$do_launch = true;
$body_text = 'Launching jFed ...';

//----------------------------------------------------------------------
// If the browser is Chrome, jFed won't start so alert the user.
//----------------------------------------------------------------------
$browser = getBrowser();
$browser_name = strtolower($browser["name"]);
if (strpos($browser_name, "chrom") !== false) {
  $do_launch = false;
  $body_text = ('jFed cannot currently be launched from Chrome.'
                . ' Please try a different browser.');
}

//----------------------------------------------------------------------
// If the user doesn't have an outside certificate or if it's
// expired, point them to creating or renewing one
//----------------------------------------------------------------------
if ($do_launch && ! isset($ma_url)) {
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  if (! $ma_url) {
    error_log("Found no MA in SR in jfed launch()");
    $do_launch = false;
    $body_text = "Sorry, an error has occurred.";
  }
}

if ($do_launch) {
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
  if ($has_certificate && ! $expired) {
    if ($has_key) {
      $certstring = ($certresult[MA_ARGUMENT::PRIVATE_KEY]
                     . "\n" . $certresult[MA_ARGUMENT::CERTIFICATE]);
      $certkey = base64_encode($certstring);
    }
  } else {
    $do_launch = false;
    $verb_phrase = "create an SSL certificate";
    if ($expired) {
      $verb_phrase = "renew your SSL certificate";
    }
    $body_text = ('You must <a href="profile.php#ssl">'
                  . $verb_phrase
                  . '</a> to launch jFed.');
  }
}

//----------------------------------------------------------------------
// Handle the slice argument if one was passed.
//----------------------------------------------------------------------
if ($do_launch) {
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
      $body_text = "Launching jFed on slice $slice_name ...";
    }
  }
}

//----------------------------------------------------------------------
// Finally, display the page
//----------------------------------------------------------------------
show_header('GENI Portal: Launch jFed', true, true);
?>

<div class="card">
<h1>jFed</h1>
  <p>
    <?php echo $body_text; ?>
  </p>

<?php
if ($do_launch) {
?>
  <div id='java7Dialog' title=\"Old Java version detected\" style=\"display: none\">
  <p>The latest version of jFed is only compatible with Java 8 or higher.
     We detected that you are using an older version.</p>
  <p>Please upgrade to Java 8 to get access to the newest version of jFed.
     Otherwise, you can use jFed 5.3.2, which is Java 7-compatible.</p>
  </div>

  <div id='noJavaDialog' title=\"No Java detected\" style=\"display: none\">
  <p>jFed requires Java to run. We however couldn't detect a Java
     installation in your browser.</p>
  <p>Please install the latest version of Java to continue.</p>
  </div>

  <script src="//java.com/js/dtjava.js"></script>
  <script src="https://authority.ilabt.iminds.be/js/jfed_webstart_geni.js"></script>
  <script>
    var config = {
      java8_jnlp: 'http://jfed.iminds.be/jfed-geni-java8.jnlp',
      java7_jnlp: 'http://jfed.iminds.be/jfed-geni-java7.jnlp'
    };
    var certkey = <?php echo "'$certkey';\n"; ?>;
    var slice_urn = <?php echo "'$slice_urn';\n"; ?>
    $( document ).ready(function() {
      launchjFed();
    });
  </script>
<?php
// Close if ($do_launch)
}
?>

</div>

<?php
include("footer.php");
?>
