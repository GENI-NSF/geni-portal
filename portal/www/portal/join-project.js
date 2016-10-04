function show_lookup_error(msg) {
  $('#finderror').append(msg);
  $('#findname').select();
}

function handle_lookup(data) {
  if (data.code === 0) {
    // Project lookup was successful
    // redirect to join-this-project.php?project_id=id
    // get project from value field, and project_id from that
    var project = data.value;
    if (project.expired) {
      show_lookup_error("Project " + project.project_name + " has expired.");
    } else {
      var url = "join-this-project.php?project_id=" + project.project_id;
      window.location.href = url;
    }
  } else {
    // Handle error case
    show_lookup_error(data.output);
  }
}

function handle_error(jqXHR, textStatus, errorThrown) {
  // An unknown error has occurred. Pop up a dialog box.
  alert("An unknown error occurred.");
}

function lookup_project(project_name) {
  // Clear out any previous errors
  $('#finderror').empty();
  var lookup_url = "/secure/lookup-project.php";
  var data = {name: project_name};
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
