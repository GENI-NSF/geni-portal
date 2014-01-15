<?php
//----------------------------------------------------------------------
// Copyright (c) 2013-2014 Raytheon BBN Technologies
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
 * Request a speaks-for credential from the user.
 */
require_once 'user.php';
require_once 'db-util.php';

$key_token = NULL;
if (array_key_exists('AUTH_TYPE', $_SERVER)
    && strcmp($_SERVER['AUTH_TYPE'], 'shibboleth') == 0) {
  /* Shibboleth authentication is present. Look for EPPN. */
  if (array_key_exists('eppn', $_SERVER)) {
    /* Our key token is the EPPN with shibboleth authentication. */
    $key_token = $_SERVER['eppn'];
  }
}

/* Bail out because no key token was found. */
if (is_null($key_token)) {
  header('Unauthorized', true, 401);
  exit();
}

$raw_cred = file_get_contents("php://input");
if (! $raw_cred) {
  header('Bad Request', true, 400);
  exit();
}

// $raw_cred should be XML
$xml_parser = xml_parser_create();
$parse_result = xml_parse($xml_parser, $raw_cred, true);
if ($parse_result === 0) {
  $xml_error = xml_error_string(xml_get_error_code($xml_parser));
  $line = xml_get_current_line_number($xml_parser);
  $column = xml_get_current_column_number($xml_parser);
  $error_msg = "$xml_error at line $line, column $column";
  xml_parser_free($xml_parser);
  error_log("SpeaksFor upload failed: $error_msg");
  header('Bad Request', true, 400);
  exit();
} else {
  xml_parser_free($xml_parser);
}

class SF_TAG {
  const EXPIRES = 'expires';
  const X509_CERTIFICATE = 'X509Certificate';
}

/*
 * If we're here, the uploaded data is XML. Do a little more checking
 * and extract the expiration.
 */
$dom_document = new DOMDocument();
$dom_document->loadXML($raw_cred);
$root = $dom_document->documentElement;
/* foreach ($root->childNodes as $child) { */
/*   if ($child->nodeType !== XML_ELEMENT_NODE) { */
/*     continue; */
/*   } */
/*   error_log('SFU child name = ' . $child->nodeName */
/*             . '(type ' . $child->nodeType . ')'); */
/* } */
$expires_nodes = $dom_document->getElementsByTagName(SF_TAG::EXPIRES);
if ($expires_nodes->length !== 1) {
  header('HTTP/1.1 400 Invalid credential: expires node count = '
         . $expires_nodes->length);
}

$expires_node = $expires_nodes->item(0);
$expires = $expires_node->nodeValue;
//error_log('Expiration = ' . $expires_node->nodeValue);

/* Now extract the URN of the user signing the credential. */
$cert_nodes = $dom_document->getElementsByTagName(SF_TAG::X509_CERTIFICATE);
if ($cert_nodes->length !== 1) {
  header('HTTP/1.1 400 Invalid credential: ' . SF_TAG::X509_CERTIFICATE
         . ' node count = ' . $cert_nodes->length);
}

$certNode = $cert_nodes->item(0);
$pemCert = $certNode->nodeValue;
$beginpem = "-----BEGIN CERTIFICATE-----\n";
$endpem = "-----END CERTIFICATE-----\n";
$signer_urn = pem_cert_geni_urn($beginpem . $pemCert . $endpem);

/* Now put the credential in the database. */
$db_result = store_speaks_for($key_token, $raw_cred, $signer_urn, $expires);
if (! $db_result) {
  header('HTTP/1.1 500 Cannot store uploaded credential');
  exit();
}

// All done. Signal success without passing any content.
$_SESSION['lastmessage'] = "You succesfully authorized the GENI Portal";
header('HTTP/1.1 204 No Content');
?>
