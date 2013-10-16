var portal = {};
portal.authorize = function()
{
  var tool_urn = document.getElementById('toolurn').innerHTML;
  var tool_cert = document.getElementById('toolcert').innerHTML;
  genilib.authorize(tool_urn, tool_cert, portal.authZResponse);
  return false;
}
portal.authZResponse = function(speaks_for_cred)
{
  // Called if the user authorizes us in the signing tool
  alert('Response available from genilib.authorize');
  $("#cred").text(speaks_for_cred).html();
  var jqxhr = $.post('speaks-for-upload.php', speaks_for_cred);
  jqxhr.done(function(data, textStatus, jqxhr) {
      alert('got result: ' + textStatus);
    })
  .fail(function(data, textStatus, jqxhr) {
      alert('got fail result: ' + textStatus);
    });
}
portal.initialize = function()
{
  /* Add a click callback to the "authorize" button. */
  $('#authorize').click(portal.authorize);
}
$(document).ready(portal.initialize);
