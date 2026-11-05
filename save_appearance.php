<?php
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

if ($field === 'theme' && in_array($value, ['light', 'dark'], true)) {
    $column = 'tema';
} elseif ($field === 'accent' && in_array($value, ['violet', 'blue', 'teal', 'rose'], true)) {
    $column = 'warna';
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Pilihan tampilan tidak valid.']);
    exit;
}

require __DIR__ . '/config.php';
$query = $pdo->prepare("INSERT INTO pengaturan_anggota (NIS, `$column`) VALUES (?, ?)
    ON DUPLICATE KEY UPDATE `$column` = VALUES(`$column`)");
$query->execute([$_SESSION['literaksi_user']['NIS'], $value]);

$_SESSION['literaksi_user'][$column] = $value;
$_SESSION['member_appearance_loaded'] = true;

echo json_encode(['success' => true]);
