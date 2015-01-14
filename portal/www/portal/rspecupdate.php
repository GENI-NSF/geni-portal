<?php
//----------------------------------------------------------------------
// Copyright (c) 2011-2015 Raytheon BBN Technologies
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

require_once('db-util.php');


// This file is invoked to update an existing rspec with new rspec contents

// error_log("RSU: " . print_r($_POST, true));

if (!array_key_exists('rspec_id', $_POST) || 
    (!array_key_exists('rspec', $_POST))) {
  error_log("Invalid call to rspecupdate: no rspec_id or rspec provided");
  return;
}

$rspec_id = $_POST['rspec_id'];
$rspec = $_POST['rspec'];

$result = db_update_rspec_contents($rspec_id, $rspec);
// error_log("RSPEC_UPDATE = " . print_r($result, true));

relative_redirect('profile#rspecs');

?>
