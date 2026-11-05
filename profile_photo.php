<?php
require_once __DIR__ . '/session.php';
start_app_session();

if (!isset($_SESSION['literaksi_user']['NIS'])) {
    http_response_code(401);
    exit;
}

require __DIR__ . '/config.php';

$query = $pdo->prepare('SELECT foto_profil FROM pengaturan_anggota WHERE NIS = ?');
$query->execute([$_SESSION['literaksi_user']['NIS']]);
$filename = $query->fetchColumn();

if (!is_string($filename) || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $filename)) {
    http_response_code(404);
    exit;
}

$path = __DIR__ . '/uploads/profile-photos/' . $filename;
if (!is_file($path)) {
    http_response_code(404);
    exit;
}

$contentTypes = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
header('Content-Type: ' . $contentTypes[pathinfo($filename, PATHINFO_EXTENSION)]);
header('Content-Length: ' . (string) filesize($path));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
readfile($path);
