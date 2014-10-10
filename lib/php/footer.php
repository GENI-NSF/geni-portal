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

require_once('settings.php');

?>

<!-- close the "content" div. -->
</div></div>

<div id="footer">
<div id="footer-left">
  <!-- <a href="https://portal.geni.net">GENI Portal Home</a><br>
  <a href="http://www.geni.net">GENI Home</a><br>
  <a href="http://groups.geni.net/geni">GENI Wiki</a> -->
</div>
<div id="footer-right">
<?php
  // show version if it exists in settings.php
  if(isset($portal_version)) {
    echo "GENI Portal Version $portal_version<br/>";
  }
?>
  Copyright &copy; 2014 Raytheon BBN Technologies<br>
  All Rights Reserved - NSF Award CNS-0714770<br>
  <a href="http://www.geni.net/">GENI</a> is sponsored by the <a href="http://www.nsf.gov/"><img src="/common/nsf1.gif" alt="NSF Logo" height="16" width="16"/> National Science Foundation</a>

</div>
</div>
</body>
</html>
