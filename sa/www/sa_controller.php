<?php
function smime_decrypt($message)
{
  return $message;
}

function smime_validate($message)
{
  return $message;
}

function smime_sign_message($message)
{
  return $message;
}

function smime_encrypt($message)
{
  return $message;
}

function encode_result($result)
{
  return json_encode($result);
}

function parse_message($msg)
{
  $map = json_decode($msg, true);
  $pretty_map = print_r($map, true);
  error_log("json_decode returned $pretty_map");
  $funcargs[0] = $map['operation'];
  unset($map['operation']);
  $funcargs[1] = $map;
  return $funcargs;
}

function create_slice($args)
{
  $slice_name = $args['slice_name'];
  error_log("created $slice_name");
  return "created $slice_name";
}


error_log("SA: starting");

$request_method = strtolower($_SERVER['REQUEST_METHOD']);
switch($request_method)
  {
  case 'put':
    $putdata = fopen("php://input", "r");
    $data = '';
    error_log("SA starting to read...");
    while ($putchunk = fread($putdata, 1024))
      {
        error_log("Read chunk: $putchunk");
        $data .= $putchunk;
      }
    fclose($putdata);
    break;
  case 'post':
    if (array_key_exists('file', $_FILES)) {
      $errorcode = $_FILES['file']['error'];
      if ($errorcode != 0) {
        // An error occurred with the upload.
        if ($errorcode == UPLOAD_ERR_NO_FILE) {
          $error = "No file was uploaded.";
        } else {
          $error = "Unknown upload error (code = $errorcode).";
        }
        error_log("SA: $error");
      } else {
        $msg_file = $_FILES["file"]["tmp_name"];
      }
    }
    break;
  }
error_log("SA: finished switch");
// Now process the data
$data = smime_decrypt($data);
$msg = smime_validate($data);
// XXX Error check smime_validate result here

$funcargs = parse_message($msg);
$result = call_user_func($funcargs[0], $funcargs[1]);
$output = encode_result($result);
$output = smime_sign_message($output);
$output = smime_encrypt($output);
print $output;

?>