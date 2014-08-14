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

//----------------------------------------------------------------------
// Utility functions
//----------------------------------------------------------------------

//--------------------------------------------------
// Compute a url relative to the current page.
//--------------------------------------------------
function relative_url($relpath) {
  if (! isset($relpath) or is_null($relpath)) {
    // Asked for a relative URL to empty.
    error_log("Asked for relative URL to empty path");
    $relpath = '';
  }

  // Now check that this is in fact a page and we can do a relative redirect
  $relpieces = parse_url($relpath);
  if ($relpieces === FALSE) {
    // completely malformed URL. Go to the server I guess
    error_log("Asked for relative_url from malformed " . $relpath);
    $relpath = '';
  } else {
    // Make sure this is not already an absolute URL
    if (array_key_exists('scheme', $relpieces) and isset($relpieces['scheme']) and ! is_null($relpieces['scheme']) and \
	array_key_exists('host', $relpieces) and isset($relpieces['host']) and ! is_null($relpieces['host'])) {
      // Appears to already be an absolute path. Huh?
      error_log("Asked for relative_url to absolute path " . $relpath);
      // Return what they asked for? Or force it to be a link on this server?

      // Here is code that would force us to always go to a URL on this server
      $relpath = '';
      if (array_key_exists('path', $relpieces) and isset($relpieces['path']) and ! is_null($relpieces['path'])) {
	$relpath = $relpieces['path'];
	if (array_key_exists('fragment', $relpieces) and isset($relpieces['fragment']) and ! is_null($relpieces['fragment'])) {
	  $relpath = $relpath . '#' . $relpieces['fragment'];
	}
	if (array_key_exists('query', $relpieces) and isset($relpieces['query']) and ! is_null($relpieces['query'])) {
	  $relpath = $relpath . '?' . $relpieces['query'];
	}
      }

      // But here we just go where they asked
      //      return $relpath;
    } else {
      // missing scheme or host, so treat as relative. Normal case.
    }
  }
  $protocol = "http";
  if (array_key_exists('HTTPS', $_SERVER)) {
    $protocol = "https";
  }
  $host  = $_SERVER['SERVER_NAME'];
  $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
  $extra = $relpath;
  if (strpos($extra, $uri) === 0) {
    // Extra starts with URI already - trim that off
    $extra = substr($extra, strlen($uri));
  }
  // If extra starts with /, pull that first / off extra
  if (strpos($extra, '/') === 0) {
    $extra = substr($extra, 1);
  }
  return "$protocol://$host$uri/$extra";
}

//--------------------------------------------------
// Redirect to a url relative to the current page.
//--------------------------------------------------
function relative_redirect($relpath) {
  $url = relative_url($relpath);
  header("Location: $url");
  exit;
}

/*
 * Generate the URL for the InCommon federated error handling service
 * for redirection.
 */
function incommon_feh_url() {
  $error_service_url = 'https://ds.incommon.org/FEH/sp-error.html?';
  $params['sp_entityID'] = "https://" . $_SERVER['SERVER_NAME'] . "/shibboleth";
  $params['idp_entityID'] = $_SERVER['Shib-Identity-Provider'];
  $query = http_build_query($params);
  $url = $error_service_url . $query;
  return $url;
}

/**
 * Redirect to the referer. If no referer,
 * redirect to $fallback.
 */
function redirect_referer($fallback) {
  $referer_key = 'HTTP_REFERER';
  if (key_exists($referer_key, $_SERVER)) {
    header("Location: " . $_SERVER[$referer_key]);
  } else if (! is_null($fallback)) {
    relative_redirect($fallback);
  }
}

// Determine if a uuid is valid
function uuid_is_valid($uuid) {
  if (! isset($uuid) || is_null($uuid)) {
    return false;
  }
  return (boolean) preg_match('/^[A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12}$/', $uuid);
}

