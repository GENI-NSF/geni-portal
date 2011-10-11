<?php
require_once("user.php");
if (! $user->privAdmin()) {
  exit();
}
?>
<h1>Adminstrator Tools</h1>

<?php
 // Start with a table of requested accounts, including checkboxes
 // for privileges (slice, admin)

function attribute_row($name, $value, $self_asserted)
{
  if ($self_asserted) {
    print("<tr color=\"yellow\"><td>$name</td><td>$value</td></tr>\n");
  } else {
    print("<tr><td>$name</td><td>$value</td></tr>\n");
  }
}

function show_requested_accounts()
{
  $accts = requestedAccounts();
  $num_accts = count($accts);
  if ($num_accts < 1) {
    print("<h2>No requested accounts.</h2>\n");
  } else {
    print("<h2>Requested Accounts</h2>\n");
    print("<table border=1>");
    print("<tr><th>Account ID</th><th>Attributes</th><th>Actions</th></tr>\n");
    foreach ($accts as $acct) {
      $account_id = $acct['account_id'];
      print("<tr><td>$account_id</td><td>\n");
      // Now print out the attributes...
      // XXX TEMPORARY XXX
      $account_id = "158322ff-7dda-4fc6-ab2b-f03526f37544";
      $identities = loadIdentitiesByAccountId($account_id);
      // There will only be one identity on a requested account
      $identity = $identities[0];
      $identity_id = $identity['identity_id'];
      // Nest a table for identity info
      print("<table border=1>\n");
      // surely there is a better way to do this:
      attribute_row("eppn", $identity['eppn'], False);
      attribute_row("affiliation", $identity['affiliation'], False);
      attribute_row("IdP", $identity['provider_url'], False);

      // And extra attributes:
      $attrs = loadIdentityAttributes($identity_id);
      foreach ($attrs as $attr) {
        // XXX Still need to mark self-asserted items
        attribute_row($attr['name'], $attr['value'], True);
      }

      print("</table>\n");

      // Close out the attribute row
      print("</td><td>\n");

      print("Privileges:<br/><br/>\n");
      print("<input type=\"checkbox\" name=\"privilege\" value=\"slice\" />Slice<br/>");
      print("<br/><button>Approve</button>\n");

      // Close out the account row
      print("</td></tr>\n");
    }
    print("</table>\n");
  }
}

?>

<?php
// Page layout
show_requested_accounts();
?>