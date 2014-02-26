<?php
//----------------------------------------------------------------------
// Copyright (c) 2011-2014 Raytheon BBN Technologies
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
?>

<?php
require_once("user.php");
require_once("header.php");
require_once('util.php');
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

// Does the user have an outside certificate?
if (! isset($sa_url)) {
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
  if (! isset($sa_url) || is_null($sa_url) || $sa_url == '') {
    error_log("Found no SA in SR!'");
  }
}
if (! isset($ma_url)) {
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  if (! isset($ma_url) || is_null($ma_url) || $ma_url == '') {
    error_log("Found no MA in SR!'");
  }
}

// Store any warnings here for display at the top of the page.
$warnings = array();
// Warnings specific to omni 2.2 or newer
$warnings22 = array();

$result = ma_lookup_certificate($ma_url, $user, $user->account_id);
$has_certificate = ! is_null($result);
$has_private_key = false;
if ($has_certificate
    && array_key_exists(MA_OUTSIDE_CERT_TABLE_FIELDNAME::PRIVATE_KEY,
                        $result)
    && (! is_null($result[MA_OUTSIDE_CERT_TABLE_FIELDNAME::PRIVATE_KEY]))) {
  $has_private_key = true;
}

if (! $has_certificate) {
  // warn that no cert has been generated
  $warnings[] = '<p class="warn">No certificate has been generated.'
        . ' Please <a href="kmcert.php?close=1" target="_blank">'
        . 'generate a certificate'
        . '</a>.'
        . '</p>';
}

// FIXME: hardcoded path
$download_url = 'https://' . $_SERVER['SERVER_NAME'] . '/secure/kmcert.php?close=1';
$download_text = 'Create and download your certificate';
if ($has_certificate) {
  $download_text = 'Download your certificate';
}

/* --------- PROJECTS ---------- */
$all_project_ids = get_projects_for_member($sa_url, $user, $user->account_id, true);
$num_projects = count($all_project_ids);
$project_ids = array();
// Filter out expired projects
if ($num_projects >0) {
  $projects = lookup_project_details($sa_url, $user, $all_project_ids);
  foreach ($projects as $proj) {
    if (!convert_boolean($proj[PA_PROJECT_TABLE_FIELDNAME::EXPIRED])) {
      $project_ids[] = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
    }
  }
}
$num_projects = count($project_ids);

$is_project_lead = $user->isAllowed(PA_ACTION::CREATE_PROJECT, CS_CONTEXT_TYPE::RESOURCE, null);
if ($num_projects > 0) {
  // If there are any projects, look up their details. We need the names
  // in order to set up a default project in the config file.
  $projects = lookup_project_details($sa_url, $user, $project_ids);
}
if ($num_projects == 0) {
  // warn that the user has no projects
  $warn = '<p class="warn">You are not a member of any projects.'
        . ' No default project can be chosen unless you';
  if ($is_project_lead) {
    $warn .=  ' <button onClick="window.location=\'edit-project.php\'"><b>create a project</b></button> or';
  }
  $warn .= ' <button onClick="window.location=\'join-project.php\'"><b>join a project</b></button>.</p>';
  $warnings22[] = $warn;
}



// Build the certificate and key list items. These are based on whether
// the user generated a cert/key or uploaded a CSR.
// By default, assume they uploaded a CSR.
$cert_key_list_items = '<li>the path to the certificate downloaded in step 2</li>';
$cert_key_list_items .= '<li>the path to the SSL private key used to generate your certificate, and </li>';
if ($has_private_key) {
  $cert_key_list_items = '<li>the path to the combined certificate and private key downloaded in step 2</li>';
}

/* ---------- Set up the omni config link. ---------- */
$config_url = 'portal_omni_config.php';
$config_link = $config_url;
if ($num_projects > 0) {
  // if there is a project, set the link to the default
  // project, which is the first in the list
  $proj = $projects[$project_ids[0]];
  $proj_name = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
  $config_link .= "?project=$proj_name";
}

/* ---------- PAGE OUTPUT STARTS HERE ---------- */
show_header('GENI Portal: Profile', $TAB_PROFILE);
include("tool-breadcrumbs.php");
include("tool-showmessage.php");
?>


