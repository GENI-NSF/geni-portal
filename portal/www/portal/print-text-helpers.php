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
?>
<?php
require_once("header.php");
require_once( "am_map.php");

function print_agg_list( $list ){
  print_list( $list, $use_nickname=True );
}


function print_list( $list, $use_nickname=False ){
  $num_items = count($list);
  print "<ul class='list'>";
  if (count($list) == 0) {
    echo "<li><i>No aggregates.</i></li>\n";    
  } else {
    foreach ($list as $item) {
      if ($use_nickname) {
	$name=am_name($item);
      } else {
	$name = $item;
      }
      echo "<li>$name</li>\n";
    }
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

function print_rspec_pretty_ORIG( $xml ){
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


function get_name_from_urn( $urn ){
  $urn_pieces = explode( "+", $urn );
  $name = end($urn_pieces);
  return $name;
}
function print_rspec_pretty( $xml ){
  $err_str = "<p><i>Rspec returned was not valid XML.</i></p>";
  try {
    $rspec = new SimpleXMLElement($xml);
    if (!$rspec) {
      error_log("Call to print_rspec_pretty() FAILED");
      echo $err_str;
      return ;
    }
  } catch (Exception $e) {
      error_log("Call to print_rspec_pretty() FAILED");
      echo $err_str;
      return;
  }

  $rspec->registerXPathNamespace("def", "http://www.geni.net/resources/rspec/3/manifest.xsd");
  print "<div class='xml'>";
  $nodes = $rspec->node;
  $links = $rspec->link;
  $num_nodes = $nodes->count();
  $num_links = $links->count();

  $nodes_text = "<b>".$num_nodes."</b> node";
  if ($num_nodes!=1) {
    $nodes_text = $nodes_text."s";
  }
  $links_text = "<b>".$num_links."</b> link";
  if ($num_links!=1) {
    $links_text = $links_text."s";
  }
  echo "<p>There are ",$nodes_text," and ",$links_text," at this aggregate.</p>";
  
  $node_num = 1;
  foreach ($nodes as $node) {

    $num_ifs = $node->interface->count();
    echo "<b>Node #",$node_num,"</b>";
    $node_num = $node_num+1;
    echo "<table><tr>\n";
    echo "<th>Client ID</th>\n";
    echo "<th>Component ID</th>\n";
    echo "<th>Exclusive</th>\n";
    echo "<th>Type</th>\n";
    echo "<th>Hostname</th>\n";
    echo "</tr>\n";
    /* echo "<tr>\n"; */
    /* echo "<th colspan='2'>Node</th>"; */
    /* echo "<th>Exclusive</th>\n"; */
    /* echo "</tr>\n"; */
    echo "<tr>\n"; 
    echo "<td>",$node['client_id'],"</td>\n";
    $comp_id = $node['component_id'];
    $comp_name = get_name_from_urn($comp_id);
    echo "<td>",$comp_name,"</td>";
    if (strtolower($node['exclusive'])=="true"){
      $exclusive = "exclusive";
    } else {
      $exclusive = "not exclusive";
    }
    echo "<td>",$exclusive,"</td>";
    $sliver_type=$node->sliver_type;
    $host=$node->host;
    $services=$node->services;
    $login=$services->login;
    if ($sliver_type){
      echo "<td>",$sliver_type['name'],"</td>\n";
    }
    if ($host){
      echo "<td>",$host['name'],"</td>\n";
    }
    echo "</tr>\n";
    echo "<tr>\n";    
    if ($login){
      echo "<th colspan='2'>Login</th>\n";
      echo "<td colspan='3'>ssh ", $login['username'],"@",$login['hostname'];
      if ($login['port'] and !$login['port']==22 and !$login['port']=="22"){
	echo " -p ", $login['port'];
      }
      echo "</td>\n";
    }
    echo "</tr>\n";

    $interfaces = $node->interface;
    /* Add interface header if relevant */
    if ($interfaces->count() > 0) {
      echo "<tr>\n";
      echo "<th colspan='2'>Interfaces</th>";
      echo "<th colspan='2'>MAC</th>\n";
      echo "<th>Layer 3</th>\n";
      echo "</tr>\n";
    }
    foreach ($interfaces as $interface){
      $comp_id = $interface['component_id'];
      $comp_name = get_name_from_urn($comp_id);
      echo "<tr>\n";
      echo "<td>",$interface['client_id'],"</td>";
      echo "<td>",$comp_name,"</td>";
      echo "<td colspan='2'>",$interface['mac_address'],"</td>";
      foreach ($interface as $ip){
	if ($ip->getName() == "ip") {
	  echo "<td>",$ip['type'],": ",$ip['address'],"</td>";
	}
      }
      echo "</tr>\n";
    }
    echo "</table>\n";
    print "\n";
  }
  
  $link_num = 1;
  foreach ($links as $link) {
    echo "<b>Link #",$link_num,"</b>";
    $link_num = $link_num+1;
    echo "<table><tr>\n";
    $num=0;
    $num_endpts = $link->interface_ref->count();
    echo "<th>Client ID</th>\n";
    while ($num < $num_endpts) {
      echo "<th>Endpoint #",$num,"</th>\n";
      $num = $num + 1;
    }
    echo "</tr>\n";
    echo "<tr>\n";
    echo "<td>",$link['client_id'],"</td>\n";
    $interface_refs = $link->interface_ref;
    foreach ($interface_refs as $interface_ref) {
      echo "<td>",$interface_ref['client_id'],"</td>";
      /* $comp_id = $interface_ref['component_id']; */
      /* $comp_name = get_name_from_urn($comp_id); */
      /* echo "<td>Component ID: ",$comp_name,"</td>"; */
    }
    print "</tr></table>\n";
    
    /* foreach ($link as $property) { */
    /* 	if ($property->getName() == "property") { */
    /* 	  echo "<li><b>",$property['source_id']," --> ",$property['dest_id'],"</b></li>"; */
    /* 	  //	  echo "<li><b>Source ID: </b>",$property['source_id'],"</li>"; */
    /* 	  //	  echo "<li><b>Destination ID: </b>",$property['dest_id'],"</li>"; */
    /* 	  print "<ul>\n"; */
    /* 	  echo "<li>Capacity: ",$property['capacity'],"</li>"; */
    /* 	  echo "<li>Latency: ",$property['latency'],"</li>"; */
    /* 	  echo "<li>Packet Loss: ",$property['packet_loss'],"</li>"; */
    /* 	  print "</ul>\n"; */
    /* 	} */
    /* } */
    /* print "</ul>\n"; */
  }
  print "</div>\n";
}

function print_rspec( $obj, $pretty ) {
  $args = array_keys( $obj );
  foreach ($args as $arg){

    $pattern = "/[\'\"]([^,]*)[\'\"]/";
    $matches = array();
    preg_match($pattern, $arg, $matches);
    $arg_urn = $matches[0];
    $arg_url = $matches[1];
    $arg_name = am_name($arg_url);
    $xml = $obj[$arg];
    print "<div class='aggregate'>Aggregate <b>".$arg_name."'s</b> Resources:</div>";
    print "<div class='resources'>";
    if ($pretty){
      /* Parsed into a table */
      print_rspec_pretty($xml);
    } else {
      /* As plain XML */
      print_xml($xml);
    }
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