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

require_once("util.php");
require_once('rq_client.php');
require_once('ma_client.php');
require_once('sa_client.php');
require_once('pa_client.php');
//require_once('starter-status-bar.php');
require_once('geni_syslog.php');
require_once("maintenance_mode.php");
require_once('settings.php');
require_once('cs_constants.php');
include_once('/etc/geni-ch/settings.php');


/*----------------------------------------------------------------------
 * Tab Bar
 *----------------------------------------------------------------------
 */

$TAB_HOME = 'Home';
$TAB_SLICES = 'Slices';
$TAB_PROJECTS = 'Projects';
$TAB_ADMIN = 'Admin';
$TAB_DEBUG = 'Debug';
$TAB_HELP = "Help";
$TAB_PROFILE = "Profile";
require_once("user.php");

// Should the Debug tab be shown?
$show_debug = false;

$standard_tabs = array(array('name' => $TAB_HOME,
                             'url' => 'home.php'),
                       array('name' => $TAB_PROJECTS,
                             'url' => 'projects.php'),
                       array('name' => $TAB_SLICES,
                             'url' => 'slices.php'),
                       array('name' => $TAB_PROFILE,
                             'url' => 'profile.php'),
                       array('name' => $TAB_HELP,
                             'url' => 'help.php')
		       );
if ($show_debug) {
  $standard_tabs[] = array('name' => $TAB_DEBUG,
			   'url' => 'debug.php');
}

function show_tab_bar($active_tab = '', $load_user=true)
{
  global $standard_tabs;
  global $TAB_ADMIN;
  global $user;

  // Do we check per user permissions/state to modify the set of tabs?
  if ($load_user) {

    if (!isset($user)) {
      $user = geni_loadUser();
    }
    
    if (isset($user) && ! is_null($user)) {
      if ($user->isAllowed(CS_ACTION::ADMINISTER_MEMBERS, CS_CONTEXT_TYPE::MEMBER, null)) {
	array_push($standard_tabs, array('name' => $TAB_ADMIN,
					 'url' => 'admin.php'));
      }
      // Record the last seen time/place
      record_last_seen($user, $_SERVER['REQUEST_URI']);
    }
  }

  echo '<div id="mainnav" class="nav">';
  echo '<ul>';
  if (!$load_user || (isset($user) && !is_null($user) && $user->isActive())) {
    foreach ($standard_tabs as $tab) {
      echo '<li';
      if ($active_tab == $tab['name']) {
	echo ' class="active first">';
      } else {
	echo '>';
      }
      echo '<a href="' . relative_url($tab['url']) . '">' . $tab['name'] . '</a>';
      echo '</li>';
    }
  }
  echo '</ul>';
  echo '</div>';
}

function skip_km_authorization() {
  global $NO_AUTHZ_REDIRECT;
  $NO_AUTHZ_REDIRECT = true;
}

function check_km_authorization($user)
{
  global $NO_AUTHZ_REDIRECT;
  if (isset($NO_AUTHZ_REDIRECT) && $NO_AUTHZ_REDIRECT) {
    return;
  }
  if (! $user->portalIsAuthorized()) {
    $request_uri = $_SERVER['REQUEST_URI'];
    //    $km_url = get_first_service_of_type(SR_SERVICE_TYPE::KEY_MANAGER);
    $home = "kmhome.php";
    relative_redirect($home);
  }
}

/*
 * We want to syslog whenever we have a new shib session ID
 */
$CURRENT_SHIB_ID_TAG = "CURRENT_SHIB_ID";
$current_shib_id = $_SERVER["Shib-Session-ID"];
if(!isset($_SESSION)) { session_start(); }
$shib_id_changed = false;
if(!array_key_exists($CURRENT_SHIB_ID_TAG, $_SESSION) ||
   $_SESSION[$CURRENT_SHIB_ID_TAG] != $current_shib_id) {
  $shib_id_changed = true;
}
// error_log("NEW SHIB_ID = " . $current_shib_id);
if ($shib_id_changed) {
  $eppn = "No EPPN Found";
  if (array_key_exists("eppn", $_SERVER)) {
    $eppn = strtolower($_SERVER["eppn"]);
  }
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, "New login to portal: " . $eppn);
  $_SESSION[$CURRENT_SHIB_ID_TAG] = $current_shib_id;
}

