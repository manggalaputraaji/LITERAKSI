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

if (empty($_SESSION['return_csrf'])) {
    $_SESSION['return_csrf'] = bin2hex(random_bytes(32));
}

$returnError = '';
$returnNotice = $_SESSION['return_notice'] ?? null;
unset($_SESSION['return_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['return_csrf'], $token)) {
        http_response_code(400);
        exit('Permintaan tidak valid. Muat ulang halaman dan coba lagi.');
    }

    if (($_POST['action'] ?? '') !== 'request_return') {
        http_response_code(400);
        exit('Aksi tidak dikenal.');
    }

    $detailId = trim($_POST['id_detail'] ?? '');
    if ($detailId === '') {
        $returnError = 'Detail peminjaman tidak valid.';
    } else {
        $request = $pdo->prepare('UPDATE detail_peminjaman d
            JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
            SET d.tgl_pengajuan_kembali = NOW()
            WHERE d.id_detail = ? AND p.NIS = ?
                AND d.tgl_kembali IS NULL AND d.tgl_pengajuan_kembali IS NULL');
        $request->execute([$detailId, $nis]);

        if ($request->rowCount() === 0) {
            $returnError = 'Peminjaman tidak ditemukan atau pengajuan pengembalian sudah dikirim.';
        } else {
            $_SESSION['return_notice'] = 'Permintaan pengembalian dikirim. Serahkan buku ke petugas untuk konfirmasi.';
            header('Location: peminjaman.php');
            exit;
        }
    }
}

$filter = $_GET['status'] ?? 'semua';
if (!in_array($filter, ['semua', 'dipinjam', 'kembali'], true)) {
    $filter = 'semua';
}

// Find out whether detail_peminjaman links to buku through id_buku.
$dCols = array_map('strtolower', $pdo->query('SHOW COLUMNS FROM detail_peminjaman')->fetchAll(PDO::FETCH_COLUMN));
$bCols = array_map('strtolower', $pdo->query('SHOW COLUMNS FROM buku')->fetchAll(PDO::FETCH_COLUMN));
$hasBuku = in_array('id_buku', $dCols, true) && in_array('id_buku', $bCols, true);

$select = 'p.id_peminjaman, p.tgl_jatuh_tempo, d.tgl_kembali,
           d.id_detail, d.tgl_pengajuan_kembali,
           DATEDIFF(p.tgl_jatuh_tempo, CURDATE()) AS sisa';
$join = '';
if ($hasBuku) {
    $select .= ', b.id_buku, b.judul, k.kategori';
    $join = 'LEFT JOIN buku b ON b.id_buku = d.id_buku
             LEFT JOIN kategori k ON k.id_kategori = b.id_kategori';
}

$sql = "SELECT $select
        FROM detail_peminjaman d
        JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
        $join
        WHERE p.NIS = ?";
if ($filter === 'dipinjam') {
    $sql .= ' AND d.tgl_kembali IS NULL';
} elseif ($filter === 'kembali') {
    $sql .= ' AND d.tgl_kembali IS NOT NULL';
}
$sql .= ' ORDER BY (d.tgl_kembali IS NULL) DESC, p.tgl_jatuh_tempo ASC';

$q = $pdo->prepare($sql);
$q->execute([$nis]);
$loans = $q->fetchAll();

$palette = ['#5b8fc9', '#7a1620', '#2d4a6e', '#3b9a8f', '#6c5ce7', '#b5651d'];

function loan_status(array $r): array
{
    if ($r['tgl_kembali'] !== null) {
        return ['ok', 'Dikembalikan ' . $r['tgl_kembali']];
    }
    if ($r['tgl_pengajuan_kembali'] !== null) {
        return ['warn', 'Menunggu konfirmasi pengembalian'];
    }
    $sisa = (int) $r['sisa'];
    if ($sisa < 0)   return ['danger', 'Terlambat ' . abs($sisa) . ' hari'];
    if ($sisa === 0) return ['warn', 'Jatuh tempo hari ini'];
    if ($sisa <= 3)  return ['warn', 'Sisa ' . $sisa . ' hari'];
    return ['info', 'Sisa ' . $sisa . ' hari'];
}

$tabs = ['semua' => 'Semua', 'dipinjam' => 'Sedang dipinjam', 'kembali' => 'Sudah dikembalikan'];

$page  = 'peminjaman';
$badge = count(array_filter(get_notifications($pdo, $nis), fn($n) => $n['urgent']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Peminjaman Saya - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dash-body">

    <?php render_sidebar($page); ?>

    <main class="main">
        <?php render_topbar($user, $badge, $page); ?>

        <h3 class="page-title">Peminjaman Saya</h3>

        <div class="chips">
            <?php foreach ($tabs as $key => $label): ?>
                <a class="chip <?= $filter === $key ? 'active' : '' ?>"
                   href="peminjaman.php<?= $key === 'semua' ? '' : '?status=' . $key ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>

            <?php if ($returnError !== ''): ?>
                <div class="alert bad"><?= htmlspecialchars($returnError) ?></div>
            <?php elseif (is_string($returnNotice)): ?>
                <div class="alert ok"><?= htmlspecialchars($returnNotice) ?></div>
            <?php endif; ?>

            <?php if (!$hasBuku): ?>
                <p class="result-info">Judul buku belum bisa ditampilkan (kolom id_buku tidak ditemukan di tabel detail_peminjaman / buku).</p>
        <?php endif; ?>

        <div class="books">
            <?php if (!$loans): ?>
                <p>Belum ada peminjaman<?= $filter !== 'semua' ? ' pada kategori ini' : '' ?>.</p>
            <?php endif; ?>
            <?php foreach ($loans as $r):
                $judul = $hasBuku && !empty($r['judul']) ? $r['judul'] : 'Peminjaman #' . $r['id_peminjaman'];
                $coverUrl = !empty($r['id_buku']) ? book_cover_url((string) $r['id_buku']) : null;
                [$cls, $text] = loan_status($r);
            ?>
                <div class="book-card">
                    <div class="cover<?= $coverUrl !== null ? ' has-image' : '' ?>" style="background: <?= $palette[crc32($judul) % count($palette)] ?>">
                        <?php if ($coverUrl !== null): ?>
                            <img class="cover-image" src="<?= htmlspecialchars($coverUrl) ?>" alt="Sampul buku: <?= htmlspecialchars($judul) ?>">
                        <?php else: ?>
                            <span><?= htmlspecialchars($judul) ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="book-title"><?= htmlspecialchars($judul) ?></p>
                    <?php if ($hasBuku && !empty($r['kategori'])): ?>
                        <p class="book-meta"><?= htmlspecialchars($r['kategori']) ?></p>
                    <?php endif; ?>
                    <span class="status <?= $cls ?>"><?= htmlspecialchars($text) ?></span>
                    <p class="book-meta">Jatuh tempo: <?= htmlspecialchars((string) $r['tgl_jatuh_tempo']) ?></p>
                    <?php if ($r['tgl_kembali'] === null && $r['tgl_pengajuan_kembali'] === null): ?>
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['return_csrf']) ?>">
                            <input type="hidden" name="action" value="request_return">
                            <input type="hidden" name="id_detail" value="<?= htmlspecialchars($r['id_detail']) ?>">
                            <button type="submit" class="admin-action">Ajukan pengembalian</button>
                        </form>
                    <?php elseif ($r['tgl_pengajuan_kembali'] !== null && $r['tgl_kembali'] === null): ?>
                        <p class="book-meta">Diajukan: <?= htmlspecialchars($r['tgl_pengajuan_kembali']) ?>. Serahkan buku ke petugas.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

</body>
</html>
