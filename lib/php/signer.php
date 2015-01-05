<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2015 Raytheon BBN Technologies
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

// base class representing a signer object which presents a cert and private key
// for an object that can sign messages for packaging and sending to another service

class Signer
{
  function __construct($cert_file, $private_key_file, $sfcred=NULL) {
    $this->cert_file = $cert_file;
    $this->private_key_file = $private_key_file;
    $this->sfcred = $sfcred;
    $this->certificate = NULL;
    $this->private_key = NULL;
  }

  function certificate() {
    if (is_null($this->certificate)) {
      $this->certificate = file_get_contents($this->cert_file);
    }
    return $this->certificate;
  }

  function privateKey() {
    if (is_null($this->private_key)) {
      $this->private_key = file_get_contents($this->private_key_file);
    }
    return $this->private_key;
  }

  /**
   * The speaks for credential if this signer is speaking for another
   * actor.
   *
   * @return a SpeaksForCredential instance if "speaking for" another
   * actor, NULL otherwise.
   */
  function speaksForCred() {
    return $this->sfcred;
  }
}
?>
