<?php

function admin_session_name(): string
{
    return 'LITERAKSIADMIN';
}

function is_admin_session_request(): bool
{
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (in_array($script, ['admin.php', 'adminkelola.php'], true)) {
        return true;
    }

    if ($script === 'autentikasi.php') {
        return ($_GET['action'] ?? 'login') === 'loginadmin'
            || (($_GET['action'] ?? '') === 'logout' && ($_GET['role'] ?? '') === 'admin');
    }

    return in_array($script, ['diskusi.php', 'user.php', 'fungsi.php'], true)
        && ($_GET['role'] ?? '') === 'admin';
}

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.gc_maxlifetime', (string) (30 * 24 * 60 * 60));
    $isAdminContext = is_admin_session_request();
    session_name($isAdminContext ? admin_session_name() : 'PHPSESSID');

    $isLoginPost = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'autentikasi.php'
        && ($_GET['action'] ?? 'login') === 'login'
        && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    $remember = !$isAdminContext && ($isLoginPost
        ? isset($_POST['remember'])
        : isset($_COOKIE['literaksi_remember']));

    session_set_cookie_params([
        'lifetime' => $remember ? 30 * 24 * 60 * 60 : 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function is_admin_session(): bool
{
    if (session_name() !== admin_session_name()) {
        return false;
    }

    if (!isset($_SESSION['literaksi_admin'])) {
        return false;
    }

    if (!is_array($_SESSION['literaksi_admin']) || !isset($_SESSION['literaksi_admin']['id_petugas'])) {
        unset($_SESSION['literaksi_admin']);
        return false;
    }

    return true;
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
