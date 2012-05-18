<?php
//----------------------------------------------------------------------
// Copyright (c) 2011 Raytheon BBN Technologies
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
?>
<?php
require_once("header.php");

function print_xml( $xml ){
  $xml2 = explode("\n",$xml);
  foreach ($xml2 as $line_num => $line) {
    echo htmlspecialchars($line) . "<br />\n";
  }
}

function print_rspec( $obj ) {
  $args = array_keys( $obj );
  foreach ($args as $arg){
    $arg_urn = $arg[0];
    $arg_url = $arg[1];
    $xml = $obj[$arg];
    print "<div class='aggregate'>Aggregate <b>".$arg."'s</b> Resources:</div>";
    print "<div class='resources'><div class='xml'>";
    print_xml($xml);
    print "</div></div>\n";

  }
  print "</table>";
}



function print_return( $obj, $topLevel ) {
  if (!$topLevel){
    print "<ul>";
  }
  $keys = array_keys( $obj );
  foreach ($keys as $key){
    $value = $obj[$key];
    if ($topLevel){
      print "<li><b>Aggregate</b>: ".$key."</li>";      
    } elseif (!is_integer($key) and !is_array($value)){
      print "<li><b>".$key."</b>: ".$value."</li>";
    } elseif (!is_integer($key)){
      print "<li><b>".$key."</b></li>";
    }
    if (is_array($value)){
//      print "This is an array!\n";
      print_return( $value, False );
    }
  }
  if (!$topLevel){
    print "</ul>";
  }
}

?>