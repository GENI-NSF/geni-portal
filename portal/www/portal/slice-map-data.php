<?php
//----------------------------------------------------------------------
// Copyright (c) 2011-2015 Raytheon BBN Technologies
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

// Generate and return JSON structure representing the nodes/links of 
// resources allocated to a given slice
//
// Format: slice-map-data.php?slice_id=...
//

?>

<?php

require_once('user.php');
require_once('header.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once("sa_constants.php");
require_once("sa_client.php");
require_once("am_client.php");
require_once('json_util.php');

function lookup_component_info($agg_urn, $component_name, 
			       $component_id,
			       $current_resource_map_data)
{
#  error_log("AGG_URN = " . $agg_urn . " CN = " . $component_name . 
#	    " CID = " . $component_id);
  $componnent_info = NULL;
  foreach($current_resource_map_data['features'] as $feature) {
    $am_id = $feature['properties']['am_id'];
    $am = $feature['properties']['am'];
    $comp_id = $feature['properties']['component_id'];
    $match = false;

    # Usual match: Match by AM URN and component name
    if($am_id == $agg_urn && $comp_id == $component_name) {
      $match = true;
    }

    # Case where AM does not advertise component IDs: match on am_id
    if(!$match) {
      if(($component_id == "") || ($component_id == NULL)) {
	if ($am_id == $agg_urn) {
	  $match = true;
	}
      }
    }

    # Match on PC by constructing component_id from am_id and comp_id
    # E.g. if am_id => urn:publicid:IDN+utahddc.geniracks.net+authority+cm
    #      and comp_id => pc16
    #  then match  component_id against 
    #         urn:publicid:IDN+utahddc.geniracks.net+node+pc16
    if(!$match) {
      $authority_suffix = implode("+", array_slice(explode("+", $am_id), 0, 2));
      $node_name = $authority_suffix . "+node+" . $comp_id;
#      error_log("AUTH_SUFFIX = " . $authority_suffix);
#      error_log("NODE_NAME = " . $node_name . " CID = " . $component_id);
      if ($node_name == $component_id) {
	$match = true;
      }
    }

    if ($match) {
      $coords = $feature['geometry']['coordinates'];
      $x = $coords[0];
      $y = $coords[1];
      $componnent_info = array('am_name' => $am, 'x' => $x, 'y' => $y);
      break;
    }
  }

  return $componnent_info;
}

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
unset($slice);
include("tool-lookupids.php");

# error_log("SLICE_ID = " . $slice_id);

if (isset($slice)) {
  $slice_urn = $slice[SA_ARGUMENT::SLICE_URN];
}
#error_log("SLICE_URN = " . $slice_urn);

if (! isset($sa_url)) {
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
}

# Need to get the sliver info for the slice
# For each sliver of the slice, all we really need is 
# 1. The LAT/LONG of the AM
#    Take the sliver URN => authority URN => LAT/LONG in common.json
# 2. The name of the AM 
#    Take from service_registry (service_urn => service_name)
# 3. The name of the sliver
#    Need to ask for manifest at each AM
# Then we need to go through all the slivers and find their AMs.
# Then find the lat/long of that aggregte
# And add to the list of features

# Get the current resource geo information (we parse this regularly from Ads)
$current_resource_map_data_raw = file_get_contents("/var/www/common/map/current.json");
$current_resource_map_data = json_decode($current_resource_map_data_raw, true);

# Get the sliver info for this slice
$slivers = lookup_sliver_info_by_slice($sa_url, $user, $slice_urn);
# error_log("SLIVERS = " . print_r($slivers, true));

# Gather all aggregates at which this slice has resources
$all_aggs = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
$all_slice_aggs = array();
foreach($slivers as $sliver) {
  $aggregate_urn = $sliver[SA_SLIVER_INFO_TABLE_FIELDNAME::SLIVER_INFO_AGGREGATE_URN];
  $all_slice_aggs[$aggregate_urn] = "";
}

# Lookup the URL for each AGG URN
# error_log("SLICE_AGGS = " . print_r($all_slice_aggs, true));
foreach(array_keys($all_slice_aggs) as $slice_agg_urn) {
  foreach($all_aggs as $agg) {
    $agg_urn = $agg[SR_TABLE_FIELDNAME::SERVICE_URN];
    if ($agg_urn == $slice_agg_urn) {
      $agg_url = $agg[SR_TABLE_FIELDNAME::SERVICE_URL];
      $all_slice_aggs[$agg_urn] = $agg_url;
      break;
    }
  }
}
# error_log("SLICE_AGGS = " . print_r($all_slice_aggs, true));

# Get the manifests for this slice at each aggregate in the sliver info
$manifests = array();
foreach($all_slice_aggs as $agg_urn => $agg_url) {
#  error_log("URN " . $agg_urn . " URL " . $agg_url);
  $slice_cred = get_slice_credential($sa_url, $user, $slice_id);
#  error_log("SC = " . $slice_cred);
  $raw_output = list_resources_on_slice($agg_url, $user, $slice_cred, $slice_urn);
  $output = $raw_output[1][$agg_url];
#  error_log("OUTPUT = " . print_r($output, true));
  if(array_key_exists('code', $output) &&
     array_key_exists('value', $output) &&
     array_key_exists('geni_code', $output['code']) &&
     $output['code']['geni_code'] == 0) {
    $manifest = $output['value'];
    $manifests[$agg_urn] = $manifest;
  }
}
# error_log("MANIFESTS = " . print_r($manifests, true));

# Now we have the manifests and the mapping of component_ids to lat/long
# We can create our graph info

$node_features = array();
$link_features = array();

$component_info_per_interface = array();

# First pull all the nodes out of the manifests
foreach($manifests as $slice_agg_urn => $manifest) {
  $dom_document = new DOMDocument();
  $dom_document->loadXML($manifest);
  $nodes = $dom_document->getElementsByTagName('node');
  foreach($nodes as $node) {
    $client_id = NULL;
    if ($node->hasAttribute('client_id')) {
      $client_id = $node->getAttribute('client_id');
    }
    $component_id = NULL;
    if ($node->hasAttribute('component_id')) {
      $component_id = $node->getAttribute('component_id');
    }
    $component_manager_id = NULL;
    if ($node->hasAttribute('component_manager_id')) {
      $component_manager_id = $node->getAttribute('component_manager_id');
    }
    $component_name = NULL;
    if ($node->hasAttribute('component_name')) {
      $component_name = $node->getAttribute('component_name');
    }
    # Also available:
    # exclusive
    # sliver_id

      //    error_log("AGG_URN " . $slice_agg_urn . " CLIENT_ID " . $client_id . 
      //	      " COMPONENT_NAME " . $component_name .
      //	      " COMPONENT_ID " . $component_id .
      //	      " CMID " . $component_manager_id);
    if ($component_manager_id != $slice_agg_urn)  continue; 

    $component_info = lookup_component_info($slice_agg_urn, 
					    $component_name,
					    $component_id, 
					    $current_resource_map_data);
#    error_log("COMP_INFO = " . print_r($component_info, true));
  
    if($component_info != NULL) {

      $interfaces = $node->getElementsByTagName("interface");
      foreach($interfaces as $interface) {
	$if_name = $interface->getAttribute('client_id');
	$component_info_per_interface[$if_name] = $component_info;
      }

      $am_name = $component_info['am_name'];
      $x = $component_info['x'];
      $y = $component_info['y'];
      $node_feature = array(
			    "geometry" => array("type" => "Point",
						"coordinates" => array($x, $y)),
			    "type" => "Feature",
			    "properties" => array(
						  "type" => "Node",
						  "am_id" => $slice_agg_urn,
						  "am" => $am_name,
						  "resources" => 1,
						  "name" => $client_id,
						  "component_id" => $component_name
						  ));
      $node_features[] =  $node_feature;
    }
  }
}


// error_log("CIPI = " . print_r($component_info_per_interface, true));

# Now go through the links
foreach($manifests as $slice_agg_urn => $manifest) {
  $dom_document = new DOMDocument();
  $dom_document->loadXML($manifest);
  $links = $dom_document->getElementsByTagName('link');
  foreach($links as $link) {
    $link_points = array();
    $link_id = $link->getAttribute('client_id');
    $iface_refs = $link->getElementsByTagName('interface_ref');
    $interfaces = array();
    foreach($iface_refs as $iface_ref) {
      $iface_name = $iface_ref->getAttribute('client_id');
      $interfaces[] = $iface_name;
      if(array_key_exists($iface_name, $component_info_per_interface)) {
	$component_info = $component_info_per_interface[$iface_name];
	$am_name = $component_info['am_name'];
	$x = $component_info['x'];
	$y = $component_info['y'];
	$link_points[] = array($x, $y);
      }
    }
    $link_feature = array(
			  "geometry" => array("type" => "LineString",
					      "coordinates" => $link_points),
			  "type" => "Feature",
			  "properties" => array("type" => "Link",
						"name" => $link_id,
						"interfaces" => $interfaces));
    //    error_log("C(LF) = " . count($link_points) . " " . print_r($link_feature, true));

    $link_features[] = $link_feature;
    
  }
}

# Above we need to keep a mapping of interfaces to coordinates
# Then we go through the links and get the interface refs, lookup 
# the interfaces and corresponding coordinates
# and add a line segment (OpenLayers.Geometry.LineString) between these
# points
# 
# Eventually, try to avoid drawing the same line twice(A -> B, B -> A) 

  //error_log("NODE_FEATURES = " . print_r($node_features, true));
  //error_log("LINK_FEATURES = " . print_r($link_features, true));

$slice_info = array(
		    "type" => "FeatureCollection",
		    "features" => array_merge($node_features, $link_features)
		    );




header("Cache-Control: public");
header("Content-Type: application/json");

$slice_info_json = json_encode($slice_info);
print json_indent($slice_info_json);

?>
