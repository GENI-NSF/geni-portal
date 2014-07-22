<script>

user = "<?php echo $invocation_user; ?>";
id = "<?php echo $invocation_id; ?>";
    
debug_log_offset = 0;
console_log_offset = 0;

$( document ).ready( function() {
    getPID(user, id);
    getCommand(user, id);
    getRequestRSpec(user, id);
    updateConsoleLog(user, id, console_log_offset);
    updateDebugLog(user, id, debug_log_offset);
    updateXMLResults(user, id);
    updateElapsedTime(user, id);
    updateManifestRSpec(user, id);
    get_console = setInterval( "updateConsoleLog(user, id, console_log_offset)", 1000 );
    get_debug = setInterval( "updateDebugLog(user, id, debug_log_offset)", 1000 );
    get_xml = setInterval( "updateXMLResults(user, id)", 1000 );
    get_elapsed = setInterval( "updateElapsedTime(user, id)", 1000 );
    get_manifest_rspec = setInterval( "updateManifestRSpec(user, id)", 1000 );
});

function updateConsoleLog(invocationUser, invocationID, offset) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+'&invocation_id='+invocationID+'&request=console&offset='+offset+'&raw=false',
        function(data) {
            var scrollPositionContainer = $("#console_data_container").scrollTop();
            var dataHeight = $( "#console_data" ).height();
            var containerHeight = $( "#console_data_container" ).height();
            $("#console_bytes_read").html(data.bytes_read);
            $("#console_new_offset").html(data.new_offset);
            $("#console_time").html(data.time);
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

function updateDebugLog(invocationUser, invocationID, offset) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+'&invocation_id='+invocationID+'&request=debug&offset='+offset+'&raw=false',
        function(data) {
            var scrollPositionContainer = $("#debug_data_container").scrollTop();
            var dataHeight = $( "#debug_data" ).height();
            var containerHeight = $( "#debug_data_container" ).height();
            $("#debug_bytes_read").html(data.bytes_read);
            $("#debug_new_offset").html(data.new_offset);
            $("#debug_time").html(data.time);
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

function updateXMLResults(invocationUser, invocationID) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+
    '&invocation_id='+invocationID+'&request=stdout&raw=false',
        function(data) {
            if(data.code == 0) {
                $("#prettyxml").html(data.obj);
                stopPolling();
            }
            $("#results_time").html(data.time);
        });
}

function updateElapsedTime(invocationUser, invocationID) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+
    '&invocation_id='+invocationID+'&request=elapsed&raw=false',
        function(data) {
            if(data.code == 0) {
                $("#pid_elapsed").html(data.obj);
            }
            $("#pid_time").html(data.time);
        });
}

function getPID(invocationUser, invocationID) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+
    '&invocation_id='+invocationID+'&request=pid',
        function(data) {
            if(data.code == 0) {
                $("#pid_pid").html(data.obj);
            }
        });
}

function getCommand(invocationUser, invocationID) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+
    '&invocation_id='+invocationID+'&request=command&raw=false',
        function(data) {
            if(data.code == 0) {
                $("#command_data").html(data.obj);
            }
        });
}

function getRequestRSpec(invocationUser, invocationID) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+
    '&invocation_id='+invocationID+'&request=requestrspec&raw=false',
        function(data) {
            if(data.code == 0) {
                $("#requestrspec_data").html(data.obj);
            }
        });
}

function updateManifestRSpec(invocationUser, invocationID) {
    $.getJSON('get_omni_invocation_data.php?invocation_user='+invocationUser+
    '&invocation_id='+invocationID+'&request=manifestrspec&raw=false',
        function(data) {
            if(data.code == 0) {
                $("#manifestrspec_data").html(data.obj);
            }
        });
}

function stopPolling() {
    clearInterval(get_xml);
    clearInterval(get_debug);
    clearInterval(get_console);
    clearInterval(get_elapsed);
    clearInterval(get_manifest_rspec);
}
    
</script>
