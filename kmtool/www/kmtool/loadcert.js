var km = {};
km.sendcert = function() {
    var userPrivateKey = document.getElementById('privatekey').innerHTML;
    var userCert = document.getElementById('certificate').innerHTML;
    genilib.sendCertificate(userPrivateKey + "\n" + userCert);
}
km.showtool = function() {
    genilib.sendCertificate("");
}
km.initialize = function() {
  /* Add a click callback to the "authorize" button. */
  $('#loadcert').click(km.sendcert);
  $('#showtool').click(km.showtool);
}
$(document).ready(km.initialize);
