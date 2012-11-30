var updating_text = "...updating...";

function build_agg_table_on_slicepg() 
{
   // (1) query the server for all a list of aggregates
   $.getJSON("aggregates.php",function(responseTxt,statusTxt,xhr){
   if(statusTxt=="success") 
   {
     var json_agg;
     var name;
     var output; 
     var status_url, listres_url;

     status_url = 'sliverstatus.php?slice_id='+slice;
     listres_url = 'listresources.php?slice_id='+slice;

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
	    output += "<td><button id='reload_button_'"+am_id+" type='button' onclick='refresh_agg_row("+am_id+")'>Reload</button>";
	    output += "</td><td id='status_"+am_id+"' class='updating'>";	
	    output += updating_text;
	    output += "</td><td>";	
	    output += name;
	    output += "</td>";	
	    // sliver expiration
	    if (renew_slice_privilege) {
                output += "<td><form  method='GET' action=\"do-renew.php\">";
		output += "<input type=\"hidden\" name=\"slice_id\" value=\""+slice+"\"/>\n";
		output += "<input id='renew_field_"+am_id+"' disabled='' class='date' type='text' name='slice_expiration'";
		output += "value=\""+slice_expiration+"\"/>\n";
		output += "<input id='renew_button_"+am_id+"' disabled='' type='submit' name= 'Renew' value='Renew'/>\n";
		output += "</form></td>\n";
	    } else {
		output += "<td>"+sliver_expiration+"</td>"; 
	    }
	    // sliver actions
	    output += "<td>";
	    output += "<button id='status_button_"+am_id+"' disabled='' onClick=\"window.location='"+status_url+"&am_id="+am_id+"'\"><b>Resource Status</b></button>";
	    output += "<button  id='details_button_"+am_id+"' disabled='' title='Login info, etc' onClick=\"window.location='"+listres_url+"&am_id="+am_id+"'\"><b>Details</b></button>\n";
	    output += "<button  id='delete_button_"+am_id+"' disabled='' onClick=\"window.location='confirm-sliverdelete.php?slice_id=" + slice+ "&am_id="+am_id+"'\" "+ delete_slivers_disabled +"><b>Delete Resources</b></button>\n";
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
	    output += "<td><button id='button_'"+am_id+" type='button' onclick='update_agg_row("+am_id+")'>Reload</button>";
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


function refresh_agg_row(am_id) {
 
    geni_status = "updating"
    $("td#status_"+am_id).text( updating_text);
    $("td#status_"+am_id).attr( "class", geni_status );
    update_agg_row(am_id);
}



function update_agg_row(am_id) {
  // This queries for the json file at (for example):
  // https://sergyar.gpolab.bbn.com/secure/amstatus.php?am_id=9&slice_id=b18cb314-c4dd-4f28-a6fd-b355190e1b61
  $("button#reload_button_"+am_id).prop( "disabled", true ); 
  $.getJSON("amstatus.php", { am_id:am_id, slice_id:slice },function(responseTxt,statusTxt,xhr){
     if(statusTxt=="success") 
     {
        var json_am;
        var am;
        var geni_status;
        var output=""; 
        json_am = responseTxt;
        am = json_am[am_id];	   
        geni_status = am['geni_status'];
        status_code = am['status_code'];
    	output += geni_status;
        $("td#status_"+am_id).text( output );
        $("td#status_"+am_id).attr( "class", GENI_CLASSES[ status_code ] );

	if (status_code == GENI_NO_RESOURCES){
	    $("button#status_button_"+am_id).prop( "disabled", true ); 
	    $("button#details_button_"+am_id).prop( "disabled", true ); 
	    $("button#delete_button_"+am_id).prop( "disabled", true ); 
	    $("input#renew_button_"+am_id).prop( "disabled", true ); 
	    $("input#renew_field_"+am_id).prop( "disabled", true ); 
	} else {
	    $("button#status_button_"+am_id).removeProp( "disabled"); 
	    $("button#details_button_"+am_id).removeProp( "disabled"); 
	    $("button#delete_button_"+am_id).removeProp( "disabled");
	    $("input#renew_button_"+am_id).removeProp( "disabled");
	    $("input#renew_field_"+am_id).removeProp( "disabled");
	}
     }
     if(statusTxt=="error")
        alert("Error: "+xhr.status+": "+xhr.statusText);
  });  
}


function build_agg_table_on_sliverstatuspg() 
{
   // (1) query the server for all a list of aggregates
   $.getJSON("aggregates.php",function(responseTxt,statusTxt,xhr){
     var json_agg;
     json_agg = responseTxt;
     for (tmp_am_id in json_agg ) {
       add_agg_row_on_sliverstatuspg(tmp_am_id);
     }
//FIXME When done hide the querying message       $("div#header").hide;
   });
}



function add_agg_row_on_sliverstatuspg(am_id) {
  // This queries for the json file at (for example):
  // https://sergyar.gpolab.bbn.com/secure/amstatus.php?am_id=9&slice_id=b18cb314-c4dd-4f28-a6fd-b355190e1b61
  $.getJSON("amstatus.php", { am_id:am_id, slice_id:slice },function(responseTxt,statusTxt,xhr){
      var json_am, am;
      var geni_urn, geni_status, agg_name, geni_resources, colspan;
      var resource, firstrow, num_rsc, rsc_urn, rsc_status, rsc_error;
      var output=""; 
      
     if(statusTxt=="success") 
     {
         json_am = responseTxt;
         am = json_am[am_id];	   
         geni_urn = am['geni_status'];
         geni_status = am['geni_status'];
         status_code = am['status_code'];
	 agg_name= am['am_name'];
	 geni_resources = am['resources'];

	 if ((status_code != GENI_NO_RESOURCES) && (status_code != GENI_BUSY)){
	     output += "<tr class='aggregate'><th>Status</th><th colspan='2'>Aggregate</th></tr>";
	     output += "<tr class='aggregate'><td class='"+geni_status+"'>"+geni_status+"</td>";
	     output += "<td colspan='2'>"+agg_name+"</td></tr>";
	     firstrow = true;
	     num_rsc = geni_resources.length;
	     $.each(geni_resources, function(item, val){
		 $.each(geni_resources, function(item, resource){
		     rsc_urn = resource['geni_urn'];
		     rsc_status = resource['geni_status'];
		     rsc_error = resource['geni_error'];
		     if (firstrow) {
			 firstrow = false;		 
			 // put headers on the first row
			 colspan = "colspan='"+num_rsc+"'";
			 output +=  "<tr class='resource'><th class='notapply'></th><th>Status</th><th>Resource</th></tr>";
			 output +=  "<tr  class='resource'>";
			 output +=  "<td rowspan="+num_rsc+" class='notapply'/>";
		     } else {
			 colspan = "";
			 output +=  "<tr  class='resource'>";
		     }
		     output +=  "<td class='"+rsc_status+"'>"+rsc_status+"</td><td>"+resource.geni_error+rsc_urn+"</td></tr>";
		     if (rsc_status == "failed"){
			 output +=  "<tr><td></td><td>"+rsc_error+"</td></tr>";
		     }
		 });
	     });
             $("table#sliverstatus").append( output );
	     output = ""
	 } else {
	     am_error = am['geni_error'];
	     /* output += "<div>Returned status of slivers on ".$n." of ".$m." aggregates.</div>"; */
             if ( $("table#slivererror").children().length == 0 ) {
		 $("table#slivererror").before( "<div>Received errors from the following aggregates:</div>" );
		 output += "<tr><th>Aggregate</th><th>Message</th></tr>";
	     }
	     output += "<tr>";
	     output += "<td>"+agg_name+"</td>";
	     output += "<td>"+am_error+"</td>";
	     output += "</tr>";
	     output += "</table>";
	     
             $("table#slivererror").append( output );

/*	 output = ""
	 m = 
	 n = $("table.sliverstatus").length;
	 num_errs = $("table.slivererror").length;
	 if ($n === 0) {
*/	     /* No aggregates responded succesfully */
/*	     $hdr = "Checked $m aggregate" . ($m > 1 ? "s" : "") . ", no resources found:";
	 } else {
	     $hdr = "Checked $num_errs other aggregate" . ($num_errs > 1 ? "s" : "") . ":";
	 }
         $("div#slivererror").text( output );
*/
	 }
     }
     if(statusTxt=="error")
        alert("Error: "+xhr.status+": "+xhr.statusText);
  });  
}



