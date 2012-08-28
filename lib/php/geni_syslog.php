<?php
//----------------------------------------------------------------------
// Copyright (c) 2012 Raytheon BBN Technologies
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

/**
 * Services to provide uniform access to syslog from portal and CH services
 * Only critical events needed for forensics purposes should be sent
 * to syslog, such as account creation/deletion, session creation
 */

class GENI_SYSLOG_PREFIX
{
  const PORTAL = "GENI-PORTAL";
  const AUTHZ = "GENI-AUTHZ";
  const CS = "GENI-CS";
  const MA = "GENI-MA";
  const PA = "GENI-PA";
  const SA = "GENI-SA";
  const SR = "GENI-SR";
}

// Uses 'User' by default.
// class Syslog_State {
//   public static $openlog_called = false;
// }

// $DEFAULT_SYSLOG_FACILITY = LOG_USER;
// function geni_syslog($prefix, $message, $priority = LOG_INFO)
// {
//   global $DEFAULT_SYSLOG_FACILITY;
//   if (Syslog_State::$openlog_called == false) {
//     openlog("", 0, $DEFAULT_SYSLOG_FACILITY);
//     Syslog_State::$openlog_called = true;
//     error_log("Called openlog");
//   }
//   syslog($priority, $prefix . " " . $message);
//   // syslog(LOG_USER | $priority, $prefix . " " . $message);
// }

function geni_syslog($prefix, $message, $priority = LOG_INFO)
{
  syslog(LOG_USER | $priority, $prefix . " " . $message);
}
?>