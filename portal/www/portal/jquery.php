<!DOCTYPE html>
<html>
<head>
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
   $("#div1").load("aggregates.php",function(responseTxt,statusTxt,xhr){
   if(statusTxt=="success") 
   {
     var json_agg;
     var name;
     var output; 
     json_agg = JSON.parse(responseTxt);
     output = "<table id='status_table'>";
     for (am_id in json_agg ) {
	    agg = json_agg[am_id];                    
	    name = agg.name;
            output += "<tr id='"+am_id+"'><td>";
	    output += "<button id='hello' type='button' onclick='update_agg_row("+am_id+")'>Reload</button>";
	    output += "</td><td>";	
	    output += slice;
	    output += name;
	    output += "</td><td id='status_"+am_id+"' class='updating'>";	
	    output += "...updating...";
	    output += "</td><td>";	
	    output += agg.url;
	    output += "</td></tr>";
	    update_agg_row( am_id );
     }	
     output += "</table>";
     $("#div1").html(output);
   }
   if(statusTxt=="error")
     alert("Error: "+xhr.status+": "+xhr.statusText);
   });
}


function update_agg_row(am_id) {
  $.getJSON("amstatus.php?am_id="+am_id+"&slice_id="+slice+"",function(responseTxt,statusTxt,xhr){
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
 
function update_agg_row2(am_id) {
  $("#div1").load("amstatus.php?am_id="+am_id+"&slice_id="+slice,function(responseTxt,statusTxt,xhr){
     if(statusTxt=="success") 
     {
        var json_am;
        var am;
        var geni_status;
        var output=""; 
        json_am = JSON.parse(responseTxt);

        for (new_id in json_am ) {	
           am = json_am[new_id];	   
           geni_status = am['geni_status'];
           output += new_id;
    	   output += json_am[new_id];
    	   output += json_am[new_id]['geni_status'];
    	   output += geni_status;
        }
        var output2;
//        output2 = $("#status_"+am_id).html();
        alert("#status_"+am_id);
//        alert( output );
//        $("#status_"+am_id).html( output );


        $("#status_"+am_id).style.color="blue";

     }
     if(statusTxt=="error")
        alert("Error: "+xhr.status+": "+xhr.statusText);
  });  
}
 


$(document).ready(build_agg_table);
/*$("#hello").click( function(){
  var id = $("<tr>").attr(id);
  update_agg_row(id);
});*/
//$("#hello").click( function() {alert(slice);});

</script>
</head>
<body>



<div id="div1"><h2>Let jQuery AJAX Change This Text</h2>

</div>
<p><?php echo $slice_id ?></p>
<!-- <button type='button' onclick="build_agg_table('<?php echo $slice_id ?>')">Change Content</button> -->



</body>
</html>
