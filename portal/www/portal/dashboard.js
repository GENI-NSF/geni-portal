//----------------------------------------------------------------------
// Copyright (c) 2015 Raytheon BBN Technologies
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

// dashboard.js: enable interactivity and animations on the dashboard page
GENI_LS_VERSION = 1; // increment if your changes to localStorage stuff require clearing localStorage.

$(document).ready(function(){
  if ($("#slicefilterswitch").length > 0) { // they have some projects or slices
    resume_dashboard();
  } else { // they're a brand new user
    $("#logs").hide();
    $("#map").hide();
  }

  var old_callback = get_callback;
  get_callback = function (tab_name) {
    if (tab_name == "#map") {
      return function(){
        map_init("/common/map/current.json", [42, -72], 3);
      }
    } else {
      return old_callback(tab_name);
    }
  }
  
  // Make header links and new selectors show dropdown when you hover on them
  $(".has-sub").hover(function(){ $(this).find('ul').first().show(); },
                      function(){ $(this).find('ul').hide(); });

  // Make the new selectors behave like regular html selects
  $(".selector > .submenu > li").click(function() {
    if (!$(this).hasClass("selectorlabel")){
      update_selector($(this).parents(".selector"), $(this).attr("data-value"));
    }
  });

  // Make slices reappear when a filter or sort is changed
  $("#slicefilterswitch .submenu li").click(function() {
    if (!$(this).hasClass("selectorlabel")){
      update_slices();
    }
  });
  $("#slicesortby .submenu li").click(update_slices);
  $('#sliceascendingcheck').click(update_slices);

  // Same idea for projects. TODO: Sorts
  $("#projectfilterswitch .submenu li").click(update_projects);
  $("#projectsortby .submenu li").click(update_projects);
  $('#projectascendingcheck').click(update_projects);

  $('#loglength li').click(function() {
    localStorage.loghours = $(this).attr("data-value");
    get_logs($(this).attr("data-value"));
  });

  $(window).resize(function() {
    if ($(window).width() > 480) {
      $("#dashboardtools").show();
    }
  });

});

function update_slices() {
  show_slices(get_selector_value($("#slicefilterswitch")), get_selector_value($("#slicesortby")));
}

function update_projects() {
  show_projects(get_selector_value($("#projectfilterswitch")), get_selector_value($("#projectsortby")));
}

// save the state of section section with filter selection and sort criterion sortby
function save_state(section, selection, sortby) {
  localStorage.setItem("last" + section + "selection", selection);
  localStorage.setItem("last" + section + "sortby", sortby);
}

// Get the values that you last used and display them
function resume_dashboard() {
  if ((localStorage.username && localStorage.username != GENI_USERNAME) || localStorage.GENI_LS_VERSION != GENI_LS_VERSION) {
    localStorage.clear();
  }

  localStorage.username = GENI_USERNAME;
  localStorage.GENI_LS_VERSION = GENI_LS_VERSION;

  return_to_prev_state($("#slicefilterswitch"), "lastsliceselection");
  return_to_prev_state($("#slicesortby"), "lastslicesortby");
  return_to_prev_state($("#projectfilterswitch"), "lastprojectselection");
  return_to_prev_state($("#projectsortby"), "lastprojectsortby");

  update_slices();
  update_projects();

  if (localStorage.loghours) {
    get_logs(localStorage.loghours);
  } else {
    $('#loglengthselector .selectorshown').html("day");
    get_logs(24);
  }
}

// sets the selector to have the value stored at localStorage[storage_key] selected
function return_to_prev_state(selector, storage_key) {
  if (localStorage[storage_key]) {
    update_selector(selector, localStorage[storage_key]);
  } else {
    update_selector(selector, get_selector_default(selector));
  }
}

// gets default selector value, aka the value of the first item in the dropdown
function get_selector_default(selector) {
  return selector.find("li:not(.selectorlabel)").first().attr("data-value");
}

function get_selector_value(selector) {
  return selector.find(".selectorshown").first().attr("data-value");
}

// update a selector to display the dropdown item with newval
function update_selector(selector, newval) { 
  newtext = selector.find(".submenu li[data-value='" + newval + "']:not(.selectorlabel)").html();
  selector.find(".selectorshown").attr("data-value", newval);
  selector.find(".selectorshown").html(newtext);
}

// Retrieve all the GENI logs for the user in the past hours hours
function get_logs(hours){
  $.get("do-get-logs.php?hours="+hours, function(data) {
    if (data.split("<html").length == 1) {
      $('#logtable').html(data);
    } else {
      location.reload();
    }
  });
}

function renew_slice(slice_id, days, count, sliceexphours, resourceexphours) {
  var newexp = new Date();
  newexp.setDate(newexp.getDate() + days);
  var d = newexp.getDate();
  var m = newexp.getMonth() + 1;
  var y = newexp.getFullYear();
  var newexpstring = y + '-' + m + '-'+ d; 
  renewalhours = 24 * days;

  if (count > 0 && resourceexphours < renewalhours) {
    if (sliceexphours < renewalhours) {
      renew_resources("do-renew.php?renew=slice_sliver&slice_id=" + slice_id + "&sliver_expiration=" + newexpstring, slice_id);   
    } else {
      renew_resources("do-renew.php?renew=sliver&slice_id=" + slice_id + "&sliver_expiration=" + newexpstring, slice_id);   
    }
  } else {
    if (sliceexphours < renewalhours) {
      window.location = "do-renew.php?renew=slice&slice_id=" + slice_id + "&sliver_expiration=" + newexpstring;    
    }
  }
}

