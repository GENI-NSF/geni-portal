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
                stdout
                status
                elapsed
        Optional:
            raw: 'true' (default) or 'false' (pretty print if available)
            offset: offset in bytes of where to get file data for requests 
                that use tailing (default is 0)
    
    Returns:
        $retVal: JSON-encoded array of 4 key/values:
            'code': 0 for success, non-zero number for failure
            'msg': user-friendly message about the result of the action
            'obj': raw or pretty data
                for non-tailing objects, 'obj' will be string (or NULL if failed)
                for tailing objects, 'obj' has 3 key/values:
                    'data': raw or pretty data returned (or NULL if nothing)
                    'bytes_read': number of bytes read
                    'new_offset': what the next query's offset should be
            'time': local server time when request is returned
    
*/

// FIXME: Add security (who is allowed to call this?)

/* Handle incoming AJAX calls here */
if(array_key_exists("invocation_id", $_REQUEST) && 
        array_key_exists("invocation_user", $_REQUEST) &&
        array_key_exists("request", $_REQUEST)) {

    $invocation_user = $_REQUEST['invocation_user'];
    $invocation_id = $_REQUEST['invocation_id'];
    $request = $_REQUEST['request'];
    
    // set raw to true by default unless it's set to 'false' in AJAX call
    if(array_key_exists("raw", $_REQUEST) && $_REQUEST['raw'] == 'false') {
        $raw = false;
    }
    else {
        $raw = true;
    }
    
    // set offset to 0 by default unless it's set in AJAX call
    if(array_key_exists("offset", $_REQUEST) 
            && is_int(intval($_REQUEST['offset']))
            && intval($_REQUEST['offset'] >= 0)) {
        $offset = intval($_REQUEST['offset']);
    }
    else {
        $offset = 0;
    }

    // set up invocation directory based on username and invocation ID
    $invocation_dir = get_invocation_dir_name($invocation_user, $invocation_id);

    switch($request) {
        case "pid":
            $retVal = get_omni_invocation_pid($invocation_dir);
            break;
        case "command":
            $retVal = get_omni_invocation_command($invocation_dir, $raw);
            break;
        case "console":
            $retVal = get_omni_invocation_console_log($invocation_dir, $raw, $offset);
            break;
        case "error":
            $retVal = get_omni_invocation_error_log($invocation_dir, $raw);
            break;
        case "debug":
            $retVal = get_omni_invocation_debug_log($invocation_dir, $raw, $offset);
            break;
        case "stdout":
            $retVal = get_omni_invocation_stdout($invocation_dir, $raw);
            break;
        case "status":
            $retVal = get_omni_invocation_status($invocation_dir, $raw);
            break;
        case "elapsed":
            $retVal = get_omni_invocation_elapsed_time($invocation_dir, $raw);
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

/* set JSON header */
header("Content-Type: application/json", true);

/* add timestamp with local server time (i.e. EST/EDT) because
   this is likely the time zone that the omni log files will show
*/
$dt = new DateTime();
$dt->setTimezone(new DateTimeZone("America/New_York"));
$retVal['time'] = $dt->format('H:i:s T');

/* send back JSON-encoded data */
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
    Get a file's contents (with no offset via file_get_contents)
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
    // file doesn't exist
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
    Get a file's contents using fopen/fseek/fread
    Use this for functions that have to tail a file (and which have an offset)
    Returns all of the file's content starting at the offset byte and ending
        at the current end of the file
    (based on http://code.google.com/p/php-tail/source/browse/trunk/PHPTail.php)
*/
function get_omni_invocation_file_raw_contents_offset($dir, $file, 
        $description, $offset) {

    clearstatcache();
    
    $file_path = "$dir/$file";

    // file exists and isn't empty
    if(is_file($file_path) && filesize($file_path)) {
    
        // get file size and length of data that we should be getting
        $fsize = filesize($file_path);
        $length_to_read = ($fsize - $offset);
        
        $data = "";
        if($length_to_read > 0) {
            $fp = fopen($file_path, 'r');
            fseek($fp, -$length_to_read , SEEK_END);
            $data = fread($fp, $length_to_read);
            $length = strlen($data);
            fclose($fp);
        }
        else {
            $length_to_read = 0;
        }
        
        /* return data back
           in this case, the object will contain 3 key/value pairs:
                data: the data from the file itself
                bytes_read: number of bytes that were read from the file based
                    on file size and offset specified
                new_offset: what should be the next offset when another AJAX
                    call is made on the client
        */
        $retVal = array(
            'code' => 0,
            'msg' => "Opened $description of length " .
                    filesize($file_path) . " bytes.",
            'obj' => array(
                'data' => $data,
                'bytes_read' => $length_to_read,
                'new_offset' => $fsize
            )
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
    // file doesn't exist
    else {
        $retVal = array(
            'code' => 1,
            'msg' => "Could not find or open $description.",
            'obj' => NULL
        );
        error_log("get_omni_data.php get_omni_invocation_file_raw_contents_offset: " .
            $retVal['msg']);
    }
    
    return $retVal;

}

/*
    Get the PID from the omni invocation
*/
function get_omni_invocation_pid($dir) {
    $retVal = get_omni_invocation_file_raw_contents($dir, "omni-pid", "PID file");
    return $retVal;
}

/*
    Get the command called from the omni invocation
*/
function get_omni_invocation_command($dir, $raw=true) {
    $retVal = get_omni_invocation_file_raw_contents($dir, "omni-command", 
            "command file");
    return $raw ? $retVal : make_pretty_code($retVal);
}

/*
    Get the console messages from the omni invocation
    (This is what the user might see if they were running omni on
    the command line.)
*/
function get_omni_invocation_console_log($dir, $raw=true, $offset=0) {
    $retVal = get_omni_invocation_file_raw_contents_offset($dir, "omni-console", 
            "console log", $offset);
    return $raw ? $retVal : make_pretty_tailed_logs($retVal);
}

/*
    Get the log messages from the omni invocation
    (This is the full log set to the DEBUG level.)
*/
function get_omni_invocation_debug_log($dir, $raw=true, $offset=0) {
    $retVal = get_omni_invocation_file_raw_contents_offset($dir, "omni-log", 
            "debug log", $offset);
    return $raw ? $retVal : make_pretty_tailed_logs($retVal);
}

/*
    Get any error messages (stderr) from the omni invocation
*/
function get_omni_invocation_error_log($dir, $raw=true) {
    $retVal = get_omni_invocation_file_raw_contents($dir, "omni-stderr", 
            "error log");
    return $raw ? $retVal : make_pretty_code($retVal);
}

/*
    Get the data returned from stitcher.call
*/
function get_omni_invocation_stdout($dir, $raw=true) {
    $retVal = get_omni_invocation_file_raw_contents($dir, "omni-stdout", 
            "stdout from stitcher.call");
    return $raw ? $retVal : make_pretty_stdout($retVal);
}

/*
    Get the status of the omni invocation based on its PID
*/
function get_omni_invocation_status($dir, $raw=true) {
    $retVal = get_omni_invocation_pid($dir);
    if($retVal['obj']) {
        $pid = $retVal['obj'];
        $command = 'ps -p ' . $pid;
        exec($command, $output);
        if(isset($output[1])) {
            $retVal['code'] = 0;
            $retVal['msg'] = "Process $pid running.";
            $retVal['obj'] = "run";
        }
        else {
            $retVal['code'] = 0;
            $retVal['msg'] = "Process $pid not running.";
            $retVal['obj'] = "norun";
        }
        return $retVal;
    }
    else {
        return $retVal;
    }

}

/*
    Get the elapsed time since omni was invoked
*/
function get_omni_invocation_elapsed_time($dir, $raw=true) {
    $retVal = get_omni_invocation_pid($dir);
    if($retVal['obj']) {
        $pid = $retVal['obj'];
        $command = 'ps -o etime -p ' . $pid;
        exec($command, $output);
        if(isset($output[1])) {
            $etime_s = parse_etime($output[1]);
            $retVal['code'] = 0;
            $retVal['msg'] = "Process $pid running with elapsed time $etime_s s.";
            if($raw) {
                $retVal['obj'] = $etime_s;
            }
            else {
                $retVal['obj'] = secs_to_h($etime_s);
            }
        }
        else {
            // FIXME: Decide what happens if process not running
            $retVal['code'] = 0;
            $retVal['msg'] = "Process $pid not running, so no elapsed time.";
            $retVal['obj'] = "";
        }
        return $retVal;
    }
    else {
        return $retVal;
    }

}

/*
    Parse etime from calling ps
    Source: http://stackoverflow.com/questions/14652445/parse-ps-etime-output-into-seconds
*/
function parse_etime($s) {
    $m = array();
    //Man page for `ps` says that the format for etime is [[dd-]hh:]mm:ss
    preg_match("/^(([\d]+)-)?(([\d]+):)?([\d]+):([\d]+)$/", trim($s), $m);
    return
        $m[2]*86400+    //Days
        $m[4]*3600+     //Hours
        $m[5]*60+       //Minutes
        $m[6];          //Seconds
}

/*
    Convert seconds to something human readable
    Source: http://csl.name/php-secs-to-human-text/
*/
function secs_to_h($secs)
{
        $units = array(
                "week"   => 7*24*3600,
                "day"    =>   24*3600,
                "hour"   =>      3600,
                "minute" =>        60,
                "second" =>         1,
        );

	// specifically handle zero
        if ( $secs == 0 ) return "0 seconds";

        $s = "";

        foreach ( $units as $name => $divisor ) {
                if ( $quot = intval($secs / $divisor) ) {
                        $s .= "$quot $name";
                        $s .= (abs($quot) > 1 ? "s" : "") . ", ";
                        $secs -= $quot * $divisor;
                }
        }

        return substr($s, 0, -2);
}

/*
    Return pretty print for command that was called
        This should return basic HTML that converts newlines into <br> tags
*/
function make_pretty_code($retVal) {
    if($retVal['obj']) {
        $new = str_replace("\n", "<br>", $retVal['obj']);
        $retVal['obj'] = $new;
        return $retVal;
    }
    else {
        return $retVal;
    }
}

/*
    Return pretty print for tailed logs
*/
function make_pretty_tailed_logs($retVal) {
    if($retVal['obj']['data']) {
        $new = str_replace("\n", "<br>", $retVal['obj']['data']);
        $retVal['obj']['data'] = $new;
        return $retVal;
    }
    else {
        return $retVal;
    }

}

/*
    Return pretty print for omni-stdout data
*/
function make_pretty_stdout($retVal) {

    // check that obj isn't empty
    if($retVal['obj']) {
        // FIXME: Do checks on this to see if this contains real data
        $output2 = json_decode($retVal['obj'], True);
            
        // FIXME: Note that this captures whatever is buffered in the
        //      output, as print_rspec_pretty() uses prints and echoes.
        //      Probably need a more elegant solution going forward.
        ob_start();
        $obj = print_rspec_pretty($output2[1]);
        $retVal['obj'] = ob_get_clean();
        return $retVal;
    }
    // pass back what we originally got
    else {
        return $retVal;
    }

}

?>
