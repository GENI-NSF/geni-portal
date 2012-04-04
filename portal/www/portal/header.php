<?php
require_once("util.php");

/*----------------------------------------------------------------------
 * Tab Bar
 *----------------------------------------------------------------------
 */

$TAB_HOME = 'Home';
$TAB_SLICES = 'Slices';
$TAB_DEBUG = 'Debug';
$standard_tabs = array(array('name' => $TAB_HOME,
                             'url' => 'home.php'),
                       array('name' => $TAB_SLICES,
                             'url' => 'slices.php'),
                       array('name' => $TAB_DEBUG,
                             'url' => 'debug.php')
                       );

function show_tab_bar($active_tab)
{
  global $standard_tabs;
  echo '<div id="mainnav" class="nav">';
  echo '<ul>';
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

function show_header($title, $active_tab)
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
  echo '<link type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/humanity/jquery-ui.css" rel="Stylesheet" />';
  echo '<link type="text/css" href="/common/css/portal.css" rel="Stylesheet"/>';

  /* Close the "head" */
  echo '</head>';
  echo '<body>';
  echo '<div id="header">';
  echo '<img src="/images/geni.png" alt="GENI"/>';
  echo '<img src="/images/portal.png" alt="Portal"/>';
  show_tab_bar($active_tab);
  echo '</div>';
  echo '<div id="content">';
}

?>
