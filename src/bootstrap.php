<?php
// Single include point for every page: constants, error mode, session, and the rest of src/.

define('APP_ROOT', dirname(__DIR__));
define('VIEWS', APP_ROOT . '/views');

require_once __DIR__ . '/env.php';

if (env('APP_ENV', 'dev') === 'dev') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/google_auth.php';

ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax', // 'Strict' would drop the cookie on the return leg of the Google redirect
    'secure' => !empty($_SERVER['HTTPS']),
]);
session_start();
