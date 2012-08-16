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

require_once('logging_constants.php');
require_once('cs_constants.php');

// Client side services for Logging of events within GENI clearinghouse

// Log an event to the logging service
// Event consists of 
//    message - Text of log message
//    attributes - Dictionaary of name/value pairs by which to tag and retrieve mmessage
//    user_id (the writer of the log entry)
function log_event($log_url, $message, $attributes, $user_id )
{
  $log_event_message['operation'] = 'log_event';
  $log_event_message[LOGGING_ARGUMENT::EVENT_TIME] = time();
  $log_event_message[LOGGING_ARGUMENT::MESSAGE] = $message;
  $log_event_message[LOGGING_ARGUMENT::ATTRIBUTES] = $attributes;
  $log_event_message[LOGGING_ARGUMENT::USER_ID] = $user_id;
  //   error_log("LOG_EVENT : " . print_r($log_event_message, true));
  $result = put_message($log_url, $log_event_message);
  return $result;
}

function get_log_entries_by_author($log_url, $user_id, $num_hours=24)
{
  $get_log_entries_message['operation'] = 'get_log_entries_by_author';
  $get_log_entries_message[LOGGING_ARGUMENT::EARLIEST_TIME] = time() - 3600*$num_hours;
  $get_log_entries_message[LOGGING_ARGUMENT::USER_ID] = $user_id;
  //  error_log("GET_LOG_ENTRIES : " . print_r($get_log_entries_message, true));
  $result = put_message($log_url, $get_log_entries_message);
  return $result;
}

function get_attribute_for_context($context_type, $context_id)
{
  global $CS_CONTEXT_TYPE_NAME;
  $context_type_as_name = $CS_CONTEXT_TYPE_NAME[$context_type];
  $attribute[$context_type_as_name] = $context_id;
  return $attribute;
}

function get_log_entries_for_context($log_url, $context_type, $context_id, $num_hours=24)
{
  $attribute_sets = 
    array(get_attribute_for_context($context_type, $context_id));

  //  error_log("GLEFC.AS = " . print_r($attribute_sets, true));

  $result = get_log_entries_by_attributes($log_url, $attribute_sets, $num_hours);
  return $result;
}

function get_log_entries_by_attributes($log_url, $attribute_sets, $num_hours=24)
{

  $get_log_entries_message['operation'] = 'get_log_entries_by_attributes';
  $get_log_entries_message[LOGGING_ARGUMENT::EARLIEST_TIME] = time() - 3600*$num_hours;
  $get_log_entries_message[LOGGING_ARGUMENT::ATTRIBUTE_SETS] = $attribute_sets;
  $result = put_message($log_url, $get_log_entries_message);
  return $result;
}

function get_attributes_for_log_entry($log_url, $event_id)
{
  $get_attributes_message['operation'] = 'get_attributes_for_log_entry';
  $get_attributes_message[LOGGING_ARGUMENT::EVENT_ID] = $event_id;
  $result = put_message($log_url, $get_attributes_message);
  return $result;
}

?>
