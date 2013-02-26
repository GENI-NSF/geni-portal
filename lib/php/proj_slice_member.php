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

function get_project_slice_member_info($pa_url, $sa_url, $ma_url, $user)
{
  $member_ids = array();
  $slice_ids = array();
  $slice_objects = array();
  $project_objects = array();
  $member_objects = array();
  $project_slice_map = array();
  $projects = get_projects_for_member($pa_url, $user, $user->account_id, true);
  if (count($projects) > 0) {
     $project_objects = lookup_project_details($pa_url, $user, $projects);
     foreach ($project_objects as $project) {
        $project_id = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
        $proj_lead_id = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
	$member_ids[] = $proj_lead_id;
	$proj_slice_ids = lookup_slice_ids($sa_url, $user, $project_id);	// combine this into a smaller number of calls?
	$project_slice_map[ $project_id ] = $proj_slice_ids;
	$slice_ids = array_merge( $slice_ids, $proj_slice_ids ); // is this ok
     }	      
  }
  if (count($slice_ids) > 0) {
     $slice_objects = lookup_slice_details($sa_url, $user, $slice_ids);
     foreach ($slice_objects as $slice) {
        $owner_id = $slice[SA_SLICE_TABLE_FIELDNAME::OWNER_ID]; // SA_ARGUMENT::OWNER_ID????
 	$member_ids[] = $owner_id;
     }          
  }    
  if (count($member_ids) > 0) {
     $member_objects = lookup_member_details($ma_url, $user, $member_ids);     
  }
  return array( $project_objects, $slice_objects, $member_objects, $project_slice_map );
}

?>

