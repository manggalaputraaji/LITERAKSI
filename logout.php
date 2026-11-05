<?php
require_once __DIR__ . '/session.php';
start_app_session();
$_SESSION = [];
session_destroy();
set_remember_cookie(false);
setcookie(session_name(), '', [
    'expires' => time() - 42000,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
header('Location: login.php');
exit;
