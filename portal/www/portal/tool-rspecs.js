<script>

function showViewerContainer(rspec_id, rspec_name) {
    $.ajax({
        type: "GET",
        url: "rspecview.php?id="+rspec_id,
        dataType: "xml",
        success: function(data) {
            updateJacksContainer(data, rspec_id, rspec_name);
        }
       });
}

function updateJacksContainer(rspec, rspec_id, rspec_name) {
    var rspecText = new XMLSerializer().serializeToString(rspec);
    thisInstance = new window.Jacks({
        mode: 'viewer',
        source: 'rspec',
        size: { x: 750, y: 400},
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
                    callback(true);
                    },
                "Download Raw RSpec": function () {
                    window.location.href = 'rspecdownload.php?id='+rspec_id;
                    callback(true);
                    },
                "Close": function () {
                        $(this).dialog('close');
                        callback(true);
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
