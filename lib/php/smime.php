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

function decode_result($result)
{
  return json_decode($result, true); // Return associative array
}

function parse_message($msg)
{
  $map = json_decode($msg, true);
  $pretty_map = print_r($map, true);
  //  error_log("json_decode returned $pretty_map");
  $funcargs[0] = $map['operation'];
  unset($map['operation']);
  $funcargs[1] = $map;
  return $funcargs;
}

function parse_result($result)
{
  return $result;
}

?>
