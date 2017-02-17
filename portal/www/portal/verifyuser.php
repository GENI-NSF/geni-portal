<?php
//----------------------------------------------------------------------
// Copyright (c) 2017 Raytheon BBN Technologies
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
require_once("user.php");

// Get the authenticated user for logging
$user = geni_loadUser();
if (! isset($user)) {
        header('X-PHP-Response-Code: 400', true, 400);
        exit;
}

$KEY_USER = "user";
$KEY_PASS = "pass";

// Get these from the request
$gpo_user = null;
if (array_key_exists($KEY_USER, $_REQUEST)) {
        $gpo_user = $_REQUEST[$KEY_USER];
} else {
        header('X-PHP-Response-Code: 400', true, 400);
        exit;
}

$gpo_pass = null;
if (array_key_exists($KEY_PASS, $_REQUEST)) {
        $gpo_pass = $_REQUEST[$KEY_PASS];
} else {
        header('X-PHP-Response-Code: 400', true, 400);
        exit;
}


// Get these from /etc settings
$idp_user = "scott";
$idp_password = "tiger";
$idp_host = "idp.example.com";

$url = "https://$idp_host/manage/verifyuser.php?user=$gpo_user&pass=$gpo_pass";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_USERPWD, "$idp_user:$idp_password");
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // enable this
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

$output = curl_exec($ch);
if ($output === FALSE) {
        $err = curl_error($ch);
        error_log("curl_exec has failed. error: $err");
        curl_close($ch);
        header('X-PHP-Response-Code: 400', true, 400);
        exit;
}

$info = curl_getinfo($ch);
$response_code = $info["http_code"];
if ($response_code != 200) {
        error_log("Failed to verify user");
}
curl_close($ch);

// Return whatever the IdP returned for status
header("X-PHP-Response-Code: $response_code", true, $response_code);
?>
