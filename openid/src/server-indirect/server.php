<?php

$path_extra = dirname(dirname(dirname(__FILE__)));
$path = ini_get('include_path');
$path = $path_extra . PATH_SEPARATOR . $path;
// Ugh. Hardcode the path to the portal code. Because the OpenID
// indirect server (the one the browser gets redirected to) sits
// inside https://xyz.com/secure, it gets the include path inherited
// from the /secure definition in apache2.conf. But it needs access to
// portal code. So for now, hardcode the path.
$path = '/var/www/secure' . PATH_SEPARATOR . $path;
ini_set('include_path', $path);

$try_include = @include 'config.php';

if (!$try_include) {
    header("Location: setup.php");
}

/* Bring in some GENI code. */
require_once "settings.php";
require_once "user.php";
require_once "proj_slice_member.php";
require_once "irods_utils.php";

header('Cache-Control: no-cache');
header('Pragma: no-cache');

define('server_page_template',
'<html>
  <head>
    <meta http-equiv="cache-control" content="no-cache"/>
    <meta http-equiv="pragma" content="no-cache"/>
    <title>%s</title>
    <link type="text/css" href="/common/css/portal.css" rel="Stylesheet"/>
  </head>
  <body>
<div id="header">
  <div id="header-top">
    <div id="metanav" class="nav">
     <ul>
      <li>Logged in as <b>%s</b> (%s)</li>
      <li style="border-right: none">
        <a href="%s">Logout</a>
      </li>
     </ul>
    </div>
  </div>
</div>
<div id="content-outer">
  <div id="content">
    <h1>GENI Portal OpenID Trust</h1>
    You are about to release the following information to <b>%s</b>.
    <br/><br/>
    <table>
    %s
    </table><br/>
    Do you trust <b>%s</b> ?
    <br/><br/>
    <form action="%s" method="post">
      <input type="submit" name="save" value="Yes, send my information"/>
      <input type="submit" name="cancel" value="No, I do not trust that site"/>
    </form>
  </div>
</div>
  </body>
</html>');



function make_trust_page($geni_user, $title, $trust_root, $authorize_url, $released_data) {
  $pretty_name = $geni_user->prettyName();
  $username = $geni_user->username;
  $logout_url = '/secure/dologout.php';

  $page = sprintf(server_page_template, $title, $pretty_name, $username,
                  $logout_url, $trust_root, $released_data, $trust_root, $authorize_url);
  return $page;
}


function action_show_trust() {
  $server =& getServer();
  $info = getRequestInfo();
  if (! $info) {
    $info = $server->decodeRequest();
    setRequestInfo($info);
  }

  $geni_user = geni_loadUser();
  $req_url = idURL($geni_user->username);
  $trust_root = htmlspecialchars($info->trust_root);
  $title = 'GENI OpenID Trust';
  $authorize_url = buildURL('authorize', true);

  // Build up a string describing what will be sent.
  // Use $info to determine what fields are being sent
  // username, email are always included
  // Each field is a row in a table with label and value
  $released_data = "";
  $geni_user = geni_loadUser();
  if ($geni_user) {
    $released_data .= "<tr><td>Username</td><td>" . $geni_user->username . "</td></tr>\n";
    $released_data .= "<tr><td>Email</td><td>" . $geni_user->email() . "</td></tr>\n";
  }
  // Then depending on what is requested, these other fields
  $ax_request = Auth_OpenID_AX_FetchRequest::fromOpenIDRequest($info);
  if ($ax_request and ! Auth_OpenID_AX::isError($ax_request)) {
    add_project_slice_info($geni_user, $projects, $slices);
    foreach ($ax_request->iterTypes() as $ax_req_type) {
      switch ($ax_req_type) {
        case 'http://geni.net/projects':
	  // project uid|name for unexpired projects you are in
	  if ($projects) {
            $released_data .= "<tr><td>Projects</td><td>";
	    $hadOne = False;
	    foreach ($projects as $project) {
              if ($hadOne) {
                $released_data .= ", ";
              }
	      $hadOne = True;
	      $released_data .= $project;
	    }
	    $released_data .= "</td></tr>\n";
          }
	  break;
        case 'http://geni.net/slices':
	  // slice_id|project+id|slice_name for unexpired slices you are in
	  if ($slices) {
	    $released_data .= "<tr><td>Slices</td><td>";
	    $hadOne = False;
	    foreach ($slices as $slice) {
              if ($hadOne) {
                $released_data .= ", ";
              }
	      $hadOne = True;
	      $released_data .= $slice;
	    }
	    $released_data .= "</td></tr>\n";
          }
	  break;
        case 'http://geni.net/user/urn':
	  $urn = $geni_user->urn();
	  $urn = str_replace('+', '|', $urn);
	  // your GENI CH URN (includes username)
	  $released_data .= "<tr><td>URN</td><td>" . $urn . "</td></tr>\n";
	  break;
        case 'http://geni.net/user/prettyname':
	  // your full first+last name
	  $released_data .= "<tr><td>Name</td><td>" . $geni_user->prettyName() . "</td></tr>\n";
	  break;
        case 'http://geni.net/wimax/username':
        case 'http://geni.net/wimax/wimax_username':
	  if(isset($geni_user->ma_member->wimax_username)) {
	    // your GENI Wireless (Orbit / WiMAX) username, else not sent
	    $released_data .= "<tr><td>GENI Wireless username</td><td>" . $geni_user->ma_member->wimax_username . "</td></tr>\n";
	  }
	  break;
        case 'http://geni.net/irods/username':
	  if(isset($geni_user->ma_member->irods_username)) {
	    // your iRODS username
	    $released_data .= "<tr><td>iRODS username</td><td>" . $geni_user->ma_member->irods_username . "</td></tr>\n";
	  }
	  break;
        case 'http://geni.net/irods/zone':
	  if (irods_default_zone()) {
	    $released_data .= "<tr><td>iRODS zone</td><td>" . irods_default_zone() . "</td></tr>\n";
	  }
	  break;
      } // End switch
    } // End foreach req_type
  } // End if ax_request

  $text = make_trust_page($geni_user, $title, $trust_root, $authorize_url, $released_data);
  $headers = array();
  return array($headers, $text);
}

function action_authorize() {
  $server =& getServer();
  $info = getRequestInfo();
  if (! $info) {
    $info = $server->decodeRequest();
  }

  // Throw away the info, we no longer need it.
  setRequestInfo();
  $trusted = isset($_POST['save']);
  if ($trusted) {
    return send_geni_user($server, $info);
  } else {
    return send_cancel($info);
  }
}

function send_cancel($info)
{
    if ($info) {
        $url = $info->getCancelURL();
    } else {
        $url = getServerURL();
    }
    return redirect_render($url);
}



function add_project_slice_info($geni_user, &$projects, &$slices) {
  $projects = array();
  $slices = array();
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  $retVal  = get_project_slice_member_info($sa_url, $ma_url, $geni_user, True);
  $project_objects = $retVal[0];
  $slice_objects = $retVal[1];
  $member_objects = $retVal[2];
  $project_slice_map = $retVal[3];
  $project_activeslice_map = $retVal[4];

  foreach ($project_slice_map as $project_id => $proj_slices) {
    $proj = $project_objects[$project_id];
    $expired = $proj[PA_PROJECT_TABLE_FIELDNAME::EXPIRED];
    if ($expired == 't') {
      continue;
    }
    $pval = "$project_id";
    $pval .= "|" . $proj['project_name'];
    $projects[] = $pval;
    /* error_log("project $project_id: " . print_r($project_objects, true)); */
    foreach ($proj_slices as $slice_id) {
      //error_log("OpenID found slice $slice_id in project $project_id");
      $slice = $slice_objects[$slice_id];
      $expired = $slice[SA_SLICE_TABLE_FIELDNAME::EXPIRED];
      if ($expired == 't') {
        continue;
      }
      $sval = "$slice_id|$project_id";
      $sval .= "|" . $slice['slice_name'];
      $slices[] = $sval;
    }
  }
}


function send_geni_user($server, $info) {
  $geni_user = geni_loadUser();
  $req_url = idURL($geni_user->username);
  $response =& $info->answer(true, null, $req_url);

  // Answer with some sample Simple Registration data.
  global $portal_cert_file;
  global $portal_private_key_file;
  $sreg_data = array();
  if ($geni_user) {
    $sreg_data['nickname'] = $geni_user->username;
    $sreg_data['email'] = $geni_user->email();
  }
  if (empty($sreg_data)) {
    error_log("OpenID: Unable to access user information.");
  }
  // Add the simple registration response values to the OpenID
  // response message.
  $sreg_request = Auth_OpenID_SRegRequest::fromOpenIDRequest($info);

  $sreg_response = Auth_OpenID_SRegResponse::extractResponse($sreg_request,
                                                             $sreg_data);

  $sreg_response->toMessage($response->fields);

  /*
   * Attribute Exchange (AX) is an OpenID extension to pass additional
   * attributes. This code was derived by looking at some client
   * examples and the AX code. No server-side examples of PHP OpenID
   * AX were found.
   *
   * AX seems to be fragile. Small changes to the code below can
   * result in authentication failures.
   *
   * The user URN has '+' characters but these consistently caused
   * authentication failures in testing. Replacing the '+' with '|'
   * worked, so that is a necessary transformation below.
   */
  $ax_request = Auth_OpenID_AX_FetchRequest::fromOpenIDRequest($info);
  if ($ax_request and ! Auth_OpenID_AX::isError($ax_request)) {
    /* error_log("received AX request: " . print_r($ax_request, true)); */
    $ax_response = new Auth_OpenID_AX_FetchResponse();
    add_project_slice_info($geni_user, $projects, $slices);
    foreach ($ax_request->iterTypes() as $ax_req_type) {
      switch ($ax_req_type) {
      case 'http://geni.net/projects':
        $ax_response->setValues($ax_req_type, $projects);
        break;
      case 'http://geni.net/slices':
        $ax_response->setValues($ax_req_type, $slices);
        break;
      case 'http://geni.net/user/urn':
        $urn = $geni_user->urn();
        $urn = str_replace('+', '|', $urn);
        $ax_response->addValue('http://geni.net/user/urn', $urn);
        break;
      case 'http://geni.net/user/prettyname':
        $ax_response->addValue($ax_req_type, $geni_user->prettyName());
        break;
      case 'http://geni.net/wimax/username':
      case 'http://geni.net/wimax/wimax_username':
        $wimax_name = null;
        if(isset($geni_user->ma_member->wimax_username)) {
          $wimax_name = $geni_user->ma_member->wimax_username;
        }
        /* Only send wimax name if it exists. */
        if ($wimax_name) {
          $ax_response->addValue($ax_req_type, $wimax_name);
        }
        break;
      case 'http://geni.net/irods/username':
        /* Get the iRODS username. Do we need to respect the
         * 'irods_enabled' flag?
         */
        $irods_username = null;
        if(isset($geni_user->ma_member->irods_username)) {
          $irods_username = $geni_user->ma_member->irods_username;
        }
        /* Only send it if it exists. */
        if ($irods_username) {
          error_log("Returning iRODS username $irods_username for user "
                    . $geni_user->urn());
          $ax_response->addValue($ax_req_type, $irods_username);
        } else {
          error_log("No iRODS username in OpenID for user "
                    . $geni_user->urn());
        }
        break;
      case 'http://geni.net/irods/zone':
        /* Get the IRods zone for this user. */
        $irods_zone = irods_default_zone();
        /* Only send it if it exists. */
        if ($irods_zone) {
          error_log("Returning iRODS zone $irods_zone for user "
                    . $geni_user->urn());
          $ax_response->addValue($ax_req_type, $irods_zone);
        } else {
          error_log("No iRODS zone in OpenID for user "
                    . $geni_user->urn());
        }
        break;
      }
    }
    $ax_response->toMessage($response->fields);
  }

  // Generate a response to send to the user agent.
  $webresponse =& $server->encodeResponse($response);

  $new_headers = array();

  foreach ($webresponse->headers as $k => $v) {
    $new_headers[] = $k.": ".$v;
  }

  return array($new_headers, $webresponse->body);
}

if (function_exists('getOpenIDStore')) {
    require_once 'lib/session.php';
    require_once 'lib/actions.php';

    init();

    $action = getAction();
    if (!function_exists($action)) {
        $action = 'action_show_trust';
    }
    $resp = $action();

    writeResponse($resp);
} else {
?>
<html>
  <head>
    <title>PHP OpenID Server</title>
    <body>
      <h1>PHP OpenID Server</h1>
      <p>
        This server needs to be configured before it can be used. Edit
        <code>config.php</code> to reflect your server's setup, then
        load this page again.
      </p>
    </body>
  </head>
</html>
<?php
}
?>