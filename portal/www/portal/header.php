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

$standard_tabs = array(array('name' => $TAB_HOME,
                             'url' => 'home.php'),
                       array('name' => $TAB_PROJECTS,
                             'url' => 'projects.php'),
                       array('name' => $TAB_SLICES,
                             'url' => 'slices.php'),
                       array('name' => $TAB_PROFILE,
                             'url' => 'profile.php'),
                       array('name' => $TAB_HELP,
                             'url' => 'help.php'),
                       array('name' => $TAB_DEBUG,
                             'url' => 'debug.php')
                       );

function show_tab_bar($active_tab = '', $load_user=true)
{
  global $standard_tabs;
  global $TAB_ADMIN;

  // Do we check per user permissions/state to modify the set of tabs?
  if ($load_user) {
    $user = geni_loadUser();
    
    if (isset($user) && ! is_null($user)) {
      if ($user->privAdmin()) {
      array_push($standard_tabs, array('name' => $TAB_ADMIN,
				       'url' => 'admin.php'));
      }
    }
  }

  echo '<div id="mainnav" class="nav">';
  echo '<ul>';
  if (isset($user) && ! is_null($user) && $user->isActive()) {
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

function show_header($title, $active_tab = '', $load_user=1)
{
  echo '<!DOCTYPE HTML>';
  echo '<html>';
  echo '<head>';
  echo '<title>';
  echo $title;
  echo '</title>';

  /* Javascript stuff. */
  /* echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>'; */
  /* echo '<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>'; */

  /* Stylesheet(s) */
  echo '<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/humanity/jquery-ui.css" rel="Stylesheet" />';
  echo '<link type="text/css" href="/common/css/portal.css" rel="Stylesheet"/>';

  /* Close the "head" */
  echo '</head>';
  echo '<body>';
  echo '<div id="header">';
  echo '<img src="/images/geni.png" alt="GENI"/>';
  echo '<img src="/images/portal.png" alt="Portal"/>';
  show_tab_bar($active_tab, $load_user);
  echo '</div>';
  echo '<div id="content">';
}

?>
