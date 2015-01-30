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

require_once("user.php");
require_once 'db-util.php';
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
include("header.php");

$is_pi = false;
if ($user->isAllowed(PA_ACTION::CREATE_PROJECT, CS_CONTEXT_TYPE::RESOURCE, null)) {
  $is_pi = true;
}

show_header('GENI Portal: Profile', $TAB_PROFILE);
include("tool-breadcrumbs.php");
include("tool-showmessage.php");

$boilerplate = <<<EOD
<h1> Modify Your Account </h1>
<p>Request a modification to user supplied account properties. For
example, use this page to request to be a Project Lead (get Project
Creation permissions).</p>
<p>Please provide a current telephone number. GENI operations staff will
use it only in an emergency, such as if a resource owned by you is severely
misbehaving. </p>
<p>If you do not have Project Creation permission and need it, provide an
   updated reference or profile and your request will be considered.</p>
<p><i>Note</i>: Based on current GENI policy only faculty and senior
 members of an organization
may be project leads (e.g. students <i>may not</i> be project leads).</p>
EOD;

print "$boilerplate\n";
print '<form method="POST" action="do-modify.php">';

print '<ul>';

print '<li>';
print '<label for="name">';
print '<b>Name: </b>';
print '</label>';
print '<input type="text" name="name" id="name" size="40" value="';
$name = $user->prettyName();
if (strpos($name, '@') !== false) {
  // If the name has an @ it is an email, so leave it blank.
  $name = '';
}
print $name;
print '"/></li>';
print "\n";

print '<li>';
print '<label for="email">';
print '<b>Email:</b> ';
print '</label>';
print '<input type="text" name="email" id="email" size="40" value="';
print $user->email();
print '"  disabled="yes"/></li>';
print "\n";

print '<li>';
print '<label for="telephone">';
print '<b>Telephone:</b> ';
print '</label>';
print '<input type="text" name="telephone" id="telephone" size="20" value="';
print $user->phone();
print '"/></li>';
print "\n";

print '<li>';
print '<label for="reference">';
print '<b><i>(Optional)</i> Reference Contact (e.g. Advisor):</b> ';
print '</label>';
print '<input type="text" name="reference" id="reference" size="80" value="';
print $user->reference();
print '"/></li>';
print "\n";

print '<li>';
print '<label for="url">';
print '<b><i>(Optional)</i> URL of your profile page for more information';
print ' (not GENI public):</b> ';
print '</label>';
print '<input type="text" name="url" id="url" size="80" value="';
print $user->url();
print '"/></li>';
print "\n";

print '<li>';
print '<label for="reason">';
print '<b><i>(Optional)</i> Intended use of GENI, explanation of request';
print ', or other comments:</b> ';
print '</label>';
print '<textarea rows="4" cols="50" id="reason" name="reason">';
print $user->reason();
print '</textarea></li>';
print "\n";

print '</ul>';
print '<p>';
print '<input type="checkbox" name="projectlead" value="projectlead"';

if ($is_pi) {
  print "checked='checked'>I want to remain ";
} else {
  if (array_key_exists('belead', $_REQUEST)) {
    print "checked='checked'";
  }
  print ">Make me ";
}
?>

a 'Project Lead' who can create projects. I am a professor or senior
technical staff (as indicated by my profile page supplied above). I
want the ability to create projects, and I agree to take full
responsibility for all GENI resource reservations made in my projects.<br/>
</p>
<p>
<input type="submit" value="Modify Account"/>
<input type="button" value="Cancel" onclick="history.back(-1)"/>
</p>
</form>


<?php
include("footer.php");
?>
