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

include("kmheader.php");
include("util.php");
$eppn = strtolower($_SERVER['eppn']);
?>

<h2>No email address</h2>
Your identity provider is not sharing your email address with us.
<a href="http://www.geni.net">GENI</a> requires an email address
so that you can be contacted if necessary about your reserved
resources.
<br/>
<br/>
If you would like to register for a GENI account, please self-assert
your <em>institutional email address</em> by
<a href="mailto:portal-help@geni.net?subject=Self-asserted email address for EPPN
<?php
print " $eppn";
?>
&body=I would like to register for a GENI account. This email was sent from my institutional email address.">
sending an email
</a>
to portal-help@geni.net from your institutional email address. Make sure the
email you send includes your
<a href="http://www.incommon.org/federation/attributesummary.html#eduPersonPrincipal">EPPN</a>, which is:
<br/><br/>

<b>
<?php
print $eppn;
?>
</b>
<br/>
<br/>
Your email will be reviewed and you will receive a response from a GENI
administrator about how to proceed.
<br/>
<br/>
<a href="
<?php
// Link to InCommon federated error handling service.
print incommon_feh_url();
?>
">Technical Information</a>
<?php
include("kmfooter.php");
?>
