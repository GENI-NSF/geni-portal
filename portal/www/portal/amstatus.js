var updating_text = "Updating status...";
var UNKNOWN = 'unknown';
var NOT_APPLICABLE = "not applicable";
var NOT_RETRIEVED = "not retrieved";
var count = 0;

// function build_agg_table_on_slicepg() 
// {
//      var json_agg;
//      var name;
//      var output; 
//      var status_url, listres_url;

//      var s = all_ams;
//      var all_am_obj = JSON.parse(s);

//      status_url = 'sliverstatus.php?slice_id='+slice;
//      listres_url = 'listresources.php?slice_id='+slice;

//      // (2) create an HTML table with one row for each aggregate
//      json_agg = all_am_obj;
//      output = "<table id='status_table'>";
//      //  output +=  "<tr><th>Status</th><th colspan='2'>Slice</th><th>Creation</th><th>Expiration</th><th>Actions</th></tr>\n";
//      output +=  "<tr><th>Status</th><th colspan='6'>Slice</th></tr>\n";
//      /* Slice Info */
//      output +=  "<tr>";
//      output +=  "<td class='$slice_status'>"+slice_status+"</td>";
//      output +=  "<td colspan='5'>"+slice_name+"</td>";
//      output +=  "</tr>\n";
//      output += "<tr>";
//      output += "<th class='notapply'>";
//      output += "</th><th>";
//      output += "<button id='reload_all_button' type='button' onclick='refresh_all_agg_rows()'>Get All Status</button>";
//      output += "</th><th>Status</th><th>Aggregate</th>";
//      //      output += "<th>&nbsp;</th>";
//      output += "<th>Renew</th>";
//      output += "<th>Actions</th></tr>\n";
//      for (am_id in json_agg ) {
// 	    agg = json_agg[am_id];                    
// 	    name = agg.name;
//             output += "<tr id='"+am_id+"'>";
// 	    output += "<td class='notapply'></td>";
// 	    output += "<td><button id='reload_button_'"+am_id+" type='button' onclick='refresh_agg_row("+am_id+")'>Get Status</button>";
// 	    output += "</td><td id='status_"+am_id+"' class='notqueried'>";	
// 	    output += initial_text;
// 	    output += "</td><td>";	
// 	    output += name;
// 	    output += "</td>";	
// 	    // sliver expiration
// 	    if (renew_slice_privilege) {
//                 output += "<td><form  method='GET' action=\"do-renew.php\">";
// 		output += "<input type=\"hidden\" name=\"slice_id\" value=\""+slice+"\"/>\n";
// 		output += "<input type=\"hidden\" name=\"am_id\" value=\""+am_id+"\"/>\n";
// 		output += "<input id='renew_field_"+am_id+"' disabled='' class='date' type='text' name='sliver_expiration'";
// 		output += "value=\""+slice_expiration+"\"/>\n";
// 		output += "<input id='renew_button_"+am_id+"' disabled='' type='submit' name= 'Renew' value='Renew'/>\n";
// 		output += "</form></td>\n";
// 	    } else {
// 		output += "<td>"+sliver_expiration+"</td>"; 
// 	    }
// 	    // sliver actions
// 	    output += "<td>";
// 	    output += "<button id='status_button_"+am_id+"' disabled='' onClick=\"window.location='"+status_url+"&am_id="+am_id+"'\"><b>Resource Status</b></button>";
// 	    output += "<button  id='details_button_"+am_id+"' disabled='' title='Login info, etc' onClick=\"window.location='"+listres_url+"&am_id="+am_id+"'\"><b>Details</b></button>\n";
// 	    output += "<button  id='delete_button_"+am_id+"' disabled='' onClick=\"window.location='confirm-sliverdelete.php?slice_id=" + slice+ "&am_id="+am_id+"'\" "+ delete_slivers_disabled +"><b>Delete Resources</b></button>\n";
// 	    output += "</td></tr>";
//             // (3) Get the status for this slice at this aggregate
// //	    update_agg_row( am_id );
//      }	
//      output += "</table>";
//      $("#status_table_div").html(output);
// }


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
    geni_status = "updating";
    $("td#status_"+am_id).text( updating_text);
    $("td#status_"+am_id).attr( "class", geni_status );
    $("td#status_"+am_id).parent().attr( "class", geni_status );
    update_agg_row(am_id);
}

