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

function returnUserInfo() {
  $server =& getServer();
  $info = $server->decodeRequest();

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

  $sreg_response = Auth_OpenID_SRegResponse::extractResponse($sreg_request, $sreg_data);

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

    $resp = returnUserInfo();

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