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


//RRH - 5-22
//refresh_all_agg_rows, hid_agg_row, and do_action_on_picked_aggs all
//have to deal with managing the aggregate table on the slice page
//need to re check when Keith puts in his UI changes to the slice page

//do_on_picked_aggs_from_pg(page_url) could be an alternate to do_action_on_picked_aggs
//looking at jquery to return the AMs to act on - also UI interaction dependent
//RRH

//refresh_all_agg_rows - called on status - what AMs to call on status and redisplay
function refresh_all_agg_rows() {
    var s = all_ams;
    var all_am_obj = JSON.parse(s);
    for (var tmp_am_id in all_am_obj ) {
	//for Keith to consider in this code - we'd have to figure out
       // which rows to display
       //if(!($('#t_' + tmp_am_id)).hasClass('hidden')) {
       refresh_agg_row(tmp_am_id);
    }
}

//called to do a particular action from the slice page
//page_url is a string referring to a page, current examples are:
//listresources.php, confirm-sliverdelete.php, sliverstatus.php
//do_action_on_picked_aggs("sliverstatus.php")
function do_action_on_picked_aggs(page_url) {
    var s = all_ams;
    var all_am_obj = JSON.parse(s);
    var am_str = "";
    var new_url = page_url;
    //could probably do this with the $(".class").each from the page
    for (var tmp_am_id in all_am_obj ) {
       if(!($('#t_' + tmp_am_id)).hasClass('hidden')) {
           am_str += "&am_id[]=" + tmp_am_id;
       }
    }
    new_url += "?slice_id=" + slice + am_str;
    console.log(new_url);
    window.location=new_url;
}

function do_on_picked_aggs_from_pg(page_url) {
    var s = all_ams;
    var all_am_obj = JSON.parse(s);
    var am_str = "";
    var new_url = page_url;

    //we'd want to pick the classes where not hidden
    //have to ask Keith about picking the right ones
    // not sufficient because there are other tbody in the table
    $('tbody:not(.hidden)').each(function() {
       am_str += "&am_id[]=" + $(this).attr('id');
     });
    new_url += "?slice_id=" + slice + am_str;
    console.log(new_url);
    //    window.location=new_url;
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
      $("#query").css( 'display', 'block');
   // (1) query the server for all a list of aggregates
    $.getJSON("aggregates.php", { am_id:am_id }, function(responseTxt,statusTxt,xhr){
     var json_agg;
     count = 0;
     json_agg = responseTxt;
     numagg = Object.keys(json_agg).length;
     for (var tmp_am_id in json_agg ) {
	 add_agg_row_on_sliverstatuspg(tmp_am_id, numagg);
     }
    $("span#numagg").text( numagg );
   });
}



