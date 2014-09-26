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

require_once("settings.php");
require_once("user.php");
require_once 'geni_syslog.php';
require_once 'db-util.php';

// Parse XML rspec contained in given file
// Is rspec in given file bound (i.e. does it have any nodes with 
//      component_manager_id tags)?
// Is it for stitching (i.e. does any link have two different 
//      component_manager_id tags)?
// Return array of five values: 
//    parsed_rspec (text), 
//    is_bound (boolean), 
//    is_stitch (boolean),
//    a list of AM URN's (component_manager_id's) of requested nodes
//    is_partially_bound (boolean)
function parseRequestRSpec($rspec_filename)
{

  // Handle case wherein no file provided (for update, not upload)
  if ($rspec_filename == NULL || $rspec_filename == "") {
    return array(NULL, False, False, array(), False);
  }

  $rspec = file_get_contents($rspec_filename);
  return parseRequestRSpecContents($rspec);

}

// Like parseRequestRSpec(), but takes input of XML string rather than filename
function parseRequestRSpecContents($rspec) {
  
  $am_urns = array();
  $is_bound = false;
  $has_unbound_node = false;
  $is_stitch = false;
  
  $dom_document = new DOMDocument();
  $dom_document->loadXML($rspec);
  $root = $dom_document->documentElement;

  // Go over each node and link child
  // If ANY node child has a component_manager_id, is_bound is true
  // If ANY link has two componennt_manager children of 
  //       different names, is_stitch is true
  foreach ($root->childNodes as $child) {
    if ($child->nodeType == XML_TEXT_NODE) { continue; }
    // Pull out 'client_id' and 'component_manager_id' attributes
    if ($child->nodeName == 'node') {
      $node = $child;
      $client_id = NULL;
      if ($node->hasAttribute('client_id')) { 
	$client_id = $node->getAttribute('client_id'); 
      }
      $component_manager_id = NULL;
      if ($node->hasAttribute('component_manager_id')) { 
	$component_manager_id = $node->getAttribute('component_manager_id'); 
	$is_bound = true;
	$am_urns[] = $component_manager_id;
      } else {
	$has_unbound_node = true;
      }
      //      error_log("Node "  . print_r($node, true) . " " . 
      //		print_r($client_id, true) . " " . 
      //		print_r($component_manager_id, true));
    } elseif ($child->nodeName == 'link') {
      $link_urns = array();
      // Extract the client_id attribute and 
      // component_manager_id child name attribute
      $link = $child;
      $link_client_id = NULL;
      if ($link->hasAttribute('client_id')) { 
	$link_client_id = $link->getAttribute('client_id'); 
      }
      //      error_log("Link "  . print_r($link, true) . " " . 
      //		print_r($link_client_id, true));
      foreach($link->childNodes as $link_child) {
	if ($link_child->nodeType == XML_TEXT_NODE) { continue; }
	if ($link_child->nodeName == 'component_manager') {
	  $cm_id = $link_child;
	  $cmid_component_manager_id = NULL;
	  if ($cm_id->hasAttribute('name')) { 
	    $cmid_component_manager_id = $cm_id->getAttribute('name'); 
	    $link_urns[$cmid_component_manager_id] = true;
	  }
	  //	  error_log("   CMID:" . print_r($cm_id, true) . " " . 
	  //		    print_r($cmid_component_manager_id, true));
	} 
      }
      // We have a link with more than 1 different aggregate URN
      if (count(array_keys($link_urns)) > 1) {
	$is_stitch = true;
      }
    }
  }

  $is_partially_bound = ($is_bound and $has_unbound_node);
  return array($rspec, $is_bound, $is_stitch, $am_urns, $is_partially_bound);
}


/**
 * Validate the uploaded RSpec.
 *
 * @return boolean -- true for valid, false if invalid
 */
function validateRSpec($rspec_filename, &$error_msg)
{
  $error = null;

  //--------------------------------------------------
  // is it parseable as XML?
  $rspec = file_get_contents($rspec_filename);
  $xml_parser = xml_parser_create();
  $parse_result = xml_parse($xml_parser, $rspec, true);
  if ($parse_result === 0) {
    $xml_error = xml_error_string(xml_get_error_code($xml_parser));
    $line = xml_get_current_line_number($xml_parser);
    $column = xml_get_current_column_number($xml_parser);
    $error_msg = "$xml_error at line $line, column $column";
    xml_parser_free($xml_parser);
    return false;
  }
  /* TODO: do some more checks on the rspec.
   * Does it pass "rspeclint"?
   * Is it a request RSpec (not ad or manifest)?
   */
  return true;
}
