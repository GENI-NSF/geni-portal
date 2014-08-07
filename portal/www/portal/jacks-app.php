<?php
function build_jacks_viewer()
{
	$output = "<script src='jacks-app.js'></script>";
  $output .= "<div id='jacks-status'><p>Starting Jacks...</p></div>";
  $output .= "<div id='jacks-pane' class='jacks'></div>";

  $output .= "<div id='jacks-buttons'>";
  $output .= "</div>";

  return $output;
}
?>