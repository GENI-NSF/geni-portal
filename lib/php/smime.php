<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2016 Raytheon BBN Technologies
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

require_once('file_utils.php');

function smime_debug($msg)
{
  //  error_log('SMIME DEBUG: ' . $msg);
}

function smime_decrypt($message)
{
  return $message;
}

function smime_validate($message, $cacerts, &$signer_pem)
{
  $signer_pem = NULL;
  $decoded = json_decode($message, true);
  if (! is_null($decoded)) {
    /* Decoding succeeded, so not smime encoded. */
    /* smime_debug("smime_validate: no smime detected."); */
    return $message;
  } else {
    smime_debug("smime_validate: smime detected.");
  }
  $msg_file = writeDataToTempFile($message, "smime-msg-");
  $flags = 0;
  $signer_cert_file = tempnam(sys_get_temp_dir(), "smime-signer-");

  /* This is ugly, but it is what it is. There is no way to get the
     contents of the signed mssage without extracerts specified. And
     there is no way to pass a NULL to extacerts, or to have
     extracerts be an empty file. Sooo, put the first CA into that
     file. Ugly. */
  $extracerts_contents = file_get_contents($cacerts[0]);
  $extracerts_file = writeDataToTempFile($extracerts_contents, "smime-extra-");
  $content_file = tempnam(sys_get_temp_dir(), "smime-content-");
  $result = openssl_pkcs7_verify($msg_file, $flags, $signer_cert_file, $cacerts,
                                 $extracerts_file, $content_file);

  /* Note the triple equals, requiring the *same type*. Two equal
     signs will match the boolean TRUE result, causing programmer
     confusion. */
  if ($result === -1) {
    /* An error occurred. */
    error_log("An error occurred trying to verify the message.");
    /* Print out as many errors as openssl can offer. */
    while ($msg = openssl_error_string()) {
      error_log("openssl_error: $msg");
    }
    $message = NULL;
  } else if ($result) {
    smime_debug("smime_validate: successful verification.");
    $message = file_get_contents($content_file);
    $signer_pem = file_get_contents($signer_cert_file);
  } else {
    /* Verification failed. */
    error_log("The message failed to verify.");
    $message = NULL;
  }
  unlink($msg_file);
  unlink($signer_cert_file);
  unlink($extracerts_file);
  unlink($content_file);
  return $message;
}

/**
 * To verify the signed message on the command line:
 *
 *  openssl smime -verify -in <msg file> \
 *                -CAfile /usr/share/geni-ch/CA/cacert.pem
 */
function smime_sign_message($message, $signer_cert=null, $signer_key=null)
{
  if (! is_null($signer_cert)) {
    $msg_file = writeDataToTempFile($message, "msg-");
    $out_file = tempnam(sys_get_temp_dir(), "smime-");
    $headers = null;
    $flags = PKCS7_DETACHED;
    $extracerts = writeDataToTempFile($signer_cert, "cert-");
    if (openssl_pkcs7_sign($msg_file, $out_file, $signer_cert, $signer_key,
                           $headers, $flags, $extracerts)) {
      /* SUCCESS */
      smime_debug("smime_sign_message succeeded.");
      $message = file_get_contents($out_file);
    } else {
      /* FAILURE */
      error_log("smime_sign_message failed.");
    }
    unlink($msg_file);
    unlink($out_file);
    unlink($extracerts);
  }
  return $message;
}

function smime_encrypt($message, $target_cert=NULL)
{
  if (! $target_cert) {
    /* Cannot encrypt without a target certificate. */
    return $message;
  }
  $msg_file = writeDataToTempFile($message, "msg-");
  $out_file = tempnam(sys_get_temp_dir(), "smime-");
  /* No mail headers */
  $headers = array();
  if (openssl_pkcs7_encrypt($msg_file, $out_file, $target_cert, $headers)) {
    /* SUCCESS */
      smime_debug("smime_sign_message succeeded.");
      $message = file_get_contents($out_file);
  } else {
    /* FAILURE */
      error_log("smime_encrypt failed.");
  }
  unlink($msg_file);
  unlink($out_file);
  return $message;
}

function encode_result($result)
{
  return json_encode($result);
}

function decode_result($result)
{
  return json_decode($result, true); // Return associative array
}

function parse_message($msg)
{
  $map = json_decode($msg, true);
  //  $pretty_map = print_r($map, true);
  //  smime_debug("json_decode returned $pretty_map");
  $funcargs[0] = $map['operation'];
  unset($map['operation']);
  $funcargs[1] = $map;
  return $funcargs;
}

function parse_result($result)
{
  return $result;
}

?>
