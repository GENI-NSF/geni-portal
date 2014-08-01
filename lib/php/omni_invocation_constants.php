<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2014 Raytheon BBN Technologies
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

/* Set of constants for managing omni invocation files
 */
class OMNI_INVOCATION_FILE
{
  const REQUEST_RSPEC_FILE = "rspec";
  const CALL_RESULTS_FILE = "omni-stdout";
  const METADATA_FILE = "metadata";
  const COMMAND_FILE = "command";
  const OMNI_CONFIGURATION_FILE = "omni-ini";
  const LOGGER_CONFIGURATION_FILE = "logger.conf";
  const PID_FILE = "omni-pid";
  const DEBUG_LOG_FILE = "omni-log";
  const CONSOLE_LOG_FILE = "omni-console";
  const ERROR_LOG_FILE = "omni-stderr";
  // these two are hard-coded in stitcher_php.py
  const START_FILE = "start";
  const STOP_FILE = "stop";
}

?>
