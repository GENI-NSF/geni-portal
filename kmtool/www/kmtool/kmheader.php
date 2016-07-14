<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2016 Raytheon BBN Technologies
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

require_once('settings.php');

// This gets used for both the page title and the page header
$title = 'GPO Member Authority';

  echo '<!DOCTYPE HTML>';
  echo '<html lang="en">';
  echo '<head>';
  echo '<meta charset="utf-8">';
  echo '<title>';
  echo "$title";
  echo '</title>';
  echo "<script src='$portal_jquery_url'></script>";

  /* Stylesheet(s) */
  echo '<link type="text/css" href="/common/css/kmtool.css" rel="stylesheet"/>';
  echo '<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700|Droid+Sans+Mono|Material+Icons" rel="stylesheet" type="text/css">';

  /* Close the "head" */
  echo '</head>';
  echo '<body>';
  echo '<div id="header">';
  echo '<a href="http://www.geni.net" target="_blank">';
  echo '<img src="/images/geni.png" width="88" height="75" alt="GENI"/>';
  echo '</a>';
  echo "<h1>$title</h1>";
  echo '<hr/>';
  echo '</div>';
?>
