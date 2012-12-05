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


class STATUS_INDEX
{
  // the four official values of geni_status returned by AM API v2
  const GENI_CONFIGURING=1;
  const GENI_READY=2;
  const GENI_FAILED=3;
  const GENI_UNKNOWN=4;
  // some other things we determine by hand
  const GENI_NO_RESOURCES=5; 
  const GENI_BUSY=6;
  const GENI_NO_STATUS=6;
}

class STATUS_MSG
{
  // the four official values of geni_status returned by AM API v2
  const GENI_CONFIGURING = "configuring";
  const GENI_READY = "ready";
  const GENI_FAILED = "failed";
  const GENI_UNKNOWN = "unknown";
  // some other things we determine by hand
  const GENI_NO_RESOURCES = "no resources"; 
  const GENI_BUSY = "busy";
  const GENI_NO_STATUS = "error";
}

class STATUS_CLASS
{
  // the four official values of geni_status returned by AM API v2
  const GENI_CONFIGURING = "configuring";
  const GENI_READY = "ready";
  const GENI_FAILED = "failed";
  const GENI_UNKNOWN = "unknown";
  // some other things we determine by hand
  const GENI_NO_RESOURCES = "noresources"; 
  const GENI_BUSY = "busy";
  const GENI_NO_STATUS = "nostatus";
}


$GENI_MESSAGES = array( 
  STATUS_MSG::GENI_CONFIGURING,
  STATUS_MSG::GENI_READY,
  STATUS_MSG::GENI_FAILED,
  STATUS_MSG::GENI_UNKNOWN,
  STATUS_MSG::GENI_NO_RESOURCES,
  STATUS_MSG::GENI_BUSY,
  STATUS_MSG::GENI_NO_STATUS);

$GENI_MESSAGES_REV = array( 
  STATUS_MSG::GENI_CONFIGURING => STATUS_INDEX::GENI_CONFIGURING,
  STATUS_MSG::GENI_READY => STATUS_INDEX::GENI_READY,
  STATUS_MSG::GENI_FAILED => STATUS_INDEX::GENI_FAILED,
  STATUS_MSG::GENI_UNKNOWN => STATUS_INDEX::GENI_UNKNOWN,
  STATUS_MSG::GENI_NO_RESOURCES => STATUS_INDEX::GENI_NO_RESOURCES,
  STATUS_MSG::GENI_BUSY => STATUS_INDEX::GENI_BUSY,
  STATUS_MSG::GENI_NO_STATUS => STATUS_INDEX::GENI_NO_STATUS);

$GENI_CLASSES = array( 
  STATUS_CLASS::GENI_CONFIGURING,
  STATUS_CLASS::GENI_READY,
  STATUS_CLASS::GENI_FAILED,
  STATUS_CLASS::GENI_UNKNOWN,
  STATUS_CLASS::GENI_NO_RESOURCES,
  STATUS_CLASS::GENI_BUSY,
  STATUS_CLASS::GENI_NO_STATUS);


/*
Valid AM API v2 SliverStatus Error Codes:
BADARGS 	One of the required arguments is badly formed or missing
SEARCHFAILED 	Slice does not exist at this AM
FORBIDDEN 	Credential does not grant permission to the slice
BUSY 	Slice is temporarily locked, try again later
ERROR 	Internal error
SERVERERROR 	Server error
UNAVAILABLE 	Unavailable (eg server in lockdown)
EXPIRED 	Slivers expired 
*/

/*
class ERROR_INDEX
{
  // some other things we determine by hand
  const NO_RESOURCES=1; 
  const BUSY=2;
}


class ERROR_MSG
{
  // some other things we determine by hand
  const NO_RESOURCES = "no resources"; 
  const BUSY = "busy";
}

$ERROR_MESSAGES = array( 
  ERROR_MSG::NO_RESOURCES,
  ERROR_MSG::BUSY);
*/
?>
