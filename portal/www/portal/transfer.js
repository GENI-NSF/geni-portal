function transfer_verify() {
  var user = $("#gpo_user").val();
  var pass = $("#gpo_pass").val();
  // alert("User " + user + " with pass " + pass);
  var jqxhr = $.post("verifyuser.php", {user: user, pass: pass});
  jqxhr.done(function(data, textStatus, jqXHR) {
    confirm_transfer();
  })
  jqxhr.fail(function(jqXHR, textStatus, errorThrown) {
    alert("Incorrect username or password. Please try again.");
  })
}

function confirm_transfer() {
  msg = "Are you sure you want to transfer your GPO account to this account?";
  if (confirm(msg)) {
          var user = $("#gpo_user").val();
          var pass = $("#gpo_pass").val();
          // alert("User " + user + " with pass " + pass);
          var jqxhr = $.post("dotransfer.php", {user: user, pass: pass});
          jqxhr.done(function(data, textStatus, jqXHR) {
            alert("Transfer is complete.");
            window.location.href = "profile.php";
          })
          jqxhr.fail(function(jqXHR, textStatus, errorThrown) {
            alert("Transfer failed. Please send email to help@geni.net");
          })
  }
}

$(document).ready(function() {
    $("#gpo_verify").click(transfer_verify);
});
