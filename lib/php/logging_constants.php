<?php

// Constants for defining client and server side of logging service API

class LOGGING_ARGUMENT {
  const EVENT_TIME = 'event_time';
  const USER_ID = 'user_id';
  const CONTEXTS = 'contexts';
  const CONTEXT_TYPE = 'context_type';
  const CONTEXT_ID = 'context_id';
  const MESSAGE = 'message';
}

$LOGGING_TABLENAME = 'logging_entry';
$LOGGING_CONTEXT_TABLENAME = "logging_entry_context";

class LOGGING_TABLE_FIELDNAME {
  const ID = "id";
  const EVENT_TIME = 'event_time';
  const USER_ID = 'user_id';
  const MESSAGE = 'message';
}

class LOGGING_CONTEXT_TABLE_FIELDNAME {
  const ID = "id";
  const CONTEXT_TYPE = 'context_type';
  const CONTEXT_ID = 'context_id';
}

?>