<h1>Omni command line resource reservation tool</h1>
<?php
/* ---------- Display warnings ---------- */
foreach ($warnings as $warning) {
  echo $warning;
}
?>
<div id="omni22">
<?php
/* ---------- Display warnings ---------- */
foreach ($warnings22 as $warning) {
  echo $warning;
}
?>
<p>
Download and use a template omni_config file for use with the
<a href="http://trac.gpolab.bbn.com/gcf/wiki">Omni</a> command line resource
reservation tool.
<br/>
<ol>
<?php
if ($num_projects > 1) {
  echo '<li>Choose project as omni default: ';
  echo '<select id="pselect" name="project" onchange="update_link()">\n';
  foreach ($projects as $proj) {
    $proj_id = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
    $proj_name = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
    $proj_desc = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
    echo "<option value=\"$proj_name\" title=\"$proj_desc\">$proj_name</option>\n";
  }
  echo '</select></li>';
  // There are multiple projects. Put up a chooser for the default project.
}
?>

  <li>Download this
      <a id='configlink' href='<?php echo $config_link; ?>&version=2.5'>omni_config</a>
      and save it to a file named <code>portal_omni_config</code>.
      <ul>
      <li>If you are running a version of omni older than 2.5, download this <a id='configlink2' href='<?php echo $config_link; ?>&version=2.3.1'>omni_config</a> instead.</li>
      </ul>
</li>
  <li><a href="<?php print $download_url; ?>" target="_blank">
        <?php echo $download_text; ?>
      </a>, noting the path.
  </li>
  <li> Edit the <code>portal_omni_config</code> to correct:
    <ol>
      <?php echo $cert_key_list_items; ?>
      <li>the path to your SSH public key to use for node logon.
        <ul><li>You can use <code>ssh-keygen</code> on many OSes to generate
                an SSH keypair if you do not have one.</li>
        </ul>
      </li>
    </ol>
  </li>
  <li> When running omni:
    <ol>
      <li> Specify the path to the omni config file and the slice name or URN.  For example:
        <pre>omni -c portal_omni_config sliverstatus &lt;slice name or URN&gt;</pre>
      </li>
      <li> Alternatively, you can specify a project with the <code>--project</code> option:
        <pre>omni -c portal_omni_config --project &lt;project name&gt; sliverstatus &lt;slice name or URN&gt;</pre>
      </li>
      </ol>
  </li>
</ol>

  <table id='tip'>
    <tr>
       <td rowspan=3><img id='tipimg' src="http://groups.geni.net/geni/attachment/wiki/GENIExperimenter/Tutorials/Graphics/Symbols-Tips-icon-clear.png?format=raw" width="75" height="75" alt="Tip"></td>
       <td><b>Tip</b> Make sure you are running <b>omni 2.3.1</b> or later.</td>
    </tr>
       <tr><td>To determine the version of an existing <code>omni</code> installation, run:
	            <pre>omni --version</pre>
       </td></tr>
        <tr><td>If necessary, <a href="http://trac.gpolab.bbn.com/gcf/wiki#GettingStarted" target='_blank'>download</a> and <a href="http://trac.gpolab.bbn.com/gcf/wiki/QuickStart" target='_blank'>install</a> the latest version of <code>omni</code>.</td></tr>

  </table>

<p/>
</div>

<div id="omni21">
<i>Note: these instructions are for omni 2.1 or earlier.
<a id="want22" href="#">See instructions for newer versions</a></i>
<p>
Download and use a template omni_config file for use with the
<a href="http://trac.gpolab.bbn.com/gcf/wiki">Omni</a> command line resource
reservation tool.
<br/>
<ol>
  <li>Download this <a href='portal_omni_config.php?version=2.1'>omni_config</a> and save it to a file named <code>portal_omni_config</code>.</li>
  <li><a href="<?php print $download_url; ?>" target="_blank">
        <?php echo $download_text; ?>
      </a>, noting the path.
  </li>
  <li> Edit the <code>portal_omni_config</code> to correct:
    <ol>
      <?php echo $cert_key_list_items; ?>
      <li> the path to your SSH public key to use for node logon.
        <ul><li>You can use <code>ssh-keygen</code> on many OSes to generate
                an SSH keypair if you do not have one.</li>
        </ul>
      </li>
    </ol>
  </li>
  <li> When running omni:
    <ol>
      <li> Specify the path to the omni configuration file and the full slice URN. For example:
        <ul><li><code>omni -c portal_omni_config sliverstatus &lt;slice URN&gt;</code></li></ul>
      </li>
      <li><i>Note: be sure to use the full slice URN when naming your slice, not just the slice name.</i>
        <ul><li>omni 2.2 (and newer) fixes this, by allowing you to specify the project in which your slice lives</li></ul>
      </li>
    </ol>
  </li>
</ol>
<p/>
</div>
<script>
$('#want21').click(function() {
  $('#omni21').show('slow');
  $('#omni22').hide('fast');
});
$('#want22').click(function() {
  $('#omni22').show('slow');
  $('#omni21').hide('fast');
});
/* hide omni 2.1 by default */
$('#omni21').hide();

function update_link() {
  var baseURL = "<?php echo $config_url; ?>";
  var projName = $('#pselect').val();
  var fullURL = baseURL + "?project=" + projName;
  $('#configlink').attr("href", fullURL);
  $('#configlink2').attr("href", fullURL);
}
</script>


<?php
include("footer.php");
?>
