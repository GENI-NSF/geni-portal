/*----------------------------------------------------------------------
 * Copyright (c) 2013-2014 Raytheon BBN Technologies
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and/or hardware specification (the "Work") to
 * deal in the Work without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Work, and to permit persons to whom the Work
 * is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Work.
 *
 * THE WORK IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS
 * IN THE WORK.
 *----------------------------------------------------------------------
 */
var km = {};
km.sendcert = function() {
    var sfcredElement = document.getElementById('sfcred');
    if (sfcredElement) {
        genilib.sendCredential(sfcredElement.innerHTML);
    } else {
        var userPrivateKey = document.getElementById('privatekey').innerHTML;
        var userCert = document.getElementById('certificate').innerHTML;
        genilib.sendCertificate(userPrivateKey + "\n" + userCert);
    }
}
km.showtool = function() {
    genilib.sendCertificate("");
}
km.showprogress = function(text, val) {
    $('#progress-text').text(text);
    $('#progress-bar').val(val);
}
km.initialize = function() {
  /* Add a click callback to the "authorize" button. */
  $('#loadcert').click(km.sendcert);
  $('#showtool').click(km.showtool);
  /* The certificate is immediately ready to send, but if we send it
   * immediately the user sees our window flash too quickly to
   * see. Instead, let them see progress and then send the
   * certificate. */
  window.setTimeout("km.showprogress('Preparing certificate...', 1);", 100);
  window.setTimeout("km.showprogress('Sending certificate...', 2);", 1000);
  window.setTimeout("km.showprogress('Done.', 3);", 1500);
  /* Autoclick the loadcert button to streamline the UI. */
  window.setTimeout("$('#loadcert').trigger('click');", 2000);
}
$(document).ready(km.initialize);
