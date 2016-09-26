function handle_lookup(data) {
  if (data.code == 0) {
    // Project lookup was successful
    // get project id from data
    // redirect to join-this-project.php?project_id=id
    var project = data.value
    console.log(project.project_id);
  } else {
    // Handle error case
    console.log("fail")
    $('#finderror').append(data.output);
    $('#findname').select();
  }
}
function handle_error(jqXHR, textStatus, errorThrown) {
  console.log("error");
  // An unknown error has occurred. Pop up a dialog box.
}

function lookup_project(project_name) {
  // Clear out any previous errors
  $('#finderror').empty();
  var lookup_url = "/secure/lookup-project.php"
  var data = {name: project_name}
  $.post(lookup_url, data, handle_lookup, 'json').fail(handle_error);
}

function do_lookup_project(event) {
        event.preventDefault();
        lookup_project($('#findname').val());
}


$(document).ready( function () {
  /* datatables.net (for sortable/searchable tables) */
  $('#projects').DataTable({paging: false});
  $('#findbtn').click(do_lookup_project);
} );
