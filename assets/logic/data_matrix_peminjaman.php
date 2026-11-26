<?php
// Membuat dan menampilkan token sekali pakai untuk pengambilan atau pengembalian buku.
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/session.php';
start_app_session();

if (!isset($_SESSION['literaksi_user']['NIS'])) {
    header('Location: ' . app_base_url() . 'autentikasi.php?action=login');
    exit;
}

require_once APP_ROOT . '/config.php';
require_once __DIR__ . '/loan_workflow.php';
require_once __DIR__ . '/layout.php';

$member = $_SESSION['literaksi_user'];
$detailId = '';
$token = null;
$purpose = null;
$expiresAt = null;
$bookTitle = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    $requestedDetail = $_POST['id_detail'] ?? '';
    if (!is_string($csrf) || empty($_SESSION['matrix_csrf']) || !hash_equals($_SESSION['matrix_csrf'], $csrf)) {
        $errorMessage = 'Sesi tidak valid. Kembali ke daftar peminjaman dan coba lagi.';
    } elseif (!is_string($requestedDetail) || $requestedDetail === '' || strlen($requestedDetail) > 30) {
        $errorMessage = 'Detail peminjaman tidak valid.';
    } else {
        $detailId = $requestedDetail;
        try {
            expire_pending_loan_requests($pdo);
            $pdo->beginTransaction();
            $loanQuery = $pdo->prepare('SELECT d.id_detail, d.tgl_kembali, b.judul, p.status,
                    p.reservation_expires_at,
                    (p.reservation_expires_at > CURRENT_TIMESTAMP) AS reservation_is_valid
                FROM detail_peminjaman d
                JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
                LEFT JOIN buku b ON b.id_buku = d.id_buku
                WHERE d.id_detail = ? AND p.NIS = ?
                FOR UPDATE');
            $loanQuery->execute([$detailId, $member['NIS']]);
            $loan = $loanQuery->fetch();
            if (!$loan) {
                throw new RuntimeException('Peminjaman tidak ditemukan.');
            }

            if ($loan['status'] === 'menunggu_pengambilan' && (bool) $loan['reservation_is_valid']) {
                $purpose = 'ambil';
                $expiresAt = (string) $loan['reservation_expires_at'];
            } elseif ($loan['status'] === 'dipinjam' && $loan['tgl_kembali'] === null) {
                $purpose = 'kembali';
                $expiresAt = (string) $pdo->query(
                    'SELECT DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 15 MINUTE)'
                )->fetchColumn();
            } else {
                throw new RuntimeException('Kode tidak tersedia untuk status peminjaman ini.');
            }

            $token = issue_data_matrix_token($pdo, (string) $loan['id_detail'], $purpose, $expiresAt);
            $pdo->commit();
            $_SESSION['data_matrix_code'] = [
                'member_id' => (string) $member['NIS'],
                'detail_id' => $detailId,
                'token' => $token,
                'purpose' => $purpose,
                'expires_at' => $expiresAt,
                'book_title' => (string) ($loan['judul'] ?? 'Buku tidak ditemukan'),
            ];
            header('Location: ' . app_base_url() . 'assets/logic/data_matrix_peminjaman.php');
            exit;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Unable to issue member Data Matrix token: ' . $error->getMessage());
            $errorMessage = $error instanceof RuntimeException
                ? $error->getMessage()
                : 'Kode tidak dapat dibuat saat ini. Minta petugas menggunakan konfirmasi manual.';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $storedCode = $_SESSION['data_matrix_code'] ?? null;
    if (
        is_array($storedCode)
        && ($storedCode['member_id'] ?? null) === (string) $member['NIS']
        && is_string($storedCode['detail_id'] ?? null)
        && is_string($storedCode['token'] ?? null)
        && preg_match('/\A[a-f0-9]{64}\z/', $storedCode['token']) === 1
        && in_array($storedCode['purpose'] ?? null, ['ambil', 'kembali'], true)
        && is_string($storedCode['expires_at'] ?? null)
    ) {
        $checkToken = $pdo->prepare('SELECT t.used_at, t.purpose,
                (t.expires_at > CURRENT_TIMESTAMP) AS token_is_valid,
                p.status, d.tgl_kembali
            FROM data_matrix_tokens t
            JOIN detail_peminjaman d ON d.id_detail = t.id_detail
            JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
            WHERE t.token_hash = ? AND t.id_detail = ? AND p.NIS = ?
            LIMIT 1');
        $checkToken->execute([
            hash('sha256', $storedCode['token']),
            $storedCode['detail_id'],
            $member['NIS'],
        ]);
        $tokenState = $checkToken->fetch();
        $matchesLoanState = $tokenState
            && $tokenState['purpose'] === $storedCode['purpose']
            && $tokenState['used_at'] === null
            && (bool) $tokenState['token_is_valid']
            && (($storedCode['purpose'] === 'ambil' && $tokenState['status'] === 'menunggu_pengambilan')
                || ($storedCode['purpose'] === 'kembali'
                    && $tokenState['status'] === 'dipinjam'
                    && $tokenState['tgl_kembali'] === null));
        if ($matchesLoanState) {
            $detailId = $storedCode['detail_id'];
            $token = $storedCode['token'];
            $purpose = $storedCode['purpose'];
            $expiresAt = $storedCode['expires_at'];
            $bookTitle = is_string($storedCode['book_title'] ?? null)
                ? $storedCode['book_title']
                : 'Buku tidak ditemukan';
        } else {
            $errorMessage = 'Kode sudah digunakan atau kedaluwarsa. Buka kembali dari kartu buku di Peminjaman Saya.';
        }
    } else {
        $errorMessage = 'Buka kode dari kartu buku di halaman Peminjaman Saya.';
    }
} else {
    http_response_code(405);
    $errorMessage = 'Metode permintaan tidak didukung.';
}

$badge = count(array_filter(
    get_notifications($pdo, (string) $member['NIS']),
    fn($notification) => $notification['urgent']
));
$pageTitle = $purpose === 'ambil' ? 'Kode Pengambilan Buku' : 'Kode Pengembalian Buku';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <base href="<?= htmlspecialchars(app_base_url()) ?>">
    <link rel="icon" href="assets/img/logo.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(APP_ROOT . '/assets/css/style.css') ?>">
</head>
<body class="dash-body">
    <?php render_sidebar('peminjaman'); ?>
    <main class="main">
        <?php render_topbar($member, $badge, 'peminjaman'); ?>
        <section class="data-matrix-page" aria-labelledby="data-matrix-title">
            <div class="data-matrix-card">
                <?php if ($token !== null): ?>
                    <p class="admin-eyebrow">PEMINJAMAN #<?= htmlspecialchars((string) $detailId) ?></p>
                    <h1 id="data-matrix-title"><?= htmlspecialchars($pageTitle) ?></h1>
                    <p class="data-matrix-book"><?= htmlspecialchars($bookTitle) ?></p>
                    <canvas class="data-matrix-code" data-matrix-value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"
                        role="img" aria-label="Kode Data Matrix untuk petugas"></canvas>
                    <p id="data-matrix-render-status" class="data-matrix-status" role="status" aria-live="polite">
                        Menyiapkan kode Data Matrix...
                    </p>
                <?php else: ?>
                    <div class="loan-result-icon is-failure" aria-hidden="true"><?= icon('close', 36) ?></div>
                    <h1 id="data-matrix-title">Kode tidak tersedia</h1>
                    <div class="alert bad" role="status"><?= htmlspecialchars($errorMessage) ?></div>
                <?php endif; ?>
                <a class="btn-secondary" href="user.php?page=peminjaman">Kembali ke Peminjaman Saya</a>
            </div>
        </section>
        <?php render_site_footer(); ?>
    </main>
    <?php if ($token !== null): ?>
        <script src="assets/js/data-matrix.bundle.js?v=<?= filemtime(APP_ROOT . '/assets/js/data-matrix.bundle.js') ?>" defer></script>
    <?php endif; ?>
</body>
</html>