function twodigits( input ) {
    var in_str = "";
    in_str = input + "";
    if (in_str.length == 1) {
	 in_str = "0"+in_str;
    }
    return in_str;
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
	var geni_expires;
        json_am = responseTxt;

	if (Object.keys(json_am).length > 0) {
            am = json_am[am_id];	   
            geni_status = am['geni_status'];
            status_code = am['status_code'];
            geni_expires = am['geni_expires'];
	    var exp = new Date( geni_expires );
	    if (isNaN(exp.getUTCFullYear())) { 
		sliver_expiration = NOT_RETRIEVED;
	    } else {
		sliver_expiration = exp.getUTCFullYear() + "-" + twodigits(exp.getUTCMonth()+1) + "-" + twodigits(exp.getUTCDate()) + " " + twodigits(exp.getUTCHours()) + ":" + twodigits(exp.getUTCMinutes()) + ":" + twodigits(exp.getUTCSeconds()) + " UTC";
	    }
    	    output += geni_status;
	} else {
	    status_code = GENI_NO_STATUS;
	    output += GENI_NO_STATUS_STR;
            sliver_expiration = UNKNOWN;
	}
        $("td#status_"+am_id).text( output );
        $("td#status_"+am_id).attr( "class", GENI_CLASSES[ status_code ] );
        $("td#status_"+am_id).parent().attr( "class", GENI_CLASSES[ status_code ] );
        $("span#renew_sliver_"+am_id).text( sliver_expiration );

	$("button#reload_button_"+am_id).prop( "disabled", false ); 
	if ((status_code == GENI_NO_RESOURCES) || (status_code == GENI_NO_STATUS)){
// could hide rows for AMs with no resources	    $("tr#"+am_id).hide(); 
	    $("button#status_button_"+am_id).prop( "disabled", true ); 
	    $("button#details_button_"+am_id).prop( "disabled", true ); 
	    $("button#delete_button_"+am_id).prop( "disabled", true );
	    $("span#renew_sliver_"+am_id).text( NOT_APPLICABLE);  
	    $("#renew_button_"+am_id).prop( "disabled", true ); 
	    $("input#renew_field_"+am_id).prop( "disabled", true ); 
	} else {
      if (status_code == GENI_READY) {
        $("button#add_button_"+am_id).prop("disabled", true);
      }
	    $("button#status_button_"+am_id).removeProp( "disabled"); 
	    $("button#details_button_"+am_id).removeProp( "disabled"); 
	    $("button#delete_button_"+am_id).removeProp( "disabled");
	    $("#renew_button_"+am_id).removeProp( "disabled");
	    $("input#renew_field_"+am_id).removeProp( "disabled");
	}
     } 
     if(statusTxt=="error")
        alert("Error: "+xhr.status+": "+xhr.statusText);
  });  
}


function build_agg_table_on_sliverstatuspg() 
{
      $("#query").css( 'display', 'block');
   // (1) query the server for all a list of aggregates
    $.getJSON("aggregates.php", { am_id:am_id }, function(responseTxt,statusTxt,xhr){
     var json_agg;
     count = 0;
     json_agg = responseTxt;
     numagg = Object.keys(json_agg).length;
     for (var tmp_am_id in json_agg ) {
         var am = json_agg[tmp_am_id];
         am.id = tmp_am_id;
         add_agg_row_on_sliverstatuspg(am, numagg);
     }
    $("span#numagg").text( numagg );
   });
}



