<?php

require_once('util.php');
require_once('sa_client.php');

$ALERT_COLOR ='red';
$NO_ALERT_COLOR ='green';

$USER_STARTER_TASK_CACHE_TAG ='user_starter_task_cache';
$IS_ACTIVATED_TAG ='is_activated';
$HAS_SSH_KEYS_TAG ='has_ssh_keys';
$HAS_PROJECTS_TAG ='has_projects';
$HAS_SLICES_TAG ='has_slices';

// Plot starter status for given tag : Red for alert, green for no alert
function put_starter_status($status, $tag)
{
  global $ALERT_COLOR, $NO_ALERT_COLOR;
  $color = $ALERT_COLOR;
  if($status) { $color = $NO_ALERT_COLOR; }
  echo "<$color>&gt;&gt; $tag    </$color>";
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

  echo '<style type="text/css">';
  echo "red {color:red;}";
  echo "green {color:green;}";
  echo "</style>";
  echo "<p class='starter'>";
  echo "GENI START:   ";
  put_starter_status($activated, "ACTIVE");
  put_starter_status(count($ssh_keys)> 0, "SSH_KEYS");
  //   put_starter_status(count($project_requests)> 0, "REQUESTS");
  put_starter_status(count($projects)> 0, "PROJECTS");
  put_starter_status(count($slices)> 0, "SLICES");
  echo "</p>";
}


?>
