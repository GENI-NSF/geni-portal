var km = {};
km.sendcert = function() {
    var userPrivateKey = document.getElementById('privatekey').innerHTML;
    var userCert = document.getElementById('certificate').innerHTML;
    genilib.sendCertificate(userPrivateKey + "\n" + userCert);
}
km.initialize = function() {
  /* Add a click callback to the "authorize" button. */
  $('#loadcert').click(km.sendcert);
}
$(document).ready(km.initialize);
