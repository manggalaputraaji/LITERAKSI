<?php
require_once __DIR__ . '/bootstrap.php';

// Tolak aksi yang tidak tersedia sebelum memilih endpoint yang sesuai.
$action = $_GET['action'] ?? '';
if (
    !is_string($action)
    || !in_array(
        $action,
        ['book_cover', 'profile_photo', 'profile_banner', 'save_appearance', 'admin_loan_notifications', 'scan_data_matrix'],
        true
    )
) {
    http_response_code(404);
    exit;
}

if ($action === 'book_cover') {
?>
<?php

$id = $_GET['id'] ?? '';
if (!is_string($id) || $id === '') {
    http_response_code(404);
    exit;
}

$directory = APP_ROOT . '/uploads/book-covers/';
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
<?php } elseif ($action === 'admin_loan_notifications') { ?>
<?php
require_once __DIR__ . '/session.php';
start_app_session();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak didukung.']);
    exit;
}

if (!is_admin_session()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Silakan masuk sebagai admin.']);
    exit;
}

$adminId = (string) $_SESSION['literaksi_admin']['id_petugas'];
session_write_close();
require_once APP_ROOT . '/config.php';

$mode = $_GET['mode'] ?? '';
if (!is_string($mode) || !in_array($mode, ['init', 'poll'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Mode notifikasi tidak valid.']);
    exit;
}

$unreadQuery = $pdo->prepare('SELECT COUNT(*) FROM notifikasi_admin WHERE id_petugas = ? AND read_at IS NULL');
$unreadQuery->execute([$adminId]);
$unreadCount = (int) $unreadQuery->fetchColumn();
$latestQuery = $pdo->prepare('SELECT COALESCE(MAX(id), 0) FROM notifikasi_admin WHERE id_petugas = ?');
$latestQuery->execute([$adminId]);
$latestId = (int) $latestQuery->fetchColumn();

if ($mode === 'init') {
    echo json_encode([
        'success' => true,
        'cursor' => $latestId,
        'unread_count' => $unreadCount,
        'notifications' => [],
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$after = $_GET['after'] ?? '0';
if (!is_string($after) || !ctype_digit($after) || strlen($after) > 18) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Penanda notifikasi tidak valid.']);
    exit;
}

$notificationsQuery = $pdo->prepare('SELECT n.id, n.id_peminjaman, n.created_at, p.status AS loan_status,
        COALESCE(u.nama, \'Pengguna dihapus\') AS member_name,
        COALESCE(b.judul, \'Buku tidak ditemukan\') AS book_title
    FROM notifikasi_admin n
    JOIN peminjaman p ON p.id_peminjaman = n.id_peminjaman
    JOIN detail_peminjaman d ON d.id_peminjaman = p.id_peminjaman
    LEFT JOIN `user` u ON u.NIS = p.NIS
    LEFT JOIN buku b ON b.id_buku = d.id_buku
    WHERE n.id_petugas = ? AND n.id > ? AND n.read_at IS NULL
    ORDER BY n.id ASC
    LIMIT 20');
$notificationsQuery->execute([$adminId, (int) $after]);
$notifications = $notificationsQuery->fetchAll(PDO::FETCH_ASSOC);
$cursor = count($notifications) === 20
    ? (int) $notifications[count($notifications) - 1]['id']
    : $latestId;

echo json_encode([
    'success' => true,
    'cursor' => $cursor,
    'unread_count' => $unreadCount,
    'notifications' => $notifications,
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
?>
<?php } elseif ($action === 'scan_data_matrix') { ?>
<?php
require_once __DIR__ . '/session.php';
start_app_session();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$respond = static function (int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $respond(405, ['success' => false, 'message' => 'Metode permintaan tidak didukung.']);
}
if (!is_admin_session()) {
    $respond(401, ['success' => false, 'message' => 'Silakan masuk sebagai admin.']);
}

$csrf = $_POST['csrf'] ?? '';
if (!is_string($csrf) || empty($_SESSION['admin_csrf']) || !hash_equals($_SESSION['admin_csrf'], $csrf)) {
    $respond(400, ['success' => false, 'message' => 'Sesi pemindai tidak valid. Muat ulang halaman admin.']);
}
$token = $_POST['token'] ?? '';
if (!is_string($token) || preg_match('/\A[a-f0-9]{64}\z/', $token) !== 1) {
    $respond(400, ['success' => false, 'message' => 'Kode Data Matrix tidak dikenali.']);
}
$adminId = (string) $_SESSION['literaksi_admin']['id_petugas'];
session_write_close();

require_once APP_ROOT . '/config.php';
require_once __DIR__ . '/loan_workflow.php';

try {
    expire_pending_loan_requests($pdo);
    $pdo->beginTransaction();
    $tokenLookup = $pdo->prepare('SELECT id_detail FROM data_matrix_tokens WHERE token_hash = ? LIMIT 1');
    $tokenLookup->execute([hash('sha256', $token)]);
    $detailId = $tokenLookup->fetchColumn();
    if ($detailId === false) {
        $pdo->rollBack();
        $respond(404, ['success' => false, 'message' => 'Kode tidak ditemukan atau sudah tidak berlaku.']);
    }

    $loanQuery = $pdo->prepare('SELECT d.id_detail, d.tgl_kembali, p.id_peminjaman,
            p.status, p.reservation_expires_at,
            (p.reservation_expires_at > CURRENT_TIMESTAMP) AS reservation_is_valid
        FROM detail_peminjaman d
        JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
        WHERE d.id_detail = ?
        FOR UPDATE');
    $loanQuery->execute([$detailId]);
    $loan = $loanQuery->fetch();
    $tokenQuery = $pdo->prepare('SELECT id, purpose, expires_at, used_at,
            (expires_at > CURRENT_TIMESTAMP) AS token_is_valid
        FROM data_matrix_tokens
        WHERE token_hash = ? AND id_detail = ?
        FOR UPDATE');
    $tokenQuery->execute([hash('sha256', $token), $detailId]);
    $matrixToken = $tokenQuery->fetch();

    if (!$loan || !$matrixToken) {
        $pdo->rollBack();
        $respond(404, ['success' => false, 'message' => 'Kode tidak ditemukan atau sudah tidak berlaku.']);
    }
    if ($matrixToken['used_at'] !== null) {
        $pdo->rollBack();
        $respond(409, ['success' => false, 'message' => 'Kode sudah digunakan atau peminjaman tidak lagi aktif.']);
    }
    if ($matrixToken['purpose'] === 'ambil' && $loan['status'] === 'dibatalkan') {
        $pdo->rollBack();
        $respond(410, ['success' => false, 'message' => 'Batas pengambilan sudah lewat. Permintaan dibatalkan.']);
    }
    if (!(bool) $matrixToken['token_is_valid']) {
        $pdo->rollBack();
        $respond(410, ['success' => false, 'message' => 'Kode sudah kedaluwarsa. Minta anggota membuka kode baru.']);
    }

    $detailId = (string) $loan['id_detail'];
    if ($matrixToken['purpose'] === 'ambil') {
        if ($loan['status'] !== 'menunggu_pengambilan') {
            $pdo->rollBack();
            $respond(409, ['success' => false, 'message' => 'Permintaan pengambilan sudah diproses.']);
        }
        if (!(bool) $loan['reservation_is_valid']) {
            $pdo->prepare('UPDATE peminjaman SET status = \'dibatalkan\' WHERE id_peminjaman = ?')
                ->execute([$loan['id_peminjaman']]);
            $pdo->commit();
            $respond(410, ['success' => false, 'message' => 'Batas pengambilan sudah lewat. Permintaan dibatalkan.']);
        }
        $activate = $pdo->prepare('UPDATE peminjaman
            SET status = \'dipinjam\', tgl_peminjam = CURDATE(),
                tgl_jatuh_tempo = DATE_ADD(CURDATE(), INTERVAL 7 DAY),
                id_petugas = ?, reservation_expires_at = NULL
            WHERE id_peminjaman = ? AND status = \'menunggu_pengambilan\'');
        $activate->execute([$adminId, $loan['id_peminjaman']]);
        if ($activate->rowCount() === 0) {
            $pdo->rollBack();
            $respond(409, ['success' => false, 'message' => 'Permintaan pengambilan sudah diproses.']);
        }
        $message = 'Peminjaman berhasil diaktifkan. Batas pengembalian dihitung mulai hari ini.';
        $purpose = 'ambil';
    } elseif ($matrixToken['purpose'] === 'kembali') {
        if ($loan['status'] !== 'dipinjam' || $loan['tgl_kembali'] !== null) {
            $pdo->rollBack();
            $respond(409, ['success' => false, 'message' => 'Peminjaman ini tidak lagi menunggu pengembalian.']);
        }
        $returnBook = $pdo->prepare('UPDATE detail_peminjaman
            SET tgl_kembali = CURDATE(), tgl_pengajuan_kembali = NULL
            WHERE id_detail = ? AND tgl_kembali IS NULL');
        $returnBook->execute([$detailId]);
        if ($returnBook->rowCount() === 0) {
            $pdo->rollBack();
            $respond(409, ['success' => false, 'message' => 'Pengembalian sudah diproses.']);
        }
        $remaining = $pdo->prepare('SELECT COUNT(*) FROM detail_peminjaman
            WHERE id_peminjaman = ? AND tgl_kembali IS NULL');
        $remaining->execute([$loan['id_peminjaman']]);
        if ((int) $remaining->fetchColumn() === 0) {
            $pdo->prepare('UPDATE peminjaman SET status = \'kembali\' WHERE id_peminjaman = ?')
                ->execute([$loan['id_peminjaman']]);
        }
        $message = 'Pengembalian buku berhasil dicatat.';
        $purpose = 'kembali';
    } else {
        $pdo->rollBack();
        $respond(400, ['success' => false, 'message' => 'Tujuan kode Data Matrix tidak valid.']);
    }

    $pdo->prepare('UPDATE data_matrix_tokens SET used_at = CURRENT_TIMESTAMP
        WHERE id_detail = ? AND purpose = ? AND used_at IS NULL')
        ->execute([$detailId, $purpose]);
    $pdo->commit();
    $respond(200, ['success' => true, 'message' => $message]);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Data Matrix scan failed: ' . $error->getMessage());
    $respond(500, ['success' => false, 'message' => 'Kode tidak dapat diproses saat ini. Silakan coba lagi atau gunakan konfirmasi manual.']);
}
?>
<?php } elseif ($action === 'profile_photo') { ?>
<?php
require_once __DIR__ . '/session.php';
start_app_session();

if (!isset($_SESSION['literaksi_user']['NIS']) && !is_admin_session()) {
    http_response_code(401);
    exit;
}

require_once APP_ROOT . '/config.php';

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

$path = APP_ROOT . '/uploads/profile-photos/' . $filename;
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

require_once APP_ROOT . '/config.php';
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

$path = APP_ROOT . '/uploads/profile-banners/' . $filename;
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

require_once APP_ROOT . '/config.php';
$query = $pdo->prepare('INSERT INTO `preferensi_user` (NIS, warna) VALUES (?, ?)
    ON DUPLICATE KEY UPDATE warna = VALUES(warna)');
$query->execute([$_SESSION['literaksi_user']['NIS'], $value]);

$_SESSION['literaksi_user']['warna'] = $value;
$_SESSION['member_appearance_loaded'] = true;

echo json_encode(['success' => true]);
} ?>
