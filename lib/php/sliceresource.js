<script>
//----------------------------------------------------------------------
// Copyright (c) 2012-2015 Raytheon BBN Technologies
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

user = "<?php echo $invocation_user; ?>";
id = "<?php echo $invocation_id; ?>";
slice_id = "<?php echo $slice_id; ?>";
am_id = "<?php echo $am_id; ?>";
    
debug_log_offset = 0;
console_log_offset = 0;

start_time = "";
stop_time = "";

$( document ).ready( function() {
    getCommand(user, id, slice_id);
    getRequestRSpec(user, id, slice_id);
    getStartTime(user, id, slice_id);
    get_console = setInterval( "updateConsoleLog(user, id, slice_id, console_log_offset)", 5000 );
    get_debug = setInterval( "updateDebugLog(user, id, slice_id, debug_log_offset)", 5000 );
    get_elapsed = setInterval( "updateElapsedTime(user, id, slice_id, am_id)", 1000 );
});

function updateConsoleLog(invocationUser, invocationID, sliceID, offset) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+'&invocation_id='+invocationID+'&slice_id='+sliceID+'&request=console&offset='+offset+'&raw=false',
        function(data) {
            var scrollPositionContainer = $("#console_data_container").scrollTop();
            var dataHeight = $( "#console_data" ).height();
            var containerHeight = $( "#console_data_container" ).height();
            // Tail bottom if near the bottom
            if(((scrollPositionContainer + 50) > (dataHeight - containerHeight)) ) {
                $("#console_data").append(data.obj);
                $("#console_data_container").scrollTop($("#console_data").height());
            }
            else {
                $("#console_data").append(data.obj);
            }
            console_log_offset = data.new_offset;
        });
}

function updateDebugLog(invocationUser, invocationID, sliceID, offset) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+'&invocation_id='+invocationID+'&slice_id='+sliceID+'&request=debug&offset='+offset+'&raw=false',
        function(data) {
            var scrollPositionContainer = $("#debug_data_container").scrollTop();
            var dataHeight = $( "#debug_data" ).height();
            var containerHeight = $( "#debug_data_container" ).height();
            debug_log_offset = data.new_offset;
            // Tail bottom if near the bottom
            if(((scrollPositionContainer + 50) > (dataHeight - containerHeight)) ) {
                $("#debug_data").append(data.obj);
                $("#debug_data_container").scrollTop($("#debug_data").height());
            }
            else {
                $("#debug_data").append(data.obj);
            }
        });
}

function getXMLResults(invocationUser, invocationID, sliceID, amID) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+
    '&invocation_id='+invocationID+'&slice_id='+sliceID+'&am_id='+amID+'&request=filteredmanifestrspec&raw=false',
        function(data) {
            if(data.code == 0) {
                $("#prettyxml").html(data.obj);
		// assuming XML results, update Jacks container
                updateJacks(invocationUser, invocationID, sliceID);
            }
            $("#results_time").html(data.time);
        });
}

function updateJacks(invocationUser, invocationID, sliceID) {
    // send Jacks the raw manifest RSpec
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+
    '&invocation_id='+invocationID+'&slice_id='+sliceID+'&request=filteredmanifestrspec',
        function(data) {
            if(data.code == 0 && data.obj) {
                thisInstance = new window.Jacks({
                  mode: 'viewer',
                  source: 'rspec',
                  size: { x: 756, y: 400},
                  show: {
                      menu: true,
                      rspec: true,
                      version: true
                  },
                  nodeSelect: true,
                  multiSite: true,
                  root: '#jacksContainer',
                  readyCallback: function (input, output) {
                    input.trigger('change-topology',
                                  [{ rspec: cleanSiteIDsInOutputRSpec(data.obj,null) }]);
                  }
                });
                $("jacksContainer").css("display", "block");
            }
        });

}

