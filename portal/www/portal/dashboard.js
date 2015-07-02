  $(document).ready(function(){
    $("#hamburger").click(function(){
      $("#dashboardtools").slideToggle();
    });

    if (localStorage.lastselection && localStorage.lastselectionval) {
      $("#projectswitch .selectorshown").html(localStorage.lastselection);
      $("#projectswitch .selectorshown").attr("value", localStorage.lastselectionval);
    } else {
      $("#projectswitch .selectorshown").html($($("#projectswitch .submenu li")[0]).html());
      $("#projectswitch .selectorshown").attr("value", $($("#projectswitch .submenu li")[0]).attr("value"));
    }

    if (localStorage.lastsortby && localStorage.lastsortbyval) {
      $("#sortby .selectorshown").html(localStorage.lastsortby);
      $("#sortby .selectorshown").attr("value", localStorage.lastsortbyval);
    } else {
      $("#sortby .selectorshown").html($($("#sortby .submenu li")[0]).html());
      $("#sortby .selectorshown").attr("value", $($("#sortby .submenu li")[0]).attr("value"));
    }

    show_slices($("#projectswitch .selectorshown").attr("value"), $("#sortby .selectorshown").attr("value"),
                $("#projectswitch .selectorshown").html(), $("#sortby .selectorshown").html());

    $(".has-sub").hover(function(){ $(this).find('ul').show(); },
                        function(){ $(this).find('ul').hide(); });

    $(".selector > .submenu > li").click(function(){
      $(this).parents(".selector").children(".selectorshown").html($(this).html());
      $(this).parents(".selector").children(".selectorshown").attr("value", $(this).attr("value"));
    });

    $("#projectswitch .submenu li").click(function(){
      show_slices($("#projectswitch .selectorshown").attr("value"), $("#sortby .selectorshown").attr("value"),
                  $("#projectswitch .selectorshown").html(), $("#sortby .selectorshown").html());
    });

    $("#sortby .submenu li").click(function(){
      show_slices($("#projectswitch .selectorshown").attr("value"), $("#sortby .selectorshown").attr("value"),
                  $("#projectswitch .selectorshown").html(), $("#sortby .selectorshown").html());
    });

    $('#ascendingcheck').click(function() {
      sort_slices($("#sortby > .selectorshown").attr("value"), this.checked);       
    });

    $('.slicebox').click(function(){
      box = $(this);
      if($(this).hasClass("expanded")){
        unexpand_box(box, 200);
      } else {
        expand_box(box, 200);
      }
    });

    function expand_box(box, duration) {
      box.animate({width: "375px"}, duration,
        function(){
          box.find(".slicebuttons").slideToggle(duration/3);
        });
      $(box.find(".slicetopbar")).attr("colspan", "3");
      box.addClass("expanded");
    }

    function unexpand_box(box, duration) {
      box.find(".slicebuttons").slideToggle(duration / 3,
        function(){
          $(box.find(".slicetopbar")).attr("colspan", "2");
          box.animate({width: "275px"}, duration);
          box.removeClass("expanded");
        });
    }

    function show_slices(selection, sortby, selectionstring, sortbystring) {
      $(".slicebox").each(function() {
        if ($(this).hasClass("expanded")) {
          unexpand_box($(this), 0);
        }
      });
      $(".slicebox").hide();
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
        no_slice_msg = "No slices for project" + project_name;
        $("#" + project_name + "info").show();
      }

      $("." + class_name).show();
      sort_slices(sortby, $("#ascendingcheck").prop("checked"));
      if($("." + class_name).length == 0) {
        $("#slicearea").append("<h3 class='dashtext noslices'>" + no_slice_msg + "</h3>");
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


  });