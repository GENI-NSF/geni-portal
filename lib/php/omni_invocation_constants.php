<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2015 Raytheon BBN Technologies
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

/* Set of constants for managing omni invocation path
   FIXME: Not currently used
*/
class OMNI_INVOCATION_DIRECTORY
{
  const DIRECTORY_ROOT = "/tmp/";
  const DIRECTORY_PREFIX = "omni-invoke";
}

/* Set of constants for managing omni invocation files
 */
class OMNI_INVOCATION_FILE
{
  const REQUEST_RSPEC_FILE = "request_rspec.xml";
  const CALL_RESULTS_FILE = "omni_results.txt";
  const METADATA_FILE = "metadata.json";
  const METADATA_BUG_REPORT_EMAIL_FILE = "metadata-email.json";
  const COMMAND_FILE = "omni_command.txt";
  const OMNI_CONFIGURATION_FILE = "omni_configuration.txt";
  const LOGGER_CONFIGURATION_FILE = "omni_logging_configuration.txt";
  const PID_FILE = "omni_pid.txt";
  const DEBUG_LOG_FILE = "omni_debug_log.txt";
  const CONSOLE_LOG_FILE = "omni_console_log.txt";
  const ERROR_LOG_FILE = "omni_error_log.txt";
  const SLICE_CREDENTIAL_FILE = "slice_credential";
  const SPEAKSFOR_CREDENTIAL_FILE = "speaksfor_credential";
  const CERTIFICATE_FILE = "certificate";
  const PRIVATE_KEY_FILE = "private_key";
  const PUBLIC_SSH_KEY_PREFIX = "ssh-key";
  const ZIP_ARCHIVE_PREFIX = "omni-invocation-bug-report";
  // Note: These two below are hard-coded separately in stitcher_php.py
  const START_FILE = "start";
  const STOP_FILE = "stop";
}

?>
