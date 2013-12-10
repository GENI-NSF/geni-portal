<?php
//----------------------------------------------------------------------
// Copyright (c) 2013 Raytheon BBN Technologies
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

require_once("user.php");
require_once("sr_client.php");
require_once('util.php');
//$user = geni_loadUser();

$openflow_ns = "http://www.geni.net/resources/rspec/ext/openflow/3";

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
  var $type;
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
  public static $am_services;

  private static function mgr_name($mgr_id) {
    $mgr_name = NULL;
    /* Prefer a URN match in the service registry. */
    foreach(GENIResource::$am_services as $am_service) {

      // if it happens to be in SR, use it
      //echo "<p>";echo $am_service[SR_TABLE_FIELDNAME::SERVICE_URN]; echo "<br>";
      //echo $type_value->attributes()->component_manager_id; echo "</p>";
      if ($am_service[SR_TABLE_FIELDNAME::SERVICE_URN] == $mgr_id) {
        $mgr_name = $am_service[SR_TABLE_FIELDNAME::SERVICE_NAME];
      }
    }
    if (is_null($mgr_name)) {
      $count = preg_match("/IDN\+(.*)\+authority/", $mgr_id, $matches);
      if ($count === FALSE) {
        // An error occurred.
      } else if ($count > 0) {
        $mgr_name = $matches[1];
      }
    }
    return $mgr_name;
  }

  public static function parse_datapath($type, $type_value) {
    global $openflow_ns;
    $node = new GENIResource();
    $mgr_id = (string) $type_value->attributes()->component_manager_id;
    // Is it ever not a string?
    $node->am = GENIResource::mgr_name($mgr_id);

    /* determine what type of resource it is
       switch: anything with 'procurve', 'cisco', 'switch' in URN
    */
    $component_id = (string) $type_value->attributes()->component_id;
    $node->type = 'OpenFlow datapath';
    $node->am_id = $mgr_id;
    $node->name = (string)$type_value->attributes()->dpid;
    $node->id = $component_id;

    foreach ($type_value->children($openflow_ns) as $child) {
      if ($child->getName() == 'location') {
        $attrs = $child->attributes();
        foreach ($child->attributes() as $attr => $value) {
          switch ((string)$attr) {
          case 'latitude':
            $node->latitude = $value;
          case 'longitude':
            $node->longitude = $value;
          }
        }
      }
    }
    if (! $node->longitude) {
      // FIXME: fix what happens when no location is specified
      $node->latitude = "0";
      $node->longitude = "0";
    }
    return $node;
  }
}


// array of all GENI resources by node
$resources_by_node = array();

// directory
$directory = "../common/map/";

// list of AM rspecs to check
/*
 * The filenames below correspond to omni output from
 *
 *        omni.py -o listresources
 *
 */
$files = array(
  $directory . 'rspec-emulab-net.xml',
  $directory . 'rspec-exogeni-net-bbnvmsite.xml',
  $directory . 'rspec-exogeni-net-fiuvmsite.xml',
  $directory . 'rspec-exogeni-net-rcivmsite.xml',
  $directory . 'rspec-exogeni-net-uhvmsite.xml',
  $directory . 'rspec-exogeni-net.xml',
  $directory . 'rspec-geni-kettering-edu.xml',
  $directory . 'rspec-genirack-nyu-edu.xml',
  $directory . 'rspec-instageni-clemson-edu.xml',
  $directory . 'rspec-instageni-gpolab-bbn-com.xml',
  $directory . 'rspec-instageni-illinois-edu.xml',
  $directory . 'rspec-instageni-maxgigapop-net.xml',
  $directory . 'rspec-instageni-northwestern-edu.xml',
  $directory . 'rspec-instageni-nysernet-org.xml',
  $directory . 'rspec-instageni-rnet-missouri-edu.xml',
  $directory . 'rspec-instageni-rnoc-gatech-edu.xml',
  $directory . 'rspec-instageni-wisc-edu.xml',
  $directory . 'rspec-lan-sdn-uky-edu.xml',
  $directory . 'rspec-openflow-foam-bbn-hn-exogeni-gpolab-bbn-com.xml',
  $directory . 'rspec-openflow-foam-foam-geni-kettering-edu.xml',
  $directory . 'rspec-openflow-foam-foam-genirack-nyu-edu.xml',
  $directory . 'rspec-openflow-foam-foam-instageni-clemson-edu.xml',
  $directory . 'rspec-openflow-foam-foam-instageni-gpolab-bbn-com.xml',
  $directory . 'rspec-openflow-foam-foam-instageni-illinois-edu.xml',
  $directory . 'rspec-openflow-foam-foam-instageni-maxgigapop-net.xml',
  $directory . 'rspec-openflow-foam-foam-instageni-northwestern-edu.xml',
  $directory . 'rspec-openflow-foam-foam-instageni-nysernet-org.xml',
  $directory . 'rspec-openflow-foam-foam-instageni-rnet-missouri-edu.xml',
  $directory . 'rspec-openflow-foam-foam-instageni-wisc-edu.xml',
  $directory . 'rspec-openflow-foam-foam-instageni-rnoc-gatech-edu.xml',
  $directory . 'rspec-openflow-foam-foam-lan-sdn-uky-edu.xml',
  $directory . 'rspec-openflow-foam-foam-nysernet-org.xml',
  $directory . 'rspec-openflow-foam-foam-sox-net.xml',
  $directory . 'rspec-openflow-foam-foam-utahddc-geniracks-net.xml',
  $directory . 'rspec-openflow-foam-ig-utah.xml',
  $directory . 'rspec-openflow-foam-rci-hn-exogeni-gpolab-bbn-com.xml',
  $directory . 'rspec-uky-emulab-net.xml',
  $directory . 'rspec-utah-geniracks-net.xml',
  $directory . 'rspec-utahddc-geniracks-net.xml'
);

// list of AMs from service registry
// use these to get pretty names of AMs if they exist
$am_services = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
GENIResource::$am_services = $am_services;

//var_dump($am_services);

foreach($files as $file) {
  $xml = simplexml_load_file($file);
  if ($xml === FALSE) {
    error_log("Error parsing advertisement rspec $file");
    continue;
  }

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
      
      /* determine what type of resource it is
          switch: anything with 'procurve', 'cisco', 'switch' in URN
      */
      if(strpos((string)$type_value->attributes()->component_id, 'pc') !== false
        || strpos((string)$type_value->attributes()->component_id, 'pg') !== false
      ) {
        $node->type = 'pc';
      }
      else if(strpos((string)$type_value->attributes()->component_id, 'procurve') !== false
        || strpos((string)$type_value->attributes()->component_id, 'cisco') !== false
      ) {
        $node->type = 'switch';
      }
      else {
        $node->type = 'unknown';
      }
      
      $node->am_id = (string)$type_value->attributes()->component_manager_id;
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

  $of_children = $xml->children($openflow_ns);
  if (count($of_children) > 0) {
    foreach ($of_children as $type => $type_value) {
      $node = GENIResource::parse_datapath($type, $type_value);
      if ($node) {
        $resources_by_node[] = $node;
      }
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
  $feature->properties->type = $resource->type;
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
