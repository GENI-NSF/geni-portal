<?php
//----------------------------------------------------------------------
// Copyright (c) 2011 Raytheon BBN Technologies
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
?>
<?php
require_once("settings.php");
require_once("db-util.php");
require_once("file_utils.php");
require_once("cert_utils.php");
require_once("util.php");
require_once("user.php");

function derive_username() {
  // See http://www.linuxjournal.com/article/9585
  // try to figure out a reasonable username.
  $email_addr = NULL;
  if (array_key_exists('mail', $_SERVER)) {
    $email_addr = filter_input(INPUT_SERVER, 'mail', FILTER_SANITIZE_EMAIL);
  } else if (array_key_exists('mail', $_POST)) {
    $email_addr = filter_input(INPUT_POST, 'mail', FILTER_SANITIZE_EMAIL);
  } else {
    // Use a fake one.
    $email_addr = 'unknown@example.com';
  }

  /* print "<br/>derive2: email_addr = $email_addr<br/>\n"; */

  // Now get the username portion.
  $atindex = strrpos($email_addr, "@");
  /* print "atindex = $atindex<br/>\n"; */
  $username = substr($email_addr, 0, $atindex);
  /* print "base username = $username<br/>\n"; */

  // FIXME: Follow the rules here: http://groups.geni.net/geni/wiki/GeniApiIdentifiers#Name
  // Max 8 characters
  // Case insensitive internally
  // Obey this regex: '^[a-zA-Z][\w]\{1,8\}$'

  // Sanitize the username so it can be used in ABAC
  $username = strtolower($username);
  $username = preg_replace("/[^a-z0-9_]/", "", $username);
  if (! db_fetch_user_by_username($username)) {
    /* print "no conflict with $username<br/>\n"; */
    return $username;
  } else {
    for ($i = 1; $i <= 99; $i++) {
      $tmpname = $username . $i;
      /* print "trying $tmpname<br/>\n"; */
      if (! db_fetch_user_by_username($tmpname)) {
        /* print "no conflict with $tmpname<br/>\n"; */
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
// TODO: Use filters to sanitize these
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
function attrValue($attr, &$value, &$self_asserted) {
  $value = null;
  $self_asserted = null;
  $result = false;
  if (array_key_exists($attr, $_SERVER)) {
    $value = $_SERVER[$attr];
    $self_asserted = false;
    $result = true;
  } else if (array_key_exists($attr, $_POST)) {
      $value = $_POST[$attr];
      $self_asserted = true;
      $result = true;
  }
  return $result;
}

$attrs = array('givenName','sn', 'mail','telephoneNumber', 'reference', 'reason', 'profile');
// FIXME: Use filters to sanitize these
foreach ($attrs as $attr) {
  if (attrValue($attr, $value, $self_asserted)) {
    $sql = "INSERT INTO identity_attribute "
      . "(identity_id, name, value, self_asserted) VALUES ("
      . $conn->quote($identity_id, 'integer')
      . ", " . $conn->quote($attr, 'text')
      . ", " . $conn->quote($value, 'text')
      . ", " . $conn->quote($self_asserted)
      . ");";
    /* print "attr insert: $sql<br/>"; */
    $result = $conn->exec($sql);
    if (PEAR::isError($result)) {
      die("error on attr $attr insert: " . $result->getMessage());
    }
  }
}

// ----------------------------------------------------------------------
// Add an ABAC cert and key. This should probably be done when
// and admin approves the request, but we don't have that page
// yet, so... do it here for now, and migrate it later.
// ----------------------------------------------------------------------
$cmd_array = array("/usr/local/bin/creddy",
                   "--generate",
                   "--cn",
                   $username
                   );
$command = implode(" ", $cmd_array);
$orig_dir = getcwd();
chdir(sys_get_temp_dir());
$result = exec($command, $output, $status);
$abac_id_file = $username . "_ID.pem";
$abac_key_file = $username . "_private.pem";
$abac_id = file_get_contents($abac_id_file);
$abac_key = file_get_contents($abac_key_file);

// Get the ABAC fingerprint for use in creating attributes later
$cmd_array = array("/usr/local/bin/creddy",
                   "--keyid",
                   "--cert",
                   $abac_id_file
                   );
$command = implode(" ", $cmd_array);
// Clear the previous output
unset($output);
$result = exec($command, $output, $status);
$abac_fingerprint = $output[0];
unlink($abac_id_file);
unlink($abac_key_file);
chdir($orig_dir);

// Put this stuff in the database
$sql = "INSERT INTO abac"
  . "(account_id, abac_id, abac_key, abac_fingerprint) VALUES ("
  . $conn->quote($account_id, 'text')
  . ", ". $conn->quote($abac_id, 'text')
  . ", ". $conn->quote($abac_key, 'text')
  . ", ". $conn->quote($abac_fingerprint, 'text')
  . ");";
$result = $conn->exec($sql);
if (PEAR::isError($result)) {
  die("error on abac insert: " . $result->getMessage());
}

// --------------------------------------------------
// Create new private key and certificate ("inside keys")
// --------------------------------------------------
$user = geni_loadUser();
$email_addr = $user->email();
$urn = $user->urn();
$signer_cert_file = "/usr/share/geni-ch/ma/ma-cert.pem";
$signer_key_file = "/usr/share/geni-ch/ma/ma-key.pem";
$result = make_cert_and_key($account_id, $email_addr, $urn,
                            $signer_cert_file, $signer_key_file,
                            $cert, $key);
if ($result) {
  db_add_inside_key_cert($account_id, $cert, $key);
}


// --------------------------------------------------
// Send mail about the new account request
// --------------------------------------------------
$server_host = $_SERVER['SERVER_NAME'];
$body = "There is a new portal account request on $server_host:\n";
$mail_attrs = array('givenName' => 'First Name',
        'sn' => 'Last Name',
        'mail' => 'Email Address',
        'telephoneNumber' => 'Phone Number',
        'affiliation' => 'Affiliation',
        'eppn' => 'EPPN',
        'reference' => 'Reference',
        'reason' => 'Reason',
        'profile' => 'Profile');
foreach (array_keys($mail_attrs) as $attr) {
  $pretty = $mail_attrs[$attr];
  if (attrValue($attr, $value, $self_asserted)) {
    $body .= "\n$pretty: $value";
    if ($self_asserted) {
      $body .= "   (self asserted)";
    } else {
      $body .= "   (from IdP)";
    }
  } else {
    /* The attribute is not present. */
    $body .= "\n$pretty: no value available";
  }
}

mail($portal_admin_email,
     "New portal account request",
     $body);

relative_redirect('home.php');

/* <?php */
/* include("header.php"); */
/* show_header('GENI Portal Home', $TAB_HOME); */
/* ?> */
/* <h2>Your account request has been submitted.</h2> */
/* Go to the <a href= */
/* <?php */
/* $url = relative_url("home.php"); */
/* if ($portal_auto_approve) { */
/*   $args['id'] = $account_id; */
/*   $query = http_build_query($args); */
/*   $url = relative_url("approve.php?"); */
/*   $url = $url . $query; */
/* } */
/* print $url */
/* ?> */
/* >portal home page</a> */

/* <?php */
/* //$array = $_POST; */
/* //foreach ($array as $var => $value) { */
/* //    print "POST[$var] = $value<br/>"; */
/* //    } */
/* ?> */

/* <hr/> */

/* <?php */
/* include("footer.php"); */
/* ?> */


?>