function add_agg_row_on_sliverstatuspg(am, numagg) {
  // This queries for the json file at (for example):
  // https://sergyar.gpolab.bbn.com/secure/amstatus.php?am_id=9&slice_id=b18cb314-c4dd-4f28-a6fd-b355190e1b61
  var am_id = am.id;
  $.getJSON("amstatus.php", { am_id:am_id, slice_id:slice },function(responseTxt,statusTxt,xhr){
      var json_am, am;
      var geni_urn, geni_status, agg_name, geni_resources, colspan;
      var resource, firstrow, num_rsc, rsc_urn, rsc_status, rsc_error;
      var output=""; 

     if(statusTxt=="success") 
     {
         json_am = responseTxt;
	 if (Object.keys(json_am).length == 0) {
	     return;
	 }
         am = json_am[am_id];	   
	 
	 if (am == null) {
	     output +=  "<tr><td></td><td>"+"ERROR"+"</td></tr>";
	     $("table#slivererror").append( output);
	     count++;
	     if (count == numagg) {
		 $("#query").css('display','none');
		 $("#summary").css( 'display', 'block');
	     }
	     return;
	 }
         geni_urn = am['slice_urn'];
         geni_status = am['geni_status'];
         status_code = am['status_code'];
	 agg_name= am['am_name'];
	 geni_resources = am['resources'];

	 if ((status_code != GENI_NO_RESOURCES) && (status_code != GENI_BUSY) && (status_code != GENI_FAILED)){
	     output += "<tr class='aggregate'><th>Status</th><th colspan='2'>Aggregate</th></tr>";
	     output += "<tr class='aggregate'><td class='"+geni_status+"'>"+geni_status+"</td>";
	     output += "<td colspan='2'>"+agg_name+"</td></tr>";
	     firstrow = true;
	     num_rsc = geni_resources.length;
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
             $("table#sliverstatus").append( output );
	     output = "";
	     count++;
	     if (count == numagg) {
		 $("#query").css('display','none');
		 $("#summary").css( 'display', 'block');
	     }

	 } else {
             if ( $("table#slivererror").children().length == 0 ) {
		 output += "<tr><th>Aggregate</th><th>Message</th></tr>";
	     }
	     output += "<tr>";
	     output += "<td>"+agg_name+"</td>";
	     output += "<td>"+geni_status+"</td>";
	     output += "</tr>";
	     output += "</table>";
	     
             $("table#slivererror").append( output );
	     count++;
	     if (count == numagg) {
		 $("#query").css('display','none');
		 $("#summary").css( 'display', 'block');
	     }
	     
	 }  
     }
      if(statusTxt=="error") {
	  am_error = am['geni_error'];
	  /* output += "<div>Returned status of slivers on ".$n." of ".$m." aggregates.</div>"; */
	  if ( $("table#slivererror").children().length == 0 ) {
	      //$("table#slivererror").before( "<div><p>No resources on the following aggregates:</p></div>" );
	      output += "<tr><th>Aggregate</th><th>Message</th></tr>";
	  }
	  output += "<tr>";
	  output += "<td>"+agg_name+"</td>";
	  output += "<td>"+geni_status+"</td>";
	  //output += "<td>Error</td>";
	  output += "</tr>";
	  output += "</table>";

	  $("table#slivererror").append( output );
	  count++;
	  if (count == numagg) {
	      $("#query").css('display','none');
	      $("#summary").css( 'display', 'block');
	  }
      }
  })
  .fail(function(responseTxt, statusTxt, xhr) {
      var output="";
      var agg_name = am.name;
      var geni_status = 'Error determining status';
      if ( $("table#slivererror").children().length == 0 ) {
	  output += "<tr><th>Aggregate</th><th>Message</th></tr>";
      }
      output += "<tr>";
      output += "<td>"+agg_name+"</td>";
      output += "<td>"+geni_status+"</td>";
      output += "</tr>";
      output += "</table>";

      $("table#slivererror").append( output );
      count++;
      if (count == numagg) {
	  $("#query").css('display','none');
	  $("#summary").css( 'display', 'block');
      }
  });  
}



function build_details_table() 
{
   // (1) query the server for all a list of aggregates
    $.getJSON("aggregates.php", { am_id:am_id }, function(responseTxt,statusTxt,xhr){
	var json_agg, numAgg;
	var count;
	json_agg = responseTxt;
	// update the displayed count of number of aggregates contacting 
	numAgg = Object.keys(json_agg).length;
        $("#total").text( numAgg );
	for (var tmp_am_id in json_agg ) {
	    add_agg_row_to_details_table(tmp_am_id, numAgg);
	}
   });
}

