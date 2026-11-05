<?php
$adminPageTab = 'manajemen';
require __DIR__ . '/admin_shared.php';
    $adminSections = [];
    $booksSql = 'SELECT b.id_buku, b.judul, b.id_kategori, b.id_penerbit, k.kategori, p.nama_penerbit
        FROM buku b
        LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
        LEFT JOIN penerbit p ON p.id_penerbit = b.id_penerbit';
    $booksParams = [];
    if ($search !== '') {
        $booksSql .= ' WHERE b.id_buku LIKE ? OR b.judul LIKE ? OR k.kategori LIKE ? OR p.nama_penerbit LIKE ?';
        $needle = '%' . $search . '%';
        $booksParams = [$needle, $needle, $needle, $needle];
    }
    $booksSql .= ' ORDER BY b.judul';
    $query = $pdo->prepare($booksSql);
    $query->execute($booksParams);
    $adminSections['books'] = $query->fetchAll();

    foreach (['publishers', 'categories'] as $section) {
        if ($section === 'publishers') {
            $sectionSql = 'SELECT p.id_penerbit, p.nama_penerbit, COUNT(b.id_buku) AS jumlah_buku
                FROM penerbit p LEFT JOIN buku b ON b.id_penerbit = p.id_penerbit';
            $sectionSql .= $search !== '' ? ' WHERE p.id_penerbit LIKE ? OR p.nama_penerbit LIKE ?' : '';
            $sectionSql .= ' GROUP BY p.id_penerbit, p.nama_penerbit ORDER BY p.nama_penerbit';
        } else {
            $sectionSql = 'SELECT k.id_kategori, k.kategori, COUNT(b.id_buku) AS jumlah_buku
                FROM kategori k LEFT JOIN buku b ON b.id_kategori = k.id_kategori';
            $sectionSql .= $search !== '' ? ' WHERE k.id_kategori LIKE ? OR k.kategori LIKE ?' : '';
            $sectionSql .= ' GROUP BY k.id_kategori, k.kategori ORDER BY k.kategori';
        }
        $query = $pdo->prepare($sectionSql);
        $query->execute($search !== '' ? [$needle, $needle] : []);
        $adminSections[$section] = $query->fetchAll();
    }
    $categories = $pdo->query('SELECT id_kategori, kategori FROM kategori ORDER BY kategori')->fetchAll();
    $publishers = $pdo->query('SELECT id_penerbit, nama_penerbit FROM penerbit ORDER BY nama_penerbit')->fetchAll();
$rows = $adminSections[$activeSection];
?>
<?php admin_page_header('manajemen', 'Manajemen Perpustakaan', $sectionOptions['manajemen'], $activeSection); require __DIR__ . '/admin_tabel.php'; require __DIR__ . '/admin_interaksi.php'; admin_page_footer(); ?>
