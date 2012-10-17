<?php
//----------------------------------------------------------------------
// Copyright (c) 2012 Raytheon BBN Technologies
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
  $protocol = "http";
  if (array_key_exists('HTTPS', $_SERVER)) {
    $protocol = "https";
  }
  $host  = $_SERVER['HTTP_HOST'];
  $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
  $extra = $relpath;
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
  $date_dt = new DateTime($dateString); 
  // See http://www.w3.org/QA/Tips/iso-date which argues for ISO8601 date formats
  // ISO8601
  //  $prettyDateString = $date_dt->format('c');
  // Mostly ISO8601, but spell out the time zone
  $prettyDateString = $date_dt->format('Y-m-d H:i:s e');
  // Spell out the month name
  //    $prettyDateString = $date_dt->format('j-M-Y H:i:s e');
  return $prettyDateString;
}
?>