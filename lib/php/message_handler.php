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

/* Top level message handler for a PHP top-level process
 * to handle smime encrypted/signed message files as JSON files
 *
 * Take the message as a file, decrypt, validate, decode as JSON, 
 * run the signified function with arguments
 * take the result and JSON encode into a document, encrypt and sign and return response
 */

require_once("smime.php");
require_once('response_format.php');
require_once('util.php');

function default_cacerts()
{
  return array('/usr/share/geni-ch/CA/cacert.pem');
}

/*
 * Extract the message depending on how it was sent.
 *
 * Currently only handles 'put'.
 *
 * Returns the message as a string.
 */
function extract_message()
{
  $request_method = strtolower($_SERVER['REQUEST_METHOD']);
  switch($request_method)
    {
    case 'put':
      $putdata = fopen("php://input", "r");
      $data = '';
      //      error_log($prefix . " starting to read...");
      while ($putchunk = fread($putdata, 1024))
	{
	  //	  error_log("Read chunk: $putchunk");
	  $data .= $putchunk;
	}
      fclose($putdata);
      break;
    case 'postXXX':
      /* This case is incomplete. */
      if (array_key_exists('file', $_FILES)) {
	$errorcode = $_FILES['file']['error'];
	if ($errorcode != 0) {
	  // An error occurred with the upload.
	  if ($errorcode == UPLOAD_ERR_NO_FILE) {
	    $error = "No file was uploaded.";
	  } else {
	    $error = "Unknown upload error (code = $errorcode).";
	  }
	  //	  error_log($prefix . ": $error");
	} else {
	  $msg_file = $_FILES["file"]["tmp_name"];
	}
      }
      break;
    }
  return $data;
}

/*
 * Top level message handler for services. Processes an incoming
 * message, invokes the appropriate function, signs and encrypts the
 * result.
 *
 * $prefix - a string prefix for debugging
 *
 * $cacerts - an array of files or directories containing CA
 *            certificates. See PHP openssl documentation for info
 *            on the format of directories.
 *
 * $receiver_cert - the certificate for signing responses.
 *
 * $receiver_key - the key for decrypting incoming messages and
 *                 signing outgoing responses.
 *
 * Returns nothing.
 */
function handle_message($prefix, $cacerts=null, $receiver_cert = null,
                        $receiver_key=null)
{
  // error_log($prefix . ": starting");
  $data = extract_message();

  // Now process the data
  $data = smime_decrypt($data);

  // No CAs specified, use the default set.
  if (is_null($cacerts)) {
    $cacerts = default_cacerts();
  }
  $msg = smime_validate($data, $cacerts, $signer_pem);
  $geni_message = new GeniMessage($msg);
  $geni_message->setSignerPem($signer_pem);
  if (is_null($msg)) {
    /* Message failed to verify. Return authentication failure. */
    $result = generate_response(RESPONSE_ERROR::AUTHENTICATION,
                                NULL,
                                "Message verification failed.");
  } else {
    $funcargs = parse_message($msg);
    $func = $funcargs[0];
    if (is_callable($func)) {
      $refFunc = new ReflectionFunction($func);
      $paramCount = $refFunc->getNumberOfParameters();
      if ($paramCount === 1) {
        $result = call_user_func($func, $funcargs[1]);
      } else if ($paramCount === 2) {
        $result = call_user_func($func, $funcargs[1], $geni_message);
      } else {
        error_log("Unknown method signature for invoked method \"$func\"."
                  . " Expected 1 or 2 parameters, but $func expects"
                  . " $paramCount");
        generate_response(RESPONSE_ERROR::ARGS,
                          NULL,
                          "Bad callback signature for \"$func\".");
      }
    } else {
      $result = generate_response(RESPONSE_ERROR::ARGS,
                                  NULL,
                                  "Unknown operation \"$func\".");
    }
  }

  //  error_log("RESULT = " . $result);
  $output = encode_result($result);
  //   error_log("RESULT(enc) = " . $output);
  //   error_log("RESULT(dec) = " . decode_result($output));
  $output = smime_sign_message($output);
  $output = smime_encrypt($output);
  //   error_log("BEFORE PRINT:" . $output);
  print $output;
}

/*
 * *** TEMP FIX
//--------------------------------------------------
// Get account_ID for current user on portal
//--------------------------------------------------
function get_account_id()
{
  $eppn = $_SERVER['eppn'];
  $query = "SELECT account_id from identity where eppn = '" . $eppn . "'";
  $row = db_fetch_row($query);
  error_log("GAI QUERY = " . $query . " ROW = " . print_r($row, true));
  $account_id = $row['account_id'];
  return $account_id;
}

$ACCOUNT_ID = null; 
// END OF TEMP FIX 
*/

