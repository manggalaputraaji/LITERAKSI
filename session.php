<?php

function start_app_session(): void
{
    ini_set('session.gc_maxlifetime', (string) (30 * 24 * 60 * 60));

    $isLoginPost = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'login.php'
        && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    $remember = $isLoginPost
        ? isset($_POST['remember'])
        : isset($_COOKIE['literaksi_remember']);

    session_set_cookie_params([
        'lifetime' => $remember ? 30 * 24 * 60 * 60 : 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function set_remember_cookie(bool $remember): void
{
    setcookie('literaksi_remember', $remember ? '1' : '', [
        'expires' => $remember ? time() + 30 * 24 * 60 * 60 : time() - 3600,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