function add_agg_row_to_details_table(am_id, numagg) {
  // This queries for the json file at (for example):
  // https://sergyar.gpolab.bbn.com/secure/amdetails.php?am_id=9&slice_id=b18cb314-c4dd-4f28-a6fd-b355190e1b61&pretty=False
    // amdetails.php returns HTML (not JSON) since there was already php code to generate the needed text
    $.get("amdetails.php", { am_id:am_id, slice_id:slice, pretty:pretty},function(responseTxt,statusTxt,xhr){
      var json_am, am, numAttempt;
      var geni_urn, geni_status, agg_name, geni_resources, colspan;
      var resource, firstrow, num_rsc, rsc_urn, rsc_status, rsc_error;
      var output=""; 

      $("#query").css('display','none');
      $("#summary").css( 'display', 'block');
      // update displayed count of number aggregates contacted
      numAttempt = parseInt($("#numagg").text());
      numAttempt += 1;
      $("#numagg").text( numAttempt );
     if(statusTxt=="success") 
     {
         am = responseTxt;
	 if (am == null) {
	     output +=  "<tr><td></td><td>"+"ERROR"+"</td></tr>";
	     $("table#slivererror").append( output);
	     return;
	 } else {
	     output = am;
	 }
	 $("div#details").append( output );
	 output = "";
	 if (pretty=="true" && am) {
	     add_one_login(am_id, slice);
	 }
     }

     if (numAttempt == numagg) {
	 if ( $("div#details").children().length == 0 ) {
	     $("#noresources").css( 'display', 'block');
	 }
     }


//       if(statusTxt=="error") {
// 	  am_error = am['geni_error'];
// 	  /* output += "<div>Returned status of slivers on ".$n." of ".$m." aggregates.</div>"; */
// 	  if ( $("table#slivererror").children().length == 0 ) {
// 	      //$("table#slivererror").before( "<div><p>No resources on the following aggregates:</p></div>" );
// 	      output += "<tr><th>Aggregate</th><th>Message</th></tr>";
// 	  }
// 	  output += "<tr>";
// 	  output += "<td>"+agg_name+"</td>";
// 	  output += "<td>"+geni_status+"</td>";
// 	  //output += "<td>Error</td>";
// 	  output += "</tr>";
// 	  output += "</table>";

// 	  $("table#slivererror").append( output );
// 	  count++;
// 	  if (count == numagg) {
// 	      $("#query").css('display','none');
// 	      $("#summary").css( 'display', 'block');
// 	  }
//       }
  });  
}


function add_all_logins_to_manifest_table() 
{
   // (1) query the server for all a list of aggregates
    $.getJSON("aggregates.php", { am_id:am_id, slice_id:slice }, function(responseTxt,statusTxt,xhr){
     var json_agg;
     json_agg = responseTxt;
     for (var tmp_am_id in json_agg ) {
	 add_one_login(tmp_am_id, slice);
     }
   });
}

// Add all information from am_status for a set of AMs 
// including login, status, expiration
function add_all_logins(am_ids, slice_id)
{
    for(var i in am_ids) {
	var am_id = am_ids[i];
	add_one_login(am_id, slice_id);
    }
}