//--------------------------------------------------
// Send a message (via PUT) to a given URL and return response
//--------------------------------------------------
function put_message($url, $message, $signer_cert=null, $signer_key=null)
{
  //  error_log("PUT_MESSAGE " . $url . " " . $_SERVER['PHP_SELF'] . " " . $message['operation']);

  /* 
   * *** TEMP FIX
  // *** TEMP FIX - Stick the account id as 'signer' field in message
  global $ACCOUNT_ID;
  if ($ACCOUNT_ID == null) { // First time through
    $ACCOUNT_ID = get_account_id();
  }
  $signer = $ACCOUNT_ID;
  $message['signer'] = $signer;
  //  error_log("MSG (SEND) = " . print_r($message, true));
  //  error_log("Sent : SIGNER = " . $signer);
  // *** END OF TEMP FIX
  */

  if (! isset($url) || is_null($url) || trim($url) == '') {
    error_log("put_message error: empty URL");
    return null;
  }

  $message = json_encode($message);
  //  error_log("PUT_MESSAGE(enc) " . $message);
  $message = smime_sign_message($message, $signer_cert, $signer_key);
  // encrypt
  $tmpfile = tempnam(sys_get_temp_dir(), "msg");
  file_put_contents($tmpfile, $message);
  $ch = curl_init();
  $fp = fopen($tmpfile, "r");
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_PUT, true);
  curl_setopt($ch, CURLOPT_INFILE, $fp);
  curl_setopt($ch, CURLOPT_INFILESIZE, strlen($message));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $result = curl_exec($ch);
  $error = curl_error($ch);
  curl_close($ch);
  fclose($fp);
  unlink($tmpfile);
  if ($error) {
    error_log("put_message error: $error");
    $result = NULL;
  }
  // error_log("Received raw result : " . $result);
  $result = trim($result); // Remove trailing newlines
  if (strpos($result, "404 Not Found")) {
    error_log("put_message error: Page $url Not Found");
    return null;
  }

  /* Responses are signed messages. Verify the signature on return. */
  $cacerts = default_cacerts();
  $result = smime_validate($result, $cacerts, $signer_pem);

  $result = decode_result($result);
  //  error_log("Decoded raw result : " . $result);

  //  error_log("MH.RESULT = " . print_r($result, true));

  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    error_log("SCRIPT_NAME = " . $_SERVER['SCRIPT_NAME']);
    error_log("ERROR.CODE " . print_r($result[RESPONSE_ARGUMENT::CODE], true));
    error_log("ERROR.VALUE " . print_r($result[RESPONSE_ARGUMENT::VALUE], true));
    error_log("ERROR.OUTPUT " . print_r($result[RESPONSE_ARGUMENT::OUTPUT], true));

    relative_redirect('error-text.php' . "?" . $result[RESPONSE_ARGUMENT::OUTPUT]);
  }


  //     error_log("ERROR.OUTPUT " . print_r($result[RESPONSE_ARGUMENT::OUTPUT], true));

  return $result[RESPONSE_ARGUMENT::VALUE];
}

Class GeniMessage {
  private $extensions_key = 'extensions';
  private $subjectAltName_key = 'subjectAltName';
  private $email_regex = "/^email:(.*)$/";
  private $urn_regex = "/^URI:(urn:publicid:.*)$/";
  private $uuid_regex = "/^URI:(urn:uuid:.*)$/";

  function __construct($raw_message) {
    $this->raw_message = $raw_message;
    $this->signer_pem = NULL;
    $this->signer = NULL;
  }
  function setSignerPem($signer_pem) {
    $this->signer_pem = $signer_pem;
    /* Clear a cached x509 cert */
    $this->signer = NULL;
    $this->signer_urn = NULL;
  }
  function signerPem() {
    return $this->signer_pem;
  }
  function signer() {
    if (is_null($this->signer) && ! is_null($this->signer_pem)) {
      $this->signer = openssl_x509_parse($this->signer_pem);
    }
    return $this->signer;
  }
  private function parseSubjectAltName() {
    $signer = $this->signer();
    if (! is_null($signer)
        && array_key_exists($this->extensions_key, $signer)) {
      $extensions = $signer[$this->extensions_key];
      if (array_key_exists($this->subjectAltName_key, $extensions)) {
        $subjectAltName = $extensions[$this->subjectAltName_key];
        $names = explode(",", $subjectAltName);
        foreach ($names as $name) {
          error_log("Untrimmed SAN: \"$name\"");
          $name = trim($name);
          error_log("Trimmed SAN: \"$name\"");
          if (preg_match($this->email_regex, $name, $matches) === 1) {
            $this->signer_email = $matches[1];
            error_log("signer email = " . $this->signer_email);
          } else if (preg_match($this->urn_regex, $name, $matches) === 1) {
            $this->signer_urn = $matches[1];
            error_log("signer urn = " . $this->signer_urn);
          } else if (preg_match($this->uuid_regex, $name, $matches) === 1) {
            $this->signer_uuid = $matches[1];
            error_log("signer uuid = " . $this->signer_uuid);
          } else {
          }
        }
      }
    }
  }
  function signerUrn() {
    if (is_null($this->signer_urn) && ! is_null($this->signer_pem)) {
      $this->parseSubjectAltName();
    }
    return $this->signer_urn;
  }
}

?>
