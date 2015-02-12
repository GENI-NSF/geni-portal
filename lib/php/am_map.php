<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2015 Raytheon BBN Technologies
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

require_once('sr_constants.php');
require_once('sr_client.php');

function create_am_map() {
  global $am_mapping;
  global $am_mapping_id;
  global $am_mapping_urn;
  global $am_mapping_name_by_urn;
  $am_mapping=array();
  $am_mapping_id=array();
  $all_aggs = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
  foreach ($all_aggs as $agg) {
    $aggid = $agg['id'];
    $aggurl = $agg[SR_TABLE_FIELDNAME::SERVICE_URL];
    $aggurn = $agg[SR_TABLE_FIELDNAME::SERVICE_URN];
    $aggname = $agg[SR_TABLE_FIELDNAME::SERVICE_NAME];
    $aggdesc = $agg[SR_TABLE_FIELDNAME::SERVICE_DESCRIPTION];
    $am_mapping[ $aggurl ] = $aggname;
    $am_mapping_id[ $aggurl ] = $aggid;
    $am_mapping_urn[ $aggurl ] = $aggurn;
    $am_mapping_name_by_urn[ $aggurn ] = $aggname;
  }
}

create_am_map();

function am_name( $aggurl ) {
  global $am_mapping;
  if (array_key_exists($aggurl, $am_mapping)) {
    return $am_mapping[ $aggurl ];
  } else {
    error_log("No AM name: Unknown AM URL $aggurl");
    return $aggurl;
  }
}

function am_name_from_urn( $aggurn ) {
  global $am_mapping_name_by_urn;
  if (array_key_exists($aggurn, $am_mapping_name_by_urn)) {
    return $am_mapping_name_by_urn[$aggurn];
  } else {
    error_log("No AM name: Unknown AM URN $aggurn");
    return $aggurn;
  }
}

function am_id( $aggurl ) {
  global $am_mapping_id;
  if (array_key_exists($aggurl, $am_mapping_id)) {
    return $am_mapping_id[ $aggurl ];
  } else {
    error_log("No AM id: Unknown AM URL $aggurl");
    return $aggurl;
  }
}

function am_urn( $aggurl ) {
  global $am_mapping_urn;
  if (array_key_exists($aggurl, $am_mapping_urn)) {
    return $am_mapping_urn[ $aggurl ];
  } else {
    error_log("No AM urn: Unknown AM URL $aggurl");
    return "";
  }
}

?>
