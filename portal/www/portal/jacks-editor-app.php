<?php
function build_jacks_editor()
{
  $output = "<script src='jacks-editor-app.js'></script>";
  $output .= "<div id='jacks-editor-status'><p>Starting Jacks Editor...</p></div>";
  $output .= "<div id='jacks-editor-pane' class='jacks'></div>";

  $output .= "<div id='jacks-editor-buttons'>";
  $output .= "</div>";

  return $output;
}
?>