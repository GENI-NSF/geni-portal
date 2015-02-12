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

/**
 * Functions to help with file handling.
 */

$omni_invocation_prefix = "omni-invoke";

/**
 * Write $data to a temp file and return the filename.  An optional
 * file prefix can be specified.
 *
 * NOTE: The caller is responsible for deleting ("unlink") the file
 * when it is no longer needed.
 *
 *     $fname = writeDataToTempFile($mydata, "foo-");
 *     doSomething($fname);
 *     unlink($fname);
 */
function writeDataToTempFile($data, $prefix = "geni-")
{
  $tmpfile = tempnam(sys_get_temp_dir(), $prefix);
  if (file_put_contents($tmpfile, $data) === False) {
    // Error writing the data to the file!
    error_log("Failed to write to $tmpfile!");
    return null;
  }
  return $tmpfile;
}

/*
    Like above, but writes to a temporary directory that was
    already created by createTempDir(); does not come up with its
    own unique name via tempnam()
*/
function writeDataToTempDir($dir, $data, $prefix = "geni-")
{
  $tmpfile = "$dir/$prefix";
  if (file_put_contents($tmpfile, $data) === False) {
    // Error writing the data to the file!
    error_log("Failed to write to $tmpfile!");
    return null;
  }
  return $tmpfile;
}

/*
    Create a temporary directory
*/
function createTempDir($prefix) {
    global $omni_invocation_prefix;
    $tempfile=tempnam(sys_get_temp_dir(), "$omni_invocation_prefix-$prefix-");
    if (file_exists($tempfile)) { 
        unlink($tempfile);
    }
    mkdir($tempfile);
    if (is_dir($tempfile)) {
        return $tempfile;
    }
    // return null if directory wasn't created
    return NULL;
}

/*
    Checks to see if directory is empty
    Source: http://stackoverflow.com/questions/7497733/how-can-use-php-to-check-if-a-directory-is-empty
*/
function isDirEmpty($dir) {
  if (!is_readable($dir)) return NULL; 
  $handle = opendir($dir);
  while (false !== ($entry = readdir($handle))) {
    if ($entry != "." && $entry != "..") {
      return FALSE;
    }
  }
  return TRUE;
}

/**
 * create a UUID
 */
function make_uuid() {
  $uuid = exec('/usr/bin/uuidgen');
  return $uuid;
}

// Class to hold a list of files and unlink them in destructor
class FileManager {
  function __construct() {
    $this->filenames = array();
  }

  function add($filename) { $this->filenames[]=$filename; }

  function destruct() {
    foreach($this->filenames as $filename) {
        unlink($filename);
        // now see if directory can be deleted
        if(isDirEmpty(dirname($filename))) {
            rmdir(dirname($filename));
        }
    }
  }
  
}

// delete all files in a directory
// parameter: $dir - directory to look through
// Source: http://stackoverflow.com/questions/4594180/deleting-all-files-from-a-folder-using-php
function clean_directory($dir) {
    $files = glob(rtrim($dir,'/').'/*'); // get all file names
    foreach($files as $file){ // iterate files
      if(is_file($file))
        unlink($file); // delete file
    }
}

/*
    Get the omni invocation directory based on username and invocation ID
*/
function get_invocation_dir_name($user, $id) { 
    global $omni_invocation_prefix;
    return sys_get_temp_dir() . "/$omni_invocation_prefix-$user-$id";
}

/*
    Get the invocation ID based on the omni invocation directory
*/
function get_invocation_id_from_dir($omni_invocation_dir) { 
    return array_pop(explode("-", $omni_invocation_dir));
}

/*
    Zip all files in a directory (optionally exclude some files)
    Returns zip file location if successful, NULL if not
*/
function zip_dir_files($zip_name, $dir, $excluded_files_list=array()) {

    // all files in directory
    $files_list = glob(rtrim($dir,'/').'/*'); // get all file names

    // all files we want to zip (all files in directory - excluded files)
    $zip_files_list = array_diff($files_list, $excluded_files_list);

    $zip = new ZipArchive();
    
    if ($zip->open($zip_name, ZipArchive::CREATE) === TRUE) {
        foreach($zip_files_list as $file) {
            if(file_exists($file)) {
                $zip->addFile($file, basename($file));
            }
        }
        $zip->close();
        return $zip_name;
    }
    else {
        return NULL;
    }

}

?>
