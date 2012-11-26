function build_agg_table_on_slicepg() 
{
   // (1) query the server for all a list of aggregates
   $.getJSON("aggregates.php",function(responseTxt,statusTxt,xhr){
   if(statusTxt=="success") 
   {
     var json_agg;
     var name;
     var output; 
       var status_url, listres_url, disabled;
       var delete_slivers_privilege, delete_slivers_disabled;
       var renew_slice_privilege, renew_sliver_disabled;
       var slice_status="UNDEFINED", slice_name="UNDEFINED";

     status_url = 'sliverstatus.php?slice_id='+slice;
     listres_url = 'listresources.php?slice_id='+slice;
     // String to disable button or other active element
     disabled = "disabled = " + '"' + "disabled" + '"'; 
// FIX ME     delete_slivers_privilege = $user->isAllowed(SA_ACTION::DELETE_SLIVERS,
//				    CS_CONTEXT_TYPE::SLICE, slice);
     delete_slivers_privilege = true;
     delete_slivers_disabled = "";
     if(!delete_slivers_privilege) { delete_slivers_disabled = disabled; }

// FIX ME     renew_slice_privilege = $user->isAllowed(SA_ACTION::RENEW_SLICE,
//				    CS_CONTEXT_TYPE::SLICE, slice);
     renew_slice_privilege = true;
     renew_sliver_disabled = "";
     if(!renew_slice_privilege) { renew_sliver_disabled = disabled; }
//     slice_expiration_db = $slice[SA_ARGUMENT::EXPIRATION];
//FIX ME     slice_expiration = dateUIFormat($slice_expiration_db);
       slice_expiration = "1234";
     // (2) create an HTML table with one row for each aggregate
     json_agg = responseTxt;
     output = "<table id='status_table'>";
     //  output +=  "<tr><th>Status</th><th colspan='2'>Slice</th><th>Creation</th><th>Expiration</th><th>Actions</th></tr>\n";
     output +=  "<tr><th>Status</th><th colspan='6'>Slice</th></tr>\n";
     /* Slice Info */
     output +=  "<tr>";
     output +=  "<td class='$slice_status'>"+slice_status+"</td>";
     output +=  "<td colspan='5'>"+slice_name+"</td>";
     output +=  "</tr>\n";
     output += "<tr>";
     output += "<th class='notapply'>";
     output += "</th><th>";
     output += "</th><th>Status</th><th>Aggregate</th>";
     //      output += "<th>&nbsp;</th>";
     output += "<th>Expiration</th>";
     output += "<th>Actions</th></tr>\n";
     for (am_id in json_agg ) {
	    agg = json_agg[am_id];                    
	    name = agg.name;
            output += "<tr id='"+am_id+"'>";
	    output += "<td class='notapply'></td>";
	    output += "<td><button id='hello' type='button' onclick='update_agg_row("+am_id+")'>Reload</button>";
	    output += "</td><td id='status_"+am_id+"' class='updating'>";	
	    output += "...updating...";
	    output += "</td><td>";	
	    output += name;
	    output += "</td>";	
	    // sliver expiration
	    if (renew_slice_privilege) {
                output += "<td><form method='GET' action=\"do-renew.php\">";
		output += "<input type=\"hidden\" name=\"slice_id\" value=\""+slice+"\"/>\n";
		output += "<input class='date' type='text' name='sliver_expiration'";
		output += "value=\""+slice_expiration+"\"/>\n";
		output += "<input type='submit' name= 'Renew' value='Renew'/>\n";
		output += "</form></td>\n";
	    } else {
		output += "<td>"+sliver_expiration+"</td>";
	    }
	    // sliver actions
	    output += "<td>";
	    output += "<button onClick=\"window.location='"+status_url+"&am_id="+am_id+"'\"><b>Resource Status</b></button>";
	    output += "<button title='Login info, etc' onClick=\"window.location='"+listres_url+"&am_id="+am_id+"'\"><b>Details</b></button>\n";
	    output += "<button onClick=\"window.location='confirm-sliverdelete.php?slice_id=" + slice+ "&am_id="+am_id+"'\" "+ delete_slivers_disabled +"><b>Delete Resources</b></button>\n";
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
            output += "<tr id='"+am_id+"'>";
	    output += "<td class='notapply'></td>";
	    output += "<td><button id='hello' type='button' onclick='update_agg_row("+am_id+")'>Reload</button>";
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
           output += "<td class='"+geni_status+"'>";
    	   output += geni_status;
           output += "</td>";
        }
        $("td#status_"+am_id).html( output );
//        $("td#status_"+am_id).text( output );
     }
     if(statusTxt=="error")
        alert("Error: "+xhr.status+": "+xhr.statusText);
  });  
}

$(document).ready(build_agg_table_on_slicepg);