function selfURL() {
  $protocol = "http";
  $port = "80";
  if (array_key_exists('HTTPS', $_SERVER)) {
    $protocol = "https";
    $port = "443";
  }
  if ($_SERVER["SERVER_PORT"] !== $port) {
    $port = ":" . $_SERVER["SERVER_PORT"];
  } else {
    $port = "";
  }
  return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];
}

function dateUIFormat($dateString) {
  // Note this leaves the TZ alone, which we hope is UTC
  // Note also that if you don't supply a dateString, you'll get the current date-time.
  if (is_a($dateString, 'DateTime')) {
    $date_dt = $dateString;
  } else {
    $date_dt = new DateTime($dateString);
  }
  // See http://www.w3.org/QA/Tips/iso-date which argues for ISO8601 date formats
  // ISO8601
  //  $prettyDateString = $date_dt->format('c');
  // Mostly ISO8601, but spell out the time zone
  $prettyDateString = $date_dt->format('Y-m-d H:i:s e');
  // Spell out the month name
  //    $prettyDateString = $date_dt->format('j-M-Y H:i:s e');
  return $prettyDateString;
}

function dateOnlyUIFormat($dateString) {
  // Note this leaves the TZ alone, which we hope is UTC
  // Note also that if you don't supply a dateString, you'll get the current date-time.
  if (is_a($dateString, 'DateTime')) {
    $date_dt = $dateString;
  } else {
    $date_dt = new DateTime($dateString);
  }
  // See http://www.w3.org/QA/Tips/iso-date which argues for ISO8601 date formats
  // ISO8601
  //  $prettyDateString = $date_dt->format('c');
  // Mostly ISO8601, but spell out the time zone
  $prettyDateString = $date_dt->format('Y-m-d');
  // Spell out the month name
  //    $prettyDateString = $date_dt->format('j-M-Y H:i:s e');
  return $prettyDateString;
}

function rfc3339Format($date_string)
{
  $date_dt = new DateTime($date_string);
  $rfc3339 = $date_dt->format(DateTime::RFC3339);
  return $rfc3339;
}

// Are the candidates all in the given list
// If 'true_if_any' is true, return true if ANY candidate is in the list, not ALL
function already_in_list($candidates, $members, $true_if_any)
{
  $all_members = true;
  foreach($candidates as $candidate) {
    if (in_array($candidate, $members)) {
	if ($true_if_any) 
	  return true;
      } else {
      $all_emmbers = false;
    }
  }
  return $all_members;
}

// Parse HTTP_USER_AGENT
// Return array of userAgent, name (of browser), version (of browser), platform (ie OS), pattern
// See http://php.net/manual/en/function.get-browser.php#101125
function getBrowser() {
  $u_agent = $_SERVER['HTTP_USER_AGENT'];
  $bname = 'Unknown';
  $platform = 'Unknown';
  $version= "";

  //First get the platform?
  if (preg_match('/linux/i', $u_agent)) {
    $platform = 'linux';
  } else if (preg_match('/macintosh|mac os x/i', $u_agent)) {
    $platform = 'mac';
  } else if (preg_match('/windows|win32/i', $u_agent)) {
    $platform = 'windows';
  }

  // Next get the name of the useragent yes seperately and for good reason
  if (preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) {
    $bname = 'Internet Explorer';
    $ub = "MSIE";
  } else if (preg_match('/Firefox/i',$u_agent)) {
    $bname = 'Mozilla Firefox';
    $ub = "Firefox";
  } else if (preg_match('/Chrom/i',$u_agent)) {
    $bname = 'Google Chrome';
    $ub = "Chrome";
  } else if (preg_match('/Safari/i',$u_agent)) {
    $bname = 'Apple Safari';
    $ub = "Safari";
  } else if (preg_match('/Opera/i',$u_agent)) {
    $bname = 'Opera';
    $ub = "Opera";
  } else if (preg_match('/Netscape/i',$u_agent)) {
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
    if (strripos($u_agent,"Version") < strripos($u_agent,$ub)) {
      $version = $matches['version'][0];
    } else {
      $version = $matches['version'][1];
    }
  } else {
    $version = $matches['version'][0];
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
?>
