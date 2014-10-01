<script>

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

                    
                    // display Jacks window in dialog
                        /*$("#showViewer").dialog({
                            resizable: false,
                            modal: true,
                            title: "Modal",
                            height: 400,
                            width: 756,
                            buttons: {
                                "Yes": function () {
                                    $(this).dialog('close');
                                    callback(true);
                                },
                                    "No": function () {
                                    $(this).dialog('close');
                                    callback(false);
                                }
                            }
                        });*/
                    



</script>
