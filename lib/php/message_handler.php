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

/* Top level message handler for a PHP top-level process
 * to handle smime encrypted/signed message files as JSON files
 *
 * Take the message as a file, decrypt, validate, decode as JSON, 
 * run the signified function with arguments
 * take the result and JSON encode into a document, encrypt and sign and return response
 */

require_once("smime.php");

function handle_message($prefix)
{
  //  error_log($prefix . ": starting");
  $request_method = strtolower($_SERVER['REQUEST_METHOD']);
  switch($request_method)
    {
    case 'put':
      $putdata = fopen("php://input", "r");
      $data = '';
      //      error_log($prefix . " starting to read...");
      while ($putchunk = fread($putdata, 1024))
	{
	  //	  error_log("Read chunk: $putchunk");
	  $data .= $putchunk;
	}
      fclose($putdata);
      break;
    case 'post':
      if (array_key_exists('file', $_FILES)) {
	$errorcode = $_FILES['file']['error'];
	if ($errorcode != 0) {
	  // An error occurred with the upload.
	  if ($errorcode == UPLOAD_ERR_NO_FILE) {
	    $error = "No file was uploaded.";
	  } else {
	    $error = "Unknown upload error (code = $errorcode).";
	  }
	  //	  error_log($prefix . ": $error");
	} else {
	  $msg_file = $_FILES["file"]["tmp_name"];
	}
      }
      break;
    }
   
  //  error_log($prefix . ": finished switch");
  //  error_log("Data = " . $data);

  // Now process the data
  $data = smime_decrypt($data);
  $msg = smime_validate($data);
  // XXX Error check smime_validate result here
   
  $funcargs = parse_message($msg);
  $result = call_user_func($funcargs[0], $funcargs[1]);
  //  error_log("RESULT = " . $result);
  $output = encode_result($result);
  //   error_log("RESULT(enc) = " . $output);
  //   error_log("RESULT(dec) = " . decode_result($output));
  $output = smime_sign_message($output);
  $output = smime_encrypt($output);
  //  error_log("BEFORE PRINT:" . $output);
  print $output;
}

?>
