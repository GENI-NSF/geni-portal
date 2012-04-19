<?php

require_once('logging_constants.php');
require_once('user.php');

// Client side services for Logging of events within GENI clearinghouse

// Log an event to the logging service
// Event consists of 
//    user_id (the writer of the log entry)
//    message - Text of log message
//    contexts - List of context_type/context_id pairs by which to index message
function log_event($log_url, $message, $contexts )
{
  $log_event_message['operation'] = 'log_event';
  $log_event_message[LOGGING_ARGUMENT::EVENT_TIME] = time();
  $user = geni_loadUser();
  $log_event_message[LOGGING_ARGUMENT::USER_ID] = $user->account_id;
  $log_event_message[LOGGING_ARGUMENT::MESSAGE] = $message;
  $log_event_message[LOGGING_ARGUMENT::CONTEXTS] = $contexts;
  //  error_log("LOG_EVENT : " . print_r($log_event_message, true));
  $result = put_message($log_url, $log_event_message);
  return $result;
}

function get_log_entries_by_author($log_url, $user_id, $num_hours=24)
{
  $get_log_entries_message['operation'] = 'get_log_entries_by_author';
  $get_log_entries_message[LOGGING_ARGUMENT::EVENT_TIME] = time() - 3600*$num_hours;
  $get_log_entries_message[LOGGING_ARGUMENT::USER_ID] = $user_id;
  //  error_log("GET_LOG_ENTRIES : " . print_r($get_log_entries_message, true));
  $result = put_message($log_url, $get_log_entries_message);
  return $result;
}

function get_log_entries_for_context($log_url, $context_type, $context_id, $num_hours=24)
{
  $get_log_entries_message['operation'] = 'get_log_entries_for_context';
  $get_log_entries_message[LOGGING_ARGUMENT::CONTEXT_TYPE] = $context_type;
  $get_log_entries_message[LOGGING_ARGUMENT::CONTEXT_ID] = $context_id;
  $get_log_entries_message[LOGGING_ARGUMENT::EVENT_TIME] = time() - 3600*$num_hours;
  //  error_log("GET_LOG_ENTRIES : " . print_r($get_log_entries_message, true));
  $result = put_message($log_url, $get_log_entries_message);
  return $result;
}

?>
