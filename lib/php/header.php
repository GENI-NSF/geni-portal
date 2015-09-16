<?php
//----------------------------------------------------------------------
// Copyright (c) 2011-2015 Raytheon BBN Technologies
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
require_once('geni_syslog.php');
require_once("maintenance_mode.php");
require_once('settings.php');
require_once('cs_constants.php');
require_once("tool-jfed.php");
include_once('/etc/geni-ch/settings.php');

require_once("user.php");

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
    relative_redirect("kmhome.php");
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

$extra_js = array();
function add_js_script($script_url)
{
  global $extra_js;
  $extra_js[] = $script_url;
}

function show_header($title, $load_user=1, $show_cards=false){
  global $extra_js;
  global $in_maintenance_mode;
  global $in_lockdown_mode;
  global $portal_analytics_enable;
  global $portal_analytics_string;
  global $has_maintenance_alert;
  global $maintenance_alert;
  global $portal_jquery_url; 
  global $portal_jqueryui_js_url; 
  global $portal_jqueryui_css_url; 
  global $portal_datatablesjs_url; 
  global $user;

  if ($load_user) {
    global $user;
    if (!isset($user)) {
      $user = geni_loadUser();
    }
    check_km_authorization($user);
    record_last_seen($user, $_SERVER['REQUEST_URI']);
  }
  
  echo '<!DOCTYPE HTML>';
  echo '<html lang="en">';
  echo '<head>';
  echo '<meta charset="utf-8">';
  echo "<title>$title</title>";

  /* Javascript stuff. */
  echo "<script src='$portal_jquery_url'></script>";
  echo "<script src='$portal_jqueryui_js_url'></script>";
  foreach ($extra_js as $js_url) {
    echo '<script src="' . $js_url . '"></script>' . PHP_EOL;
  }
  echo "<script type='text/javascript' charset='utf8' src='$portal_datatablesjs_url'></script>";
  /* Stylesheet(s) */
  echo "<link type='text/css' href='$portal_jqueryui_css_url' rel='stylesheet' />";
  echo '<link type="text/css" href="/common/css/newportal.css" rel="stylesheet"/>';
  echo '<link type="text/css" rel="stylesheet" media="(max-width: 480px)" href="/common/css/mobile-portal.css" />';
  echo '<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700|Droid+Sans+Mono|Material+Icons" rel="stylesheet" type="text/css">';

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

  /* for proper scaling on mobile devices/ mobile web app support */ 
  echo '<meta name="viewport" content="initial-scale=1.0, user-scalable=0, width=device-width, height=device-height"/>';
  echo '<meta name="mobile-web-app-capable" content="yes">';

  /* Close the "head" */
  echo '</head>';
  echo '<body>';
  echo '<script>';
  // For header interactivity
  echo '$(document).ready(function(){';
  echo '$(".has-sub").hover(function(){ $(this).find(\'ul\').show(); }, function(){ $(this).find(\'ul\').hide(); });';
  echo '$("#hamburger").click(function(){';
  echo '$("#dashboardtools").slideToggle();';
  echo '});';
  echo '});';
  echo '</script>';
  echo '<div id="dashboardheader">';
  echo '<img id="globe" src="/images/geni_globe.png" alt="Geni Logo" style="height:45px; margin-left: 20px; float: left;"/>';
  echo '<img id="hamburger" src="/images/menu.png" alt="optionsicon" style="height:20px; width: 20px; padding:15px; float: left;"/>';
  echo '<h2 class="dashtext" style="float: left; line-height: 50px; text-align: center; margin: 0 20px; display: inline; height: 50px; cursor: pointer;" 
          onclick="window.location=\'dashboard.php\'">GENI Portal</h2>';
  echo '<ul id="dashboardtools" class="floatright" style="vertical-align: top;">';
  if($load_user) {
    echo "<li class='has-sub headerlink'>{$user->prettyName()}";
  } else {
    echo "<li class='has-sub headerlink'>User";
  }
  echo '<ul class="submenu">';
  echo '<li><a href="profile.php">Profile</a></li>';
  echo '<li><a href="profile.php#ssh">SSH Keys</a></li>';
  echo '<li><a href="profile.php#rspecs">RSpecs</a></li>';
  echo '<li><a href="profile.php#tools">Manage Accounts</a></li>'; 
  echo '<li><a href="profile.php#preferences">Preferences</a></li>'; 
  echo '<li><a href="' . relative_url("dologout.php") . '" >Logout</a></li>';
  if ($load_user && $user->isAllowed(CS_ACTION::ADMINISTER_MEMBERS, CS_CONTEXT_TYPE::MEMBER, null)) {
    echo '<li><a href="admin.php">Admin</a></li>';
  }
  echo '</ul></li>';
  echo '<li class="headerlink has-sub"><a href="help.php">Help</a>';
  echo '<ul class="submenu">';
  echo '<li><a target="_blank" href="http://groups.geni.net/geni/wiki">GENI Wiki <i class="material-icons">launch</i></a></li>';
  echo '<li><a target="_blank" href="http://groups.geni.net/geni/wiki/GENIExperimenter/GetHelp">GENI Help Wiki <i class="material-icons">launch</i></a></li>';
  echo '<li><a target="_blank" href="http://gmoc.grnoc.iu.edu/gmoc/index/support/gmoc-operations-calendars.html">Outages <i class="material-icons">launch</i> </a></li>';
  echo '<li><a target="_blank" href="http://groups.geni.net/geni/wiki/GENIGlossary">Glossary <i class="material-icons">launch</i></a></li>';
  echo '<li><a target="_blank" href="http://groups.geni.net/geni/wiki/GENIBibliography">Bibliography<i class="material-icons">launch</i></a></li>';
  echo '<li><a href="contact-us.php">Contact Us</a></li>';
  echo '</ul></li>';

  if ($load_user) {
    if (! isset($jfed_button_start)) {
      $jfedret = get_jfed_strs($user);
      $jfed_script_text = $jfedret[0];
      $jfed_button_start = $jfedret[1];
      $jfed_button_part2 = $jfedret[2];
      if (! is_null($jfed_button_start)) {
        print $jfed_script_text;
      }
    }
  }

  echo '<li class="headerlink has-sub">Partners';
  echo '<ul class="submenu">';
  echo "<li><a href='https://www.cloudlab.us/login.php' target='_blank'>CloudLab <i class='material-icons'>launch</i></a></li>";
  echo "<li><a href='http://gee-project.org/user' target='_blank'>GEE <i class='material-icons'>launch</i></a></li>";
  echo "<li><a href='wireless_redirect.php?site=ORBIT' target='_blank'>ORBIT<i class='material-icons'>launch</i></a></li>";
  echo "<li><a href='http://portal.savitestbed.ca/auth/login' target='_blank'>SAVI<i class='material-icons'>launch</i></a></li>";
  echo "<li><a href='wireless_redirect.php?site=WITEST' target='_blank'>WiTest<i class='material-icons'>launch</i></a></li>";
  echo '</ul></li>';

  echo '<li class="headerlink has-sub">Tools';
  echo '<ul class="submenu">';
  echo "<li><a href='gemini.php' target='_blank'>GENI Desktop<i class='material-icons'>launch</i></a></li>";
  if ($load_user && !is_null($jfed_button_start)) {
    echo "<li>";
    echo $jfed_button_start . getjFedSliceScript(NULL) . $jfed_button_part2 . ">jFed<i class='material-icons'>launch</i></button>";
    echo "</li>";
  }
  echo "<li><a href='http://labwiki.casa.umass.edu' target='_blank'>LabWiki <i class='material-icons'>launch</i></a></li>";
  echo "<li><a href='http://groups.geni.net/geni/wiki/GENIExperimenter/Tools' target='_blank' title='Omni, Geni-lib, VTS...'>Other Tools <i class='material-icons'>launch</i></a></li>";
  echo '</ul></li>';

  echo '<li class="headerlink has-sub"><a href="dashboard.php">Home</a>';
  echo '<ul class="submenu">';
  echo '<li><a href="dashboard.php#slices">Slices</a></li>';
  echo '<li><a href="dashboard.php#projects">Projects</a></li>';
  echo '</ul></li></ul>';
  echo '</div>';

  $cards_class = $show_cards ? 'content-cards' : 'one-card'; 

  echo '<div style="clear:both; height: 50px;">&nbsp;</div>';

  if ($in_maintenance_mode) {
    echo "<center><b>***** Maintenance Outage *****</b></center>";
  }

  if($has_maintenance_alert) {
    print "<p class='instruction' id='maintenance_alert'>$maintenance_alert</p>";
  }
  echo "<div id='content-outer' class='$cards_class'>";
  echo "<div id='content'>";
}

?>
