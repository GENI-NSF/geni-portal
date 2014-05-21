<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2014 Raytheon BBN Technologies
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
  Note: This code contains many overrides from the portal CSS file
  TODO: Fix some of the stylesheet to make this less dependent on manual overrides
  
  Widths and heights designed for a 1920x1080 screen size
*/

  echo '<!DOCTYPE HTML>';
  echo '<html>';
  echo '<head>';
  echo '<title>';
  echo "Welcome to GENI";
  echo '</title>';
  /* Stylesheet(s) */
  echo '<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/humanity/jquery-ui.css" rel="Stylesheet" />';
  echo '<link type="text/css" href="/common/css/portal.css" rel="Stylesheet"/>';
  echo '<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700|PT+Serif:400,400italic|Droid+Sans+Mono" rel="stylesheet" type="text/css">';
  /* Close the "head" */
  echo '</head>';
  echo '<body>';

  echo '<div id="welcome" style="width:1820px;min-height:980px;margin-top:30px;">';

  echo '<div id="welcome-left" style="width:220px;"><img src="/images/geni.png" alt="GENI"/></div>';
  echo '<div id="welcome-right" style="width:1600px;">';
    echo '<div id="welcome-right-top" style="width:1600px;">';

  echo '<h1> Welcome to GENI </h1>';
  echo '<a href="http://www.geni.net">GENI</a> is a new, nationwide suite of infrastructure supporting ';
  echo '"at scale" research in networking, distributed systems, security, and novel applications. ';
  echo 'It is supported by the <a href="http://www.nsf.gov/">National Science Foundation</a>, ';
  echo 'and available without charge for research and classroom use.';
  echo '</div>';

?>

<div id="welcome-right-right" style="width:1600px;">
<div id="content" style="margin:0;border:0;padding:0;">
<?php include "map-big.html"; ?>
</div>
<p><i>These are some of the many resources being used in GENI experiments across the country.</i></p>
</div>



</div>

</div>



</body>
</html>
