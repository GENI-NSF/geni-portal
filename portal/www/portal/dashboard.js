//----------------------------------------------------------------------
// Copyright (c) 2011-2015 Raytheon BBN Technologies
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

$(document).ready(function(){
  if($("#slicefilterswitch").length > 0){ // they have some projects or slices
    return_to_prev_state();
  } else { // they're a brand new user
    $('#loglengthselector .selectorshown').html("day");
    get_logs("24");
  }

  // Make the toggle for the projects | slices work
  $("#sectionswitch a").click(function() {
    if(!$(this).hasClass("activesection")){
      $('#Projectsection').toggle();
      $('#Slicesection').toggle();
      $("#sectionswitch a").removeClass("activesection");
      $(this).addClass("activesection");
      localStorage.lastsection = $(this).html();
    }
  });    
  
  // Make header links and new selectors show dropdown when you hover on them
  $(".has-sub").hover(function(){ $(this).find('ul').first().show(); },
                      function(){ $(this).find('ul').hide(); });

  // Make the new selectors behave like regular html selects
  $(".selector > .submenu > li").click(function(){
    if (!$(this).hasClass("selectorlabel")){
      $(this).parents(".selector").children(".selectorshown").html($(this).html());
      $(this).parents(".selector").children(".selectorshown").attr("value", $(this).attr("value"));
    }
  });

  // Make slices reappear when a filter or sort is changed
  $("#slicefilterswitch .submenu li").click(function(){
    if (!$(this).hasClass("selectorlabel")){
      show_slices($("#slicefilterswitch .selectorshown").attr("value"), $("#slicesortby .selectorshown").attr("value"),
                $("#slicefilterswitch .selectorshown").html(), $("#slicesortby .selectorshown").html());
    }
  });

  $("#slicesortby .submenu li").click(function(){
    show_slices($("#slicefilterswitch .selectorshown").attr("value"), $("#slicesortby .selectorshown").attr("value"),
                $("#slicefilterswitch .selectorshown").html(), $("#slicesortby .selectorshown").html());
  });

  $('#sliceascendingcheck').click(function() {
    show_slices($("#slicefilterswitch .selectorshown").attr("value"), $("#slicesortby .selectorshown").attr("value"),
                $("#slicefilterswitch .selectorshown").html(), $("#slicesortby .selectorshown").html());    
  });

  // Same idea for projects. TODO: Sorts
  $("#projectfilterswitch .submenu li").click(function(){
    if (!$(this).hasClass("selectorlabel")) {
      show_projects($("#projectfilterswitch .selectorshown").attr("value"), $("#projectsortby .selectorshown").attr("value"),
                $("#projectfilterswitch .selectorshown").html(), $("#projectsortby .selectorshown").html());
    }
  });

  $("#projectsortby .submenu li").click(function(){
    show_projects($("#projectfilterswitch .selectorshown").attr("value"), $("#projectsortby .selectorshown").attr("value"),
                $("#projectfilterswitch .selectorshown").html(), $("#projectsortby .selectorshown").html());
  });

  $('#projectascendingcheck').click(function() {
    show_projects($("#projectfilterswitch .selectorshown").attr("value"), $("#projectsortby .selectorshown").attr("value"),
                $("#projectfilterswitch .selectorshown").html(), $("#projectsortby .selectorshown").html());    
  });

  $('#loglength li').click(function() {
    localStorage.loghours = $(this).val();
    localStorage.loghoursstring = $(this).html();
    get_logs($(this).val());
  });

});

function save_state(section, selection, sortby, selectionstring, sortbystring) {
  localStorage.setItem("last" + section + "selection", selectionstring);
  localStorage.setItem("last" + section + "selectionval", selection);
  localStorage.setItem("last" + section + "sortby", sortbystring);
  localStorage.setItem("last" + section + "sortbyval", sortby);
}