// Add all information from am_status including login, status, expiration
function add_one_login(am_id, slice_id) 
{
  $.getJSON("amstatus.php", { am_id:am_id, slice_id:slice },function(responseTxt,statusTxt,xhr){
     $("div#agg_"+am_id+" .status_msg").css( 'display', 'none' );
     if(statusTxt=="success") 
     {
	var tmp_am_id;
        var json_am;
	var login_info;
	var resources;
	var client;
        var hostname;
        var client_id;
        var output=""; 
	var port;
	var username;
	var firstrow;
        json_am = responseTxt;
	if (Object.keys(json_am).length > 0) {
	    am = json_am[am_id];


	    // Set the expiration on each sliver
	    if ('resources' in am) {
		resources = am['resources'];

		// Set the overall slice status at the aggregate
		if ('geni_status' in am) {
		    am_status = am['geni_status'];
		    am_status_box = $('#am_status_' + am_id);
		    am_status_box.text(am_status.toUpperCase());
		    for(var i in am_status_box) {
			am_status_box[i].className = am_status
		    }
		}

		var expires = null;
		if ('geni_expires' in am) {
		    expires = am['geni_expires'];
		} else if ('pg_expires' in am) {
		    expires = am['pg_expires'];
		} else if ('foam_expires' in am) {
		    expires = am['foam_expires'];
		} else if ('orca_expires' in am) {
		    expires = am['orca_expires'];
		} else if ('pl_expires' in am) {
		    expires = am['pl_expires'];
		} else if ('sfa_expires' in am) {
		    expires = am['sfa_expires'];
		}
		if (expires != null) {
		    for(var i in resources) {
			var res = resources[i];
			var sliver_id = res['geni_urn'];
			// Replace : and .
			var adjusted_sliver_id = sliver_id.replace( /(:|\.|\[|\]|\+)/g, "_" );

			if (expires != null) {
			    var exp_tds = $('#expiration-' + adjusted_sliver_id);
			    exp_tds.text(new Date(expires).toISOString());
			}

			var sliver_status = null;
			if ('geni_status' in res) 
			    sliver_status = res['geni_status'];

			if (sliver_status != null) {
			    var status_tds = $('#status-' + adjusted_sliver_id);
			    status_tds.text(sliver_status.toUpperCase());
			    for(var i in status_tds) {
				status_tds[i].className = sliver_status;
			    }
			}
		    }
		}
	    }

	    if (!("login_info" in am)) {
		return;
	    }
	    login_info = am['login_info'];
	    if (!(login_info)) {
		// sometimes (eg if BUSY) might have contain anything
		return;
	    }
	    resources = login_info['resources'];

	    if (!(resources)) {
		return;
	    }
	    for (var i in resources ){
		var client = resources[i];
		firstrow = true;
		for (var j in client ){
		var rsc = client[j];
		hostname = rsc['hostname'];
		client_id = rsc['client_id'];
		port = rsc['port'];
		username = rsc['username'];
		// eg <a href='ssh://sedwards@pc1.pgeni3.gpolab.bbn.com:2020' target='_blank'>ssh sedwards@pc1.pgeni3.gpolab.bbn.com -p 2020</a>
		anchor_login = "ssh://"+username+"@"+hostname;
		login = "ssh "+username+"@"+hostname;
		if (port !=22){
		    login += " -p "+port;
		    anchor_login += ":"+port;
		}
		// check for a div with an ID for the aggregates AND
		// then update it's descendant td with an ID for the client_id
		if (firstrow) {
		    $("div#agg_"+am_id+" td#login_"+client_id).html( "<a href='"+anchor_login+"' target='_blank'>" + login+ "</a>" );
		    firstrow = false;
		} else {
		    $("div#agg_"+am_id+" td#login_"+client_id).append( "<br/><a href='"+anchor_login+"' target='_blank'>" + login+ "</a>" );
		}
		}
	    }
	}
     }
     if(statusTxt=="error")
        alert("Error: "+xhr.status+": "+xhr.statusText);
   });
}


function build_delete_table() 
{
   // (1) query the server for all a list of aggregates
    $.getJSON("aggregates.php", { am_id:am_id }, function(responseTxt,statusTxt,xhr){
	var json_agg, numAgg;
	json_agg = responseTxt;
	// update the displayed count of number of aggregates contacting 
	numAgg = Object.keys(json_agg).length;
        $("#total").text( numAgg );
	for (var tmp_am_id in json_agg ) {
	    add_agg_row_to_delete_table(tmp_am_id);
	}
   });
}

