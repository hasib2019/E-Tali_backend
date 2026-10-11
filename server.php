<?php

/*
 * Router for `php artisan serve` only (ServeCommand prefers this file over the
 * framework's copy; Apache/nginx never load it). Same as the framework router,
 * plus one fix for the /web-app SPA:
 *
 * PHP's built-in server resolves /web-app/party/12 to public/web-app/index.html
 * and sets SCRIPT_NAME=/web-app/index.html, PATH_INFO=/party/12. Laravel then
 * derives a base URL of /web-app and routes "/party/12" → 404, so deep links
 * and refreshes inside the web app break locally. Point the script vars back
 * at the real front controller so Laravel sees the full path.
 */

$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

$_SERVER['SCRIPT_FILENAME'] = $publicPath.'/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php'.$uri;
$_SERVER['PATH_INFO'] = $uri;

$formattedDateTime = date('D M j H:i:s Y');

$requestMethod = $_SERVER['REQUEST_METHOD'];
$remoteAddress = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];

file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");

require_once $publicPath.'/index.php';