// Get the values that you last used and display them on page load
function return_to_prev_state() {
  if (localStorage.lastsliceselection && localStorage.lastsliceselectionval) {
    $("#slicefilterswitch .selectorshown").html(localStorage.lastsliceselection);
    $("#slicefilterswitch .selectorshown").attr("value", localStorage.lastsliceselectionval);
  } else {
    $("#slicefilterswitch .selectorshown").html($($("#slicefilterswitch .submenu li")[0]).html());
    $("#slicefilterswitch .selectorshown").attr("value", $($("#slicefilterswitch .submenu li")[0]).attr("value"));
  }

  if (localStorage.lastslicesortby && localStorage.lastslicesortbyval) {
    $("#slicesortby .selectorshown").html(localStorage.lastslicesortby);
    $("#slicesortby .selectorshown").attr("value", localStorage.lastslicesortbyval);
  } else {
    $("#slicesortby .selectorshown").html($($("#slicesortby .submenu li")[0]).html());
    $("#slicesortby .selectorshown").attr("value", $($("#slicesortby .submenu li")[0]).attr("value"));
  }

  if (localStorage.lastprojectselection && localStorage.lastprojectselectionval) {
    $("#projectfilterswitch .selectorshown").html(localStorage.lastprojectselection);
    $("#projectfilterswitch .selectorshown").attr("value", localStorage.lastprojectselectionval);
  } else {
    $("#projectfilterswitch .selectorshown").html($($("#projectfilterswitch .submenu li")[0]).html());
    $("#projectfilterswitch .selectorshown").attr("value", $($("#projectfilterswitch .submenu li")[0]).attr("value"));
  }

  if (localStorage.lastprojectsortby && localStorage.lastprojectsortbyval) {
    $("#projectsortby .selectorshown").html(localStorage.lastprojectsortby);
    $("#projectsortby .selectorshown").attr("value", localStorage.lastprojectsortbyval);
  } else {
    $("#projectsortby .selectorshown").html($($("#projectsortby .submenu li")[0]).html());
    $("#projectsortby .selectorshown").attr("value", $($("#projectsortby .submenu li")[0]).attr("value"));
  }

  show_slices($("#slicefilterswitch .selectorshown").attr("value"), $("#slicesortby .selectorshown").attr("value"),
              $("#slicefilterswitch .selectorshown").html(), $("#slicesortby .selectorshown").html());

  show_projects($("#projectfilterswitch .selectorshown").attr("value"), $("#projectsortby .selectorshown").attr("value"),
            $("#projectfilterswitch .selectorshown").html(), $("#projectsortby .selectorshown").html());

  // Retrieve the last used amount of time for the logs or default to 24 hours
  if (localStorage.loghours && localStorage.loghoursstring) {
    $('#loglengthselector .selectorshown').html(localStorage.loghoursstring);
    get_logs(localStorage.loghours);
  } else {
    $('#loglengthselector .selectorshown').html("day");
    get_logs(24);
  }

  // Retrieve last used visited section on the dashboard
  // default is slices page if no data found
  if (localStorage.lastsection) {
    sectionname = localStorage.lastsection
    $("#sectionswitch a").removeClass("activesection");
    $("#" + sectionname + "button").addClass("activesection");
    $(".dashsection").hide();
    $("#" + sectionname + "ection").show();
  }
}

// Retrieve all the GENI logs for the user in the past hours hours
function get_logs(hours){
  $.get("do-get-logs.php?hours="+hours, function(data) {
    $('#logtable').html(data);
  });
}

// Shows all the projects matching selection, sorting by the sorting type
// given by sortby. *string are for use in displaying in the selectors
function show_projects(selection, sortby, selectionstring, sortbystring) {
  $("#projectarea .slicebox").addClass("gone");
  $("#projectarea .slicebox").hide();
  $(".noprojects").hide();

  save_state("project", selection, sortby, selectionstring, sortbystring);

  sort_slices(sortby, $("#projectascendingcheck").prop("checked"), "#projectarea");
  animate_boxes("#projectarea", selection);

  // sort_slices(sortby, $("#ascendingcheck").prop("checked"));
  if($("." + selection).length == 0) {
    $("#projectarea").append("<h3 class='dashtext noprojects'><i>No projects to display.</i></h3>");
  }
}

// Shows all the slices matching selection, sorting by the sorting type
// given by sortby. *string are for use in displaying in the selectors
function show_slices(selection, sortby, selectionstring, sortbystring) {
  $("#slicearea .slicebox").hide();
  $(".noslices").remove();
  $(".projectinfo").hide();
  save_state("slice", selection, sortby, selectionstring, sortbystring);

  if (is_category(selection)) {
    project_name = "";
    class_name = selection;
    no_slice_msg = "No slices to display.";
  } else {
    project_name = selection;
    class_name = selection + "slices";
    no_slice_msg = "No slices for project " + project_name;
    $("#" + project_name + "info").show();
  }

  sort_slices(sortby, $("#sliceascendingcheck").prop("checked"), "#slicearea");
  animate_boxes("#slicearea", class_name);

  if($("." + class_name).length == 0) {
    $("#slicearea").append("<h4 class='dashtext noslices'><i>" + no_slice_msg + "</i></h4>");
  }
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

  $('.' + selection).each(function(index, element) {
    setTimeout(function() {
        element = $(element);
        element.removeClass('loading');
    }, (index % num_columns) * 100 + ((index * 100)/ num_columns));
  });
}

// sort boxes in container based on their values for attribute attr
function sort_slices(attr, ascending, container) {
  numberical_attrs = ['sliceexp', 'resourceexp', 'resourcecount', 'projexp'];
  sorted_slices = $(container).children().sort(function(a, b) {
    if ($.inArray(attr, numberical_attrs) != -1) { // is it a numerical attribute, if so, don't lexically sort
      vA = parseInt($(a).attr(attr));
      vB = parseInt($(b).attr(attr));
    } else {
      vA = $(a).attr(attr);
      vB = $(b).attr(attr); 
    }
    if(ascending) {
      return (vA < vB) ? -1 : (vA > vB) ? 1 : 0;
    } else {
      return (vA < vB) ? 1 : (vA > vB) ? -1 : 0;
    }
  });
  $(container).append(sorted_slices);
}
