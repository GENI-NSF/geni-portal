function transfer_verify() {
  var user = $("#gpo_user").val();
  var pass = $("#gpo_pass").val();
  // alert("User " + user + " with pass " + pass);
  var jqxhr = $.post("verifyuser.php", {user: user, pass: pass});
  jqxhr.done(function(data, textStatus, jqXHR) {
    alert("success");
  })
  jqxhr.fail(function(jqXHR, textStatus, errorThrown) {
    alert("failure");
  })
}

$(document).ready(function() {
    $("#gpo_verify").click(transfer_verify);
});
