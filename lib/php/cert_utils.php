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

require_once('file_utils.php');

/**
 * Functions to help with certificate handling.
 */


/**
 * Get the PEM portion of a certificate file. When openssl signs a
 * certificate request, the resulting certificate file has a text
 * portion and a PEM portion. This function loads just the PEM portion
 * and returns it as a string.
 *
 * Return a string of PEM contents are successfully loaded, null
 * otherwise.
 */
function get_pem_contents($file)
{
  $cmd_array = array('/usr/bin/openssl',
                     'x509',
                     '-in', $file);
  $command = implode(" ", $cmd_array);
  $result = exec($command, $output, $status);
  if ($status != 0) {
    print "Command $command returned status $status.\n";
    return null;
  }
  return implode("\n", $output);
}

function make_csr($uuid, $email, &$csrfile, &$keyfile, $temp_prefix="geni-")
{
  $csrfile = null;
  $keyfile = null;
  $keytmpfile = tempnam(sys_get_temp_dir(), $temp_prefix);
  $csrtmpfile = tempnam(sys_get_temp_dir(), $temp_prefix);
  $subject = "/CN=$uuid/emailAddress=$email";
  $cmd_array = array('/usr/bin/openssl',
                     'req',
                     '-new',
                     '-newkey', 'rsa:1024',
                     '-nodes',
                     '-keyout', $keytmpfile,
                     '-out', $csrtmpfile,
                     '-subj', $subject,
                     '-batch');
  $command = implode(" ", $cmd_array);
  $result = exec($command, $output, $status);
  if ($status == 0) {
    $csrfile = $csrtmpfile;
    $keyfile = $keytmpfile;
    return TRUE;
  } else {
    print "Command $command returned status $status.\n";
    return FALSE;
  }
}

function sign_csr($csr_file, $uuid, $urn, $signer_cert_file, $signer_key_file,
                  &$cert, $temp_prefix="geni-")
{
  $cert_file = tempnam(sys_get_temp_dir(), $temp_prefix);

  // Write the extension data to a file
  $extname = 'v3_user';
  $extdata = "[ $extname ]\n"
    . "subjectKeyIdentifier=hash\n"
    . "authorityKeyIdentifier=keyid:always,issuer:always\n"
    . "basicConstraints = CA:false\n"
    //    . "authorityInfoAccess = caIssuers;URI:https://ma.example.com/ma_controller.php\n"
    . "subjectAltName=email:copy,URI:$urn,URI:urn:uuid:$uuid\n";
  $ext_file = writeDataToTempFile($extdata, $temp_prefix);

  $cmd_array = array('/usr/bin/openssl',
                     'ca',
                     // FIXME: this should be an MA-specific openssl conf file.
                     '-config', '/usr/share/geni-ch/CA/openssl.cnf',
                     '-extfile', $ext_file,
                     '-policy', 'policy_anything',
                     '-out', $cert_file,
                     '-in', $csr_file,
                     '-extensions', $extname,
                     '-batch',
                     '-cert', $signer_cert_file,
                     '-keyfile', $signer_key_file);
  $command = implode(" ", $cmd_array);
  $result = exec($command, $output, $status);
  if ($status != 0) {
    print "Command $command returned status $status.\n";
    return FALSE;
  }

  /*
   * Now load the contents of the generated cert and concatentate with
   * the signer to form a chain certificate (suitable for framing).
   */
  $cert_pem = get_pem_contents($cert_file);
  if (is_null($cert_pem)) {
    print "Unable to load user cert from $cert_file.\n";
    return FALSE;
  }
  $signer_pem = get_pem_contents($signer_cert_file);
  if (is_null($signer_pem)) {
    print "Unable to load signer cert from $signer_cert_file.\n";
    return FALSE;
  }
  $cert = $cert_pem . "\n" . $signer_pem;

  unlink($cert_file);
  unlink($ext_file);
  return TRUE;
}

function make_cert_and_key($uuid, $email, $urn,
                           $signer_cert_file, $signer_key_file,
                           &$cert, &$key, $temp_prefix="geni-")
{
  $cert = null;
  $key = null;

  if (! make_csr($uuid, $email, $csr_file, $key_file, $temp_prefix)) {
    return FALSE;
  }

  if (! sign_csr($csr_file, $uuid, $urn, $signer_cert_file, $signer_key_file,
                 $cert, $temp_prefix)) {
    return FALSE;
  }

  $key = file_get_contents($key_file);
  unlink($csr_file);
  unlink($key_file);
  return TRUE;
}

?>