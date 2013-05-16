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


// require_once("user.php");
// require_once("header.php");
// require_once("portal.php");
require_once('util.php');
require_once('sr_constants.php');
require_once('sr_client.php');

require_once('session_cache.php');
// require_once('sr_controller_test.php');


// $user = geni_loadUser();
// if (!isset($user) || is_null($user) || ! $user->isActive()) {
//   relative_redirect('home.php');
// }


global $SR_SERVICE_TYPE_NAMES;	
global $services, $sa_url, $ma_url, $sa_url, $log_url;

if (! isset($services)) {
   $services = get_services();	
}


if (! isset($sa_url)) {
  $sa_list = select_services($services, SR_SERVICE_TYPE::SLICE_AUTHORITY);
  if (count($sa_list) >= 0) 
  {
    $sa = $sa_list[0];		
    $sa_url = $sa[SR_TABLE_FIELDNAME::SERVICE_URL];
  }   
  if (! isset($sa_url) || is_null($sa_url) || $sa_url == '') {
    error_log("Found no SA in SR!'");
  }
}


if (! isset($ma_url)) {
  $ma_list = select_services($services, SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  if (count($ma_list) >= 0) 
  {
    $ma = $ma_list[0];		
    $ma_url = $ma[SR_TABLE_FIELDNAME::SERVICE_URL];
  }   
  if (! isset($ma_url) || is_null($ma_url) || $ma_url == '') {
    error_log("Found no MA in SR!'");
  }
}

if (! isset($log_url)) {
  $log_list = select_services($services, SR_SERVICE_TYPE::LOGGING_SERVICE);
  if (count($log_list) >= 0) 
  {
    $log = $log_list[0];		
    $log_url = $log[SR_TABLE_FIELDNAME::SERVICE_URL];
  }   
  if (! isset($log_url) || is_null($log_url) || $log_url == '') {
    error_log("Found no LOG in SR!'");
  }
}

// print "<html>";
// print "<p>".$sa_url."</p>";
// print "<p>".$ma_url."</p>";
// print "<p>".$log_url."</p>";
// print "</html>";

?>