function updateElapsedTime(invocationUser, invocationID, sliceID, amID) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+
    '&invocation_id='+invocationID+'&slice_id='+sliceID+'&request=elapsed&raw=false',
        function(data) {
            if(data.code == 0) {
                // essentially, we're done at this point
                // stop polling when we know that stitcher_php.py has written
                // both the start and stop files - nothing else will get updated
                // beyond this point
                stopPolling();
                // get the manifest RSpec, pretty XML, stop time, and error log
                // if exists
                getStopTime(invocationUser, invocationID, sliceID);
                getErrorLog(invocationUser, invocationID, sliceID);
                getManifestRSpec(invocationUser, invocationID, sliceID); 
                getXMLResults(invocationUser, invocationID, sliceID, amID); 
            }
            else {
                // since not finished, update the 'Last updated:' time
                $("#last_updated_or_finished_time").html(data.time);
            }
            $("#total_run_time").html(data.obj);
            $("#total_run_time_status").html(data.msg);
        });
}

function getStopTime(invocationUser, invocationID, sliceID) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+
    '&invocation_id='+invocationID+'&slice_id='+sliceID+'&request=stop&raw=false',
        function(data) {
            if(data.code == 0) {
                stop_time = data.obj;
                $("#last_updated_or_finished_time").html(data.obj);
            }
        });
}

function getErrorLog(invocationUser, invocationID, sliceID) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+
    '&invocation_id='+invocationID+'&slice_id='+sliceID+'&request=error&raw=false',
        function(data) {
            if(data.code == 0) {
                // some error was detected
                // set 'Last updated:' to 'Failed at:'
                $("#last_updated_or_finished_text").html("Failed at:");
                // update 'Results' section to reflect this
                $("#prettyxml").html("<p><b>Error:</b> Failed to create a sliver.<br><br><i>"+data.msg+"</i></p>");
                // update 'Advanced' tab with results of omni-stderr
                $("#error_data").html(data.obj);
                // allow for error log to be downloaded
                $("#download_error").removeAttr('disabled');
            }
            else {
                // no error was detected
                // set 'Last updated:' to 'Finished at:'
                $("#last_updated_or_finished_text").html("Finished at:");
            }
        });
}

function getStartTime(invocationUser, invocationID, sliceID) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+
    '&invocation_id='+invocationID+'&slice_id='+sliceID+'&request=start&raw=false',
        function(data) {
            if(data.code == 0) {
                $("#start_time").html(data.obj);
                // update global variable start_time
                start_time = data.obj;
            }
        });
}

function getCommand(invocationUser, invocationID, sliceID) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+
    '&invocation_id='+invocationID+'&slice_id='+sliceID+'&request=command&raw=false',
        function(data) {
            if(data.code == 0) {
                $("#command_data").html(data.obj);
            }
        });
}

function getRequestRSpec(invocationUser, invocationID, sliceID) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+
    '&invocation_id='+invocationID+'&slice_id='+sliceID+'&request=requestrspec&raw=false',
        function(data) {
            if(data.code == 0) {
                $("#requestrspec_data").html(data.obj);
            }
        });
}

function getManifestRSpec(invocationUser, invocationID, sliceID) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+
    '&invocation_id='+invocationID+'&slice_id='+sliceID+'&request=manifestrspec&raw=false',
        function(data) {
            if((data.code == 0) && data.obj) {
                $("#manifestrspec_data").html(data.obj);
                // allow manifest to be downloaded
                $("#download_manifestrspec").removeAttr('disabled');
                // display note about 'Results current as of...'
                $("#results_stop_msg").html("<p><i>Note that the results are current as of the finish time. Your resource allocation may have changed after this time if resources expired or were deleted. Check the <a target='_blank' href='slice.php?slice_id="+slice_id+"'>slice page</a> for the most up-to-date results about your slice's current allocated resources.</i></p>");
                // display link to get raw manifest RSpec
                $("#results_manifest_link").html("<p><a href='#tab_manifest_rspec'>Show Raw XML Resource Specification (Manifest)</a></p>");
            }
        });
}

function stopPolling() {
    clearInterval(get_debug);
    clearInterval(get_console);
    clearInterval(get_elapsed);
    // get log data one last time to make sure that no data was missed
    updateConsoleLog(user, id, slice_id, console_log_offset);
    updateDebugLog(user, id, slice_id, debug_log_offset);
}
    
</script>
