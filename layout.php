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

    if (!isset($_SESSION['literaksi_user']['NIS'])) {
        return [
            'katalog' => ['book', 'Katalog Buku', 'katalog.php'],
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

function member_appearance(PDO $pdo): array
{
    $defaults = ['foto_profil' => null, 'tema' => 'light', 'warna' => 'violet'];
    if (!isset($_SESSION['literaksi_user']['NIS'])) {
        return $defaults;
    }

    if (empty($_SESSION['member_appearance_loaded'])) {
        $settings = $pdo->prepare('SELECT foto_profil, tema, warna FROM pengaturan_anggota WHERE NIS = ?');
        $settings->execute([$_SESSION['literaksi_user']['NIS']]);
        $preferences = $settings->fetch() ?: $defaults;
        $_SESSION['literaksi_user']['foto_profil'] = $preferences['foto_profil'];
        $_SESSION['literaksi_user']['tema'] = $preferences['tema'];
        $_SESSION['literaksi_user']['warna'] = $preferences['warna'];
        $_SESSION['member_appearance_loaded'] = true;
    }

    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    $photo = $_SESSION['literaksi_user']['foto_profil'] ?? null;
    return [
        'foto_profil' => is_string($photo)
            && preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $photo) ? $photo : null,
        'tema' => in_array($_SESSION['literaksi_user']['tema'] ?? '', ['light', 'dark'], true)
            ? $_SESSION['literaksi_user']['tema'] : 'light',
        'warna' => in_array($_SESSION['literaksi_user']['warna'] ?? '', ['violet', 'blue', 'teal', 'rose'], true)
            ? $_SESSION['literaksi_user']['warna'] : 'violet',
    ];
}

function render_sidebar(string $page, array $subnav = [], ?string $activeSubnav = null): void
{
    global $pdo;

    $isGuest = !isset($_SESSION['literaksi_user']['NIS']) && !isset($_SESSION['literaksi_admin']);
    $isMember = !$isGuest && !isset($_SESSION['literaksi_admin']);
    $appearance = $isMember ? member_appearance($pdo) : null;
?>
    <aside class="sidebar">
        <div>
            <img id="logodash" src="assets/img/logol.png" alt="Literaksi">
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
        <?php if (!$isGuest): ?>
            <a href="logout.php" class="logout"><?= icon('power') ?> <span>Keluar Akun</span></a>
        <?php endif; ?>
        <?php if ($isMember): ?>
            <div class="sidebar-accent-picker" role="group" aria-label="Warna aksen">
                <span>Warna</span>
                <?php foreach (['violet' => 'Ungu', 'blue' => 'Biru', 'teal' => 'Teal', 'rose' => 'Rose'] as $color => $label): ?>
                    <button type="button" class="accent-swatch accent-<?= $color ?>"
                        data-save-accent="<?= $color ?>" aria-label="Gunakan warna <?= $label ?>"
                        aria-pressed="<?= $appearance['warna'] === $color ? 'true' : 'false' ?>"
                        title="<?= $label ?>"></button>
                <?php endforeach; ?>
            </div>
            <p class="appearance-status sidebar-appearance-status" aria-live="polite"></p>
        <?php endif; ?>
    </aside>
<?php
}

function render_topbar(array $user, int $badge, string $page): void
{
    global $pdo;

    $isAdmin = isset($_SESSION['literaksi_admin']);
    $isGuest = !$isAdmin && !isset($_SESSION['literaksi_user']['NIS']);
    $isMember = !$isAdmin && !$isGuest;
    $appearance = $isMember ? member_appearance($pdo) : null;
    $storedPhoto = $appearance['foto_profil'] ?? null;
    $profilePhoto = $storedPhoto !== null ? 'profile_photo.php' : null;
?>
    <?php if ($isMember): ?>
        <script>
            document.documentElement.dataset.theme = <?= json_encode($appearance['tema']) ?>;
            document.documentElement.dataset.accent = <?= json_encode($appearance['warna']) ?>;
        </script>
    <?php endif; ?>
    <header class="topbar">
        <?php if (!$isAdmin): ?>
            <form class="search-box" method="get" action="katalog.php">
                <?= icon('search', 18) ?>
                <input type="search" name="q" placeholder="Cari buku..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </form>
        <?php endif; ?>
        <div class="top-right">
            <?php if ($isGuest): ?>
                <nav class="guest-actions" aria-label="Akun">
                    <a class="guest-login" href="login.php">Masuk</a>
                    <a class="guest-register" href="register.php">Daftar</a>
                </nav>
            <?php endif; ?>
            <?php if (!$isAdmin && !$isGuest): ?>
                <a href="notifikasi.php" class="bell <?= $page === 'notifikasi' ? 'active' : '' ?>"
                    aria-label="Notifikasi" title="Notifikasi">
                    <?= icon('bell', 22) ?>
                    <?php if ($badge > 0): ?><span class="badge"><?= $badge ?></span><?php endif; ?>
                </a>
            <?php endif; ?>
            <?php if ($isMember): ?>
                <button type="button" class="theme-toggle" data-save-theme
                    aria-label="Ganti tema" aria-pressed="<?= $appearance['tema'] === 'dark' ? 'true' : 'false' ?>"
                    title="Ganti tema">
                    <span class="theme-toggle-icon" aria-hidden="true"><?= $appearance['tema'] === 'dark' ? '☾' : '☼' ?></span>
                    <span class="theme-toggle-label"><?= $appearance['tema'] === 'dark' ? 'Gelap' : 'Terang' ?></span>
                </button>
            <?php endif; ?>
            <div class="avatar">
                <?php if ($profilePhoto !== null): ?>
                    <img src="profile_photo.php?v=<?= rawurlencode($storedPhoto) ?>" alt="">
                <?php endif; ?>
            </div>
            <div class="who">
                <strong><?= htmlspecialchars($user['nama'] ?? 'Tamu') ?></strong>
                <small><?= $isAdmin ? 'Admin Perpustakaan' : ($isGuest ? 'Tamu - belum login' : 'Anggota Perpustakaan') ?></small>
            </div>
        </div>
    </header>
    <?php if ($isMember): ?>
        <script>
            (() => {
                const csrfToken = <?= json_encode($_SESSION['csrf']) ?>;
                const statusNodes = document.querySelectorAll('.appearance-status');
                const controls = document.querySelectorAll('[data-save-accent], [data-save-theme]');

                async function savePreference(field, value) {
                    if (Array.from(controls).some((control) => control.disabled)) return false;
                    controls.forEach((control) => { control.disabled = true; });
                    statusNodes.forEach((node) => { node.textContent = 'Menyimpan...'; });
                    const body = new URLSearchParams({ csrf: csrfToken, field, value });
                    try {
                        const response = await fetch('save_appearance.php', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body
                        });
                        const result = await response.json();
                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Pengaturan gagal disimpan.');
                        }
                        if (field === 'theme') {
                            document.documentElement.dataset.theme = value;
                        } else {
                            document.documentElement.dataset.accent = value;
                        }
                        statusNodes.forEach((node) => { node.textContent = 'Tersimpan'; });
                        return true;
                    } catch (error) {
                        statusNodes.forEach((node) => {
                            node.textContent = error instanceof Error ? error.message : 'Pengaturan gagal disimpan.';
                        });
                        return false;
                    } finally {
                        controls.forEach((control) => { control.disabled = false; });
                    }
                }

                document.querySelectorAll('[data-save-accent]').forEach((button) => {
                    button.addEventListener('click', async () => {
                        if (button.getAttribute('aria-pressed') === 'true') return;
                        const saved = await savePreference('accent', button.dataset.saveAccent);
                        if (saved) {
                            document.querySelectorAll('[data-save-accent]').forEach((swatch) => {
                                swatch.setAttribute('aria-pressed', String(swatch === button));
                            });
                        }
                    });
                });

                document.querySelectorAll('[data-save-theme]').forEach((button) => {
                    button.addEventListener('click', async () => {
                        const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
                        if (!await savePreference('theme', theme)) return;
                        button.setAttribute('aria-pressed', String(theme === 'dark'));
                        button.querySelector('.theme-toggle-icon').textContent = theme === 'dark' ? '☾' : '☼';
                        button.querySelector('.theme-toggle-label').textContent = theme === 'dark' ? 'Gelap' : 'Terang';
                    });
                });
            })();
        </script>
    <?php endif; ?>
<?php
}
