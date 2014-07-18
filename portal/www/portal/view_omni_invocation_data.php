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

require_once("user.php");
require_once("header.php");
require_once('util.php');
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
skip_km_authorization();
show_header('GENI Portal: Slices',  $TAB_SLICES);
include("tool-breadcrumbs.php");
$hostname = $_SERVER['SERVER_NAME'];

if(array_key_exists("invocation_id", $_REQUEST) && 
        array_key_exists("invocation_user", $_REQUEST)) {
 
    $invocation_user = $_REQUEST['invocation_user'];
    $invocation_id = $_REQUEST['invocation_id'];

}
else {
    echo "<p>Could not show data</p>";
}


?>

<!-- JS functions for tailing -->
<script>

user = "<?php echo $invocation_user; ?>";
id = "<?php echo $invocation_id; ?>";
    
debug_log_offset = 0;
console_log_offset = 0;

$( document ).ready( function() {
    getPID(user, id);
    updateConsoleLog(user, id, console_log_offset);
    updateDebugLog(user, id, debug_log_offset);
    updateXMLResults(user, id);
    updateElapsedTime(user, id);
    get_console = setInterval( "updateConsoleLog(user, id, console_log_offset)", 1000 );
    get_debug = setInterval( "updateDebugLog(user, id, debug_log_offset)", 1000 );
    get_xml = setInterval( "updateXMLResults(user, id)", 1000 );
    get_elapsed = setInterval( "updateElapsedTime(user, id)", 1000 );
});

function updateConsoleLog(invocationUser, invocationID, offset) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+'&invocation_id='+invocationID+'&request=console&offset='+offset+'&raw=false',
        function(data) {
            var scrollPositionContainer = $("#console_data_container").scrollTop();
            var dataHeight = $( "#console_data" ).height();
            var containerHeight = $( "#console_data_container" ).height();
            $("#console_bytes_read").html(data.obj.bytes_read);
            $("#console_new_offset").html(data.obj.new_offset);
            $("#console_time").html(data.time);
            // Tail bottom if near the bottom
            if(((scrollPositionContainer + 50) > (dataHeight - containerHeight)) ) {
                $("#console_data").append(data.obj.data);
                $("#console_data_container").scrollTop($("#console_data").height());
            }
            else {
                $("#console_data").append(data.obj.data);
            }
            console_log_offset = data.obj.new_offset;
        });
}

function updateDebugLog(invocationUser, invocationID, offset) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+'&invocation_id='+invocationID+'&request=debug&offset='+offset+'&raw=false',
        function(data) {
            var scrollPositionContainer = $("#debug_data_container").scrollTop();
            var dataHeight = $( "#debug_data" ).height();
            var containerHeight = $( "#debug_data_container" ).height();
            $("#debug_bytes_read").html(data.obj.bytes_read);
            $("#debug_new_offset").html(data.obj.new_offset);
            $("#debug_time").html(data.time);
            debug_log_offset = data.obj.new_offset;
            // Tail bottom if near the bottom
            if(((scrollPositionContainer + 50) > (dataHeight - containerHeight)) ) {
                $("#debug_data").append(data.obj.data);
                $("#debug_data_container").scrollTop($("#debug_data").height());
            }
            else {
                $("#debug_data").append(data.obj.data);
            }
        });
}

function updateXMLResults(invocationUser, invocationID) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+'&invocation_id='+invocationID+'&request=stdout&raw=false',
        function(data) {
            if(data.code == 0) {
                $("#prettyxml").html(data.obj);
                stopPolling();
            }
            $("#results_time").html(data.time);
        });
}

function updateElapsedTime(invocationUser, invocationID) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+'&invocation_id='+invocationID+'&request=elapsed&raw=false',
        function(data) {
            if(data.code == 0) {
                $("#pid_elapsed").html(data.obj);
            }
            $("#pid_time").html(data.time);
        });
}

function getPID(invocationUser, invocationID) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+'&invocation_id='+invocationID+'&request=pid',
        function(data) {
            if(data.code == 0) {
                $("#pid_pid").html(data.obj);
            }
        });
}

function stopPolling() {
    clearInterval(get_xml);
    clearInterval(get_debug);
    clearInterval(get_console);
    clearInterval(get_elapsed);
}
    
</script>

<h1>View <tt>omni</tt> Invocation Data</h1>

<h2>Console Log</h2>
<pre id='console_data_container' style="height:300px;">
<div id='console_data'></div>
</pre>

<h2>Debug Log</h2>
<pre id='debug_data_container' style="height:300px;">
<div id='debug_data'></div>
</pre>

<h2>Results</h2>
<div class='resources' id='prettyxml'>
</div>

<h2>Statistics</h2>
<h3>Console Log</h3>
<p>Bytes read: <b><span id='console_bytes_read'></span> bytes</b>, New offset: <b><span id='console_new_offset'></span> bytes</b>, Last read: <b><span id='console_time'></span></b></p>
<h3>Debug Log</h3>
<p>Bytes read: <b><span id='debug_bytes_read'></span> bytes</b>, New offset: <b><span id='debug_new_offset'></span> bytes</b>, Last read: <b><span id='debug_time'></span></b></p>
<h3>Results</h3>
<p>Last read: <b><span id='results_time'></span></b></p>
<h3>Process Information</h3>
<p>PID: <b><span id='pid_pid'></span></b>, Elapsed time: <b><span id='pid_elapsed'></span></b>, Last read: <b><span id='pid_time'></span></b></p>
<?php


include("footer.php");
?>
