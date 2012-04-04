<?php

require_once("message_handler.php");

function create_slice($args)
{
  $slice_name = $args['slice_name'];
  error_log("created $slice_name");
  return "created $slice_name";
}

function create_slice_credential($args)
{
  // *** WRITE ME
}

handle_message("SA");

?>