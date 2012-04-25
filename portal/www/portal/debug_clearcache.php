<?php
//----------------------------------------------------------------------
// Copyright (c) 2011 Raytheon BBN Technologies
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

/**
 * Clear the cache. Remove all keys from the _SESSION array.
 */


$cleared_keys = array();
session_start();
if (isset($_SESSION)) {
  foreach (array_keys($_SESSION) as $skey) {
    $cleared_keys[] = $skey;
    unset($_SESSION[$skey]);
  }
}

require_once("user.php");
require_once("header.php");
show_header('GENI Portal: Clear cache', $TAB_DEBUG);

if (count($cleared_keys) > 0) {
  print "<h1>Cache Cleared</h1>\n";
  print "The following session keys have been unset:\n";
  print "<ul>\n";
  foreach ($cleared_keys as $key) {
    print "<li>$key</li>\n";
  }
  print "</ul>\n";
} else {
  print "The cache contained no values.\n";
}

include("footer.php");
?>
