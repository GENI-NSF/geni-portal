function info_set_location(slice_id, url)
{
    $.getJSON("aggregates.php", { slice_id: slice_id },
              function (responseTxt, statusTxt, xhr) {
                  var json_agg = responseTxt;
                  var agg_ids = Object.keys(json_agg);
                  agg_ids = [80, 82, 109];
                  var agg_count = agg_ids.length;
                  if (agg_count > 0) {
                      for (var i = 0; i < agg_count; i++) {
                          url += "&am_id[]=" + agg_ids[i];
                      }
                      window.location = url;
                  } else {
                      alert("Slice has no sliver information");
                  }
              })
        .fail(function() {
            alert("Unable to location sliver information for this slice.");
        });
}
