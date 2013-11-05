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

require_once("user.php");
require_once 'db-util.php';
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
include("header.php");

// Local functions
function shib_input($shib_name, $pretty_name, $value)
{
  print "<li><b>" . $pretty_name . ":</b> ";
  print "<input type=\"text\" name=\"$shib_name\"";
  if (array_key_exists($shib_name, $_SERVER)) {
      if (isset($value)) {
	echo " value=\"$value\"";
      } else {
	$value = $_SERVER[$shib_name];
	echo " value=\"$value\" disabled=\"yes\"";
      }
  } else {
     echo " value=\"$value\"";
  }
  print "/></li>\n";
}

// Only allow modifying fields that didn't come from Shib

// This should go through stuff in DB for this user.
// If user-supplied then make it editable, else disabled=yes
// pull from identity_attribute table I think
// so need the identity_id

//--------------------------------------------------
// Now pull the id out of that newly inserted identity record and add
// the additional attributes.
//--------------------------------------------------
$conn = portal_conn();
$conn->setFetchMode(MDB2_FETCHMODE_ASSOC);
$sql = "SELECT * from identity_attribute WHERE identity_id = "
  . $conn->quote($user->identity_id, 'text')
  . ";";
//print "Query = $sql<br/>";
$resultset = $conn->query($sql);
if (PEAR::isError($resultset)) {
  die("error on identity attrs select: " . $resultset->getMessage());
}
$rows = $resultset->fetchall(MDB2_FETCHMODE_ASSOC);
$rowcount = count($rows);
$attrs = array();
foreach ($rows as $row) {
  if (strtolower($row['self_asserted']) == 't') {
    // FIXME: attrs a list of name/value? so I can get it out ok....
    array_push($attrs, $row);
    //    print "Found self asserted " . $row['name'] . " = " . $row['value'] . "<br/>\n";
  } else {
    //    print "Found NOT self asserted " . $row['name'] . " = " . $row['value'] . "<br/>\n";
  }
}

$is_pi = false;
if ($user->isAllowed(PA_ACTION::CREATE_PROJECT, CS_CONTEXT_TYPE::RESOURCE, null)) {
  $is_pi = true;
}

show_header('GENI Portal: Profile', $TAB_PROFILE);
include("tool-breadcrumbs.php");
include("tool-showmessage.php");

?>

<h1> Modify Your Account </h1>
<p>Request a modification to user supplied account properties. For
example, use this page to request to be a Project Lead (get Project
Creation permissions).</p>
<p>Please provide a current telephone number. GENI operations staff will
use it only in an emergency, such as if a resource owned by you is severely misbehaving. </p>
<p>If you do not have Project Creation permission and need it, provide an updated reference or profile and your request will be considered.</p>
<p><i>Note</i>: Based on GENI's current policy, only faculty and senior members of an organization
may be project leads (e.g. students <i>may not</i> be project leads).</p>
<form method="POST" action="do-modify.php">
<?php
  //  $shib_fields = array('givenName' => 'First name', 'sn' => 'Last name', 'mail' => 'Email', 'telephoneNumber' => 'Telephone');
  $shib_fields = array('givenName' => 'First name', 'sn' => 'Last name', 'mail' => 'Email', 'telephoneNumber' => 'Telephone',
		       'reference' => '<i>(Optional)</i> Reference Contact (e.g. Advisor)',
		       'reason' => '<i>(Optional)</i> Intended use of GENI, explanation of request, or other comments',
		       'profile'=> '<i>(Optional)</i> URL of your profile page for more information (not GENI public)');
print "<ul>";
foreach (array_keys($shib_fields) as $fieldkey) {
    $is_user = false;
    foreach ($attrs as $a) {
      if ($a['name'] == $fieldkey ) {
	$is_user = true;
	shib_input($a['name'], $shib_fields[$a['name']], $a['value']);
        $a['printed'] = true;
      }
    }
    if (!$is_user) {
      shib_input($fieldkey, $shib_fields[$fieldkey], null);
    }
  }
  foreach ($attrs as $a) {
    if (! array_key_exists($a['name'], $shib_fields)) {
      shib_input($a['name'], $a['name'], $a['value']);
    }
  }
print "</ul>";
?>
<p>
<input type="checkbox" name="projectlead" value="projectlead"
<?php
    if ($is_pi) {
      print "checked='checked'>I want to remain ";
    } else {
      if (array_key_exists('belead', $_REQUEST)) {
	print "checked='checked'";
      }
      print ">Make me ";
    }
?>
a 'Project Lead' who can create projects. I am a professor or senior technical staff (as indicated by my profile page supplied above). I want the ability to create projects, and I agree to take full responsibility for all GENI resource reservations by members of my projects and understand what it means to be a project lead.<br/>
</p>
<p>
<input type="submit" value="Modify Account"/>
<input type="button" value="Cancel" onclick="history.back(-1)"/>
</p>
</form>
<?php
include("footer.php");
?>
