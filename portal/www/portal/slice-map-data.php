<?php
//----------------------------------------------------------------------
// Copyright (c) 2011-2014 Raytheon BBN Technologies
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

require_once('header.php');
require_once('json_util.php');

$slice_id = "ea91ad13-88dd-45ee-97a6-c414b002b789";

error_log("SLICE_ID = " . $slice_id);

header("Cache-Control: public");
header("Content-Type: application/json");

$feat1 = array(
	       "geometry" => array(
				   "type" => "Point",
				   "coordinates" => array("-80.0", "40.0")
				   ),
	       "type" => "Feature",
	       "properties" => array("type" => "unknown",
				    "am_id" => "AAA_ID",
				    "am" => "AAA",
				    "resources" => 1,
				     "name" => "node-1",
				    "component_id" => "AAA_CID")
	       );
$feat2 = array(
	       "geometry" => array(
				   "type" => "Point",
				   "coordinates" => array("-80.0", "30.0")
				   ),
	       "type" => "Feature",
	       "properties" => array("type" => "unknown",
				    "am_id" => "BBB_ID",
				    "am" => "BBB",
				    "resources" => 1,
				     "name" => "node-2",
				    "component_id" => "BBB_CID")
	       );
$feat3 = array(
	       "geometry" => array(
				   "type" => "Point",
				   "coordinates" => array("-80.0", "40.0")
				   ),
	       "type" => "Feature",
	       "properties" => array("type" => "unknown",
				    "am_id" => "CCC_ID",
				    "am" => "CCC",
				    "resources" => 1,
				     "name" => "node-3",
				    "component_id" => "CCC_CID")
	       );
$slice_info = array(
		    "type" => "FeatureCollection",
		    "features" => array(
					$feat1,
					$feat2,
					$feat3
					)
		    );

$slice_info_json = json_encode($slice_info);
print json_indent($slice_info_json);

?>
