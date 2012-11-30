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
  const GENI_READY=1;
  const GENI_NO_RESOURCES=2; 
  const GENI_BOOTING=3;
  const GENI_BUSY=4;
}


class STATUS_MSG
{
  const GENI_READY = "ready";
  const GENI_NO_RESOURCES = "no resources"; 
  const GENI_BOOTING = "booting";
  const GENI_BUSY = "busy";
}

class STATUS_CLASS
{
  const GENI_READY = "ready";
  const GENI_NO_RESOURCES = "noresources"; 
  const GENI_BOOTING = "booting";
  const GENI_BUSY = "busy";
}


$GENI_MESSAGES = array( 
	       STATUS_MSG::GENI_READY,
	       STATUS_MSG::GENI_NO_RESOURCES,
	       STATUS_MSG::GENI_BOOTING,
	       STATUS_MSG::GENI_BUSY);

$GENI_CLASSES = array( 
	       STATUS_CLASS::GENI_READY,
	       STATUS_CLASS::GENI_NO_RESOURCES,
	       STATUS_CLASS::GENI_BOOTING,
	       STATUS_CLASS::GENI_BUSY);



?>
