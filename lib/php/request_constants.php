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

// Names of types of requests. See REQ_TYPE class
$REQ_TYPE_NAMES = array(
			"Join",
			"Update Attributes");

// Types of requests. Goes in request_type slot of request.
class REQ_TYPE
{
  const JOIN=0; // A Project or Slice, or GENI
  const UPDATE_ATTRIBUTES=1; // On your GENI account
}

// See REQ_STATUS
$REQ_STATUS_NAMES = array (
			   "Pending",
			   "Approved",
			   "Canceled",
			   "Rejected");

// Status of request. Goes in status slot on request
class REQ_STATUS
{
  const PENDING=0;
  const APPROVED=1;
  const CANCELED=2; // By the requestor
  const REJECTED=3;
}


