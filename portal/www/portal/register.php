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

include("header.php");
show_header('GENI Portal Home', $TAB_HOME, 0); // 0=Don't load user to show header
?>

<?php
// Local functions
function shib_input($shib_name, $pretty_name)
{
  print $pretty_name . ": ";
  print "<input type=\"text\" name=\"$shib_name\"";
  if (array_key_exists($shib_name, $_SERVER)) {
    $value = $_SERVER[$shib_name];
    echo "value=\"$value\" disabled=\"yes\"";
  }
  print "/><br/>\n";
}
?>

<h2> Registration Page </h2>
<br/>
<b>Note</b>: By registering on this page, you agree to:<br/>
<ul>
  <li><a href="">GENI Code of Ethics</a>: Be nice!</li>
  <li><a href="">GENI Privacy Policy</a>: We may use and share your InCommon attributes among GENI operators...</li>
  <li><a href="">GENI Citation Policy</a>: In particular, cite the GENI paper in all research that uses GENI.</li>
<!-- FIXME: Get the right list here! -->
</ul>
<br/>

Please provide your current telephone number, for use in emergencies. <br/>
Other optional attributes are used by GENI operations staff to identify you and determine what GENI permissions you will be granted.<br/>

<form method="POST" action="do-register.php">
<?php
  shib_input('givenName', 'First name');
  shib_input('sn', 'Last name');
  shib_input('mail', 'EMail');
  shib_input('telephoneNumber', 'Telephone');
  shib_input('reference', 'Optional: Reference Contact (e.g. Advisor)');
  shib_input('reason', 'Optional: Intended use of GENI, explanation of request, or other comments');
  shib_input('profile', 'Optional: URL of your profile page for more information (not GENI public)');
?>
<br/>
<input type="submit" value="Register"/>
</form>
<?php
include("footer.php");
?>
