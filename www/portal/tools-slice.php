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
  print "<tr><th>Name</th><th>Expiration</th><th>Id</th><th>Credential</th></tr>\n";
  $base_url = relative_url("slicecred.php?");
  foreach ($slices as $slice) {
    $slice_id = $slice['slice_id'];
    $args['id'] = $slice_id;
    $query = http_build_query($args);
    $slicecred_url = $base_url . $query;
    print "<tr>"
      . "<td>" . htmlentities($slice['name']) . "</td>"
      . "<td>" . htmlentities($slice['expiration']) . "</td>"
      . "<td>" . htmlentities($slice_id) . "</td>"
      . ("<td><a href=\"$slicecred_url\">Get Credential</a></td>")
      . "</tr>\n";
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
