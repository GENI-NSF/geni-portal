<?php

require_once("smime.php");
require_once("message_handler.php");

function create_slice($args)
{
  $slice_name = $args['slice_name'];
  error_log("created $slice_name");
  return "created $slice_name";
}

handle_message("SA");

?>