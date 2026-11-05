<?php
$adminPageTab = 'loans';
require __DIR__ . '/admin_shared.php';
    $sql = 'SELECT d.id_detail, d.tgl_kembali, d.tgl_pengajuan_kembali,
                   p.id_peminjaman, p.NIS, p.tgl_peminjam,
				   p.tgl_jatuh_tempo, s.nama, b.judul
			FROM detail_peminjaman d
			JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
			LEFT JOIN siswa s ON s.NIS = p.NIS
			LEFT JOIN buku b ON b.id_buku = d.id_buku';
    $conditions = [];
    $params = [];
    if ($statusFilter === 'dipinjam') {
        $conditions[] = 'd.tgl_kembali IS NULL';
    } elseif ($statusFilter === 'terlambat') {
        $conditions[] = 'd.tgl_kembali IS NULL AND p.tgl_jatuh_tempo < CURDATE()';
    } elseif ($statusFilter === 'menunggu') {
        $conditions[] = 'd.tgl_kembali IS NULL AND d.tgl_pengajuan_kembali IS NOT NULL';
    } elseif ($statusFilter === 'kembali') {
        $conditions[] = 'd.tgl_kembali IS NOT NULL';
    }
    if ($search !== '') {
        $conditions[] = '(p.id_peminjaman LIKE ? OR p.NIS LIKE ? OR s.nama LIKE ? OR b.judul LIKE ?)';
        $needle = '%' . $search . '%';
        array_push($params, $needle, $needle, $needle, $needle);
    }
    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY (d.tgl_pengajuan_kembali IS NOT NULL) DESC,
              (d.tgl_kembali IS NULL) DESC, p.tgl_jatuh_tempo ASC, p.id_peminjaman DESC';
    $query = $pdo->prepare($sql);
    $query->execute($params);
    $rows = $query->fetchAll();
?>
<?php admin_page_header('admin_peminjaman', 'Peminjaman'); require __DIR__ . '/admin_tabel.php'; require __DIR__ . '/admin_interaksi.php'; admin_page_footer(); ?>
