<?php
// Shared helpers: icons, menu, notifications, sidebar, topbar.

function icon(string $name, int $size = 20): string
{
    $paths = [
        'home'   => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'book'   => '<path d="M2 5.5C4.5 4 8 4 12 6.5c4-2.5 7.5-2.5 10-1V19c-2.5-1.5-6-1.5-10 1-4-2.5-7.5-2.5-10-1z"/><path d="M12 6.5V20"/>',
        'loans'  => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1M9 11h6M9 15h6"/>',
        'bell'   => '<path d="M6 9a6 6 0 0 1 12 0c0 6 2.5 7.5 2.5 7.5h-17S6 15 6 9z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.5-6 8-6s8 2 8 6"/>',
        'power'  => '<path d="M12 3v9"/><path d="M6.5 6.5a8 8 0 1 0 11 0"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'alert'  => '<path d="M12 3 2 20h20z"/><path d="M12 10v4M12 17.5v.01"/>',
    ];
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size
        . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
        . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($paths[$name] ?? '') . '</svg>';
}

function nav_menu(): array
{
    if (isset($_SESSION['literaksi_admin'])) {
        return [
            'admin_dashboard' => ['home', 'Dashboard Admin', 'admin_dashboard.php'],
            'admin_peminjaman' => ['loans', 'Peminjaman', 'admin_peminjaman.php'],
            'manajemen' => ['book', 'Manajemen Perpustakaan', 'manajemen.php'],
            'manajemen_user' => ['user', 'Manajemen Pengguna', 'manajemen_user.php'],
        ];
    }

    $menu = [
        'home'       => ['home',  'Home',            'dashboard.php'],
        'katalog'    => ['book',  'Katalog Buku',    'katalog.php'],
        'peminjaman' => ['loans', 'Peminjaman Saya', 'peminjaman.php'],
        'notifikasi' => ['bell',  'Notifikasi',      'notifikasi.php'],
        'profil'     => ['user',  'Profil',          'profile.php'],
    ];
    $adminNisList = array_filter(array_map('trim', explode(',', getenv('LITERAKSI_ADMIN_NIS') ?: '')));
    $sessionNis = $_SESSION['literaksi_user']['NIS'] ?? null;
    if ($sessionNis !== null && in_array((string) $sessionNis, $adminNisList, true)) {
        $menu['admin'] = ['user', 'Admin', 'admin_dashboard.php'];
    }
    return $menu;
}

/** Notifications are built from the member's unreturned loans. */
function get_notifications(PDO $pdo, $nis): array
{
    $q = $pdo->prepare('SELECT p.id_peminjaman, p.tgl_jatuh_tempo,
            DATEDIFF(p.tgl_jatuh_tempo, CURDATE()) AS sisa, COUNT(*) AS jumlah
        FROM detail_peminjaman d
        JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
        WHERE p.NIS = ? AND d.tgl_kembali IS NULL
        GROUP BY p.id_peminjaman, p.tgl_jatuh_tempo
        ORDER BY sisa ASC');
    $q->execute([$nis]);

    $items = [];
    foreach ($q->fetchAll() as $r) {
        $sisa = (int) $r['sisa'];
        $buku = (int) $r['jumlah'] . ' buku';
        $kode = '#' . $r['id_peminjaman'];
        if ($sisa < 0) {
            $items[] = [
                'type' => 'danger',
                'icon' => 'alert',
                'urgent' => true,
                'title' => 'Peminjaman ' . $kode . ' terlambat ' . abs($sisa) . ' hari',
                'text'  => $buku . ' belum dikembalikan. Jatuh tempo: ' . $r['tgl_jatuh_tempo'] . '.'
            ];
        } elseif ($sisa <= 3) {
            $when = $sisa === 0 ? 'hari ini' : 'dalam ' . $sisa . ' hari';
            $items[] = [
                'type' => 'warn',
                'icon' => 'clock',
                'urgent' => true,
                'title' => 'Peminjaman ' . $kode . ' jatuh tempo ' . $when,
                'text'  => $buku . ' perlu dikembalikan sebelum ' . $r['tgl_jatuh_tempo'] . '.'
            ];
        } else {
            $items[] = [
                'type' => 'info',
                'icon' => 'book',
                'urgent' => false,
                'title' => 'Peminjaman ' . $kode . ' sedang berjalan',
                'text'  => $buku . ' dipinjam. Sisa waktu ' . $sisa . ' hari (sampai ' . $r['tgl_jatuh_tempo'] . ').'
            ];
        }
    }
    return $items;
}

function render_sidebar(string $page, array $subnav = [], ?string $activeSubnav = null): void
{
?>
    <aside class="sidebar">
        <div>
            <img id="logodash" src="assets/img/logod.png" alt="Literaksi">
            <nav>
                <?php foreach (nav_menu() as $key => [$ic, $label, $href]): ?>
                    <a href="<?= $href ?>" class="<?= $key === $page ? 'active' : '' ?>">
                        <?= icon($ic) ?> <span><?= $label ?></span>
                    </a>
                    <?php if ($key === $page && $subnav): ?>
                        <div class="sidebar-subnav" aria-label="Submenu manajemen">
                            <?php foreach ($subnav as $section): ?>
                                <?php
                                $sectionLabels = [
                                    'books' => 'Buku',
                                    'publishers' => 'Penerbit',
                                    'categories' => 'Kategori',
                                    'users' => 'Pengguna',
                                    'staff' => 'Petugas',
                                ];
                                ?>
                                <a href="<?= $href ?>?section=<?= rawurlencode($section) ?>"
                                   class="<?= $activeSubnav === $section ? 'active' : '' ?>"
                                   <?= $activeSubnav === $section ? 'aria-current="page"' : '' ?>>
                                    <?= htmlspecialchars($sectionLabels[$section]) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </div>
        <a href="logout.php" class="logout"><?= icon('power') ?> <span>Keluar Akun</span></a>
    </aside>
<?php
}

function render_topbar(array $user, int $badge, string $page): void
{
    $isAdmin = isset($_SESSION['literaksi_admin']);
?>
    <header class="topbar">
        <?php if (!$isAdmin): ?>
            <form class="search-box" method="get" action="katalog.php">
                <?= icon('search', 18) ?>
                <input type="search" name="q" placeholder="Cari buku..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </form>
        <?php endif; ?>
        <div class="top-right">
            <?php if (!$isAdmin): ?>
                <a href="notifikasi.php" class="bell <?= $page === 'notifikasi' ? 'active' : '' ?>"
                    aria-label="Notifikasi" title="Notifikasi">
                    <?= icon('bell', 22) ?>
                    <?php if ($badge > 0): ?><span class="badge"><?= $badge ?></span><?php endif; ?>
                </a>
            <?php endif; ?>
            <div class="avatar"></div>
            <div class="who">
                <strong><?= htmlspecialchars($user['nama']) ?></strong>
                <small><?= $isAdmin ? 'Admin Perpustakaan' : 'Anggota Perpustakaan' ?></small>
            </div>
        </div>
    </header>
<?php
}
