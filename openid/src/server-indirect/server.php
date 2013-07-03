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

header('Cache-Control: no-cache');
header('Pragma: no-cache');

define('page_template',
'<html>
  <head>
    <meta http-equiv="cache-control" content="no-cache"/>
    <meta http-equiv="pragma" content="no-cache"/>
    <title>%s</title>
  </head>
  <body>
<div id="content">
    <h1>GENI Portal OpenID Trust</h1>
<p>You are about to release some of your information to <b>%s</b>.</p
<p>Do you trust <b>%s</b>? </p>
<form action="%s" method="post">
<input type="submit" name="save" value="Send my information" />
<input type="submit" name="cancel" value="Cancel" />
</form>
</div>
  </body>
</html>');


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
  $text = sprintf(page_template, $title, $trust_root, $trust_root,
                  $authorize_url);
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