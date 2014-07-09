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
require_once("settings.php");
require_once("user.php");
require_once 'geni_syslog.php';
require_once 'tool-rspec-parse.php';

/*
    rspecuploadparser.php
    
    This is expected to be called from an AJAX call from slice-add-resources.js
    via the slice-add-resources.php page. It does server-side validation of an
    RSpec that the user is attempting to upload and will display an error if
    the RSpec is invalid (and why?).
    
    If the RSpec is valid, pass back information on whether it's bound and/or
    stitchable.
    
    Returns the following in JSON:
    
        valid:  0 (not valid)
                1 (valid)
        message: HTML text to display on slice-add-resources.php
        bound:  0 (unbound)
                1 (bound)
        stitch: 0 (non-stitching)
                1 (stitching)
        ams:    array of AM URNs parsed
*/

// defaults
$results = array();
$results['valid'] = false;
$results['message'] = "";
$results['bound'] = false;
$results['stitch'] = false;
$results['ams'] = array();

// basic checking on file sent
if (array_key_exists('user_rspec', $_FILES)) {
    $errorcode = $_FILES['user_rspec']['error'];
    if ($errorcode != 0) {
        // An error occurred with the upload.
        if ($errorcode == UPLOAD_ERR_NO_FILE) {
            $results['message'] = "<b style='color:red;'>ERROR:</b> No file was uploaded.";
        }
        else {
            $results['message'] = "<b style='color:red;'>ERROR:</b> Unknown upload error (code = $errorcode).";
        }
    }
    else {
        // Upload was successful, do some basic checks on the contents.
        $rspec_filename = $_FILES["user_rspec"]["tmp_name"];
        if (! validateRSpec($rspec_filename, $msg)) {
            $results['message'] = "<b style='color:red;'>ERROR:</b> This RSpec is invalid: " . $msg;
        }
        else {
            // RSpec was valid
            $results['valid'] = true;
            $results['message'] = "<b style='color:green;'>SUCCESS:</b> This RSpec is valid";
            
            // get bound status, stitching status, and AM URNs if possible
            $parse_results = parseRequestRSpec($rspec_filename);
            $results['bound'] = $parse_results[1];
            $results['stitch'] = $parse_results[2];
            // FIXME: We can pass the AM URNs back if we need to for bound RSpecs
            $results['ams'] = $parse_results[3];
            
            if($results['stitch']) {
                $results['message'] .= " and stitchable";
            }
            else if($results['bound']) {
                $results['message'] .= " and bound";
            }
            $results['message'] .= ".";
        }
        
    }
}

else {
    $results['message'] = "<b style='color:red;'>ERROR:</b> Invalid form data sent.";
}

//error_log("Attempted to validate RSpec on slice-add-resources: " . json_encode($results));

// pass back the JSON string to slice-add-resources.js
echo json_encode($results);

?>
