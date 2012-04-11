<?php
//----------------------------------------------------------------------
// Copyright (c) 2012 Raytheon BBN Technologies
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

/* Set of constants for managing calls and data records for 
 * GENI Clearinghouse Slice Authority
 */

/* Set of arguments to callse SA interface */
class SA_ARGUMENT
{
  const PROJECT_ID = "project_id";
  const SLICE_NAME = "slice_name";
  const SLICE_ID = "slice_id";
  const SLICE_URN = "slice_urn";
  const OWNER_ID = "owner_id";
  const EXPIRATION = "expiration";
}

/* Name of table containing slice info */
$SA_SLICE_TABLENAME = "sa_slice";

/* Fields in SA slice table */
class SA_SLICE_TABLE_FIELDNAME {
  const SLICE_ID = "slice_id";
  const SLICE_NAME = "slice_name";
  const PROJECT_ID = "project_id";
  const EXPIRATION = "expiration";
  const OWNER_ID = "owner_id";
  const SLICE_URN = "slice_urn";
}