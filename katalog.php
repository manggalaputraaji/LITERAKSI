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
$nis  = $user['NIS'];

$kategoriId = (int) ($_GET['kategori'] ?? 0);
$cari       = trim($_GET['q'] ?? '');

$kategori = $pdo->query('SELECT id_kategori, kategori FROM kategori ORDER BY kategori')->fetchAll();

$sql = 'SELECT b.id_buku, b.judul, k.kategori
        FROM buku b LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
        WHERE 1=1';
$params = [];
if ($kategoriId > 0) {
    $sql .= ' AND b.id_kategori = ?';
    $params[] = $kategoriId;
}
if ($cari !== '') {
    $sql .= ' AND b.judul LIKE ?';
    $params[] = '%' . $cari . '%';
}
$sql .= ' ORDER BY b.judul LIMIT 200';
$q = $pdo->prepare($sql);
$q->execute($params);
$books = $q->fetchAll();

$palette = ['#5b8fc9', '#7a1620', '#2d4a6e', '#3b9a8f', '#6c5ce7', '#b5651d'];

function chip_url(int $id, string $cari): string
{
    $qs = array_filter(['kategori' => $id ?: null, 'q' => $cari !== '' ? $cari : null]);
    return 'katalog.php' . ($qs ? '?' . http_build_query($qs) : '');
}

$page  = 'katalog';
$badge = count(array_filter(get_notifications($pdo, $nis), fn($n) => $n['urgent']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Katalog Buku - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dash-body">

    <?php render_sidebar($page); ?>

    <main class="main">
        <?php render_topbar($user, $badge, $page); ?>

        <h3 class="page-title">Katalog Buku</h3>

        <div class="chips">
            <a class="chip <?= $kategoriId === 0 ? 'active' : '' ?>" href="<?= chip_url(0, $cari) ?>">Semua</a>
            <?php foreach ($kategori as $k): ?>
                <a class="chip <?= $kategoriId === (int) $k['id_kategori'] ? 'active' : '' ?>"
                   href="<?= chip_url((int) $k['id_kategori'], $cari) ?>"><?= htmlspecialchars($k['kategori']) ?></a>
            <?php endforeach; ?>
        </div>

        <?php if ($cari !== ''): ?>
            <p class="result-info">Hasil pencarian untuk "<?= htmlspecialchars($cari) ?>": <?= count($books) ?> buku</p>
        <?php endif; ?>

        <div class="books">
            <?php if (!$books): ?>
                <p>Buku tidak ditemukan.</p>
            <?php endif; ?>
            <?php foreach ($books as $b): ?>
                <?php $coverUrl = book_cover_url((string) $b['id_buku']); ?>
                <a class="book-card" href="detail_buku.php?id=<?= rawurlencode((string) $b['id_buku']) ?>"
                   style="text-decoration:none;color:inherit;display:block;">
                    <div class="cover<?= $coverUrl !== null ? ' has-image' : '' ?>" style="background: <?= $palette[crc32($b['judul']) % count($palette)] ?>">
                        <?php if ($coverUrl !== null): ?>
                            <img class="cover-image" src="<?= htmlspecialchars($coverUrl) ?>" alt="Sampul buku: <?= htmlspecialchars($b['judul']) ?>">
                        <?php else: ?>
                            <span><?= htmlspecialchars($b['judul']) ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="book-title"><?= htmlspecialchars($b['judul']) ?></p>
                    <p class="book-price"><?= htmlspecialchars($b['kategori'] ?? '-') ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </main>

</body>
</html>