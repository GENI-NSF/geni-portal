$(document).ready(function(){
  $("#hamburger").click(function(){
    $("#dashboardtools").slideToggle();
  });

  $("#sectionswitch a").click(function() {
    if(!$(this).hasClass("activesection")){
      $('#Projectsection').toggle();
      $('#Slicesection').toggle();
      $("#sectionswitch a").removeClass("activesection");
      $(this).addClass("activesection");
      localStorage.lastsection = $(this).html();
    }
  });    

  // Get the values that you last used and display them on page load
  if (localStorage.lastselection && localStorage.lastselectionval) {
    $("#slicefilterswitch .selectorshown").html(localStorage.lastselection);
    $("#slicefilterswitch .selectorshown").attr("value", localStorage.lastselectionval);
  } else {
    $("#slicefilterswitch .selectorshown").html($($("#slicefilterswitch .submenu li")[0]).html());
    $("#slicefilterswitch .selectorshown").attr("value", $($("#slicefilterswitch .submenu li")[0]).attr("value"));
  }

  if (localStorage.lastsortby && localStorage.lastsortbyval) {
    $("#slicesortby .selectorshown").html(localStorage.lastsortby);
    $("#slicesortby .selectorshown").attr("value", localStorage.lastsortbyval);
  } else {
    $("#slicesortby .selectorshown").html($($("#slicesortby .submenu li")[0]).html());
    $("#slicesortby .selectorshown").attr("value", $($("#slicesortby .submenu li")[0]).attr("value"));
  }

  show_slices($("#slicefilterswitch .selectorshown").attr("value"), $("#slicesortby .selectorshown").attr("value"),
              $("#slicefilterswitch .selectorshown").html(), $("#slicesortby .selectorshown").html());

  show_slices($("#slicefilterswitch .selectorshown").attr("value"), $("#slicesortby .selectorshown").attr("value"),
            $("#slicefilterswitch .selectorshown").html(), $("#slicesortby .selectorshown").html());

  // for the header submenus and new selectors
  $(".has-sub").hover(function(){ $(this).find('ul').first().show(); },
                      function(){ $(this).find('ul').hide(); });

  // to make the new selectors behave like regular html selects
  $(".selector > .submenu > li").click(function(){
    if (!$(this).hasClass("selectorlabel")){
      $(this).parents(".selector").children(".selectorshown").html($(this).html());
      $(this).parents(".selector").children(".selectorshown").attr("value", $(this).attr("value"));
    }
  });

  // make slices reappear when a filter or sort is changed
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
    sort_slices($("#slicesortby > .selectorshown").attr("value"), this.checked);       
  });

  // same idea for projects
  $("#projectfilterswitch .submenu li").click(function(){
    if (!$(this).hasClass("selectorlabel")) {
      show_projects($("#projectfilterswitch .selectorshown").attr("value"), "sort-val",
                $("#projectfilterswitch .selectorshown").html(), "sort-string");
    }
  });

  if (localStorage.loghours) {
    getLogs(localStorage.loghours);
  } else {
    getLogs("24");
  }

  if (localStorage.lastsection) {
    sectionname = localStorage.lastsection
    $("#sectionswitch a").removeClass("activesection");
    $("#" + sectionname + "button").addClass("activesection");
    $(".dashsection").hide();
    $("#" + sectionname + "ection").show();
  }

});

function getLogs(hours){
  localStorage['loghours'] = hours;
  $.get("do-get-logs.php?hours="+hours, function(data) {
    $('#logtable').html(data);
  });
}

function show_projects(selection, sortby, selectionstring, sortbystring) {
  $("#projectarea .slicebox").hide();
  $(".noprojects").hide();

   // fancy slice animations
  $("." + selection).addClass("loading");
  $("." + selection).show();

  $('.' + selection).each(function(index, element) {
    setTimeout(function() {
        element = $(element);
        element.removeClass('loading');
    }, index * 100);
  });

  // sort_slices(sortby, $("#ascendingcheck").prop("checked"));
  if($("." + selection).length == 0) {
    $("#projectarea").append("<h3 class='dashtext noprojects'>No projects to display.</h3>");
  }
}


function show_slices(selection, sortby, selectionstring, sortbystring) {
  $("#slicearea .slicebox").hide();
  $(".noslices").remove();
  $(".projectinfo").hide();
  localStorage.setItem("lastselection", selectionstring);
  localStorage.setItem("lastselectionval", selection);
  localStorage.setItem("lastsortby", sortbystring);
  localStorage.setItem("lastsortbyval", sortby);

  if (is_all(selection)) {
    project_name = "";
    class_name = selection;
    no_slice_msg = "No slices to display.";
  } else {
    project_name = selection;
    class_name = selection + "slices";
    no_slice_msg = "No slices for project " + project_name;
    $("#" + project_name + "info").show();
  }

 // fancy slice animations
  $("." + class_name).addClass("loading");
  $("." + class_name).show();

  $('.' + class_name).each(function(index, element) {
    setTimeout(function() {
        element = $(element);
        element.removeClass('loading');
    }, index * 100);
  });

  // $("." + class_name).show();

  sort_slices(sortby, $("#sliceascendingcheck").prop("checked"));
  if($("." + class_name).length == 0) {
    $("#slicearea").append("<h4 class='dashtext noslices'>" + no_slice_msg + "</h4>");
  }
}

function is_all(class_name) {
  return class_name[0] == '-';
}

function sort_slices(attr, ascending) {
  numberical_attrs = ['sliceexp', 'resourceexp', 'resourcecount'];
  sorted_slices = $("#slicearea").children().sort(function(a, b) {
    if ($.inArray(attr, numberical_attrs) != -1) { // is it a numerical attribute?
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
  $("#slicearea").append(sorted_slices);
}
