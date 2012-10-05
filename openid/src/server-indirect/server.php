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

header('Cache-Control: no-cache');
header('Pragma: no-cache');

if (function_exists('getOpenIDStore')) {
    require_once 'lib/session.php';
    require_once 'lib/actions.php';

    init();

    $action = getAction();
    if (!function_exists($action)) {
        $action = 'action_default';
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