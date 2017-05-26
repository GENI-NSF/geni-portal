<?php
//----------------------------------------------------------------------
// Copyright (c) 2017 Raytheon BBN Technologies
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
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
  <title>IDP select test bed</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-5" />
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700" rel="stylesheet" type="text/css">
  <link rel="stylesheet" type="text/css" href="/shibboleth-ds/idpselect.css" />
  <link rel="stylesheet" type="text/css" href="/common/css/ds.css" />
</head>

<body>
  <div id="loginArea">
    <img id="logo" src='/images/ds-logo.png' alt='Geni logo'/>
    <br/>
    <div id="idpSelect"></div>
  </div>

  <div id="footer">
    <p><a href="http://groups.geni.net/geni/wiki/InCommon/GpoLogin">
      Looking for the GENI Project Office login?</a></p>
    <hr>
    <p>Can't find your school or organization above?<br>
    <a href="https://go.ncsa.illinois.edu/geni">Request an account</a>&nbsp;|&nbsp;
    <a href="mailto:help@geni.net">Contact GENI Help</a></p>
    <hr>
    <p style="font-size: .8em;">
    <a href="http://www.geni.net/">GENI</a> is sponsored by the
    <a href="http://www.nsf.gov/"><img src="/images/ds-nsf1.gif" alt="NSF Logo" height="16" width="16"/> National Science Foundation</a><br>
    NSF Award CNS-0714770</p>
  </div>

  <script src="/shibboleth-ds/idpselect_config.js" type="text/javascript" language="javascript"></script>
  <script src="/shibboleth-ds/idpselect.js" type="text/javascript" language="javascript"></script>
  <noscript>
    Your Browser does not support javascript. Please use
    a browser that supports javascript.
  </noscript>
</body>
</html>
