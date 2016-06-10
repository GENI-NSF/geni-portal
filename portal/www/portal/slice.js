//----------------------------------------------------------------------
// Copyright (c) 2016 Raytheon BBN Technologies
//
// Permission is hereby granted, free of charge, to any person obtaining
// a copy of this software and/or hardware specification (the "Work") to
// deal in the Work without restriction, including without limitation the
// rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Work, and to permit persons to whom the Work
// is furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be
// included in all copies or substantial portions of the Work.
//
// THE WORK IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
// NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
// HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS
// IN THE WORK.
//----------------------------------------------------------------------

/* Supporting functions for slice.js */

/**
 * Figure out if the "Update SSH Keys" button should be enabled.
 *
 * It should only be enabled if there is at least one InstaGENI
 * aggregate with resources in this slice.
 */
function enableUpdateSshKeys() {
  var has_instageni = false;
  var ui_am_type = "UI_AM_TYPE";
  var ig_type = "ui_instageni_am";
  $.each(slice_ams, function(index, value) {
    var am = jacks_all_ams[value];
    if (am.attributes[ui_am_type] == ig_type) {
      has_instageni = true;
      // Break out of the $.each by returning false
      return false;
    }
  });
  if (has_instageni) {
    // Enable the update keys button by removing the 'disabled' attribute.
    $("#updatekeys").removeAttr("disabled")
  }
}

$(document).ready(enableUpdateSshKeys);
