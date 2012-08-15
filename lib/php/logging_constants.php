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

// Constants for defining client and server side of logging service API

class LOGGING_ARGUMENT {
  const EVENT_ID = "event_id";
  const EVENT_TIME = 'event_time';
  const EARLIEST_TIME = 'earliest_time';
  const USER_ID = 'user_id';
  const ATTRIBUTES = 'attributes';
  const ATTRIBUTE_SETS = 'attribute_sets';
  const MESSAGE = 'message';
}

$LOGGING_TABLENAME = 'logging_entry';
$LOGGING_ATTRIBUTE_TABLENAME = "logging_entry_attribute";

class LOGGING_TABLE_FIELDNAME {
  const ID = "id";
  const EVENT_TIME = 'event_time';
  const USER_ID = 'user_id';
  const MESSAGE = 'message';
}

class LOGGING_ATTRIBUTE_TABLE_FIELDNAME {
  const EVENT_ID = "event_id";
  const ATTRIBUTE_NAME = 'attribute_name';
  const ATTRIBUTE_VALUE = 'attribute_value';
}

?>