function add_agg_row_to_delete_table(am_id) {
  // This queries for the json file at (for example):
  // https://sergyar.gpolab.bbn.com/secure/amstatus.php?am_id=9&slice_id=b18cb314-c4dd-4f28-a6fd-b355190e1b61
  $.getJSON("deletesliver.php", { am_id:am_id, slice_id:slice },function(responseTxt,statusTxt,xhr){
      var succ, fail;
      var agg; 
      var numSucc =0;
      var numFail =0;
      var numAttempt = 0;
      var succ_output="";
      var fail_output="";

      if(statusTxt=="success") 
      {
	  succ = responseTxt[0];
	  fail = responseTxt[1];

	  for (agg in succ) {
	      succ_output +=  "<li>"+succ+"</li>";
              $("ul#deletesliver").append( succ_output );
	  } 

	  for (agg in fail) {
	      fail_output +=  "<li>"+fail+"</li>";
              $("ul#deleteerror").append( fail_output );
	  } 

	  // remove the "Deleting resources..."
	  $("#delete").css( 'display', 'none');
	  // replace with "Have attempted to delete resources at 1 of 5 aggregate."
	  $("#summary").css( 'display', 'block');

	  
	  // update displayed count of number aggregates contacted
          numAttempt = parseInt($("span#attempted").text());
	  numAttempt += 1;
          $("span#attempted").text( numAttempt );
	  
          if ( $("ul#deletesliver").children().length > 0 ) {
	      // update displayed count of number aggregates successfully deleted at
              numSucc = parseInt($("span#success").text());
	      numSucc += 1;
              $("span#success").text( numSucc );

	      $("div#delsliverlabel").css( 'display', 'block');
	  }
          if ( $("ul#deleteerror").children().length > 0 ) {
	      // update displayed count of number aggregates failed to delete at
              numFail = parseInt($("span#fail").text());
	      numFail += 1;
              $("span#fail").text( numFail );

	      $("div#delerrorlabel").css( 'display', 'block');
	  }
      }
      if(statusTxt=="error")
          alert("Error: "+xhr.status+": "+xhr.statusText);
  });  
}


function build_renew_table() 
{
   // (1) query the server for all a list of aggregates
    $.getJSON("aggregates.php", { am_id:am_id }, function(responseTxt,statusTxt,xhr){
	var json_agg, numAgg;
	json_agg = responseTxt;
	// update the displayed count of number of aggregates contacting 
	numAgg = Object.keys(json_agg).length;
        $("#total").text( numAgg );
	for (var tmp_am_id in json_agg ) {
	    add_agg_row_to_renew_table(tmp_am_id, sliver_expiration);
	}
   });
}

function add_agg_row_to_renew_table(am_id, sliver_expiration) {
  // This queries for the json file at (for example):
  // https://sergyar.gpolab.bbn.com/secure/amstatus.php?am_id=9&slice_id=b18cb314-c4dd-4f28-a6fd-b355190e1b61
    $.getJSON("renewsliver.php", { am_id:am_id, slice_id:slice, sliver_expiration:sliver_expiration },function(responseTxt,statusTxt,xhr){
      var succ, fail;
      var agg; 
      var numSucc =0;
      var numFail =0;
      var numAttempt = 0;
      var succ_output="";
      var fail_output="";

      if(statusTxt=="success") 
      {
	  succ = responseTxt[0];
	  fail = responseTxt[1];

	  for (agg in succ) {
	      succ_output +=  "<li>"+succ+"</li>";
              $("ul#renewsliver").append( succ_output );
	  } 

	  for (agg in fail) {
	      fail_output +=  "<li>"+fail+"</li>";
              $("ul#renewerror").append( fail_output );
	  } 

	  // remove the "Renewing resources..."
	  $("#renew").css( 'display', 'none');
	  // replace with "Have attempted to renew resources at 1 of 5 aggregate."
	  $("#renewsummary").css( 'display', 'block');

	  
	  // update displayed count of number aggregates contacted
          numAttempt = parseInt($("span#attempted").text());
	  numAttempt += 1;
          $("span#attempted").text( numAttempt );
	  
          if ( $("ul#renewsliver").children().length > 0 ) {
	      // update displayed count of number aggregates successfully renewed at
              numSucc = parseInt($("span#success").text());
	      numSucc += 1;
              $("span#success").text( numSucc );

	      $("div#renewsliverlabel").css( 'display', 'block');
	  }
          if ( $("ul#renewerror").children().length > 0 ) {
	      // update displayed count of number aggregates failed to renew at
              numFail = parseInt($("span#fail").text());
	      numFail += 1;
              $("span#fail").text( numFail );

	      $("div#renewerrorlabel").css( 'display', 'block');
	  }
      }
      if(statusTxt=="error")
          alert("Error: "+xhr.status+": "+xhr.statusText);
  });  
}


// KEITH: Below are the functions needed to make the AM list run on the slice page.

