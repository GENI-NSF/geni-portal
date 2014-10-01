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
var portal = {};
portal.authorize = function()
{
  var tool_urn = document.getElementById('toolurn').innerHTML;
  var tool_cert = document.getElementById('toolcert').innerHTML;
  var ma_url_elem = document.getElementById('ma_url');
  var ma_name_elem = document.getElementById('ma_name');
  default_ma = null;
  if (ma_url_elem && ma_name_elem) {
      default_ma = {};
      default_ma.url = ma_url_elem.innerHTML;
      default_ma.name = ma_name_elem.innerHTML;
  }
  certWindow = genilib.authorize(tool_urn, tool_cert, portal.authZResponse,
                                 default_ma);
  $(certWindow.document).ready(function() {
      $('.windowOpen').removeClass('hidden');
      $(certWindow).focus();

      var interv = window.setInterval(function() {
          if (certWindow.closed !== false) {
              window.clearInterval(interv);
              $('.windowOpen').addClass('hidden');
          }
      }, 250);
  });

  return false;
}
portal.authZResponse = function(speaks_for_cred)
{
  // Called if the user authorizes us in the signing tool
  $("#cred").text(speaks_for_cred).html();
  var jqxhr = $.post('speaks-for-upload.php', speaks_for_cred);
  jqxhr.done(function(data, textStatus, jqxhr) {
      //alert('got result: ' + textStatus);
      window.location.href = "home.php";
    })
  .fail(function(data, textStatus, jqxhr) {
      if (data.status == 406) {
          alert('An error occurred storing your credential. Please use your GENI certificate to sign the credential.');
      } else {
          alert('An error occurred storing your credential. Please contact portal-help@geni.net');
      }
    });
}
portal.initialize = function()
{
  /* Add a click callback to the "authorize" button. */
  $('#authorize').click(portal.authorize);
}
$(document).ready(portal.initialize);
