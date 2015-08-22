function info_set_location(slice_id, url)
{
    $.getJSON("aggregates.php", { slice_id: slice_id },
              function (responseTxt, statusTxt, xhr) {
                  var json_agg = responseTxt;
                  var agg_ids = Object.keys(json_agg);
                  var agg_count = agg_ids.length;
                  if (agg_count > 0) {
                      for (var i = 0; i < agg_count; i++) {
                          url += "&am_id[]=" + agg_ids[i];
                      }
                      window.location = url;
                  } else {
                      alert("This slice has no known resources. \n\nSomething missing? Select aggregates manually on the slice page.");
                  }
              })
        .fail(function() {
            alert("Unable to locate sliver information for this slice.");
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