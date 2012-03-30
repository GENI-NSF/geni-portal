<?php
/*
 *----------------------------------------------------------------------
 * Copyright (c) 2012 Raytheon BBN Technologies
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and/or hardware specification (the "Work") to
 * deal in the Work without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Work, and to permit persons to whom the Work
 * is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Work.
 *
 * THE WORK IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS
 * IN THE WORK.
 *----------------------------------------------------------------------
 */

/*
 * Database utilities.
 */

/*--------------------------------------------------
 * Import the database library
 *--------------------------------------------------
 */

require_once 'MDB2.php';


function dbconn()
{
  /* pgsql://USER:PASSWORD@host/DBNAME */
  $db_dsn = 'pgsql://svcreg:svcreg@localhost/genisr';
  $db_options = array('debug' => 5,
                      'result_buffering' => false,
                      );
  $portal_db =& MDB2::singleton($db_dsn, $db_options);
  if (PEAR::isError($portal_db)) {
    die("Error connecting: " . $portal_db->getMessage());
  }
  return $portal_db;
}

/**
 * Param $svc_type: a string indicating the type of services desired.
 *
 * Return a list of arrays containing the columns and values for each
 * service of the specified type $svc_type. The column names are the
 * array keys, and the values for those columns are the array values.
 */
function db_fetch_services($svc_type)
{
  $conn = dbconn();
  $sql = "SELECT * FROM service WHERE"
    . " stype = " . $conn->quote($svc_type, 'text')
    . ';';

  // print "command = $sql<br/>";
  $resultset = $conn->query($sql);
  if (PEAR::isError($resultset)) {
    die("error on db_fetch_services: " . $resultset->getMessage());
  }
  $value = array();
  while ($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
    // Append to the array
    $value[] = $row;
  }
  // TODO: close the connection?
  return $value;
}

?>
