<?php
require_once __DIR__ . '/session.php';
start_app_session();

if (isset($_SESSION['literaksi_user']['NIS'])) {
    header('Location: user.php?page=katalog');
    exit;
}

if (is_admin_session()) {
    header('Location: admin.php?page=dashboard');
    exit;
}

$page = $_GET['page'] ?? 'katalog';
$allowedPages = ['katalog', 'detail_buku'];
if (!is_string($page) || !in_array($page, $allowedPages, true)) {
    http_response_code(404);
    exit('Halaman tamu tidak ditemukan.');
}

$_GET['page'] = $page;
$isGuestEntry = true;
require __DIR__ . '/user.php';
