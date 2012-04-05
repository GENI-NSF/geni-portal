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
  //  $pretty_map = print_r($map, true);
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
