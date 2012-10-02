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

require_once("header.php");
require_once("settings.php");
require_once("user.php");
require_once("file_utils.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("am_client.php");
require_once("sa_client.php");
require_once("am_map.php");
require_once("json_util.php");

$user = geni_loadUser();
if (! $user->isActive()) {
  relative_redirect('home.php');
}

function no_slice_error() {
  header('HTTP/1.1 404 Not Found');
  print 'No slice id specified.';
  exit();
}

 if (! count($_GET)) {
  // No parameters. Return an error result?
  // For now, return nothing.
  no_slice_error();
}
unset($slice);
include("tool-lookupids.php");
if (! isset($slice)) {
  no_slice_error();
}

if (!$user->isAllowed(SA_ACTION::LOOKUP_SLICE, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
  relative_redirect('home.php');
}

if (array_key_exists("pretty", $_REQUEST)){
  $pretty = $_REQUEST['pretty'];
  if (strtolower($pretty) == "false") {
    $pretty = False;
  } else {
    $pretty = True;
  }
} else {
  $pretty=True;
}

include("query-sliverstatus.php");

function print_sliver_status_err( $msg ) {
  /* Sample input */
  /*  Slice urn:publicid:IDN+sergyar:AMtest+slice+test1 expires on 2012-07-07 18:21:41 UTC
Failed to get SliverStatus on urn:publicid:IDN+sergyar:AMtest+slice+test1 at AM https://localhost:8001/: [Errno 111] Connection refused

Failed to get SliverStatus on urn:publicid:IDN+sergyar:AMtest+slice+test1 at AM https://www.pgeni3.gpolab.bbn.com:12369/protogeni/xmlrpc/am/2.0: No slice or aggregate here
Returned status of slivers on 0 of 2 possible aggregates.
*/

  $succ=array();
  $fail=array();
  $lines = preg_split ('/$\R?^/m', $msg);
  $num_errs = 0;
  $err_array = array();
  foreach ($lines as $line){  
    if (preg_match("/^Returned status of slivers on (\d+) of (\d+) possible aggregates.$/",$line, $succ)){
      $n = (int) $succ[1];
      $m = (int) $succ[2];
    } elseif (preg_match("/^Failed to get SliverStatus on urn\:publicid\:IDN\+(\w+)\:(\w+)\+slice\+(\w+) at AM (.*): (.*)$/",$line,$fail)) {
      $num_errs = $num_errs+1;
      $agg = $fail[4];
      $err = $fail[5];
      $err_array[ $agg ] = $err;
    }
  }

  if ($num_errs>0){
    if ($n === 0) {
      /* No aggregates responded succesfully */
      $hdr = "Checked $m aggregate" . ($m > 1 ? "s" : "") . ", no resources found:";
    } else {
      $hdr = "Checked $num_errs other aggregate" . ($num_errs > 1 ? "s" : "") . ":";
    }
    /* print "<div>Returned status of slivers on ".$n." of ".$m." aggregates.</div>"; */
    print "<div>$hdr</div>";
    print "<table>";
    print "<tr><th>Aggregate</th><th>Message</th></tr>";
    foreach ($err_array as $agg => $err_item){
      $agg_name = am_name($agg);
      print "<tr>";
      print "<td>$agg_name</td>";
      print "<td>$err_item</td>";
      print "</tr>";
    }
    print "</table>";
  }
}

function print_sliver_status( $obj ) {
  print "<table>";
  $args = array_keys( $obj );
  foreach ($args as $arg){
    $arg_obj = $obj[$arg];
    /* ignore aggregates which returned nothing */
    if (!is_array($arg_obj)){
      continue;
    }

    $geni_urn = $arg_obj['geni_urn'];
    $geni_status = $arg_obj['geni_status'];
    $geni_status = strtolower( $geni_status );
    $geni_resources = $arg_obj['geni_resources'];


    print "<tr class='aggregate'><th>Status</th><th colspan='2'>Aggregate</th></tr>";
    print "<tr class='aggregate'><td class='$geni_status'>$geni_status</td>";
    $arg_name = am_name($arg);
    print "<td colspan='2'>$arg_name</td></tr>";
    $firstRow = True;
    $num_rsc = count($geni_resources);
    foreach ($geni_resources as $rsc){
      if ( array_key_exists("pg_manifest", $rsc) ){
	$pg_rsc = $rsc["pg_manifest"];
	$pg_name = $pg_rsc["name"];
	if ($pg_name == "rspec"){
	  continue;
	}
	$pg_attr = $pg_rsc["attributes"];
	$rsc_urn = $pg_attr["client_id"];
      } else {
	$rsc_urn = $rsc['geni_urn'];
      }
      $rsc_status = $rsc['geni_status'];
      $rsc_error = $rsc['geni_error'];
      if ($firstRow) {
	$colspan = "colspan='$num_rsc'";
	$firstRow = False;
	print "<tr class='resource'><th class='notapply'></th><th>Status</th><th>Resource</th></tr>";
	print "<tr  class='resource'>";
	print "<td rowspan=$num_rsc class='notapply'/>";
      } else {
	$colspan = "";
	print "<tr  class='resource'>";
      }
      

      /* if ($rsc_status == "failed"){ */
      /*   print "<td rowspan=$num_rsc>"; */
      /* } else { */
      /*   print "<td rowspan=$num_rsc class='notapply'>"; */
      /* } */
      print "<td class='$rsc_status'>$rsc_status</td><td>$rsc_urn</td></tr>";
      if ($rsc_status == "failed"){
	print "<tr><td></td><td>$rsc_error</td></tr>";
      }
    }    
  }
  print "</table>";
}

$header = "Status of Slivers on slice: $slice_name";

show_header('GENI Portal: Slices',  $TAB_SLICES);
include("tool-breadcrumbs.php");
print "<h2>$header</h2>\n";

if (isset($msg) and isset($obj)){
  if (!$pretty) {
    echo "<div class='xml'>\n";
    /* json_encode accepts JSON_PRETTY_PRINT in PHP 5.4, but
     * we've got 5.3. Use a third-party utility instead.
     */
    echo "<pre>\n" . json_indent(json_encode($obj)) . "\n</pre>\n";
//     print_r($obj);
    echo "\n</div>\n";
  } else {
    print_sliver_status( $obj );
  }

  /*  print "<pre>$msg</pre>"; */

  print_sliver_status_err( $msg );
  /* echo "<pre>"; */
  /* echo print_r($obj); */
  /* echo "</pre>"; */

    print "<a href='sliverstatus.php?pretty=False&slice_id=".$slice_id."'>Raw SliverStatus</a>";
    print "<br/>";
    print "<br/>";


} else {
  print "<p><i>Failed to determine status of resources.</i></p>";
}

print "<a href='slices.php'>Back to All slices</a>";
print "<br/>";
print "<a href='slice.php?slice_id=$slice_id'>Back to Slice $slice_name</a>";
include("footer.php");

?>
