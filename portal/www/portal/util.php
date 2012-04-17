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

require_once('smime.php');
require_once('db_utils.php');

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

// Determine if a uuid is valid
function uuid_is_valid($uuid) {
  if (! isset($uuid) || is_null($uuid)) {
    return false;
  }
  return (boolean) preg_match('/^[A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12}$/', $uuid);
}

?>