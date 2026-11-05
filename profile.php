<?php
require_once __DIR__ . '/session.php';
start_app_session();

if (!isset($_SESSION['literaksi_user']) || !is_array($_SESSION['literaksi_user']) || !isset($_SESSION['literaksi_user']['NIS'])) {
    unset($_SESSION['literaksi_user'], $_SESSION['user']);
    header('Location: login.php');
    exit;
}

require 'config.php';
require 'layout.php';

$user = $_SESSION['literaksi_user'];
$nis  = $user['NIS'];

// Table for profile posts (created automatically on first visit).
$pdo->exec('CREATE TABLE IF NOT EXISTS postingan (
    id_post INT AUTO_INCREMENT PRIMARY KEY,
    NIS VARCHAR(30) NOT NULL,
    isi TEXT NOT NULL,
    dibuat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (NIS)
) CHARACTER SET utf8mb4');

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = 'Sesi tidak valid, silakan muat ulang halaman.';
    } elseif (($_POST['action'] ?? '') === 'post') {
        $isi = trim($_POST['isi'] ?? '');
        if ($isi === '') {
            $error = 'Tulisan tidak boleh kosong.';
        } elseif (mb_strlen($isi) > 500) {
            $error = 'Maksimal 500 karakter.';
        } else {
            $pdo->prepare('INSERT INTO postingan (NIS, isi) VALUES (?, ?)')->execute([$nis, $isi]);
            header('Location: profile.php');
            exit;
        }
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $pdo->prepare('DELETE FROM postingan WHERE id_post = ? AND NIS = ?')
            ->execute([(int) ($_POST['id_post'] ?? 0), $nis]);
        header('Location: profile.php');
        exit;
    }
}

$q = $pdo->prepare('SELECT COUNT(*) FROM detail_peminjaman d
    JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
    WHERE p.NIS = ? AND d.tgl_kembali IS NULL');
$q->execute([$nis]);
$dipinjam = (int) $q->fetchColumn();

$q = $pdo->prepare('SELECT COUNT(*) FROM detail_peminjaman d
    JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
    WHERE p.NIS = ?');
$q->execute([$nis]);
$total = (int) $q->fetchColumn();

$q = $pdo->prepare('SELECT id_post, isi, dibuat FROM postingan WHERE NIS = ? ORDER BY id_post DESC LIMIT 50');
$q->execute([$nis]);
$posts = $q->fetchAll();

$page  = 'profil';
$badge = count(array_filter(get_notifications($pdo, $nis), fn($n) => $n['urgent']));
$initial = mb_strtoupper(mb_substr($user['nama'], 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body class="dash-body">

    <?php render_sidebar($page); ?>

    <main class="main">
        <?php render_topbar($user, $badge, $page); ?>

        <section class="profile-head">
            <div class="profile-cover"></div>
            <div class="avatar-lg">
                <?php if (!empty($_SESSION['literaksi_user']['foto_profil'])): ?>
                    <img src="profile_photo.php?v=<?= rawurlencode($_SESSION['literaksi_user']['foto_profil']) ?>" alt="Foto profil <?= htmlspecialchars($user['nama']) ?>">
                <?php else: ?>
                    <?= htmlspecialchars($initial) ?>
                <?php endif; ?>
            </div>
            <h2><?= htmlspecialchars($user['nama']) ?></h2>
            <small>Anggota Perpustakaan &middot; @<?= htmlspecialchars($user['username']) ?></small>
            <br>
            <a class="btn-edit" href="edit_profile.php">Edit Profil</a>
        </section>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert ok" style="margin-top:16px">Profil berhasil diperbarui.</div>
        <?php endif; ?>

        <div class="profile-grid">
            <aside class="profile-side">
                <div class="card">
                    <h4>Tentang</h4>
                    <dl class="info">
                        <dt>Nama</dt>     <dd><?= htmlspecialchars($user['nama']) ?></dd>
                        <dt>Username</dt> <dd>@<?= htmlspecialchars($user['username']) ?></dd>
                        <dt>Email</dt>    <dd><?= htmlspecialchars($user['email']) ?></dd>
                        <dt>NIS</dt>      <dd><?= htmlspecialchars((string) $nis) ?></dd>
                    </dl>
                </div>
                <div class="card">
                    <h4>Aktivitas Membaca</h4>
                    <div class="mini-stats">
                        <div><strong><?= $dipinjam ?></strong><span>Sedang dipinjam</span></div>
                        <div><strong><?= $total ?></strong><span>Total pinjam</span></div>
                        <div><strong><?= count($posts) ?></strong><span>Postingan</span></div>
                    </div>
                </div>
            </aside>

            <section class="profile-feed">
                <form method="post" action="profile.php" class="card composer">
                    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                    <input type="hidden" name="action" value="post">
                    <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                    <textarea name="isi" rows="3" maxlength="500"
                              placeholder="Bagikan buku yang sedang kamu baca..."></textarea>
                    <div class="composer-foot">
                        <small>Maks. 500 karakter</small>
                        <button type="submit">Post</button>
                    </div>
                </form>

                <?php if (!$posts): ?>
                    <div class="notif-empty">
                        <p>Belum ada postingan. Ceritakan buku favoritmu!</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($posts as $p): ?>
                    <article class="card post">
                        <div class="post-head">
                            <div class="avatar-sm">
                                <?php if (!empty($_SESSION['literaksi_user']['foto_profil'])): ?>
                                    <img src="profile_photo.php?v=<?= rawurlencode($_SESSION['literaksi_user']['foto_profil']) ?>" alt="">
                                <?php else: ?>
                                    <?= htmlspecialchars($initial) ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong><?= htmlspecialchars($user['nama']) ?></strong>
                                <small><?= date('d M Y, H:i', strtotime($p['dibuat'])) ?></small>
                            </div>
                            <form method="post" action="profile.php" class="post-del"
                                  onsubmit="return confirm('Hapus postingan ini?');">
                                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id_post" value="<?= (int) $p['id_post'] ?>">
                                <button type="submit" title="Hapus" aria-label="Hapus postingan">&times;</button>
                            </form>
                        </div>
                        <p><?= nl2br(htmlspecialchars($p['isi'])) ?></p>
                    </article>
                <?php endforeach; ?>
            </section>
        </div>
    </main>

</body>
</html>
