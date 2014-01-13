<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2014 Raytheon BBN Technologies
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

require_once 'settings.php';
require_once 'signer.php';

class Portal extends Signer
{
  static function getInstance() {
    global $portal_cert_file, $portal_private_key_file;
    return new Portal($portal_cert_file, $portal_private_key_file);
  }

  // Get the UID for the Portal out of its cert, returning null on error.
  // See ma_client.get_portal_uid()
  static function getUid() {
    global $portal_cert_file;
    if (! isset($portal_cert_file)) {
      return null;
    }
    $san = null;
    $uid = null;
    try {
      $cert = file_get_contents($portal_cert_file);
      $ssl = openssl_x509_parse($cert);
      if (array_key_exists("extensions", $ssl)) {
	if (array_key_exists('subjectAltName', $ssl['extensions'])) {
	  $san = $ssl['extensions']['subjectAltName'];
	} else {
	  error_log("Extensions had no subjectAltName: " . print_r($ssl['extensions'], True));
	}
      } else {
	error_log("cert had no extensions: " . print_r($ssl, True));
      }
    } catch (Exception $e) {
      error_log("Failed to parse portal cert to get subjectAltName: ", $e->getMessage());
    }
    if (! is_null($san)) {
      // email:portal-sandbox-admin@gpolab.bbn.com, URI:urn:publicid:IDN+ch-ah.gpolab.bbn.com+authority+portal, URI:uuid:b2822cca-4c62-4b08-83fd-e0afaf331908
      $uidbit = stristr($san, "URI:uuid");
      if ($uidbit !== False) {
	$uid = substr($uidbit, 9);
      } else {
	error_log("Found no UID in subjectAltNames: $san");
      }
    }
    return $uid;
  }
}
?>
