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

require_once('file_utils.php');

// Utilities to generate page for embedding flack in the portal for a given slice

const FLACK_1_FILENAME = "flackportal-1.html";
const FLACK_2_FILENAME = "flackportal-2.html";
const FLACK_3_FILENAME = "flackportal-3.html";
const URL_PREAMBLE = "flack.swf?securitypreset=1&loadallmanagers=1&";
const SA_URN = "urn:publicid:IDN+geni:gpo:portal+authority+sa";

// Generate flack pages and return contents of generated page
function generate_flack_page($slice_urn, $ch_url, $sa_url, $user_cert, $user_key, $am_root_cert_bundle)
{

  $filename = "/tmp/" . make_uuid() . ".html";
  $content_1 = file_get_contents(FLACK_1_FILENAME);
  $content_2 = file_get_contents(FLACK_2_FILENAME);
  $content_3 = file_get_contents(FLACK_3_FILENAME);

  $set_commands = 'setServerCert("' . $am_root_cert_bundle . '");' . "\n" 
    . 'setClientKey("' . $user_key . '");' . "\n"
    . 'setClientCert("' . $user_cert . '");' . "\n";

  $url_params = "sliceurn=$slice_urn&saurl=$sa_url&saurn=" . SA_URN. "&churl=$ch_url";

  $content = $content_1 . $set_commands . $content_2 . '"' . URL_PREAMBLE . $url_params . $content_3;
  file_put_contents($filename, $content);
  return $content;
}

$content = generate_flack_page('111', '222', '333', '444', '555', '666');

print $content;

?>
