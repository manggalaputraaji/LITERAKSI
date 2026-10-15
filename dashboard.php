<?php
session_start();

if (!isset($_SESSION['literaksi_user']) || !is_array($_SESSION['literaksi_user']) || !isset($_SESSION['literaksi_user']['NIS'])) {
    unset($_SESSION['literaksi_user'], $_SESSION['user']);
    header('Location: login.php');
    exit;
}

require 'config.php';
require 'layout.php';

$user = $_SESSION['literaksi_user'];
$nis = $user['NIS'];

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

$q = $pdo->prepare('SELECT MIN(DATEDIFF(p.tgl_jatuh_tempo, CURDATE()))
    FROM detail_peminjaman d
    JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
    WHERE p.NIS = ? AND d.tgl_kembali IS NULL');
$q->execute([$nis]);
$sisa = $q->fetchColumn();

$stats = [
    ['icon' => 'assets/img/lended.svg', 'angka' => $dipinjam, 'label' => 'Buku dipinjam'],
    ['icon' => 'assets/img/deadline.svg', 'angka' => $sisa === null ? '-' : $sisa, 'label' => 'Hari Sebelum Jatuh Tempo'],
    ['icon' => 'assets/img/total.svg', 'angka' => $total, 'label' => 'Total Peminjaman'],
];

$books = $pdo->query('SELECT b.judul, k.kategori
    FROM buku b LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
    ORDER BY RAND() LIMIT 5')->fetchAll();

$palette = ['#5b8fc9', '#7a1620', '#2d4a6e', '#3b9a8f', '#6c5ce7', '#b5651d'];

$page = 'home';
$badge = count(array_filter(get_notifications($pdo, $nis), fn($n) => $n['urgent']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dash-body">

    <?php render_sidebar($page); ?>

    <main class="main">
        <?php render_topbar($user, $badge, $page); ?>

        <section class="banner">
            <h2>Halo, <?= htmlspecialchars($user['nama']) ?>!</h2>
            <p>Selamat Datang di Perpustakaan Digital LITERAKSI</p>
        </section>

        <h3 class="section-title">Statistik Peminjaman</h3>
        <div class="stats">
            <?php foreach ($stats as $s): ?>
                <div class="stat-card">
                    <img class="stat-icon" src="<?= htmlspecialchars($s['icon']) ?>" alt="<?= htmlspecialchars($s['label']) ?>">
                    <span class="stat-num"><?= $s['angka'] ?></span>
                    <span class="stat-label"><?= htmlspecialchars($s['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <h3 class="section-title">Rekomendasi Buku</h3>
        <div class="books">
            <?php if (!$books): ?>
                <p>Belum ada data buku.</p>
            <?php endif; ?>
            <?php foreach ($books as $b): ?>
                <div class="book-card">
                    <div class="cover" style="background: <?= $palette[crc32($b['judul']) % count($palette)] ?>">
                        <span><?= htmlspecialchars($b['judul']) ?></span>
                    </div>
                    <p class="book-title"><?= htmlspecialchars($b['judul']) ?></p>
                    <p class="book-price"><?= htmlspecialchars($b['kategori'] ?? '-') ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

</body>
</html>
