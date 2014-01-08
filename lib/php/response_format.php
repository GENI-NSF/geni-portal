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

// Standard format for responses from services: A dictionary of [code, value, output]

class RESPONSE_ARGUMENT {
  const CODE = 'code'; // Error code: no error if 0
  const VALUE = 'value'; // Result of call if no error
  const OUTPUT = 'output'; // Error info if error
}

// Canonical representation of message response
// Dictionary: [
//       code = (error code, 0 => no error), 
//       value => (result if no error), 
//       output => error info if error)]
//       ]
function generate_response($code, $value, $output)
{
  $result[RESPONSE_ARGUMENT::CODE] = $code;
  $result[RESPONSE_ARGUMENT::VALUE] = $value;
  $result[RESPONSE_ARGUMENT::OUTPUT] = $output;
  return $result;
}

class RESPONSE_ERROR {
  const NONE = 0;
  const DATABASE = 1;
  const AUTHENTICATION = 2;
  const AUTHORIZATION = 3;
  const ARGS = 4;
}

?>
