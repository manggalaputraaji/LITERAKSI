<?php
$adminPageTab = 'manajemen_user';
require __DIR__ . '/admin_shared.php';
    $usersSql = 'SELECT NIS, nama, username, email, kelas FROM siswa';
    $usersParams = [];
    if ($search !== '') {
        $usersSql .= ' WHERE NIS LIKE ? OR nama LIKE ? OR username LIKE ? OR email LIKE ?';
        $needle = '%' . $search . '%';
        $usersParams = [$needle, $needle, $needle, $needle];
    }
    $usersSql .= ' ORDER BY nama ASC';
    $query = $pdo->prepare($usersSql);
    $query->execute($usersParams);
    $adminSections['users'] = $query->fetchAll();

    $staffSql = 'SELECT id_petugas, nama, username FROM petugas';
    $staffParams = [];
    if ($search !== '') {
        $staffSql .= ' WHERE id_petugas LIKE ? OR nama LIKE ? OR username LIKE ?';
        $needle = '%' . $search . '%';
        $staffParams = [$needle, $needle, $needle];
    }
    $staffSql .= ' ORDER BY nama';
    $query = $pdo->prepare($staffSql);
    $query->execute($staffParams);
    $adminSections['staff'] = $query->fetchAll();
$rows = $adminSections[$activeSection];
?>
<?php admin_page_header('manajemen_user', 'Manajemen Pengguna', $sectionOptions['manajemen_user'], $activeSection); require __DIR__ . '/admin_tabel.php'; require __DIR__ . '/admin_interaksi.php'; admin_page_footer(); ?>
