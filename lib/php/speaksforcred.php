<?php
//----------------------------------------------------------------------
// Copyright (c) 2014 Raytheon BBN Technologies
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

class SpeaksForCredential
{
  public function __construct() {
    $this->cred = NULL;
    $this->expires = NULL;
    $this->signer_urn = NULL;
  }

  /**
   * Factory method to create an instance from
   * a credential.
   */
  public static function fromCred($cred) {
    $sfcred = new SpeaksForCredential();
    $sfcred->initCred($cred);
    return $sfcred;
  }

  /**
   * Factory method to create an instance from pre-parsed
   * information.
   *
   * Note: information is assumed to be correct and is not validated.
   */
  public static function fromInfo($cred, $expires, $signer_urn) {
    $sfcred = new SpeaksForCredential();
    $sfcred->cred = $cred;
    $sfcred->expires = $expires;
    $sfcred->signer_urn = $signer_urn;
    return $sfcred;
  }

  public function credential() {
    return $this->cred;
  }

  public function expires() {
    return $this->expires;
  }

  public function signerURN() {
    return $this->signer_urn;
  }

  /**
   * Return an map (key value pairs) representation of this credential
   * suitable for passing via the Common Federation API.
   */
  public function credentialForFedAPI() {
    $result = array('geni_type' => 'ABAC',
                    'geni_version' => '1',
                    'geni_value' => $this->cred);
    return $result;
  }
}
?>
