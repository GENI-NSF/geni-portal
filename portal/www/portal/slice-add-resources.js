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

client = new XMLHttpRequest();

/* do things when RSpec is uploaded by user (i.e. not chosen from list) */
function fileupload_onchange()
{
    user_rspec_file_input = document.getElementById("rspec_selection");
    user_rspec_file = user_rspec_file_input.files[0];
    formData = new FormData();
    formData.append("user_rspec", user_rspec_file);
    client.open("post", "rspecuploadparser.php", true);
    client.send(formData);
}

/* once uploaded, change info */
client.onreadystatechange = function() 
   {
      if (client.readyState == 4 && client.status == 200) 
      {
      
        // parse JSON message
        jsonResponse = JSON.parse(client.responseText);
      
        // display message
        $("#upload_message").html(jsonResponse.message);
        
        // if valid, change around attributes depending on stitch/bound
        if(jsonResponse.valid) {
            if(jsonResponse.stitch) {
                set_attributes_for_stitching();
            }
            else if(jsonResponse.bound) {
                set_attributes_for_bound();
            }
            else {
                set_attributes_for_unbound();
            }
        }
        // if invalid, set back to unbound
        else {
            set_attributes_for_unbound();
        }

      }
   }

/* Functions to do things when stitching/bound RSpecs are selected/deselected */

/* do things when stitchable (and therefore bound) Rspec */
function set_attributes_for_stitching()
{
    // disable AMs
    $('#agg_chooser').val('stitch');
    $('#agg_chooser').attr('disabled', 'disabled');
    $('#aggregate_message').html("You selected a <b>stitchable</b> RSpec, so aggregates will be specified from the RSpec.");
    $('#bound_rspec').val('1');
    $('#stitchable_rspec').val('1');
}

/* do things when bound but not stitchable RSpec */
function set_attributes_for_bound()
{
    // FIXME: Bound RSpecs should disable AM selection - uncomment when ready
    //$('#agg_chooser').val('bound');
    //$('#agg_chooser').attr('disabled', 'disabled');
    // FIXME: Comment these 2 lines out when the above 2 lines are uncommented
    $('#agg_chooser').val(am_on_page_load);
    $('#agg_chooser').removeAttr('disabled');
    $('#aggregate_message').html("You selected a <b>bound</b> RSpec.");
    $('#bound_rspec').val('1');
    $('#stitchable_rspec').val('0');
}

/* do things when unbound RSpec */
function set_attributes_for_unbound()
{
    $('#agg_chooser').val(am_on_page_load);
    $('#agg_chooser').removeAttr('disabled');
    $('#aggregate_message').html("");
    $('#bound_rspec').val('0');
    $('#stitchable_rspec').val('0');
}

/* do things when RSpec is chosen from list (i.e. not uploaded) */
function rspec_onchange()
{
    var rspec_opt = $('#rspec_select').val();

    //    console.log("IN RSPEC_ON_CHANGE");
    //    if (rspec_opt == 'upload') {
    //        $('#paste_rspec').hide(500);
    ///        $('#upload_rspec').show(500);
    //    } else if (rspec_opt == 'paste') {
    //        $('#paste_rspec').show(500);
    //        $('#upload_rspec').hide(500);
    //    } else {
    //        $('#paste_rspec').hide(500);
    //        $('#upload_rspec').hide(500);
    //    }

    var agg_chooser = $('#agg_chooser');
    var rspec_chooser = $('#rspec_select');

    var selected_index = document.getElementById('rspec_select').selectedIndex;
    var selected_element = rspec_chooser.children()[selected_index];
    var is_bound = selected_element.attributes.getNamedItem('bound').value;
    var is_stitchable = selected_element.attributes.getNamedItem('stitch').value;
    
    if (is_stitchable == "1") {
        set_attributes_for_stitching();
    }
    else if(is_bound == "1") {
        set_attributes_for_bound();
    }
    else {
        set_attributes_for_unbound();
    }

    // Clear the "rspec_selection" file chooser
    var rspec_file_chooser = $('#rspec_selection');
    $('#rspec_selection').val('');
    $("#upload_message").html('');
    //    console.log("CLEARING = " + rspec_file_chooser);

}
