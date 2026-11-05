<?php
require_once __DIR__ . '/session.php';
start_app_session();

if (isset($_SESSION['literaksi_user']) && (!is_array($_SESSION['literaksi_user']) || !isset($_SESSION['literaksi_user']['NIS']))) {
    unset($_SESSION['literaksi_user'], $_SESSION['user']);
}

require 'config.php';
require 'layout.php';

$isMember = isset($_SESSION['literaksi_user']['NIS']);
$user = $_SESSION['literaksi_user'] ?? $_SESSION['literaksi_admin'] ?? ['nama' => 'Tamu'];
$nis  = $isMember ? $user['NIS'] : null;

$id_buku = trim($_GET['id'] ?? '');
if ($id_buku === '') {
    header('Location: katalog.php');
    exit;
}

/* ---------- Ambil data buku ---------- */
$q = $pdo->prepare('SELECT b.id_buku, b.judul, k.kategori, p.nama_penerbit
    FROM buku b
    LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
    LEFT JOIN penerbit p ON p.id_penerbit = b.id_penerbit
    WHERE b.id_buku = ?');
$q->execute([$id_buku]);
$book = $q->fetch();

if (!$book) {
    header('Location: katalog.php');
    exit;
}

/* ---------- Helper: generate next varchar(10) ID ---------- */
function next_id(PDO $pdo, string $table, string $col, string $prefix): string
{
    $last = $pdo->query("SELECT $col FROM $table ORDER BY $col DESC LIMIT 1")->fetchColumn();
    if ($last && preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $last, $m)) {
        $n = (int) $m[1] + 1;
    } else {
        $n = 1;
    }
    return $prefix . str_pad((string) $n, 9, '0', STR_PAD_LEFT); // 1 + 9 = 10 chars
}

/* ---------- Proses Pinjam ---------- */
$flash     = '';
$flashType = 'ok';

if ($isMember && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pinjam'])) {
    $cek = $pdo->prepare('SELECT COUNT(*) FROM detail_peminjaman d
        JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
        WHERE p.NIS = ? AND d.id_buku = ? AND d.tgl_kembali IS NULL');
    $cek->execute([$nis, $id_buku]);
    $sudahPinjam = (int) $cek->fetchColumn();

    if ($sudahPinjam > 0) {
        $flash = 'Kamu masih meminjam buku ini dan belum mengembalikannya.';
        $flashType = 'bad';
    } else {
        try {
            $pdo->beginTransaction();

            $id_peminjaman = next_id($pdo, 'peminjaman', 'id_peminjaman', 'P');
            $id_detail     = next_id($pdo, 'detail_peminjaman', 'id_detail', 'D');

            $pdo->prepare('INSERT INTO peminjaman
                (id_peminjaman, NIS, id_petugas, tgl_peminjam, tgl_jatuh_tempo, status)
                VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), ?)')
                ->execute([$id_peminjaman, $nis, 'SYS', 'dipinjam']);

            $pdo->prepare('INSERT INTO detail_peminjaman
                (id_detail, id_peminjaman, id_buku, tgl_kembali, denda)
                VALUES (?, ?, ?, NULL, 0.00)')
                ->execute([$id_detail, $id_peminjaman, $id_buku]);

            $pdo->commit();
            header('Location: peminjaman.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $flash = 'Gagal memproses peminjaman. Silakan coba lagi.';
            $flashType = 'bad';
        }
    }
}

/* ---------- Warna cover ---------- */
$palette    = ['#5b8fc9', '#7a1620', '#2d4a6e', '#3b9a8f', '#6c5ce7', '#b5651d'];
$coverColor = $palette[crc32($book['judul']) % count($palette)];
$coverUrl   = book_cover_url((string) $book['id_buku']);

$page  = 'katalog';
$badge = $isMember
    ? count(array_filter(get_notifications($pdo, $nis), fn($n) => $n['urgent']))
    : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($book['judul']) ?> - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body class="dash-body">

    <?php render_sidebar($page); ?>

    <main class="main">
        <?php render_topbar($user, $badge, $page); ?>

        <a class="back-link" href="katalog.php">&larr; Kembali ke Katalog</a>

        <?php if ($flash !== ''): ?>
            <div class="alert <?= $flashType === 'ok' ? 'ok' : '' ?>"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <div class="detail-grid">
            <div class="detail-cover<?= $coverUrl !== null ? ' has-image' : '' ?>" style="background: <?= $coverColor ?>">
                <?php if ($coverUrl !== null): ?>
                    <img class="detail-cover-image" src="<?= htmlspecialchars($coverUrl) ?>" alt="Sampul buku: <?= htmlspecialchars($book['judul']) ?>">
                <?php else: ?>
                    <span><?= htmlspecialchars($book['judul']) ?></span>
                <?php endif; ?>
            </div>

            <div class="detail-body">
                <span class="status info"><?= htmlspecialchars($book['kategori'] ?? 'Umum') ?></span>
                <h1><?= htmlspecialchars($book['judul']) ?></h1>
                <p class="detail-author">
                    Penerbit: <strong><?= htmlspecialchars($book['nama_penerbit'] ?? 'Tidak diketahui') ?></strong>
                </p>

                <div class="detail-meta">
                    <div>
                        <dt>ID Buku</dt>
                        <dd><?= htmlspecialchars($book['id_buku']) ?></dd>
                    </div>
                    <div>
                        <dt>Kategori</dt>
                        <dd><?= htmlspecialchars($book['kategori'] ?? '-') ?></dd>
                    </div>
                    <div>
                        <dt>Penerbit</dt>
                        <dd><?= htmlspecialchars($book['nama_penerbit'] ?? '-') ?></dd>
                    </div>
                    <div>
                        <dt>Masa Pinjam</dt>
                        <dd>7 hari</dd>
                    </div>
                </div>

                <h3 class="section-title">Tentang Buku</h3>
                <p class="detail-synopsis">
                    Buku <strong><?= htmlspecialchars($book['judul']) ?></strong> merupakan koleksi
                    kategori <em><?= htmlspecialchars($book['kategori'] ?? 'Umum') ?></em>
                    yang diterbitkan oleh <strong><?= htmlspecialchars($book['nama_penerbit'] ?? '-') ?></strong>.
                    Silakan klik tombol di bawah untuk meminjam buku ini selama 7 hari.
                </p>

                <?php if ($isMember): ?>
                    <form method="post">
                        <button type="submit" name="pinjam" value="1" class="btn-primary">
                            Pinjam Buku
                        </button>
                    </form>
                <?php else: ?>
                    <p>Silakan masuk atau daftar sebagai anggota untuk meminjam buku.</p>
                    <a class="btn-primary" href="login.php">Masuk untuk meminjam</a>
                <?php endif; ?>
            </div>
        </div>
    </main>

</body>
</html>