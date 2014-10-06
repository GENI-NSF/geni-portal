<?php
//----------------------------------------------------------------------
// Copyright (c) 2013-2014 Raytheon BBN Technologies
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

/*
 * Redirect the experimenter to GENI Desktop with the given slice as
 * context if appropriate.
 */

require_once('util.php');

/*
 * 'site' can be used to direct to a development version. The default
 * is 'stable'. This is for developers.
 */
$site = 'stable';
if (array_key_exists('site', $_REQUEST)) {
  $site = $_REQUEST['site'];
}

if (array_key_exists('slice_id', $_REQUEST)) {
  $slice_id = $_REQUEST['slice_id'];
}


/*
 * Use this URL if not in slice context (i.e. not passing a slice id).
 */
$gd_url = ('https://genidesktop.netlab.uky.edu/'
           . $site
           .'/slice_pages/slice_list.php');

/*
 * If a slice id is present, use a different URL to pass the slice id
 * to GENI Desktop.
 */
if ($slice_id) {
  $gd_url = ('https://genidesktop.netlab.uky.edu/'
             . $site
             . '/slice_page.php?slice_uuid='
             . $slice_id);
}

header("Location: $gd_url");
exit;
