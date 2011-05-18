<?php
require_once("user.php");
if (! $user->privSlice()) {
  exit();
}
?>
<h1>Existing Slices</h1>
<?php
$slices = fetch_slices($user->account_id);
if (count($slices) > 0) {
  print "\n<table border=\"1\">\n";
  print "<tr><th>Name</th><th>Expiration</th><th>Id</th></tr>\n";
  foreach ($slices as $slice) {
    print "<tr><td>"
      . htmlentities($slice['name'])
      . "</td><td>"
      . htmlentities($slice['expiration'])
      . "</td><td>"
      . htmlentities($slice['slice_id'])
      . "</td></tr>\n";
  }
  print "</table>\n";
} else {
  print "<i>No slices.</i><br/>\n";
}
?>
<a href="
<?php
print relative_url("createslice");
?>
">Create a new slice</a>
