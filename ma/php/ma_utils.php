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

/*
 * Include local host settings.
 *
 * FIXME: parameterize file location
 */
include_once('/etc/geni-ch/settings.php');

function attr_key_exists($key, $attrs) {
  foreach ($attrs as $attr) {
    if ($attr[MA_ATTRIBUTE::NAME] === $key
            && (! empty($attr[MA_ATTRIBUTE::VALUE]))) {
      return TRUE;
    }
  }
  return FALSE;
}


/**
 * Verify that all keys in $keys exist in $search.
 *
 * @param unknown_type $search
 * @param unknown_type $keys
 * @param unknown_type $missing
 * @return TRUE if all keys are in $search, FALSE otherwise.
 */
function verify_keys($search, $keys, &$missing)
{
  $missing = array();
  foreach ($keys as $key) {
    if (! attr_key_exists($key, $search)) {
      $missing[] = $key;
    }
  }
  return $missing ? FALSE : TRUE;
}

function assert_project_lead($cs_url, $ma_signer, $member_id)
{
  $signer = NULL; /* this feels wrong */
  $attribute = CS_ATTRIBUTE_TYPE::LEAD;
  $context_type = CS_CONTEXT_TYPE::RESOURCE;
  $context = NULL;
  $result = create_assertion($cs_url, $ma_signer, $signer, $member_id,
          $attribute, $context_type, $context);
  geni_syslog(GENI_SYSLOG_PREFIX::MA,
          "assert_project_lead got result " . print_r($result, TRUE));
  return TRUE;
}

/**
 * Determine if a username already exists.
 * @param unknown_type $username
 */
function username_exists($username) {
  global $MA_MEMBER_ATTRIBUTE_TABLENAME;
  $conn = db_conn();
  $sql = ("select " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::VALUE
          . " from " . $MA_MEMBER_ATTRIBUTE_TABLENAME
          . " where " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::NAME
          . " = " . $conn->quote(MA_ATTRIBUTE_NAME::USERNAME, 'text')
          . " and " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::VALUE
          . " = " . $conn->quote($username, 'text'));
  $result = db_fetch_rows($sql);
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    $db_error = $result[RESPONSE_ARGUMENT::OUTPUT];
    geni_syslog(GENI_SYSLOG_PREFIX::MA,
            ("Database error: $db_error"));
    geni_syslog(GENI_SYSLOG_PREFIX::MA, "Query was: " . $sql);
    throw new ErrorException("Database failure: $db_error");
  }
  // True if an existing username was found, false otherwise.
  return count($result[RESPONSE_ARGUMENT::VALUE]) != 0;
}

function derive_username($email_address) {
  // See http://www.linuxjournal.com/article/9585
  // try to figure out a reasonable username.
  $email_addr = filter_var($email_address, FILTER_SANITIZE_EMAIL);
  /* print "<br/>derive2: email_addr = $email_addr<br/>\n"; */

  // Now get the username portion.
  $atindex = strrpos($email_addr, "@");
  /* print "atindex = $atindex<br/>\n"; */
  $username = substr($email_addr, 0, $atindex);
  /* print "base username = $username<br/>\n"; */

  // Follow the rules here:
  //         http://groups.geni.net/geni/wiki/GeniApiIdentifiers#Name
  //  * Max 8 characters
  //  * Case insensitive internally
  //  * Obey this regex: '^[a-zA-Z][\w]\{0,7\}$'
  // Additionally, sanitize the username so it can be used in ABAC

  // lowercase the username
  $username = strtolower($username);
  // trim the username to 8 chars
  $username = substr($username, 0, 8);
  // remove unacceptable characters
  $username = preg_replace("/[^a-z0-9_]/", "", $username);
  // remove leading non-alphabetic leading chars
  while (preg_match("/^[^a-z].*/", $username) === 1) {
    $username = substr($username, 1);
  }
  if (! $username) {
    $username = "geni1";
  }
  if (! username_exists($username)) {
    /* print "no conflict with $username<br/>\n"; */
    return $username;
  } else {
    // shorten the name and append a two-digit number
    $username = substr($username, 0, 6);
    for ($i = 1; $i <= 99; $i++) {
      if ($i < 10) {
        $tmpname = $username . "0" . $i;
      } else {
        $tmpname = $username . $i;
      }
      /* print "trying $tmpname<br/>\n"; */
      if (! username_exists($tmpname)) {
        /* print "no conflict with $tmpname<br/>\n"; */
        return $tmpname;
      }
    }
  }
  throw new Exception("Unable to find a username based on $email_address");
}

function make_member_urn($ma_signer, $username)
{
  $ma_urn = urn_from_cert($ma_signer->certificate());
  parse_urn($ma_urn, $ma_authority, $ma_type, $ma_name);
  return make_urn($ma_authority, 'user', $username);
}

