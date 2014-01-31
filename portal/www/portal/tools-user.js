/**
 * Deauthorize the portal by deleting the speaks-for credential.
 */
function deauthorizePortal() {
    var r = confirm("Are you sure you want to deauthorize the portal?");
    if (r == true) {
        var jqxhr = $.get("speaks-for-delete.php")
          .fail(function() {
              alert("An error occurred. Portal is still authorized.");
          })
          .always(function() { location.reload(); });
    }
}