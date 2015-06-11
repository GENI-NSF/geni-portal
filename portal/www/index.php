<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2015 Raytheon BBN Technologies
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

function show_last_message() {
  $message_key = 'lastmessage';
  session_start();
  if (isset($_SESSION[$message_key])) {
    $last_message = $_SESSION[$message_key];
    unset($_SESSION[$message_key]);
  }
  session_write_close();
  if (isset($last_message)) {
    echo "<center><p class='instruction'>$last_message</p></center>";
  }
}

?>

<!DOCTYPE HTML>
<html>
<head>
  <title>Welcome to the GENI Experimenter Portal</title>
  <link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/humanity/jquery-ui.css" rel="Stylesheet" />
  <link type="text/css" href="/common/css/portal.css" rel="Stylesheet"/>
  <link type="text/css" rel="stylesheet" media="(max-width: 600px)" href="/common/css/mobile-portal.css" />
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700|PT+Serif:400,400italic|Droid+Sans+Mono" rel="stylesheet" type="text/css">
  <meta name="viewport" content="initial-scale=1.0, user-scalable=0, width=device-width, height=device-height"/>
  <meta name="mobile-web-app-capable" content="yes">
</head>
<body>

<div id="content-outer" style="border-top: 5px solid #5F584E;">
<div id="content">
  <div id="welcome-left">
    <img src="/images/geni.png" alt="GENI"/>
  </div>
  <div id="welcome-right">
    <div id="welcome-right-top">
      <?php show_last_message(); ?>
      <h1> Welcome to GENI </h1>
      <a href="http://www.geni.net">GENI</a> is a new, nationwide suite of infrastructure supporting 
      "at scale" research in networking, distributed systems, security, and novel applications. 
      It is supported by the <a href="http://www.nsf.gov/">National Science Foundation</a>, 
      and available without charge for research and classroom use.
    </div>

    <div id="welcome-right-left">
      <div id='usegenicontainer'>
      <a href='secure/home.php' title='Login to the GENI Experimenter Portal' id="usegeni">
        <!-- <img src="/images/UseGENI.png" id="usegeni" alt="Use GENI"/> -->
        Use GENI
      </a>
      </div>
      <h2>Find out more about using GENI</h2>
      <ul>
        <li><a href="http://groups.geni.net/geni/wiki/GeniNewcomersWelcome">New to GENI?</a></li>
        <li><a href="http://www.geni.net/experiment">Information for GENI experimenters</a></li>
        <li><a href="http://groups.geni.net/geni/wiki/GENIBibliography">Published research that used GENI resources</a></li>
        <li>Get <a href="mailto:help@geni.net">help</a> using GENI</li>
      </ul>
    </div>

    <div id="welcome-right-right">
      <?php include "common/map/map-small.html"; ?>
      <p><i>These are some of the many resources being used in GENI experiments across the country.</i></p>
    </div>
  </div>
  <div style="clear: both;">&nbsp;</div>

<?php include("footer.php"); ?>