/*----------------------------------------------------------------------
 * Default settings
 *----------------------------------------------------------------------
 */
if (! isset($GENI_TITLE)) {
  $GENI_TITLE = "GENI Portal";
}
if (! isset($ACTIVE_TAB)) {
  $ACTIVE_TAB = $TAB_HOME;
}

$extra_js = array();
function add_js_script($script_url)
{
  global $extra_js;
  $extra_js[] = $script_url;
}

function show_header($title, $active_tab = '', $load_user=1)
{
  global $extra_js;
  global $in_maintenance_mode;
  global $in_lockdown_mode;
  global $portal_analytics_enable;
  global $portal_analytics_string;

  if ($load_user) {
    global $user;
    if (!isset($user)) {
      $user = geni_loadUser();
    }
    check_km_authorization($user);
  }
  echo '<!DOCTYPE HTML>';
  echo '<html>';
  echo '<head>';
  echo '<title>';
  echo $title;
  echo '</title>';

  /* Javascript stuff. */
  echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>';
  echo '<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>';

  foreach ($extra_js as $js_url) {
    echo '<script src="' . $js_url . '"></script>' . PHP_EOL;
  }

  /* Stylesheet(s) */
  echo '<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/humanity/jquery-ui.css" rel="Stylesheet" />';
  echo '<link type="text/css" href="/common/css/portal.css" rel="Stylesheet"/>';
  echo '<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700|PT+Serif:400,400italic|Droid+Sans+Mono" rel="stylesheet" type="text/css">';
  
  /* Google Analytics
     Get this from /etc/geni-ch/settings.php, but first check to see if
       $portal_analytics_enable exists
  */
  if(isset($portal_analytics_enable)) {
    if($portal_analytics_enable) {
      // FIXME: Allow some users (e.g. operators) to bypass tracking
      echo '<script>(function(i,s,o,g,r,a,m){i[\'GoogleAnalyticsObject\']=r;i[r]=i[r]||function(){';
      echo '(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),';
      echo 'm=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)';
      echo '})(window,document,\'script\',\'//www.google-analytics.com/analytics.js\',\'ga\');';
      
      if (! isset($portal_analytics_string) || is_null($portal_analytics_string)) {
        /* Use the following tracking IDs depending on which server this will be running on
          portal1.gpolab.bbn.com:   ga('create', 'UA-42566976-1', 'bbn.com');
          portal.geni.net:          ga('create', 'UA-42566976-2', 'geni.net');
        */
        $portal_analytics_string = "ga('create', 'UA-42566976-1', 'bbn.com');";
      }
      
      echo $portal_analytics_string;
      
      echo "ga('send', 'pageview');";
      echo '</script>';
    }
  }

  /* Close the "head" */
  echo '</head>';
  echo '<body>';
  echo '<div id="header"><div id="header-top">';
  if ($load_user) {
    echo '<div id="metanav" class="nav">';
    echo '<ul>';
    if ($in_lockdown_mode) {
      echo "<li><b>*** Read-Only Mode; Use <a href=\"https://portal.geni.net\">portal.geni.net</a> ***</b></li>";
    } 
    if ($in_maintenance_mode) {
      echo "<li><b>*** Maintenance Mode ***</b></li>";
    }
    echo '<li>Logged in as <b>' . $user->prettyName() . '</b> (' . $user->username . ')</li>';
    $logout_url = relative_url("dologout.php");
    echo '<li style="border-right: none"><a href="' . $logout_url . '">Logout</a></li>';
    echo '</ul>';
      echo '</div>';
    
  }
  echo '</div>';
  show_tab_bar($active_tab, $load_user);
  echo '</div>';
  echo '<div id="content-outer">';
  echo '<div id="content">';
  //  show_starter_status_bar($load_user);
}

?>
