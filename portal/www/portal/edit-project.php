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

require_once("user.php");
require_once("header.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("pa_client.php");
require_once("pa_constants.php");
require_once("pa_client.php");
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
include("tool-lookupids.php");
show_header('GENI Portal: Projects', $TAB_PROJECTS);

include("tool-breadcrumbs.php");
if (! isset($project)) {
  $project = "new";
  $isnew = true;
  print "<h1>NEW GENI Project</h1>\n";
} else {
  $isnew = false;
  $leadid = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
  if (! uuid_is_valid($leadid)) {
    error_log("edit-project: invalid leadid from DB for project $project_id");
    exit();
  }
  $lead = $user->fetchMember($leadid);
  $leadname = $lead->prettyName();
  $leademail = $lead->email();
  print "<h1>EDIT GENI Project: " . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME] . "</h1>\n";
}

class ProjectField
{
  function __construct($field, $pretty_name, $enabled, $required) {
    $this->field = $field;
    $this->pretty_name = $pretty_name;
    $this->enabled = $enabled;
    $this->required = $required;
  }
  public function show($project)
  {
    $txt = "<tr>";
    $txt .= "<td><b>" . $this->pretty_name . "</b></td>";
    $txt .= "<td><input type=\"text\" name=\"" . $this->field . "\"";
    // $project can be "new", so include is_array test
    if (is_array($project) && array_key_exists($this->field, $project)) {
      $txt .= " value=\"" . $project[$this->field] . "\"";
    }
    if (! $this->enabled) {
      $txt .= " disabled=\"disabled\"";
    }
    $txt .= "/>";
    if ($this->required) {
      $txt .= " - Required";
    }
    $txt .= "</td></tr>\n";
    return $txt;
  }
}

class DateField extends ProjectField
{
  /*
   * NOTE: Update this to use a jQuery DatePicker widget
   */
  public function show($project)
  {
    $txt = "<tr>";
    $txt .= "<td><b>" . $this->pretty_name . "</b></td>";
    $txt .= "<td><input type=\"text\" name=\"" . $this->field . "\"";
    $txt .= " id=\"datepicker\"";
    // $project can be "new", so include is_array test
    if (is_array($project) && array_key_exists($this->field, $project)) {
      $txt .= " value=\"" . $project[$this->field] . "\"";
    }
    if (! $this->enabled) {
      $txt .= " disabled=\"disabled\"";
    }
    $txt .= "/>";
    if ($this->required) {
      $txt .= " - Required";
    }
    $txt .= "</td></tr>\n";
    return $txt;
  }
}

$fields[] = new ProjectField(PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME,
        "Project Name", ($isnew?true:false), ($isnew?true:false));
if (! $isnew) {
  $fields[] = new ProjectField(PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL,
          "Email", false, false);
}
$fields[] = new ProjectField(PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE,
        "Purpose", true, false);
$fields[] = new DateField(PA_PROJECT_TABLE_FIELDNAME::EXPIRATION,
        "Expiration", true, false);

?>
<form method="POST" action="do-edit-project.php">
<table>
<?php
if (! $isnew) {
  print "<input type=\"hidden\" name=\"project_id\" value=\"$project_id\"/>\n";
}
foreach ($fields as $field) {
  print $field->show($project);
}
// FIXME: Note the project name character restrictions if $isnew?
$submit_label = $isnew ? "Create Project" : "Update";
?>

</table>
<?php
echo '<b>Note</b>: Project names must not contain whitespace. Use at most 32 alphanumeric characters or hyphen or underscore: "a-zA-Z0-9_-".</b><br/>';
echo '<b>Note: Project names are public, global and permanent</b>; there can only ever be a single project with a given name, and that name is visible to all registered users.<br/>';
echo '<b>Expiration</b>: The date when this project is closed, and slices will expire. Blank means no expiration.<br/>';
print "<br/>\n";
print "<input type=\"submit\" value=\"$submit_label\"/>\n";
print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
?>
</form>
<script>
  $(function() {
    // minDate = 1 will not allow today or earlier, only future dates.
    $( "#datepicker" ).datepicker({ minDate: 1 });
  });
</script>

<?php


/* print "<h2>Project Policy Defaults</h2>\n"; */
/* print "FIXME: Per project policy defaults go here.<br/>\n"; */
/* print "Slice Membership policy: Project members get <b>User</b> rights on all project slices.<br/><br/>\n"; */

if ($isnew) {
  // FIXME: Either drop this or refactor invite-to-project.php
  /* print "<p style=\"color: grey\">\n"; */
  /* print "Provide a comma-separated list of email addresses of people to invite to your project:<br/>\n"; */
  /* print "<input type=\"textarea\" name=\"invites\" disabled=\"disabled\"/>\n"; */
  /* print "</p>\n"; */
} else {
  print "<h3>Project members</h3>\n";
  print "<table>\n";
  $members = get_project_members($sa_url, $user, $project_id);
  print "<tr><th>Project Member</th><th>Roles</th><th>Send Message</th></tr>\n";
  //print "<tr><td><a href=\"project-member.php?project_id=$project_id&member_id=" . $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID] . "\">$leadname</a></td><td>Project Lead</td><td>All</td><td><button onClick=\"window.location='do-delete-project-member.php?project_id=$project_id&member_id=$leadid'\"><b>Delete</b></button></td><td><a href=\"mailto:$leademail\">Email $leadname</a></td></tr>\n";
  foreach ($members as $member) {
     $member_id = $member['member_id'];
     $member_user = $user->fetchMember($member_id);
     $member_name = $member_user->prettyName();
     $member_email = $member_user->email();
     $member_role_index = $member['role'];
     $member_role = $CS_ATTRIBUTE_TYPE_NAME[$member_role_index];
     $row = "<tr>";
     $row .= "<td><a href=\"project-member.php?project_id=$project_id&member_id=$member_id\">$member_name</a></td>";
     $row .= "<td>$member_role</td>";
     $row .= "<td><a href=\"mailto:$member_email\">$member_email</a></td>";
     $row .= "</tr>";
     print $row;
  }
  print "</table>\n";

  $edit_url = relative_url("edit-project-member.php?project_id=$project_id");
  print "<button onClick=\"window.location='$edit_url'\">";
  print "<b>Edit Membership</b></button><br/>\n";

  $inv_url= relative_url("invite-to-project.php?project_id=$project_id");
  print "<button onClick=\"window.location='$inv_url'\">";
  print "<b>Invite New Project Members</b></button><br/>\n";
}
print "<br/>\n";

if ($isnew) {
  print "<b>Project Lead</b><br/>\n";
  print "There is exactly one project lead for each project. Project leads are ultimately responsible for all activity in all slices in their project, and may be contacted by GENI operations in the event of a problem.<br/><br/>\n";
  print "You will be the project lead on your new project.<br/>\n";
  print "<input type=\"hidden\" name=\"newlead\" value=\"" . $user->account_id . "\"/>\n";
} else {
//   print "Project lead is: <b>$leadname</b><br/>\n";
//   print "<p style=\"color: grey\">\n";
//   print "To transfer project leads, enter email of proposed new project leads to ask them to take over:<br/>\n";
//   print "<input type=\"text\" name=\"newlead\" disabled=\"disabled\"/></p><br/>\n";
}

include("footer.php");
?>