// This function initializes the AM list.
// It takes the PHP generated elements (right side of the table) and generates the check box list.
function prepareList() {
  // Loop through each AM generated by the PHP.
  $('#status_table > tbody').each(function() {
    // The group it belongs in will be the first class, this grabs the first class if there are more than one.
    var className = $(this).attr('class').split(' ')[0];
    // This may not be needed, but the group id is the middle section of the class without the prefix or postfix.
    var targetParent = className.substring(3,className.length-3);
    // Find the check group it belongs in and insert the html for the checkbox
    var parentHTML = $('#am_names #g_'+targetParent+' ul').html();
    $('#am_names #g_'+targetParent+' ul').html(parentHTML+'<li><input type="checkbox" id="box-'+$(this).attr('id').substring(2)+'" class="inner" checked="checked"><span class="checkSib">'+$(this).find('.am_name_field').text()+'</span></li>');
  });
  // Loops through each top-level check box and sets up the collapsable functionality
  $('#am_names').find('.am_group .collapsable')
    .click( function(event) {
      if (this == event.target) {
        $(this).parent().toggleClass('expanded').children('ul').toggle();
      }
      return false;
    })
    .parent().addClass('collapsed');
    // Initializes the checkboxes and the top-level counter.
    $('#am_names .outer').each(function() {
      $(this).prop('checked',true);
      $(this).siblings('.checkSib').find('.countSelected').html(" ("+$(this).parents('.am_group').find('.inner:checkbox:checked').length+")");
    });
  };

// Function that gets called when the Select All button is clicked.
function selectAll() {
  // Loops through all top level checkboxes.
  $('#am_names .outer').each(function() {
    // Locates and loops through each checkbox within that group.
    $(this).parent().find('.inner').each(function() {
      // Grabs the id for that checkbox and sets the corresponding box on the right to show.
      var targetID = $(this).attr('id').substring(4);
      $('#status_table #t_'+targetID).removeClass('hidden');
      $(this).prop('checked', true);
    });

    // Sets the top level box to be checked.
    $(this).prop('checked', true).prop('indeterminate', false);
    // Nulls out the Select Only field.
    $('#checkGroups').val(' ');
    // Sets the selected counter for the group.
    $(this).parent().find('.countSelected').html(" ("+$(this).parent().find('.inner:checkbox:checked').length+")");
  });
}

// Function that gets called when the Deselect All button is clicked.
// Same as above function expect in reverse.
function deselectAll() {
  $('#am_names .outer').each(function() {
    $(this).parent().find('.inner').each(function() {
      var targetID = $(this).attr('id').substring(4);
      $('#status_table #t_'+targetID).addClass('hidden');
      $(this).prop('checked', false);
    });
    $(this).prop('checked', false).prop('indeterminate', false);
    $('#checkGroups').val(' ');
    $(this).parent().find('.countSelected').html(" ("+$(this).parent().find('.inner:checkbox:checked').length+")");
  });
}

// Function that gets called from a few events below for the inner checkboxes.
// Note that the 'that' parameter is the 'this' from the caller.
// resetSelect is a boolean to null out the Select Only field.
function checkBoxes(that, resetSelect) {
    var targetID = $(that).attr('id').substring(4);
    
    // Toggles the corresponding element on the right side of the table.
    if ($(that).is(':checked')) {
      $('#status_table #t_'+targetID).removeClass('hidden');
    }
    else {
      $('#status_table #t_'+targetID).addClass('hidden');
    }

    // This section is to determine what state the outer checkbox for this box should show as.
    // First, if all elements inside of it are checked, the outer box is checked.
    // Second, if some elements inside are checked, the outer box is set to 'indeterminate'.
    // Third, if no elements are checked, the outer box is unchecked.
    var thatParent = $(that).parents('.am_group').find('.outer');
    if ($(that).parents('.am_group').find('.inner:checkbox:checked').length == $(that).parent().siblings().length+1) {
      thatParent.prop('indeterminate', false).prop('checked', true);
    }
    else if ($(that).parents('.am_group').find('.inner:checkbox:checked').length > 0) {
      thatParent.prop('indeterminate', true).prop('checked', true);
    }
    else {
      thatParent.prop('indeterminate', false).prop('checked', false);
    }
    // Sets the count for the parent.
    thatParent.siblings('.checkSib').find('.countSelected').html(" ("+$(that).parents('.am_group').find('.inner:checkbox:checked').length+")");
    if (resetSelect) {
      $('#checkGroups').val(' ');
    }
}