function make_member_urn_attribute($ma_signer, $username)
{
  $urn = make_member_urn($ma_signer, $username);
  return array(MA_ATTRIBUTE::NAME => MA_ATTRIBUTE_NAME::URN,
          MA_ATTRIBUTE::VALUE => $urn,
          MA_ATTRIBUTE::SELF_ASSERTED => false);
}

function make_inside_cert_key($member_id, $client_urn, $signer_cert_file,
        $signer_key_file, &$cert, &$key) {
  // Not using client urn yet
  $cert = NULL;
  $key = NULL;
  $email = NULL;
  $urn = NULL;
  $info = get_member_info($member_id);
  foreach ($info[MA_ARGUMENT::ATTRIBUTES] as $attr) {
    if ($attr[MA_ATTRIBUTE::NAME] === MA_ATTRIBUTE_NAME::EMAIL_ADDRESS) {
      $email = $attr[MA_ATTRIBUTE::VALUE];
    } elseif ($attr[MA_ATTRIBUTE::NAME] === MA_ATTRIBUTE_NAME::URN) {
      $urn = $attr[MA_ATTRIBUTE::VALUE];
    }
  }
  make_cert_and_key($member_id, $email, $urn,
          $signer_cert_file, $signer_key_file,
          &$cert, &$key);
  return true;
}

/* NOTE: This is an internal function and not part of the MA API function. */
function get_member_info($member_id)
{
  global $MA_MEMBER_ATTRIBUTE_TABLENAME;
  $conn = db_conn();
  $sql = "select " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::NAME
  . ", " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::VALUE
  . ", " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::SELF_ASSERTED
  . " from " . $MA_MEMBER_ATTRIBUTE_TABLENAME
  . " where " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::MEMBER_ID
  . " = " . $conn->quote($member_id, 'text');
  $rows = db_fetch_rows($sql);
  // Convert $rows to an array of member_ids
  $attrs = array();
  foreach ($rows[RESPONSE_ARGUMENT::VALUE] as $row) {
    $aname = $row[MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::NAME];
    $avalue = $row[MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::VALUE];
    $aself = $row[MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::SELF_ASSERTED];
    // There must be a better way to convert to Boolean
    $aself = ($aself !== "f");
    $attr = array(MA_ATTRIBUTE::NAME => $aname,
            MA_ATTRIBUTE::VALUE => $avalue,
            MA_ATTRIBUTE::SELF_ASSERTED => $aself);
    $attrs[] = $attr;
  }
  $result = array(MA_ARGUMENT::MEMBER_ID => $member_id,
          MA_ARGUMENT::ATTRIBUTES => $attrs);
  return $result;
}

function mail_account_request($member_id)
{
  // From /etc/geni-ch/settings.php
  global $portal_admin_email;
  $member_info = get_member_info($member_id);
  $member_attrs = $member_info[MA_ARGUMENT::ATTRIBUTES];
  $server_host = $_SERVER['SERVER_NAME'];
  $body = "There is a new account registered on $server_host:\n";
  $body .= "\nmember_id: $member_id";
  foreach ($member_attrs as $attr) {
    $body .= "\n" . $attr[MA_ATTRIBUTE::NAME];
    $body .= ": " . $attr[MA_ATTRIBUTE::VALUE];
    if ($attr[MA_ATTRIBUTE::SELF_ASSERTED]) {
      $body .= "   (self asserted)";
    }
  }
  mail($portal_admin_email,
          "New GENI CH account registered",
          $body);
}
function mail_new_project_lead($member_id)
{
  // From /etc/geni-ch/settings.php
  global $portal_admin_email;
  $member_info = get_member_info($member_id);
  foreach ($member_info[MA_ARGUMENT::ATTRIBUTES] as $attr) {
    $member_attrs[$attr[MA_ATTRIBUTE::NAME]] = $attr[MA_ATTRIBUTE::VALUE];
  }
  $body = "Dear " . $member_attrs[MA_ATTRIBUTE_NAME::FIRST_NAME] . ",\n\n";
  $body .= "Congratulations, you have been made a 'Project Lead', meaning";
  $body .= " you can create GENI Projects, as well as create slices in";
  $body .= " projects and reserve resources.\n\n";
  $body .= "Please visit https://" . $_SERVER['SERVER_NAME'];
  $body .= "/secure/home.php for more information, or to get started.\n\n";
  $body .= "Sincerely,\n";
  $body .= "GENI Clearinghouse operations\n";
  // The example in the PHP docs uses \r\n
  $headers = "Cc: $portal_admin_email\r\n";
  mail($member_attrs[MA_ATTRIBUTE_NAME::EMAIL_ADDRESS],
          "You are now a GENI Project Lead",
          $body, $headers);
}
?>
