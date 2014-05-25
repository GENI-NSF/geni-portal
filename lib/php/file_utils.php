<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2014 Raytheon BBN Technologies
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
  file_put_contents($tmpfile, $data);
  return $tmpfile;
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

  function __destruct() {
    foreach($this->filenames as $filename) {
      unlink($filename);
    }
  }
}



?>