// Loops through all of the checked elements and triggers a refresh on the corresponding AM on the right.
function getCheckedStatus() {
  $('#am_names').find('.inner:checkbox:checked').each(function() {
    refresh_agg_row($(this).attr('id').substring(4));
  });
}

// Function to set up the click events that the table uses.
function prepareEvents() {
  // Set up the click events for all of the inner checkboxes.
  $('#am_names .inner').each(function() {
    $(this).click(function() {
      checkBoxes(this, true);
    });
    var that = this;
    // Set up the name to toggle the checkbox when clicked.
    $(this).siblings('.checkSib').click(function() {
      $(that).prop("checked", !$(that).prop("checked"));
      checkBoxes(that, true);
    });
  });

  // Set up the click events for each outer checkbox.
  $('#am_names .outer').each(function() {
    $(this).click(function(event) {
      // Depending on the resulting state, set all boxes internal to this one to selected or deselected.
      if ($(this).is(':checked')) {
        $(this).parent().find('.inner').each(function() {
          var targetID = $(this).attr('id').substring(4);
          $('#status_table #t_'+targetID).removeClass('hidden');
          $(this).prop('checked', true);
        });
      }
      else {
        $(this).parent().find('.inner').each(function() {
          var targetID = $(this).attr('id').substring(4);
          $('#status_table #t_'+targetID).addClass('hidden');
          $(this).prop('checked', false);
        });
      }
      // Update the count and null out the Select Only.
      $(this).siblings('.checkSib').find('.countSelected').html(" ("+$(this).parents('.am_group').find('.inner:checkbox:checked').length+")");
      $('#checkGroups').val(' ');
    });
    var that = this;
    // Set up the label for the outer checkbox to expand/collapse the group when clicked.
    $(this).siblings('.checkSib').click(function() {
      $(this).parent().toggleClass('expanded').children('ul').toggle();
    });
  });

  // Set up the Select Only box.
  $('#checkGroups').change(function() {
    // Grab the class to look for, make sure it isn't null, and trim off the prefix.
    var flag = $(this).find('option:selected').attr('class');
    if (flag != undefined) {
      flag = flag.substring(3);
      // Loop through all inner checkboxes and find which elements match the flag.
      $('#am_names .inner').each(function() {
        var target = $('#status_table #t_'+$(this).attr('id').substring(4)).hasClass(flag);
        if (target && !$(this).is(':checked')) {
          $(this).prop('checked',true);
          checkBoxes(this, false);
        }
        else if (!target && $(this).is(':checked')) {
          $(this).prop('checked',false);
          checkBoxes(this, false);
        }
      });
      // Collapse all groups.
      $('.am_group').each(function() {
        if ($(this).hasClass('expanded')) {
          $(this).toggleClass('expanded').children('ul').toggle();
        }
      });
    }
  });
}

// These two functions are used to build the various links based off of what is checked in the table.
function doOnChecked(baseURL, skipWarn) {
  var finalURL = baseURL;
  // Sets up an array of the checked boxes.
  var checkedBoxes = $('#am_names').find('.inner:checkbox:checked');

  // Makes sure that at least one box is checked.
  if (checkedBoxes.size() > 0) {
    // If there are more than 10 AMs checked, send them to the warning page.
    if (!skipWarn && checkedBoxes.size() > 10) {
      finalURL = 'tool-aggwarning.php?loc=' + finalURL;
    }
    // Build the final URL.
    checkedBoxes.each(function() {
      finalURL += '&am_id[]='+$(this).attr('id').substring(4);
    });
    window.location = finalURL;
  }
}

// Grabs the date that the user has checked.
function doOnRenew(baseURL) {
  var tempURL = baseURL;
  var slice_expiration = $('#renew_field_check').val();
  tempURL += '&sliver_expiration='+slice_expiration;
  doOnChecked(tempURL);
}