function renew_resources(url, slice_id) {
  $.getJSON("aggregates.php", { slice_id: slice_id },
    function (responseTxt, statusTxt, xhr) {
      var json_agg = responseTxt;
      var agg_ids = Object.keys(json_agg);
      var agg_count = agg_ids.length;
      for (var i = 0; i < agg_count; i++) {
        url += "&am_id[]=" + agg_ids[i];
      }
      if (agg_count > 10) {
        result = confirm("This action will renew resources at "
                         + agg_count
                         + " aggregates and may take several minutes.");
        if (result) {
          window.location = url;
        } else {
          return;
        }
      }
      window.location = url;
    })
    .fail(function() {
      alert("Unable to locate sliver information for this slice.");
    });
}

function info_set_location(slice_id, url, stop_if_none) {
  $.getJSON("aggregates.php", { slice_id: slice_id }, function (responseTxt, statusTxt, xhr) {
    var json_agg = responseTxt;
    var agg_ids = Object.keys(json_agg);
    var agg_count = agg_ids.length;
    for (var i = 0; i < agg_count; i++) {
      url += "&am_id[]=" + agg_ids[i];
    }
    window.location = url;
  })
  .fail(function() {
    alert("Unable to locate sliver information for this slice.");
  });
}


// Shows all the projects matching selection, sorting by the sorting type given by sortby. 
function show_projects(selection, sortby) {
  $("#projectarea .slicebox").addClass("gone");
  $("#projectarea .slicebox").hide();
  $(".noprojects").hide();

  save_state("project", selection, sortby);

  sort_boxes(sortby, $("#projectascendingcheck").prop("checked"), "#projectarea");
  animate_boxes("#projectarea", selection);



  // sort_boxes(sortby, $("#ascendingcheck").prop("checked"));
  if($("." + selection).length == 0) {
    $("#projectarea").append("<h6 style='margin:15px;' class='noprojects'><i>No projects to display.</i></h6>");
  }
}

// Shows all the slices matching selection, sorting by the sorting type given by sortby.
function show_slices(selection, sortby) {
  $("#slicearea .slicebox").hide();
  $(".noslices").remove();
  $(".projectinfo").hide();
  save_state("slice", selection, sortby);

  if (is_category(selection)) {
    project_name = "";
    class_name = selection;
    no_slice_msg = "No slices to display.";
    $("#categoryinfo").show();
  } else {
    project_name = selection;
    class_name = selection + "slices";
    no_slice_msg = "No slices for project " + project_name;
    $("#" + project_name + "info").show();
  }

  sort_boxes(sortby, $("#sliceascendingcheck").prop("checked"), "#slicearea");
  animate_boxes("#slicearea", class_name);

  if($("." + class_name).length == 0) {
    $("#slicearea").append("<h6 style='margin:15px;' class='noslices'><i>" + no_slice_msg + "</i></h6>");
  }
}

// Shows the slices for project projectname

function show_slices_for_project(projectname) { 
  update_selector($("#slicefilterswitch"), projectname);
  switch_to_card("#slices");
  update_slices();
}

// Determines if a selection for show_slices is a project name or a category
function is_category(class_name) {
  return class_name[0] == '-';
}

// Makes the slices/project boxes zoom in from the right
function animate_boxes(container, selection) { 
  num_columns = Math.floor(parseInt($(container).css("width").split("px")[0]) / 315);
   // fancy slice animations
  $("." + selection).addClass("loading");
  $("." + selection).show();
  if(selection != "-EXPIRED-PROJECTS-") {
    $(".-EXPIRED-PROJECTS-").hide();
  }

  $('.' + selection).each(function(index, element) {
    setTimeout(function() {
        element = $(element);
        element.removeClass('loading');
    }, (index % num_columns) * 100 + ((index * 100)/ num_columns));
  });
}

// sort boxes in container based on their values for attribute attr
function sort_boxes(attr, ascending, container) {
  numberical_attrs = ['sliceexp', 'resourceexp', 'resourcecount', 'projexp', 'slicecount'];
  sorted_slices = $(container).children(".slicebox").sort(function(a, b) {
    if ($.inArray(attr, numberical_attrs) != -1) { // is it a numerical attribute, if so, don't lexically sort
      vA = parseInt($(a).attr("data-" + attr));
      vB = parseInt($(b).attr("data-" + attr));
    } else {
      vA = $(a).attr("data-" + attr).toLowerCase();
      vB = $(b).attr("data-" + attr).toLowerCase(); 
    }
    if(ascending) {
      return (vA < vB) ? -1 : (vA > vB) ? 1 : 0;
    } else {
      return (vA < vB) ? 1 : (vA > vB) ? -1 : 0;
    }
  });
  $(container).append(sorted_slices);
}
