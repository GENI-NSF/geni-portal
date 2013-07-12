<?php

require_once("user.php");
require_once("sr_client.php");
require_once('util.php');
//$user = geni_loadUser();

class Feature {
  var $type;
  var $properties;
  var $geometry;
}

class Properties {
  var $component_id;
  var $resources;
  var $am;
  var $am_id;
}

class Geometry {
  var $type;
  var $coordinates = array();
}

class GENIResource {
  var $am;
  var $am_id;
  var $type;
  var $name;
  var $id;
  var $latitude;
  var $longitude;
}

// array of all GENI resources by node
$resources_by_node = array();

// directory
$directory = "../common/map/";

// list of AM rspecs to check
$files = array(
  $directory . 'exogeni.net.am.rspec',
  $directory . 'exogeni.net.bbnvmsite.am.rspec',
  $directory . 'exogeni.net.rcivmsite.am.rspec',
  $directory . 'geni.kettering.edu.cm.rspec',
  $directory . 'instageni.gpolab.bbn.com.cm.rspec',
  $directory . 'instageni.northwestern.edu.cm.rspec',
  $directory . 'lan.sdn.uky.edu.cm.rspec',
  $directory . 'uky.emulab.net.cm.rspec',
  $directory . 'utah.geniracks.net.cm.rspec',
  $directory . 'emulab.net.cm.rspec'
);

// list of AMs from service registry
// use these to get pretty names of AMs if they exist
$am_services = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
//var_dump($am_services);

foreach($files as $file) {

  $xml = simplexml_load_file($file);

  foreach($xml as $type => $type_value) {

    if($type == 'node') {

      $node = new GENIResource;
      
      // determine AM's name
      // default: regex the AM URN to get important information
      $am = (string)$type_value->attributes()->component_manager_id;
      preg_match("/IDN\+(.*)\+authority/", $am, $matches);
      $node->am = $matches[1];
      // better source of information: service registry
      foreach($am_services as $am_service) {
        // if it happens to be in SR, use it
        
        //echo "<p>";echo $am_service[SR_TABLE_FIELDNAME::SERVICE_URN]; echo "<br>";
        //echo $type_value->attributes()->component_manager_id; echo "</p>";
        
        if($am_service[SR_TABLE_FIELDNAME::SERVICE_URN] == $type_value->attributes()->component_manager_id) {
          $node->am = $am_service[SR_TABLE_FIELDNAME::SERVICE_NAME];
        }
      }
      
      $node->am_id = (string)$type_value->attributes()->component_manager_id;
      $node->type = $type;
      $node->name = (string)$type_value->attributes()->component_name;
      $node->id = (string)$type_value->attributes()->component_id;
      $node->latitude = (string)$type_value->location["latitude"];
      $node->longitude = (string)$type_value->location["longitude"];
      
      if($node->longitude == "") {
        // FIXME: fix what happens when no location is specified
        $node->latitude = "0";
        $node->longitude = "0";
        
      }
      
      $resources_by_node[] = $node;
    
    }

  }

}






// create JSON string of AMs and echo it

$json_array = array();
$json_array["type"] = "FeatureCollection";
$json_array["features"] = array();


foreach($resources_by_node as $resource) {

  $feature = new Feature;
  $feature->type = "Feature";
  $feature->properties = new Properties;
  $feature->properties->component_id = $resource->name;
  $feature->properties->am = $resource->am;
  $feature->properties->am_id = $resource->am_id;
  $feature->properties->resources = 1;
  $feature->geometry = new Geometry;
  $feature->geometry->type = "Point";
  $feature->geometry->coordinates[] = (float) $resource->longitude;
  $feature->geometry->coordinates[] = (float) $resource->latitude;

  $json_array["features"][] = $feature;


}

$json = json_encode($json_array);

echo "$json";




/*
echo "<p>Number of nodes: $number_nodes<br>Number of nodes without location: $number_nodes_no_location</p>";

echo "<pre>";
var_dump($resources_by_node);
echo "</pre>";
*/


?>