function add_agg_row_on_sliverstatuspg(am_id, numagg) {
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
    $.get("amdetails.php", { am_id:am_id, slice_id:slice, pretty:pretty, jacks:jacks },function(responseTxt,statusTxt,xhr){
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



// Functions needed to make the AM list run
function prepareList() {
  $('#status_table > tbody').each(function() {
    var className = $(this).attr('class').split(' ')[0];
    console.log(className);
    var targetParent = className.substring(3,className.length-3);
    var parentHTML = $('#am_names #g_'+targetParent+' ul').html();
    $('#am_names #g_'+targetParent+' ul').html(parentHTML+'<li><input type="checkbox" id="box-'+$(this).attr('id').substring(2)+'" class="inner" checked="checked"><span class="checkSib">'+$(this).find('.am_name_field').text()+'</span></li>');
  });
  $('#am_names').find('.am_group .collapsable')
    .click( function(event) {
      if (this == event.target) {
        $(this).parent().toggleClass('expanded').children('ul').toggle();
      }
      return false;
    })
    .parent().addClass('collapsed expanded');
    $('#am_names .outer').each(function() {
      $(this).prop('checked',true);
    });
  };

function selectAll() {
  $('#am_names .outer').each(function() {
    $(this).parent().find('.inner').each(function() {
      var targetID = $(this).attr('id').substring(4);
      $('#status_table #t_'+targetID).removeClass('hidden');
      $(this).prop('checked', true);
    });
    $(this).prop('checked', true).prop('indeterminate', false);
    $('#checkGroups').val(' ');
    $('.am_group').each(function() {
      if (!$(this).hasClass('expanded')) {
        $(this).toggleClass('expanded').children('ul').toggle();
      }
    });
  });
}

function deselectAll() {
  $('#am_names .outer').each(function() {
    $(this).parent().find('.inner').each(function() {
      var targetID = $(this).attr('id').substring(4);
      $('#status_table #t_'+targetID).addClass('hidden');
      $(this).prop('checked', false);
    });
    $(this).prop('checked', false).prop('indeterminate', false);
    $('#checkGroups').val(' ');
    $('.am_group').each(function() {
      if ($(this).hasClass('expanded')) {
        $(this).toggleClass('expanded').children('ul').toggle();
      }
    });
  });
}

function checkBoxes(that, resetSelect) {
    var targetID = $(that).attr('id').substring(4);
    
    if ($(that).is(':checked')) {
      $('#status_table #t_'+targetID).removeClass('hidden');
    }
    else {
      $('#status_table #t_'+targetID).addClass('hidden');
    }
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
    if (resetSelect) {
      $('#checkGroups').val(' ');
    }
}

function getCheckedStatus() {
  $('#am_names').find('.inner:checkbox:checked').each(function() {
    refresh_agg_row($(this).attr('id').substring(4));
  });
}

function prepareEvents() {
  $('#am_names .inner').each(function() {
    $(this).click(function() {
      checkBoxes(this, true);
    });
    var that = this;
    $(this).siblings('.checkSib').click(function() {
      $(that).prop("checked", !$(that).prop("checked"));
      checkBoxes(that, true);
    });
  });

  $('#am_names .outer').each(function() {
    $(this).click(function(event) {
      if ($(this).is(':checked')) {
        if (!$(this).parent().hasClass('expanded')) {
          $(this).parent().toggleClass('expanded').children('ul').toggle();
        }
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
      $('#checkGroups').val(' ');
    });
    var that = this;
    $(this).siblings('.checkSib').click(function() {
      $(that).prop("checked", !$(that).prop("checked"));
      if ($(that).is(':checked')) {
        if (!$(that).parent().hasClass('expanded')) {
          $(that).parent().toggleClass('expanded').children('ul').toggle();
        }
        $(that).parent().find('.inner').each(function() {
          var targetID = $(this).attr('id').substring(4);
          $('#status_table #t_'+targetID).removeClass('hidden');
          $(this).prop('checked', true);
        });
      }
      else {
        $(that).parent().find('.inner').each(function() {
          var targetID = $(this).attr('id').substring(4);
          $('#status_table #t_'+targetID).addClass('hidden');
          $(this).prop('checked', false);
        });
      }
      $('#checkGroups').val(' ');
    });
  });

  $('#checkGroups').change(function() {
    var flag = $(this).find('option:selected').attr('class');
    if (flag != undefined) {
      flag = flag.substring(3);
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
        $('.am_group').each(function() {
          if (($(this).find('.inner:checkbox:checked').length > 0 && !($(this).hasClass('expanded'))) || ($(this).find('.inner:checkbox:checked').length == 0 && $(this).hasClass('expanded'))) {
            $(this).toggleClass('expanded').children('ul').toggle();
          }
        });
      });
    }
  });
}

function doOnChecked(baseURL) {
  var finalURL = baseURL;
  $('#am_names').find('.inner:checkbox:checked').each(function() {
    finalURL += '&am_id[]='+$(this).attr('id').substring(4);
  });
  window.location = finalURL;
}

function doOnRenew(baseURL) {
  var tempURL = baseURL;
  var slice_expiration = $('#renew_field_check').val();
  tempURL += '&sliver_expiration='+slice_expiration;
  doOnChecked(tempURL);
}