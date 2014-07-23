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
//$user = geni_loadUser();

/*
    get_omni_invocation_data.php
    
    Purpose: Handle client-side AJAX calls to get data from omni invocations
    
    Accepts:
        Required:
            invocation_id: unique ID for an omni invocation (e.g. qlj7KS)
            invocation_user: user ID (e.g. bujcich)
            slice_id: slice ID related to invocation
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
            download: 'true' or 'false' (default)
    
    Returns:
        $retVal: JSON-encoded array of up to 6 key/values:
            'code': 0 for success, non-zero number for failure
            'msg': user-friendly message about the result of the action
            'obj': raw or pretty data returned (or NULL if nothing)
            'bytes_read': number of bytes read (set for tailable functions)
            'new_offset': what the next query's offset should be (set for tailable functions)
            'time': local server time when request is returned
        If download flag is set, contents of 'obj' are dumped into a response
        and sent back to the user.
    
*/

/* Handle incoming AJAX calls here */
if(array_key_exists("invocation_id", $_REQUEST) && 
        array_key_exists("invocation_user", $_REQUEST) &&
        array_key_exists("slice_id", $_REQUEST) &&
        array_key_exists("request", $_REQUEST)) {

    $invocation_user = $_REQUEST['invocation_user'];
    $invocation_id = $_REQUEST['invocation_id'];
    $slice_id = $_REQUEST['slice_id'];
    $request = $_REQUEST['request'];
    
    // Do a check to see that user is allowed to lookup slice information
    /*if(!$user->isAllowed(SA_ACTION::LOOKUP_SLICE, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
        $retVal = array(
            'code' => 1,
            'msg' => "Invalid AJAX request (user not allowed to access this slice's information)",
            'obj' => NULL
        );
        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone("America/New_York"));
        $retVal['time'] = $dt->format('r');
        error_log("get_omni_data.php: " . $retVal['msg']);
        header("Content-Type: application/json", true);
        echo json_encode($retVal);
        exit();
    }*/
    
    // set raw to true by default unless it's set to 'false' in AJAX call
    if(array_key_exists("raw", $_REQUEST) && $_REQUEST['raw'] == 'false') {
        $raw = false;
    }
    else {
        $raw = true;
    }
    
    // set download to false by default unless it's set to 'true'
    if(array_key_exists("download", $_REQUEST) && $_REQUEST['download'] == 'true') {
        $download = true;
    }
    else {
        $download = false;
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
        case "start":
            $retVal = get_omni_invocation_start_time($invocation_dir, $raw);
            break;
        case "stop":
            $retVal = get_omni_invocation_stop_time($invocation_dir, $raw);
            break;
        case "requestrspec":
            $retVal = get_omni_invocation_request_rspec($invocation_dir, $raw);
            break;
        case "manifestrspec":
            $retVal = get_omni_invocation_manifest_rspec($invocation_dir, $raw);
            break;
        default:
            $retVal = array(
                'code' => 1,
                'msg' => "Request type '$request' not valid.",
                'obj' => NULL
            );
            error_log("get_omni_data.php: " . $retVal['msg']);
    }
    
    // for downloads, send data to file and exit
    if($download) {
    
        // if client-side specifies filename to use, use it
        if(array_key_exists("filename", $_REQUEST)) {
            $filename = $_REQUEST['filename'];
        }
        else {
            $filename = $request;
        }
        // if XML, set content-type to XML, else plain text
        $filetype = array_pop(explode(".", $filename));
        if($filetype == "xml" || $filetype == "rspec") {
            $contenttype = "text/xml";
        }
        else {
            $contenttype = "text/plain";
        }
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=$filename");
        header("Content-Type: $contenttype");
        print $retVal['obj'];
        exit();
    }
    
}
else {
    $retVal = array(
        'code' => 1,
        'msg' => "Invalid AJAX request (invocation ID, invocation user, slice ID, and request type all required)",
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
//$retVal['time'] = $dt->format('H:i:s T (P \U\T\C)');
$retVal['time'] = $dt->format('r');

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
        //error_log("get_omni_data.php get_omni_invocation_file_raw_contents: " .
        //    $retVal['msg']);
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
                obj: the data from the file itself
                bytes_read: number of bytes that were read from the file based
                    on file size and offset specified
                new_offset: what should be the next offset when another AJAX
                    call is made on the client
        */
        $retVal = array(
            'code' => 0,
            'msg' => "Opened $description of length " .
                    filesize($file_path) . " bytes.",
            'obj' => $data,
            'bytes_read' => $length_to_read,
            'new_offset' => $fsize
        );
    
    }
    
    // file exists but is empty
    else if(is_file($file_path) && filesize($file_path) == 0) {
        $retVal = array(
            'code' => 1,
            'msg' => "Opened $description but file was empty.",
            'obj' => NULL,
            'bytes_read' => NULL,
            'new_offset' => NULL
        );
    }
    // file doesn't exist
    else {
        $retVal = array(
            'code' => 1,
            'msg' => "Could not find or open $description.",
            'obj' => NULL,
            'bytes_read' => NULL,
            'new_offset' => NULL
        );
        //error_log("get_omni_data.php get_omni_invocation_file_raw_contents_offset: " .
        //    $retVal['msg']);
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
    return $raw ? $retVal : make_pretty_code($retVal);
}

/*
    Get the log messages from the omni invocation
    (This is the full log set to the DEBUG level.)
*/
function get_omni_invocation_debug_log($dir, $raw=true, $offset=0) {
    $retVal = get_omni_invocation_file_raw_contents_offset($dir, "omni-log", 
            "debug log", $offset);
    return $raw ? $retVal : make_pretty_code($retVal);
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
    Get the request RSpec from the omni invocation
*/
function get_omni_invocation_request_rspec($dir, $raw=true) {
    $retVal = get_omni_invocation_file_raw_contents($dir, "rspec", 
            "request RSpec");
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
    Get the manifest RSpec
*/
function get_omni_invocation_manifest_rspec($dir, $raw=true) {
    $retVal = get_omni_invocation_file_raw_contents($dir, "omni-stdout", 
            "stdout from stitcher.call (for manifest)");
            
    // get XML from obj
    if($retVal['obj']) {
        // FIXME: Do checks on this to see if this contains real data
        $output2 = json_decode($retVal['obj'], True);
        $retVal['obj'] = $output2[1];
    }
    else {
        $retVal['obj'] = NULL;
    }
    return $raw ? $retVal : make_pretty_code($retVal);
}


/*
    Get the status of the omni invocation based on its PID
    This will be determined by 'ps -p <pid>' and not start/stop files
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
    Get the cumulative time that omni has been (or had been) running
    
    Note that 'ps -o etime -p <pid>' could work, but base this on the start/stop
    files generated by stitcher_php.py instead because those files are
    persistent after the process dies.
*/
function get_omni_invocation_elapsed_time($dir, $raw=true) {

    $retVal = array();

    // get data from start file if it exists
    $retValStart = get_omni_invocation_file_raw_contents($dir, "start", 
            "start time file");
    if($retValStart['code'] == 0) {
        $start_time = $retValStart['obj'];
    }
    // start file doesn't exist or is empty, so pass this back through
    else {
        return $retValStart;
    }

    // get data from stop file if it exists
    $retValStop = get_omni_invocation_file_raw_contents($dir, "stop", 
            "stop time file");
    if($retValStop['code'] == 0) {
        // process is finished, so find the difference between start and stop
        $stop_time = $retValStop['obj'];
        
        // return code of 0 tells client-side JS to stop polling - we want
        // this whether the finished process was a success or failure
        $retVal['code'] = 0;
        
        // check if omni-stderr is empty - if not, then process probably failed
        $retValError = get_omni_invocation_file_raw_contents($dir, "omni-stderr", 
            "error log");
        if($retValError['code'] == 0) {
            $retVal['msg'] = "<b style='color:red;'>Failed</b>";
        }
        else {
            $retVal['msg'] = "<b style='color:green;'>Finished</b>";
        }
        
    }
    else {
        // process isn't finished, so find the difference between start and now
        $stop_time = time();
        $retVal['code'] = 1;
        $retVal['msg'] = "<b style='color:#E17000;'>Running</b>";
    }
    
    $total_time = $stop_time - $start_time;
    if($raw) {
        $retVal['obj'] = $total_time;
    }
    else {
        $retVal['obj'] = secs_to_h($total_time);
    }

    return $retVal;

}

/*
    Get the start time (if exists) from the omni invocation
*/
function get_omni_invocation_start_time($dir, $raw=true) {
    $retVal = get_omni_invocation_file_raw_contents($dir, "start", 
            "start time file");
    return $raw ? $retVal : make_pretty_time($retVal);
}

/*
    Get the stop time (if exists) from the omni invocation
*/
function get_omni_invocation_stop_time($dir, $raw=true) {
    $retVal = get_omni_invocation_file_raw_contents($dir, "stop", 
            "stop time file");
    return $raw ? $retVal : make_pretty_time($retVal);
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
        // change <, >, and " to special characters so they display correctly
        $retVal['obj'] = str_replace("<", "&lt;", $retVal['obj']);
        $retVal['obj'] = str_replace(">", "&gt;", $retVal['obj']);
        $retVal['obj'] = str_replace("\"", "&quot;", $retVal['obj']);
        // change newlines to breaks (do this after the above three!)
        $retVal['obj'] = str_replace("\n", "<br>", $retVal['obj']);
        return $retVal;
    }
    else {
        return $retVal;
    }
}


/*
    Return pretty print for omni-stdout data (i.e. the results)
*/
function make_pretty_stdout($retVal) {

    // check that obj isn't empty
    if($retVal['obj']) {
        // FIXME: Do checks on this to see if this contains real data
        $output2 = json_decode($retVal['obj'], True);
        
        if ( count($output2) == 2 ) {
           $msg = $output2[0];
           $obj = $output2[1];
        } else {
           $msg = $output2;
           $obj = "";
        }
        
        // probably XML, so send to print_rspec_pretty
        // Note: prints/echos are sent to output buffer; this captures that
        // FIXME: Filter to AM
        if ($obj != "" ) {
            ob_start();
            $obj = print_rspec_pretty($output2[1]);
            $retVal['obj'] = ob_get_clean();
        }
        
        // something else, so parse out to determine if it's an error
        else {
            // Note: prints/echos are sent to output buffer; this captures that
            ob_start();
            $new_msg = $msg;
            //   note: preg_match returns 1 if expression is found
            //         and matched string is stored in $string[0]
            // match on omni python traceback error
            print "<p>";
            if(preg_match("/omnilib\.util\.omnierror.*/", $msg, $new_msg)) {
                print '<b>Error:</b> Failed to create a sliver.<br><br>';
                print "<i>";
                print $new_msg[0];
                print "</i>";
            }
            // match on InstaGENI URL
            else if(preg_match("/http[s]?:\/\/[a-zA-Z0-9.\/]*\/spewlogfile[^)]*/", $msg, $error_url)) {
                $new_msg = str_replace("\n", "<br>", $msg);
                print '<b>Error:</b> Failed to create a sliver. Check log file at <a href="' . $error_url[0] . '" target="_blank">' . $error_url[0] . '</a>.<br><br>';
                print "<i>";
                print $new_msg;
                print "</i>";
            }
            // if unknown error, display in its entirety
            else {
                $new_msg = str_replace("\n", "<br>", $msg);
                print '<b>Error:</b> Failed to create a sliver.<br><br>';
                print "<i>";
                print $new_msg;
                print "</i>";
            }
            print "</p>";
            $retVal['obj'] = ob_get_clean();
        }
        
        return $retVal;
    }
    // pass back what we originally got
    else {
        return $retVal;
    }

}


/*
    Return RFC 2822 time given Unix epoch time
*/
function make_pretty_time($retVal) {
    if($retVal['obj']) {
        date_default_timezone_set('America/New_York');
        $retVal['obj'] = date('r', $retVal['obj']);
        return $retVal;
    }
    else {
        return $retVal;
    }
}

?>
