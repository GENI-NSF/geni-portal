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

function print_list( $list ){
  $list2 = explode("\n",$list);
  $num_items = count($list);
  print "<ul class='list'>";
  $i=0;
  while ($i < $num_items) {
    echo "<li>". $list[$i] . "</li>\n";
    $i = $i+1;
  }
  if ($num_items == 0) {
    echo "<li><i>No aggregates.</i></li>\n";    
  }
  print "</ul>\n";
}


function print_xml( $xml ){
  $xml2 = explode("\n",$xml);
  print "<div class='xml'>";
  foreach ($xml2 as $line_num => $line) {
    echo htmlspecialchars($line) . "<br />\n";
  }
  print "</div>\n";
}

function print_rspec_pretty( $xml ){
  $rspec = new SimpleXMLElement($xml);
  // if ($rspec == False) {
  //  return; 
  // }
  print "<div class='xml'>";
  print "<ul>\n";
  foreach ($rspec as $node) {
    if ($node->getName() == "node") {
      echo "<li><b>Node: </b>",$node['client_id'],"</li>";
      print "<ul>\n";
      echo "<li>Exclusive: ",$node['exclusive'],"</li>";
      echo "<li>Component ID: ",$node['component_id'],"</li>";
      foreach ($node as $interface){
	if ($interface->getName() == "interface") {
	  echo "<li><b>Interface: </b></li>";
	  print "<ul>\n";
	  echo "<li><b>Client ID: </b>",$interface['client_id'],"</li>";
	  echo "<li>Component ID: ",$interface['component_id'],"</li>";
	  echo "<li>MAC Address: ",$interface['mac_address'],"</li>";
	  foreach ($interface as $ip){
	    if ($ip->getName() == "ip") {
	      print "<ul>\n";
	      echo "<li>Type: ",$ip['type'],"</li>";
	      echo "<li>IP: ",$ip['address'],"</li>";
	      print "</ul>\n";
	    }
	  }
	  print "</ul>\n";
	}
      }
      print "</ul>\n";
      print "\n";
    }
  }

  foreach ($rspec as $link) {
    if ($link->getName() == "link") {
      echo "<li><b>Link: </b>",$node['client_id'],"</li>";
      print "\n";
      print "<ul>\n";
      foreach ($link as $interface_ref) {
	if ($interface_ref->getName() == "interface_ref") {
	  echo "<li><b>Interface Ref </b></li>";
	  print "<ul>\n";
	  echo "<li><b>Client ID: </b>",$interface_ref['client_id'],"</li>";
	  echo "<li>Component ID: ",$interface_ref['component_id'],"</li>";
	  print "</ul>\n";
	}
      }
      foreach ($link as $property) {
	if ($property->getName() == "property") {
	  echo "<li><b>",$property['source_id']," --> ",$property['dest_id'],"</b></li>";
	  //	  echo "<li><b>Source ID: </b>",$property['source_id'],"</li>";
	  //	  echo "<li><b>Destination ID: </b>",$property['dest_id'],"</li>";
	  print "<ul>\n";
	  echo "<li>Capacity: ",$property['capacity'],"</li>";
	  echo "<li>Latency: ",$property['latency'],"</li>";
	  echo "<li>Packet Loss: ",$property['packet_loss'],"</li>";
	  print "</ul>\n";
	}
      }
      print "</ul>\n";
    }
  }
  print "</ul>\n";
  print "</div>\n";
}

function print_rspec( $obj ) {
  $args = array_keys( $obj );
  foreach ($args as $arg){
    $arg_urn = $arg[0];
    $arg_url = $arg[1];
    $xml = $obj[$arg];
    print "<div class='aggregate'>Aggregate <b>".$arg."'s</b> Resources:</div>";
    print "<div class='resources'>";
    //    print_xml($xml);
    print_rspec_pretty($xml);
    print "</div>\n";
  }
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