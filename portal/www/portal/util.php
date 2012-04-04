<?php

require_once('smime.php');

//----------------------------------------------------------------------
// Utility functions
//----------------------------------------------------------------------

//--------------------------------------------------
// Compute a url relative to the current page.
//--------------------------------------------------
function relative_url($relpath) {
  $protocol = "http";
  if (array_key_exists('HTTPS', $_SERVER)) {
    $protocol = "https";
  }
  $host  = $_SERVER['HTTP_HOST'];
  $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
  $extra = $relpath;
  return "$protocol://$host$uri/$extra";
}

//--------------------------------------------------
// Redirect to a url relative to the current page.
//--------------------------------------------------
function relative_redirect($relpath) {
  $url = relative_url($relpath);
  header("Location: $url");
  exit;
}

function make_uuid() {
  $uuid = exec('/usr/bin/uuidgen');
  return $uuid;
}

//--------------------------------------------------
// Send a message (via PUT) to a given URL and return response
//--------------------------------------------------
function put_message($url, $message)
{
  $message = json_encode($message);
  // sign
  // encrypt
  $tmpfile = tempnam(sys_get_temp_dir(), "msg");
  file_put_contents($tmpfile, $message);
  $ch = curl_init();
  $fp = fopen($tmpfile, "r");
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_PUT, true);
  curl_setopt($ch, CURLOPT_INFILE, $fp);
  curl_setopt($ch, CURLOPT_INFILESIZE, strlen($message));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $result = curl_exec($ch);
  $error = curl_error($ch);
  curl_close($ch);
  fclose($fp);
  unlink($tmpfile);
  if ($error) {
    error_log("put_message error: $error");
    $result = NULL;
  }
  //  error_log("Received raw result : " . $result);
  $result = trim($result); // Remove trailing newlines
  $result = decode_result($result);
  // error_log("Decoded raw result : " . $result);

  return $result;
}


?>