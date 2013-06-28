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

//--------------------------------------------------
// Site settings for slice authority
//--------------------------------------------------

// Certificate for signing slice certificates and slice credentials.
$sa_authority_cert = '/usr/share/geni-ch/sa/sa-cert.pem';

// Private key matching $sa_authority_cert
$sa_authority_private_key = '/usr/share/geni-ch/sa/sa-key.pem';

// Location of "mkslicecert" program
$sa_mkslicecert_prog = '/usr/share/geni-ch/sa/bin/mkslicecert';

// Location of "mkcred" program
$sa_mkcred_prog = '/usr/share/geni-ch/sa/bin/mkcred';

// The lifetime of slice certificates in days. 3650 = 10 years.
$sa_slice_cert_life_days = 3650;

// The location of the gcf src dir for including in python programs
$sa_gcf_include_path = '/usr/share/geni-ch/portal/gcf/src';

$sa_trusted_roots = array('/usr/share/geni-ch/CA/cacert.pem');

$sa_default_slice_expiration_hours = 168;

$sa_max_slice_renewal_days = 185;
?>
