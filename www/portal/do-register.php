<?php
require_once("settings.php");
require_once("db-util.php");
require_once("util.php");
require_once("user.php");

function derive_username() {
  // See http://www.linuxjournal.com/article/9585
  // try to figure out a reasonable username.
  $email_addr = NULL;
  if (array_key_exists('mail', $_SERVER)) {
    $email_addr = $_SERVER['mail'];
  } else if (array_key_exists('mail', $_POST)) {
    $email_addr = $_POST['mail'];
  } else {
    // Use a fake one.
    $email_addr = 'unknown@example.com';
  }

  print "<br/>derive2: email_addr = $email_addr<br/>\n";

  // Now get the username portion.
  $atindex = strrpos($email_addr, "@");
  print "atindex = $atindex<br/>\n";
  $username = substr($email_addr, 0, $atindex);
  print "base username = $username<br/>\n";
  if (! db_fetch_user_by_username($username)) {
    print "no conflict with $username<br/>\n";
    return $username;
  } else {
    for ($i = 1; $i <= 99; $i++) {
      $tmpname = $username . $i;
      print "trying $tmpname<br/>\n";
      if (! db_fetch_user_by_username($tmpname)) {
        print "no conflict with $tmpname<br/>\n";
        return $tmpname;
      }
    }
  }
  die("Unable to find a username based on $username");
}

//--------------------------------------------------
// Create the database connection
//--------------------------------------------------
$conn = portal_conn();
if (PEAR::isError($conn)) {
  die("error connecting to db: " . $conn->getMessage());
}

//--------------------------------------------------
// Insert into account table
//--------------------------------------------------
$account_id = make_uuid();
$username = derive_username();
$sql = "INSERT INTO account (account_id, username, status) VALUES ("
  . $conn->quote($account_id, 'text')
  . ", ". $conn->quote($username, 'text')
  . ", 'requested');";
$result = $conn->exec($sql);
if (PEAR::isError($result)) {
  die("error on account insert: " . $result->getMessage());
}

//--------------------------------------------------
// Insert into identity table
//--------------------------------------------------

// TODO: Check for the existence of each, error if not available.
$eppn = $_SERVER['eppn'];
$affiliation = $_SERVER['affiliation'];
$shib_idp = $_SERVER['Shib-Identity-Provider'];

$sql = "INSERT INTO identity (provider_url, eppn, affiliation, account_id)"
  . "VALUES ("
  . $conn->quote($shib_idp, 'text')
  . ", " . $conn->quote($eppn, 'text')
  . ", " . $conn->quote($affiliation, 'text')
  . ", " . $conn->quote($account_id, 'text')
  . ");";
// print $sql . "<br/>";

$result = $conn->exec($sql);
if (PEAR::isError($result)) {
  die("error on identity insert: " . $result->getMessage());
}

//--------------------------------------------------
// Now pull the id out of that newly inserted identity record and add
// the additional attributes.
//--------------------------------------------------
$sql = "SELECT identity_id from identity WHERE provider_url = "
  . $conn->quote($shib_idp, 'text')
  . " AND eppn = "
  . $conn->quote($eppn, 'text')
  . ";";
//print "Query = $sql<br/>";
$resultset = $conn->query($sql);
if (PEAR::isError($resultset)) {
  die("error on identity id select: " . $resultset->getMessage());
}
$rows = $resultset->fetchall(MDB2_FETCHMODE_ASSOC);
$rowcount = count($rows);
//print "rowcount = $rowcount<br/>";
$identity_id = $rows[0]['identity_id'];
//print "identity_id = $identity_id<br/>";

//--------------------------------------------------
// Add extra attributes
//--------------------------------------------------
$attrs = array('givenName','sn', 'mail','telephoneNumber');
foreach ($attrs as $attr) {
  print "attr = $attr<br/>";
  if (array_key_exists($attr, $_SERVER)) {
    $value = $_SERVER[$attr];
  } else {
    $value = $_POST[$attr];
  }
  $sql = "INSERT INTO identity_attribute "
    . "(identity_id, name, value, self_asserted) VALUES ("
    . $conn->quote($identity_id, 'integer')
    . ", " . $conn->quote($attr, 'text')
    . ", " . $conn->quote($value, 'text')
    . ", " . $conn->quote(false)
    . ");";
  print "attr insert: $sql<br/>";
  $result = $conn->exec($sql);
  if (PEAR::isError($result)) {
    die("error on attr $attr insert: " . $result->getMessage());
  }
}

// --------------------------------------------------
// Send mail about the new account request
// --------------------------------------------------
$url = relative_url("env.php");
mail($portal_admin_email,
     "New portal account request",
     "There is a new account request for $eppn. Please review this request"
     . " at $url");

?>

<?php
include("header.php");
?>
<h2>Your account request has been submitted.</h2>
Go to the <a href=
<?php
$url = relative_url("home.php");
print $url
?>
>portal home page</a>

<?php
$array = $_POST;
foreach ($array as $var => $value) {
    print "POST[$var] = $value<br/>";
    }
?>

<hr/>

<?php
include("footer.php");
?>
