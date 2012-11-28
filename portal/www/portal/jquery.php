<!DOCTYPE html>
<html>
<head>
<!--
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
-->

<?php
require_once("header.php");
require_once("settings.php");
require_once("user.php");
require_once("file_utils.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("am_client.php");
require_once("sa_client.php");
require_once("am_map.php");
require_once("json_util.php");

$user = geni_loadUser();
if (! $user->isActive()) {
  relative_redirect('home.php');
}

function no_slice_error() {
  header('HTTP/1.1 404 Not Found');
  print 'No slice id specified.';
  exit();
}

 if (! count($_GET)) {
  // No parameters. Return an error result?
  // For now, return nothing.
  no_slice_error();
}
unset($slice);
include("tool-lookupids.php");
if (! isset($slice)) {
  no_slice_error();
}
?>


<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
<script>

var slice= "<?php echo $slice_id ?>";
function build_agg_table() 
{
   // (1) query the server for all a list of aggregates
   $.getJSON("aggregates.php",function(responseTxt,statusTxt,xhr){
   if(statusTxt=="success") 
   {
     var json_agg;
     var name;
     var output; 
     // (2) create an HTML table with one row for each aggregate
     json_agg = responseTxt;
     output = "<table id='status_table'>";
     for (am_id in json_agg ) {
	    agg = json_agg[am_id];                    
	    name = agg.name;
            output += "<tr id='"+am_id+"'><td>";
	    output += "<button id='hello' type='button' onclick='update_agg_row("+am_id+")'>Reload</button>";
	    output += "</td><td id='status_"+am_id+"' class='updating'>";	
	    output += "...updating...";
	    output += "</td><td>";	
	    output += name;
//	    output += "</td><td>";	
//	    output += agg.url;
	    output += "</td></tr>";
            // (3) Get the status for this slice at this aggregate
	    update_agg_row( am_id );
     }	
     output += "</table>";
     $("#status_table_div").html(output);
   }
   if(statusTxt=="error")
     alert("Error: "+xhr.status+": "+xhr.statusText);
   });
}


function update_agg_row(am_id) {
  // This queries for the json file at (for example):
  // https://sergyar.gpolab.bbn.com/secure/amstatus.php?am_id=9&slice_id=b18cb314-c4dd-4f28-a6fd-b355190e1b61
  $.getJSON("amstatus.php", { am_id:am_id, slice_id:slice },function(responseTxt,statusTxt,xhr){
     if(statusTxt=="success") 
     {
        var json_am;
        var am;
        var geni_status;
        var output=""; 
        json_am = responseTxt;
        for (new_id in json_am ) {	
           am = json_am[new_id];	   
           geni_status = am['geni_status'];
    	   output += geni_status;
        }
        $("td#status_"+am_id).text( output );
//        $("td#status_"+am_id).style.color="blue";
     }
     if(statusTxt=="error")
        alert("Error: "+xhr.status+": "+xhr.statusText);
  });  
}

$(document).ready(build_agg_table);

</script>
</head>
<body>

<div id="div1"><h2>Sample Slice Status Table</h2>
<div id="status_table_div"/>
</div>

</body>
</html>
