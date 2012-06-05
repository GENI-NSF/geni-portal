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

require_once('util.php');
require_once('sa_client.php');

$USER_STARTER_TASK_CACHE_TAG ='user_starter_task_cache';
$IS_ACTIVATED_TAG ='is_activated';
$HAS_SSH_KEYS_TAG ='has_ssh_keys';
$HAS_PROJECTS_TAG ='has_projects';
$HAS_SLICES_TAG ='has_slices';

// Plot starter status for given tag : Red for alert, green for no alert
function put_starter_status($status, $tag, $page)
{
  if($status) { 
    $done = "done"; 
  } else {
    $done = "notdone";
  }
  echo "<span class='$done'>&gt;&gt; <a href='$page'>$tag</a>    </span>";
}

// Check if we're already done with the starter tasks (at some time during session)
function already_done_starter_tasks($user)
{
  global $USER_STARTER_TASK_CACHE_TAG;
  global $IS_ACTIVATED_TAG, $HAS_SSH_KEYS_TAG, $HAS_PROJECTS_TAG, $HAS_SLICES_TAG;
  $user_id = $user->account_id;
  $done = false;
  if (array_key_exists($USER_STARTER_TASK_CACHE_TAG, $_SESSION)) {
    $user_starter_task_cache = $_SESSION[$USER_STARTER_TASK_CACHE_TAG];
    if(array_key_exists($user_id, $user_starter_task_cache)) {
      $user_starter_tasks = $user_starter_task_cache[$user_id];
      $done = $user_starter_tasks[$IS_ACTIVATED_TAG] && 
	$user_starter_tasks[$HAS_SSH_KEYS_TAG] &&
	$user_starter_tasks[$HAS_PROJECTS_TAG] && 
	$user_starter_tasks[$HAS_SLICES_TAG];
    }
  }
  return $done;
}

// Maintain cache of starter tasks to see if user has achieved 
// them at some point during session
function register_starter_tasks($user, $activated, $ssh_keys, $projects, $slices)
{
  global $USER_STARTER_TASK_CACHE_TAG;
  global $IS_ACTIVATED_TAG, $HAS_SSH_KEYS_TAG, $HAS_PROJECTS_TAG, $HAS_SLICES_TAG;
  $user_id = $user->account_id;
  if (array_key_exists($USER_STARTER_TASK_CACHE_TAG, $_SESSION)) {
    $user_starter_task_cache = $_SESSION[$USER_STARTER_TASK_CACHE_TAG];
  } else {
    $user_starter_task_cache = array();
  } 
  if(array_key_exists($user_id, $user_starter_task_cache)) {
    $user_starter_tasks = $user_starter_task_cache[$user_id];
  } else {
    $user_starter_tasks = array($IS_ACTIVATED_TAG => false,
				$HAS_SSH_KEYS_TAG => false,
				$HAS_PROJECTS_TAG => false,
				$HAS_SLICES_TAG => false);
  }
  $new_user_starter_tasks = 
    array($IS_ACTIVATED_TAG => $user_starter_tasks[$IS_ACTIVATED_TAG] || $activated,
	  $HAS_SSH_KEYS_TAG => $user_starter_tasks[$HAS_SSH_KEYS_TAG] || count($ssh_keys)>0,
	  $HAS_PROJECTS_TAG => $user_starter_tasks[$HAS_PROJECTS_TAG] || count($projects)>0,
	  $HAS_SLICES_TAG => $user_starter_tasks[$HAS_SLICES_TAG] || count($slices)> 0);
  $user_starter_task_cache[$user_id] = $new_user_starter_tasks;
  $_SESSION[$USER_STARTER_TASK_CACHE_TAG] = $user_starter_task_cache;
}

function show_starter_status_bar($load_user)
{
  if (!$load_user) {
    return;
  }
  $user = geni_loadUser();

  if(already_done_starter_tasks($user)) {
    return;
  }

  if (! isset($pa_url)) {
    $pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
  }
  if (! isset($sa_url)) {
    $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
  }
  if (! isset($ma_url)) {
    $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  }

  $activated = $user->isActive();
  $ssh_keys = lookup_ssh_keys($ma_url, $user->account_id);
  $projects = get_projects_for_member($pa_url, $user->account_id, true);
  $slices = get_slices_for_member($sa_url, $user, $user->account_id, true);
  //  $project_requests = get_number_of_pending_requests_for_user($pa_url, $user, $user->account_id);

  register_starter_tasks($user, $activated, $ssh_keys, $projects, $slices);
  if(already_done_starter_tasks($user)) {
    return;
  }

  echo "<p class='starter'>";
  echo "GENI START:   ";
  put_starter_status($activated, "Activate Account", "home.php");
  put_starter_status(count($projects)> 0, "Join Project", "projects.php");
  put_starter_status(count($ssh_keys)> 0, "Register SSH Keys", "profile.php");
  //   put_starter_status(count($project_requests)> 0, "REQUESTS");
  put_starter_status(count($slices)> 0, "Join Slice", "projects.php");
  echo "</p>";
}


?>
