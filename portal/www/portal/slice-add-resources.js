//----------------------------------------------------------------------
// Copyright (c) 2012 Raytheon BBN Technologies
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
    var enable_agg_chooser = selected_element.attributes.getNamedItem('bound').value;
    
    //    console.log("ENABLE  = " + enable_agg_chooser);

    if (enable_agg_chooser == "1") {
	$('#agg_chooser').removeAttr('disabled');
	//	console.log("ENABLING");
    } else {
	$('#agg_chooser').attr('disabled', 'disabled');
	//	console.log("DISABLING");
    }

}
