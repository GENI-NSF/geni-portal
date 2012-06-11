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
if (! $user->isAllowed(CS_ACTION::ADMINISTER_MEMBERS, CS_CONTEXT_TYPE::MEMBER, null)) {
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
    print("<table>");
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
      print("<table>\n");
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