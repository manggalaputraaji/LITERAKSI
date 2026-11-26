<?php
// Menampilkan hasil pengajuan pinjam yang dikirim melalui session agar data tidak dikirim ulang di URL.
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/session.php';
start_app_session();

if (
    !isset($_SESSION['literaksi_user'])
    || !is_array($_SESSION['literaksi_user'])
    || !isset($_SESSION['literaksi_user']['NIS'])
) {
    header('Location: ' . app_base_url() . 'autentikasi.php?action=login');
    exit;
}

$result = $_SESSION['loan_result'] ?? null;
unset($_SESSION['loan_result']);
if (
    !is_array($result)
    || !isset($result['success'], $result['message'], $result['book_title'], $result['book_id'])
    || !is_bool($result['success'])
    || !is_string($result['message'])
    || !is_string($result['book_title'])
    || !is_string($result['book_id'])
    || !isset($result['loan_id'])
    || !is_string($result['loan_id'])
    || ($result['success'] && (
        !isset($result['matrix_token'], $result['matrix_expires_at'])
        || !is_string($result['matrix_token'])
        || preg_match('/\A[a-f0-9]{64}\z/', $result['matrix_token']) !== 1
        || !is_string($result['matrix_expires_at'])
    ))
) {
    header('Location: ' . app_base_url() . 'user.php?page=peminjaman');
    exit;
}

require_once APP_ROOT . '/config.php';
require_once __DIR__ . '/layout.php';

$user = $_SESSION['literaksi_user'];
$memberId = (string) $user['NIS'];
$success = $result['success'];
$matrixToken = $success ? $result['matrix_token'] : null;
$badge = count(array_filter(get_notifications($pdo, $memberId), fn($notification) => $notification['urgent']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <base href="<?= htmlspecialchars(app_base_url()) ?>">
    <link rel="icon" href="assets/img/logo.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $success ? 'Permintaan Peminjaman Berhasil' : 'Peminjaman Gagal' ?> - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(APP_ROOT . '/assets/css/style.css') ?>">
</head>
<body class="dash-body">
    <?php render_sidebar('peminjaman'); ?>

    <main class="main">
        <?php render_topbar($user, $badge, 'peminjaman'); ?>

        <section class="loan-result-page" aria-labelledby="loan-result-title">
            <div class="loan-result-card <?= $success ? 'is-success' : 'is-failure' ?>"
                role="<?= $success ? 'status' : 'alert' ?>">
                <div class="loan-result-icon <?= $success ? 'is-success' : 'is-failure' ?>" aria-hidden="true">
                    <?= icon($success ? 'check' : 'close', 42) ?>
                </div>
                <h1 id="loan-result-title"><?= $success ? 'Permintaan Peminjaman Berhasil' : 'Peminjaman Gagal' ?></h1>
                <p class="loan-result-message"><?= htmlspecialchars($result['message']) ?></p>
                <p class="loan-result-book">
                    <span>Buku</span>
                    <strong><?= htmlspecialchars($result['book_title']) ?></strong>
                </p>
                <?php if ($success): ?>
                    <p class="loan-result-reference">
                        Nomor peminjaman <strong><?= htmlspecialchars($result['loan_id']) ?></strong>
                    </p>
                    <canvas class="data-matrix-code" data-matrix-value="<?= htmlspecialchars($matrixToken, ENT_QUOTES, 'UTF-8') ?>"
                        role="img" aria-label="Kode Data Matrix untuk pengambilan buku"></canvas>
                    <p id="data-matrix-render-status" class="data-matrix-status" role="status" aria-live="polite">
                        Menyiapkan kode Data Matrix...
                    </p>
                <?php endif; ?>
                <div class="loan-result-actions">
                    <a class="btn-primary" href="user.php?page=peminjaman">Lihat Peminjaman</a>
                    <?php if ($success): ?>
                        <a class="btn-secondary" href="user.php?page=katalog">Kembali ke Katalog</a>
                    <?php else: ?>
                        <a class="btn-secondary" href="user.php?page=detail_buku&amp;id=<?= rawurlencode($result['book_id']) ?>">Kembali ke Buku</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <?php render_site_footer(); ?>
    </main>
<?php if ($success): ?>
    <script src="assets/js/data-matrix.bundle.js?v=<?= filemtime(APP_ROOT . '/assets/js/data-matrix.bundle.js') ?>" defer></script>
<?php endif; ?>
</body>
</html>
