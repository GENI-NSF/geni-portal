<?php
require_once("user.php");
?>
<h1>Existing Slices</h1>
<?php
$slices = fetch_slices($user->account_id);
if (count($slices) > 0) {
  print "\n<table border=\"1\">\n";
  print "<tr><th>Name</th><th>Expiration</th><th>URN</th><th>Credential</th>"
    . "<th>ABAC Credential</th></tr>\n";
  $base_url = relative_url("slicecred.php?");
  $abac_url = relative_url("sliceabac.php?");
  foreach ($slices as $slice) {
    $slice_id = $slice['slice_id'];
    $args['id'] = $slice_id;
    $query = http_build_query($args);
    $slicecred_url = $base_url . $query;
    $sliceabac_url = $abac_url . $query;
    print "<tr>"
      . "<td>" . htmlentities($slice['name']) . "</td>"
      . "<td>" . htmlentities($slice['expiration']) . "</td>"
      . "<td>" . htmlentities($slice['urn']) . "</td>"
      . ("<td><a href=\"$slicecred_url\">Get Credential</a></td>")
      . "<td><a href=\"$sliceabac_url\">Get ABAC Credential</a></td>"
      . "</tr>\n";
  }
  print "</table>\n";
} else {
  print "<i>No slices.</i><br/>\n";
}

/* Only show create slice link if user has appropriate privilege. */
if ($user->privSlice()) {
  print "<a href=\""
    . relative_url("createslice")
    . "\">Create a new slice</a>";
}
?>
