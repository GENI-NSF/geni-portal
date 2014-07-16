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
require_once("file_utils.php");
require_once("logging_client.php");
require_once("print-text-helpers.php");

/*
    get_omni_invocation_data.php
    
    Purpose: Handle client-side AJAX calls to get data from omni invocations
    
    Accepts:
        Required:
            invocation_id: unique ID for an omni invocation (e.g. qlj7KS)
            invocation_user: user ID (e.g. bujcich)
            request: type of data requested
                pid
                command
                console
                error
                debug
        Optional:
            raw: 'true' (default) or 'false' (pretty print if available)
    
    Returns:
        $retVal: JSON-encoded array of three key/values:
            'code': 0 for success, non-zero number for failure
            'msg': user-friendly message about the result of the action
            'obj': raw or pretty data (or NULL if error)
    
*/

/* Handle incoming AJAX calls here */
if(array_key_exists("invocation_id", $_REQUEST) && 
        array_key_exists("invocation_user", $_REQUEST) &&
        array_key_exists("request", $_REQUEST)) {

    $invocation_user = $_REQUEST['invocation_user'];
    $invocation_id = $_REQUEST['invocation_id'];
    $request = $_REQUEST['request'];
    
    // set raw to true by default unless it's set to 'false' in AJAX call
    if(array_key_exists("raw", $_REQUEST)) {
        if($_REQUEST['raw'] == 'false') {
            $raw = false;
        }
        else {
            $raw = true;
        }
    }
    else {
        $raw = true;
    }

    // set up invocation directory based on username and invocation ID
    $invocation_dir = get_invocation_dir_name($invocation_user, $invocation_id);

    switch($request) {
        case "pid":
            $retVal = get_omni_invocation_pid($invocation_dir, $raw);
            break;
        case "command":
            $retVal = get_omni_invocation_command($invocation_dir, $raw);
            break;
        case "console":
            $retVal = get_omni_invocation_console_log($invocation_dir, $raw);
            break;
        case "error":
            $retVal = get_omni_invocation_error_log($invocation_dir, $raw);
            break;
        case "debug":
            $retVal = get_omni_invocation_debug_log($invocation_dir, $raw);
            break;
        case "stdout":
            $retVal = get_omni_invocation_stdout($invocation_dir, $raw);
            break;
        default:
            $retVal = array(
                'code' => 1,
                'msg' => "Request type '$request' not valid.",
                'obj' => NULL
            );
            error_log("get_omni_data.php: " . $retVal['msg']);
    }
    
}
else {
    $retVal = array(
        'code' => 1,
        'msg' => "Invalid AJAX request.",
        'obj' => NULL
    );
    error_log("get_omni_data.php: " . $retVal['msg']);
}

/* send back JSON-encoded data */
header("Content-Type: application/json", true);
echo json_encode($retVal);


/* HELPER FUNCTIONS */

/*
    Create the invocation directory given the invocation ID and username
*/
function get_invocation_dir_name($user, $id) { 
    global $omni_invocation_prefix;
    return sys_get_temp_dir() . "/$omni_invocation_prefix-$user-$id";
}

/*
    Get a file's contents
*/
function get_omni_invocation_file_raw_contents($dir, $file, $description) {

    $file_path = "$dir/$file";
    // file exists and isn't empty
    if(is_file($file_path) && filesize($file_path)) {
        $retVal = array(
            'code' => 0,
            'msg' => "Opened $description of length " .
                    filesize($file_path) . " bytes.",
            'obj' => file_get_contents($file_path)
        );
    }
    // file exists but is empty
    else if(is_file($file_path) && filesize($file_path) == 0) {
        $retVal = array(
            'code' => 1,
            'msg' => "Opened $description but file was empty.",
            'obj' => NULL
        );
    }
    else {
        $retVal = array(
            'code' => 1,
            'msg' => "Could not find or open $description.",
            'obj' => NULL
        );
        error_log("get_omni_data.php get_omni_invocation_file_raw_contents: " .
            $retVal['msg']);
    }
    
    return $retVal;
}

/*
    Get the PID from the omni invocation
*/
function get_omni_invocation_pid($dir, $raw=true) {
    $retVal = get_omni_invocation_file_raw_contents($dir, "omni-pid", "PID file");
    return $retVal;
}

/*
    Get the command called from the omni invocation
*/
function get_omni_invocation_command($dir, $raw=true) {
    $retVal = get_omni_invocation_file_raw_contents($dir, "omni-command", 
            "command file");
    if($raw) {
        return $retVal;
    }
    else {
        if($retVal['obj']) {
            $retVal['obj'] = "<p>" . $retVal['obj'] . "</p>";
        }
        return $retVal;
    }
}

/*
    Get the console messages from the omni invocation
    (This is what the user might see if they were running omni on
    the command line.)
*/
function get_omni_invocation_console_log($dir, $raw=true) {
    $retVal = get_omni_invocation_file_raw_contents($dir, "omni-console", 
            "console log");
    return $retVal;
}

/*
    Get the log messages from the omni invocation
    (This is the full log set to the DEBUG level.)
*/
function get_omni_invocation_debug_log($dir, $raw=true) {
    $retVal = get_omni_invocation_file_raw_contents($dir, "omni-log", 
            "debug log");
    return $retVal;
}

/*
    Get any error messages (stderr) from the omni invocation
*/
function get_omni_invocation_error_log($dir, $raw=true) {
    $retVal = get_omni_invocation_file_raw_contents($dir, "omni-stderr", 
            "error log");
    return $retVal;
}

/*
    Get the data returned from stitcher.call
*/
function get_omni_invocation_stdout($dir, $raw=true) {
    $retVal = get_omni_invocation_file_raw_contents($dir, "omni-stdout", 
            "stdout from stitcher.call");
    if($raw) {
        return $retVal;
    }
    else {
        if($retVal['obj']) {
        
            // FIXME: Do checks on this to see if this contains real data
            $output2 = json_decode($retVal['obj'], True);
        
            // FIXME: Note that this captures whatever is buffered in the
            //      output, as print_rspec_pretty() uses prints and echoes.
            //      Probably need a more elegant solution going forward.
            ob_start();
            $obj = print_rspec_pretty($output2[1]);
            $retVal['obj'] = ob_get_clean();
        }
        return $retVal;
    }
}

?>
