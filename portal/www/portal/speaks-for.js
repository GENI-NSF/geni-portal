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
  genilib.authorize(tool_urn, tool_cert, portal.authZResponse);
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
      alert('got fail result: ' + textStatus);
    });
}
portal.initialize = function()
{
  /* Add a click callback to the "authorize" button. */
  $('#authorize').click(portal.authorize);
}
$(document).ready(portal.initialize);
