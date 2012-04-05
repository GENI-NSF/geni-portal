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

require_once("message_handler.php");

function create_slice($args)
{
  $slice_name = $args['slice_name'];
  error_log("created $slice_name");
  return "created $slice_name";
}

function create_slice_credential($args)
{
  /* Extract method arguments. */
  $pretty_args = print_r($args, true);
  error_log("SA CSC: args = $pretty_args");
  $slice_name = $args['slice_name'];
  $exp_cert = $args['experimenter_certificate'];
  error_log("SA CSC: exp_cert = $exp_cert");

  /* Info for settings file. */
  error_log('SA FIXME: hardcoded path to gcf install');
  $portal_gcf_dir = '/usr/share/geni-ch/portal/gcf';
  $portal_gcf_cfg_dir = '/usr/share/geni-ch/portal/gcf.d';

  $cert_file = tempnam(sys_get_temp_dir(), 'sa-');
  file_put_contents($cert_file, $exp_cert);

  // Run slicecred.py and return it as the content.
  $cmd_array = array($portal_gcf_dir . '/src/slicecred.py',
                     $portal_gcf_cfg_dir . '/gcf.ini',
                     $slice_name,
                     $portal_gcf_cfg_dir . '/ch-key.pem',
                     $portal_gcf_cfg_dir . '/ch-cert.pem',
                     $cert_file
                     );
  $command = implode(" ", $cmd_array);
  error_log("SA CSC: command = $command");
  $result = exec($command, $output, $status);
  //print_r($output);

  // Clean up, clean up
  unlink($cert_file);

  /* The slice credential is printed to stdout, which is captured in
     $output as an array of lines. Crunch them all together in a
     single string, separated by newlines.
  */
  $slice_cred = implode("\n", $output);
  $result = array('slice_credential' => $slice_cred);
  return $result;
}

handle_message("SA");

?>