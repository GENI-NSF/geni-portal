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

require_once("settings.php");
require_once("user.php");
require_once("header.php");

function show_ssh_edit_form($ssh_key, $cancel_dest) {
    $key_id = $ssh_key['id'];
    $filename = $ssh_key['filename'];
    $description = $ssh_key['description'];
    echo <<< END
    <form action="sshkeyedit.php" method="post">
    <table>
    <tr>
    <td><label for="description">Description:</label></td>
    <td><input type="text" size="60" name="description" value="$description"/></td>
    <tr/>
    <tr>
    <td><label for="name">Filename:</label></td>
    <td><input type="text" name="name" value="$filename"/></td>
    </tr>
    </table>
    <input type="hidden" name="id" value="$key_id"/>
    <br/>
    <input type="submit" name="submit" value="Submit"/>
    </form>
    <button onClick="window.location='$cancel_dest'">Cancel</button>
END;
}

function js_delete_ssh_key() {
  /*
   *    * A javascript function to confirm the delete.
   */
  echo <<< END
  <script type="text/javascript">
function deleteSshKey(dest){
  var r=confirm("Are you sure you want to delete this ssh key?");
    if (r==true) {
      window.location = dest;
  }
}
</script>
END;
}


$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

if (array_key_exists('id', $_REQUEST)
        && array_key_exists('name', $_REQUEST)
        && array_key_exists('description', $_REQUEST))
{
  // User has submitted the form
  print "Got the form";
  print "<br>Name = " . $_REQUEST['name'];
  print "<br>Description = " . $_REQUEST['description'];
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);

  $result = update_ssh_key($ma_url, $user, $user->account_id,
            $_REQUEST['id'], $_REQUEST['name'], $_REQUEST['description']);
  $_SESSION['lastmessage'] = "Updated SSH keypair ($result)";
  relative_redirect('profile.php');
} else {
  // User has requested edit
  show_header('GENI Portal: Profile', $TAB_PROFILE);
  print "<h1>Edit SSH Key</h1>";
  if (array_key_exists('id', $_REQUEST)) {
    $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
    $ssh_keys = lookup_private_ssh_keys($ma_url, $user, $user->account_id);
    $ssh_key = NULL;
    foreach($ssh_keys as $one_key) {
      if ($one_key['id'] == $_REQUEST['id']) {
	$ssh_key = $one_key;
      }
    }

    if (($ssh_key != NULL) && array_key_exists('HTTP_REFERER', $_SERVER)) {
      $cancel_dest = $_SERVER['HTTP_REFERER'];
    } else {
      // If no referer, go to home page on cancel.
      $cancel_dest = relative_url('profile.php');
    }
    show_ssh_edit_form($ssh_key, $cancel_dest);
    js_delete_ssh_key();
    $delete_sshkey_url = relative_url('deletesshkey.php?');
    $args['id'] = $ssh_key['id'];
    $query = http_build_query($args);
    print "<br/><br/>You can ";
    print ("<button onClick=\"deleteSshKey('"
            . $delete_sshkey_url . $query
            . "')\">delete</button>");
    print " this key if you are sure you no longer want to use it.";
  }
  include("footer.php");
}
