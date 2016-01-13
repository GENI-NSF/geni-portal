<?php
//----------------------------------------------------------------------
// Copyright (c) 2015-2016 Raytheon BBN Technologies
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

// This module is to intercept portal-initiated traversal to wireless sites
// and ensure the the given user's account and group membership information
// is synched with ORBIT prior to traversal.

require_once('user.php');
require_once('wireless_operations.php');
require_once('/etc/geni-ch/settings.php');

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

if (isset($portal_analytics_enable) && $portal_analytics_enable) {
  echo '<script>(function(i,s,o,g,r,a,m){i[\'GoogleAnalyticsObject\']=r;i[r]=i[r]||function(){';
  echo '(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),';
  echo 'm=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)';
  echo '})(window,document,\'script\',\'//www.google-analytics.com/analytics.js\',\'ga\');';
      
  if (! isset($portal_analytics_string) || is_null($portal_analytics_string)) {
    /* Use the following tracking IDs depending on which server this will be running on
       portal1.gpolab.bbn.com:   ga('create', 'UA-42566976-1', 'bbn.com');
       portal.geni.net:          ga('create', 'UA-42566976-2', 'geni.net');
    */
    $portal_analytics_string = "ga('create', 'UA-42566976-1', 'bbn.com');";
  }
      
  echo $portal_analytics_string;
      
  echo "ga('send', 'pageview');";
  echo '</script>';
}


$witest_url = "http://witestlab.poly.edu/site/index.php";
$orbit_url = 'https://geni.orbit-lab.org/';

// error_log("GET = " . print_r($_GET, true));

if (!array_key_exists('site', $_GET)) {
  print "Invalid invocation of wireless-redirect: no site specified";
} else {
  $site = $_GET['site'];
  perform_wireless_sync_for_user($user->username);
  if ($site == 'WITEST') {
    error_log("Going to $witest_url");
    header("Location: $witest_url");
    exit;
  } else if ($site == 'ORBIT') {
    error_log("Going to $orbit_url");
    header("Location: $orbit_url");
    exit;
  } else {
    print "Unknown wireless site: $site";
  }
}


?>

