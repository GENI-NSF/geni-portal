<?php
// Form for creating a slice. Submit to self.
?>

<?php
require_once("user.php");
require_once("util.php");
require_once("db-util.php");
?>

<?php
$user = geni_loadUser();
$name = NULL;
$message = NULL;
if (count($_GET)) {
  // parse the args
  /* print "got parameters<br/>"; */
  if (array_key_exists('name', $_GET)) {
    /* print "found name<br/>"; */
    $name = $_GET['name'];
  }
  /* print "got name = $name<br/>"; */

} else {
  /* print "no parameters in _GET<br/>"; */
}

// Do we have all the required params?
if ($name) {
  $slice = fetch_slice_by_name($name);
  if (is_null($slice)) {
    // no slice by that name, create it
    /* print "name = $name, creating slice<br/>"; */
    $slice_id = make_uuid();
    /* print "slice id = $slice_id<br/>"; */
    db_create_slice($user->account_id, $slice_id, $name);
    /* print "done creating slice<br/>"; */
    relative_redirect('home');
  } else {
    $message = "Slice name \"" . $name . "\" is already taken."
      . " Please choose a different name." ;
  }
}

// If here, present the form
include("header.php");
if ($message) {
  // It would be nice to put this in red...
  print "<i>" . $message . "</i>\n";
}
print '<form method="GET" action="createslice">';
print "\n";
print 'Slice name: ';
print "\n";
print '<input type="text" name="name"/><br/>';
print "\n";
print '<input type="submit" value="Create slice"/>';
print "\n";
print '</form>';
print "\n";
?>
<?php
include("footer.php");
?>
