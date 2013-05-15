<?php
//----------------------------------------------------------------------
// Copyright (c) 2011 Raytheon BBN Technologies
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

// Determines if the PORTAL is in maintenance mode
// If so, operators are allowed to use portal but the header is changed
// Otherwise, users are forwarded to a page indicating that GENI Portal
// is in maintenance mode

$maintenance_outage_file = "/tmp/geni_maintenance_outage.msg";

$maintenance_alert_file = "/tmp/geni_maintenance_alert.msg";

$in_maintenance_mode = file_exists($maintenance_outage_file);

$has_maintenance_alert = file_exists($maintenance_alert_file);

$maintenance_message = "";
if ($in_maintenance_mode)
  $maintenance_message = file_get_contents($maintenance_outage_file);

$maintenance_alert = "";
if ($has_maintenance_alert)
  $maintenance_alert = file_get_contents($maintenance_alert_file);

// error_log("Maint " . print_r($in_maintenance_mode, true) . " " . 
//    $maintenance_message);

?>
