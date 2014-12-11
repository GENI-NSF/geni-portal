<script>

    function showEditorContainer(rspec_id, rspec_name) {
    $.ajax({
        type: "GET",
        url: "rspecview.php?id="+rspec_id+"&strip_comments=true",
        dataType: "xml",
        success: function(data) {
		updateJacksEditorContainer(data, rspec_id, rspec_name, allAms);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            //console.log("status on rspecview: " + textStatus);
            //console.log("error on rspecview: " + errorThrown);
            alert(errorThrown);
        }
       });
}

function updateJacksEditorContainer(rspec, rspec_id, rspec_name, allAms) {
    var rspecText = new XMLSerializer().serializeToString(rspec);
    var jacksInput = null;
    var jacksOutput = null;
    
    // make window size at least 700 x 400
    var width = Math.max(700, Math.floor(window.innerWidth * 0.5));
    var height = Math.max(400, Math.floor(window.innerHeight * 0.5));

    var sortedAms = [];
    for(var am_id in allAms) {
	//	console.log("AM_ID = " + am_id + " " + allAms[am_id]['name']);
	var entry = {
	    am_id : am_id, 
	    name: allAms[am_id]['name'],
	    url : allAms[am_id]['url'],
	    id : allAms[am_id]['urn']
	};
	sortedAms.push(entry);
    }

    // sort list
    sortedAms.sort(function(am1, am2) {
	    if (am1.name < am2.name)
		return -1;
	    else
		return 1;
	});
    
    thisInstance = new window.Jacks({
        mode: 'editor',
        source: 'rspec',
        size: { x: width, y: height},
	canvasOptions : {aggregates: sortedAms},
        show: {
	    menu: true,
            rspec: true,
            version: false
        },
	multiSite: true, 
        nodeSelect: true,
        root: '#jacksEditorContainer',
        readyCallback:
            function (input, output) {
		jacksInput = input;
		jacksOutput = output;
		output.on('fetch-topology', function(rspecs) {
			var rspec = rspecs[0].rspec;
			args = {rspec_id : rspec_id,
				rspec : rspec};
			$.post("rspecupdate.php", args);
		    });
                input.trigger('change-topology',
                    [{ rspec: rspecText }]);
            }
        });


        $("#jacksEditorContainer").dialog({

            resizable: false,
            modal: true,
            title: "Editing RSpec: "+rspec_name,
            height: "auto",
            width: "auto",
            closeOnEscape: true,
            draggable: false,
            /* click outside of the dialog box to close it */
            open: function(event, ui) {
                $('.ui-widget-overlay').bind('click', function () { $(this).siblings('.ui-dialog').find('.ui-dialog-content').dialog('close'); });
            },
            
            buttons: {
                "\u21E1 Save": function () {
		    jacksInput.trigger('fetch-topology');
                    },
                "\u2715 Close": function () {
                        $(this).dialog('close');
                    }
                }
                            
        });
                        

}

                    

function showViewerContainer(rspec_id, rspec_name) {
    $.ajax({
        type: "GET",
        url: "rspecview.php?id="+rspec_id+"&strip_comments=true",
        dataType: "xml",
        success: function(data) {
            updateJacksContainer(data, rspec_id, rspec_name);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            //console.log("status on rspecview: " + textStatus);
            //console.log("error on rspecview: " + errorThrown);
            alert(errorThrown);
        }
       });
}

function updateJacksContainer(rspec, rspec_id, rspec_name) {
    var rspecText = new XMLSerializer().serializeToString(rspec);
    
    // make window size at least 700 x 400
    var width = Math.max(700, Math.floor(window.innerWidth * 0.5));
    var height = Math.max(400, Math.floor(window.innerHeight * 0.5));
    
    thisInstance = new window.Jacks({
        mode: 'viewer',
        source: 'rspec',
        size: { x: width, y: height},
        show: {
            rspec: false,
            version: false
        },
        nodeSelect: false,
        root: '#jacksContainer',
        readyCallback:
            function (input, output) {
                input.trigger('change-topology',
                    [{ rspec: rspecText }]);
            }
        });


        $("#jacksContainer").dialog({

            resizable: false,
            modal: true,
            title: "Viewing RSpec: "+rspec_name,
            height: "auto",
            width: "auto",
            closeOnEscape: true,
            draggable: false,
            /* click outside of the dialog box to close it */
            open: function(event, ui) {
                $('.ui-widget-overlay').bind('click', function () { $(this).siblings('.ui-dialog').find('.ui-dialog-content').dialog('close'); });
            },
            
            buttons: {
                "View Raw RSpec": function () {
                    window.location.href = 'rspecview.php?id='+rspec_id;
                    },
                "\u21E3 Download Raw RSpec": function () {
                    window.location.href = 'rspecdownload.php?id='+rspec_id;
                    },
                "\u2715 Close": function () {
                        $(this).dialog('close');
                    }
                }
                            
        });
                        

}

</script>
