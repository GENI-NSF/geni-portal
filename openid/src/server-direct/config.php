<?php
/**
 * Set any extra include paths needed to use the library
 */
//set_include_path(get_include_path() . PATH_SEPARATOR . "/home/tmitchel/openid/php-openid/");

/**
 * The URL for the server.
 *
 * This is the location of server.php. For example:
 *
 * $server_url = 'http://example.com/~user/server.php';
 *
 * This must be a full URL.
 */
function configGetServerURL($path)
{
    $host = $_SERVER['SERVER_NAME'];
    $port = $_SERVER['SERVER_PORT'];
    $s = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '';
    if (($s && $port == "443") || (!$s && $port == "80")) {
        $p = '';
    } else {
        $p = ':' . $port;
    }

    return "http$s://$host$p$path";
}

$server_url = configGetServerURL('/server/server.php');

$indirect_server_url = configGetServerURL('/secure/openid/server.php');


/**
 * Initialize an OpenID store
 *
 * @return object $store an instance of OpenID store (see the
 * documentation for how to create one)
 */
function getOpenIDStore()
{
    require_once "Auth/OpenID/FileStore.php";
    return new Auth_OpenID_FileStore("/tmp/php-openid");
}

?>
