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

require_once('user.php');
require_once('sr_client.php');
require_once('sr_constants.php');
require_once('ma_client.php');
require_once('ma_constants.php');

if (!isset($user)) {
  $user = geni_loadUser();
}
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

/*
 * jFed will use the outside cert and key.
 * However, if the portal does not have an outside key, send nothing,
 * and jFed will prompt the user.
 * If the user has no outside cert, redirect with an error.
 */
// Look up the key...
if (! isset($ma_url)) {
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  if (! isset($ma_url) || is_null($ma_url) || $ma_url == '') {
    error_log("Found no MA in SR!'");
    relative_redirect("error-text.php");
  }
}
$result = ma_lookup_certificate($ma_url, $user, $user->account_id);
$expiration_key = 'expiration';
$has_certificate = False;
$has_key = False;
$expired = False;
$expiration = NULL;
if (! is_null($result)) {
  $has_certificate = True;
  $has_key = array_key_exists(MA_ARGUMENT::PRIVATE_KEY, $result);
  if (array_key_exists($expiration_key, $result)) {
    $expiration = $result[$expiration_key];
    $now = new DateTime('now', new DateTimeZone("UTC"));
    $expired = ($expiration < $now);
  }
}
if (! $has_certificate or $expired) {
  $_SESSION['lasterror'] = "<a href='profile.php#ssl'>Generate a key pair</a> as for use with Omni to use jFed.";
  redirect_referer("home.php");
}

$params = '';
if ($has_key) {
  $certstring = $result[MA_ARGUMENT::PRIVATE_KEY] . "\n" . $result[MA_ARGUMENT::CERTIFICATE];
  $params = "params: {'login-certificate-string' : '" . base64_encode($certstring) . "' } },";
}

// FIXME: Could make this simply produce the HTML for the button? Or make this a page you launch in a new window that auto calls launchjFed()?

// FIXME: Chrome on Mac is not supported - it's a 32bit browser, and Java7 needs 64bit.. Warn user in advance?
// FIXME: Java on FF on Mac has to be updated for jFed to work (to Java7)
// Mac OSX 10.6 and below you use Software Update to update Java
// It's Apple Java 6 vs Oracle Java 7. Can't have both.
// Mac OSX 10.7+ does not come with Java
// Java Webstart doesn't work using Apple Java
// Once you install Oracla Java 7, then Apple Java 6 won't run, so no more Java from Chrome.

// Also, you'll be prompted if you want to let this applet run. Then you'll get a security warning potentially (or is that just our dev server).

// See http://php.net/manual/en/function.get-browser.php#101125
function getBrowser() 
{ 
    $u_agent = $_SERVER['HTTP_USER_AGENT']; 
    $bname = 'Unknown';
    $platform = 'Unknown';
    $version= "";

    //First get the platform?
    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'linux';
    }
    elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'mac';
    }
    elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'windows';
    }
    
    // Next get the name of the useragent yes seperately and for good reason
    if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) 
    { 
        $bname = 'Internet Explorer'; 
        $ub = "MSIE"; 
    } 
    elseif(preg_match('/Firefox/i',$u_agent)) 
    { 
        $bname = 'Mozilla Firefox'; 
        $ub = "Firefox"; 
    } 
    elseif(preg_match('/Chrom/i',$u_agent)) 
    { 
        $bname = 'Google Chrome'; 
        $ub = "Chrome"; 
    } 
    elseif(preg_match('/Safari/i',$u_agent)) 
    { 
        $bname = 'Apple Safari'; 
        $ub = "Safari"; 
    } 
    elseif(preg_match('/Opera/i',$u_agent)) 
    { 
        $bname = 'Opera'; 
        $ub = "Opera"; 
    } 
    elseif(preg_match('/Netscape/i',$u_agent)) 
    { 
        $bname = 'Netscape'; 
        $ub = "Netscape"; 
    } 
    
    // finally get the correct version number
    $known = array('Version', $ub, 'other');
    $pattern = '#(?<browser>' . join('|', $known) .
    ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {
        // we have no matching number just continue
    }
    
    // see how many we have
    $i = count($matches['browser']);
    if ($i != 1) {
        //we will have two since we are not using 'other' argument yet
        //see if version is before or after the name
        if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
            $version= $matches['version'][0];
        }
        else {
            $version= $matches['version'][1];
        }
    }
    else {
        $version= $matches['version'][0];
    }
    
    // check if we have a number
    if ($version==null || $version=="") {$version="?";}
    
    return array(
        'userAgent' => $u_agent,
        'name'      => $bname,
        'version'   => $version,
        'platform'  => $platform,
        'pattern'    => $pattern
    );
} 

$browser = getBrowser();
if (strpos(strtolower($browser["name"]), "chrom") !== false and strpos(strtolower($browser["platform"]),"mac") === 0) {
  error_log("User browser: " . $browser["name"] . " version " . $browser["version"] . " on " . $browser["platform"]);
  error_log("User running Chrome on Mac. Can't launch. User should try Safari or Firefox.");
  $_SESSION['lasterror'] = "jFed cannot run in Chrome on a Mac. Try Safari or Firefox.";
  redirect_referer("home.php");
}
//                dtjava.launch( { url : 'https://flsmonitor.fed4fire.eu/jfedexperimenter/geni/jfed-geni.jnlp',

?>
<html>
<head>
	<script src="dtjava_orig.js"></script>
	<script>
		function launchjFed() {
                dtjava.launch( { url : 'http://jfed.iminds.be/jfed-geni.jnlp',
		      <?php echo $params; ?>
                         { javafx : '2.2+' }, {} );
                return false;
	}
	</script>
</head>
<body>
<h1>jFed with predefined credential</h1>

<button id="start" type="button" onclick="launchjFed()">Start jFed</button>
</body>
</html>
