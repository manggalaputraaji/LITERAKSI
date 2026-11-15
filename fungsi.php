<?php
$action = $_GET['action'] ?? '';
if (!is_string($action) || !in_array($action, ['book_cover', 'profile_photo', 'profile_banner', 'save_appearance'], true)) {
    http_response_code(404);
    exit;
}

if ($action === 'book_cover') {
?>
<?php

if (($_GET['action'] ?? '') !== 'book_cover') { http_response_code(404); exit; }
$id = $_GET['id'] ?? '';
if (!is_string($id) || $id === '') {
    http_response_code(404);
    exit;
}

$directory = __DIR__ . '/uploads/book-covers/';
$filenames = [];
if (preg_match('/\A[A-Za-z0-9_-]+\z/', $id)) {
    $filenames[] = $id;
}
$filenames[] = hash('sha256', $id);
$imageTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'avif' => 'image/avif',
    'bmp' => 'image/bmp',
    'ico' => 'image/vnd.microsoft.icon',
];

foreach ($filenames as $filename) {
    foreach ($imageTypes as $extension => $contentType) {
        $path = $directory . $filename . '.' . $extension;
        if (is_file($path)) {
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: inline; filename="book-cover.' . $extension . '"');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-cache');
            readfile($path);
            exit;
        }
    }
}

http_response_code(404);
?>
<?php } elseif ($action === 'profile_photo') { ?>
<?php
if (($_GET['action'] ?? '') !== 'profile_photo') { http_response_code(404); exit; }
require_once __DIR__ . '/session.php';
start_app_session();

if (!isset($_SESSION['literaksi_user']['NIS']) && !is_admin_session()) {
    http_response_code(401);
    exit;
}

require __DIR__ . '/config.php';

$requestedNis = $_GET['nis'] ?? ($_SESSION['literaksi_user']['NIS'] ?? '');
if (!is_string($requestedNis) || $requestedNis === '' || strlen($requestedNis) > 30) {
    http_response_code(404);
    exit;
}
$query = $pdo->prepare('SELECT foto_profil FROM `preferensi_user` WHERE NIS = ?');
$query->execute([$requestedNis]);
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
?>
<?php } elseif ($action === 'profile_banner') { ?>
<?php
require_once __DIR__ . '/session.php';
start_app_session();

if (!isset($_SESSION['literaksi_user']['NIS']) && !is_admin_session()) {
    http_response_code(401);
    exit;
}

require __DIR__ . '/config.php';
$requestedNis = $_GET['nis'] ?? ($_SESSION['literaksi_user']['NIS'] ?? '');
if (!is_string($requestedNis) || $requestedNis === '' || strlen($requestedNis) > 30) {
    http_response_code(404);
    exit;
}
$query = $pdo->prepare('SELECT banner_foto FROM `preferensi_user` WHERE NIS = ?');
$query->execute([$requestedNis]);
$filename = $query->fetchColumn();

if (!is_string($filename) || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $filename)) {
    http_response_code(404);
    exit;
}

$path = __DIR__ . '/uploads/profile-banners/' . $filename;
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
?>
<?php } else { ?>
<?php
if (($_GET['action'] ?? '') !== 'save_appearance') { http_response_code(404); exit; }
require_once __DIR__ . '/session.php';
start_app_session();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak didukung.']);
    exit;
}

if (!isset($_SESSION['literaksi_user']['NIS'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Silakan masuk kembali.']);
    exit;
}

$csrf = $_POST['csrf'] ?? '';
$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';
if (!is_string($csrf) || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Sesi berakhir. Muat ulang halaman.']);
    exit;
}

if (!is_string($field) || !is_string($value)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Pilihan tampilan tidak valid.']);
    exit;
}

if ($field !== 'accent' || !in_array($value, ['violet', 'blue', 'teal', 'rose'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Pilihan tampilan tidak valid.']);
    exit;
}

require __DIR__ . '/config.php';
$query = $pdo->prepare('INSERT INTO `preferensi_user` (NIS, warna) VALUES (?, ?)
    ON DUPLICATE KEY UPDATE warna = VALUES(warna)');
$query->execute([$_SESSION['literaksi_user']['NIS'], $value]);

$_SESSION['literaksi_user']['warna'] = $value;
$_SESSION['member_appearance_loaded'] = true;

echo json_encode(['success' => true]);
} ?>
