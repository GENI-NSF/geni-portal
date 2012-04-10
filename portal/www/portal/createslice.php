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

// Form for creating a slice. Submit to self.
?>

<?php
require_once("settings.php");
require_once("db-util.php");
require_once("file_utils.php");
require_once("util.php");
require_once("user.php");
require_once("sr_constants.php");
require_once("sr_client.php");
?>

<?php
$user = geni_loadUser();
$name = NULL;
$message = NULL;
if (count($_GET)) {
  // parse the args
  /* print "got parameters<br/>"; */
  if (array_key_exists('name', $_GET)) {
    /* print "found name<br/>"; */
    $name = $_GET['name'];
  }
  /* print "got name = $name<br/>"; */

} else {
  /* print "no parameters in _GET<br/>"; */
}

function omni_create_slice($user, $slice_id, $name)
{
    /* Write key and credential files */
    $row = db_fetch_inside_private_key_cert($user->account_id);
    $cert = $row['certificate'];
    $private_key = $row['private_key'];
    $cert_file = '/tmp/' . $user->username . "-cert.pem";
    $key_file = '/tmp/' . $user->username . "-key.pem";	
    $omni_file = '/tmp/' . $user->username . "-omni.ini";
    file_put_contents($cert_file, $cert);
    file_put_contents($key_file, $private_key);

    /* Create OMNI config file */
    $omni_config = "[omni]\n"
    . "default_cf = my_gcf\n"
    . "[my_gcf]\n"
    . "type=gcf\n"
    . "authority=geni:gpo:portal\n"
    . "ch=https://localhost:8000\n"
    . "cert=" . $cert_file . "\n"
    . "key=" . $key_file;
    file_put_contents($omni_file, $omni_config);

    /* Call OMNI */
    global $portal_gcf_dir;
    $cmd_array = array($portal_gcf_dir . '/src/omni.py',
                   '-c',
		   $omni_file,
		   'createslice',
		   $name
                   );
     $command = implode(" ", $cmd_array);
     $result = exec($command, $output, $status);
//     print_r($output);  
//     print_r($result);
//     print "RESULT = " . $result . "\n";
//     print "STATUS = " . $status . "\n";
     unlink($cert_file);
     unlink($key_file);
     unlink($omni_file);

}

function sa_create_slice($user, $slice_id, $name)
{
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);

  //  error_log($sa_url);
  
  $message['operation'] = 'create_slice';
  $message['slice_name'] = $name;
  $result = put_message($sa_url, $message);
  return $result;
}

// Do we have all the required params?
if ($name) {
  $slice = fetch_slice_by_name($name);
  if (is_null($slice)) {
    // no slice by that name, create it
    /* print "name = $name, creating slice<br/>"; */
    $slice_id = make_uuid();
    /* print "slice id = $slice_id<br/>"; */

    /* Get a slice from GCF slice authority */
    //omni_create_slice($user, $slice_id, $name);
    $result = sa_create_slice($user, $slice_id, $name);
    $pretty_result = print_r($result, true);
    error_log("sa_create_slice result: $pretty_result\n");

    db_create_slice($user->account_id, $slice_id, $name);
    /* print "done creating slice<br/>"; */
    relative_redirect('slices.php');
  } else {
    $message = "Slice name \"" . $name . "\" is already taken."
      . " Please choose a different name." ;
  }
}

// If here, present the form
require_once("header.php");
show_header('GENI Portal: Debug', '');
if ($message) {
  // It would be nice to put this in red...
  print "<i>" . $message . "</i>\n";
}
print '<form method="GET" action="createslice">';
print "\n";
print 'Slice name: ';
print "\n";
print '<input type="text" name="name"/><br/>';
print "\n";
print '<input type="submit" value="Create slice"/>';
print "\n";
print '</form>';
print "\n";
?>
<?php
include("footer.php");
?>
