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
 *
 * NOTE: No authentication or authorization is done in this
 * script. This is purely a mechanical manipulation of the arguments
 * into a redirect URL. No data is accessed. This allows richer debug
 * testing by allowing malformed slice IDs to pass, and allowing
 * unregistered users to invoke this script.
 */


/*
 * 'site' can be used to direct to a development version. The default
 * is 'stable'. This is for developers.
 */
$site = 'stable';
if (array_key_exists('site', $_REQUEST)) {
  $site = $_REQUEST['site'];
}

$slice_id = NULL;
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

/* Note the redirect in the log. */
$log_msg = "Redirecting to $gd_url";
if (array_key_exists('eppn', $_SERVER)) {
  $eppn = $_SERVER['eppn'];
  $log_msg = "Redirecting eppn $eppn to $gd_url";
}
error_log($log_msg);

header("Location: $gd_url");
exit;
