<?php
// Shared helpers: icons, menu, notifications, sidebar, topbar.
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function icon(string $name, int $size = 20): string
{
    $paths = [
        'home'   => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'book'   => '<path d="M2 5.5C4.5 4 8 4 12 6.5c4-2.5 7.5-2.5 10-1V19c-2.5-1.5-6-1.5-10 1-4-2.5-7.5-2.5-10-1z"/><path d="M12 6.5V20"/>',
        'chat'   => '<path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z"/>',
        'loans'  => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1M9 11h6M9 15h6"/>',
        'bell'   => '<path d="M6 9a6 6 0 0 1 12 0c0 6 2.5 7.5 2.5 7.5h-17S6 15 6 9z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.5-6 8-6s8 2 8 6"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.6a8 8 0 0 1-1.7 1l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.7-1l-1.7.6-1.4-2.4L7.3 15a8 8 0 0 1 0-2l-1.4-1.1 1.4-2.4 1.7.6a8 8 0 0 1 1.7-1l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.7 1l1.7-.6 1.4 2.4-1.4 1.1a8 8 0 0 1-.1 2z"/>',
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
    if (is_admin_session()) {
        return [
            'admin_dashboard' => ['home', 'Dashboard Admin', 'admin.php?page=dashboard'],
            'admin_peminjaman' => ['loans', 'Peminjaman', 'admin.php?page=peminjaman'],
            'manajemen' => ['book', 'Manajemen Perpustakaan', 'admin.php?page=manajemen'],
            'manajemen_user' => ['user', 'Manajemen Pengguna', 'admin.php?page=manajemen_user'],
            'diskusi' => ['chat', 'Diskusi', 'diskusi.php?role=admin'],
        ];
    }

    if (!isset($_SESSION['literaksi_user']['NIS'])) {
        return [
            'katalog' => ['book', 'Katalog Buku', 'guest.php?page=katalog'],
        ];
    }

    $menu = [
        'home'       => ['home',  'Home',            'user.php?page=dashboard'],
        'katalog'    => ['book',  'Katalog Buku',    'user.php?page=katalog'],
        'peminjaman' => ['loans', 'Peminjaman Saya', 'user.php?page=peminjaman'],
        'notifikasi' => ['bell',  'Notifikasi',      'user.php?page=notifikasi'],
        'diskusi'    => ['chat',  'Diskusi',         'diskusi.php'],
        'profil'     => ['user',  'Profil',          'user.php?page=profile'],
    ];
    $adminNisList = array_filter(array_map('trim', explode(',', getenv('LITERAKSI_ADMIN_NIS') ?: '')));
    if (in_array((string) $_SESSION['literaksi_user']['NIS'], $adminNisList, true)) {
        $menu['admin'] = ['user', 'Admin', 'admin.php?page=dashboard'];
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
                'url' => 'user.php?page=peminjaman',
                'title' => 'Peminjaman ' . $kode . ' terlambat ' . abs($sisa) . ' hari',
                'text'  => $buku . ' belum dikembalikan. Jatuh tempo: ' . $r['tgl_jatuh_tempo'] . '.'
            ];
        } elseif ($sisa <= 3) {
            $when = $sisa === 0 ? 'hari ini' : 'dalam ' . $sisa . ' hari';
            $items[] = [
                'type' => 'warn',
                'icon' => 'clock',
                'urgent' => true,
                'url' => 'user.php?page=peminjaman',
                'title' => 'Peminjaman ' . $kode . ' jatuh tempo ' . $when,
                'text'  => $buku . ' perlu dikembalikan sebelum ' . $r['tgl_jatuh_tempo'] . '.'
            ];
        } else {
            $items[] = [
                'type' => 'info',
                'icon' => 'book',
                'urgent' => false,
                'url' => 'user.php?page=peminjaman',
                'title' => 'Peminjaman ' . $kode . ' sedang berjalan',
                'text'  => $buku . ' dipinjam. Sisa waktu ' . $sisa . ' hari (sampai ' . $r['tgl_jatuh_tempo'] . ').'
            ];
        }
    }
    return $items;
}

function member_appearance(PDO $pdo): array
{
    $defaults = ['foto_profil' => null, 'tema' => 'auto', 'warna' => 'violet', 'banner' => '#9581cd', 'banner_foto' => null, 'bio' => ''];
    if (!isset($_SESSION['literaksi_user']['NIS'])) {
        return $defaults;
    }

    if (empty($_SESSION['member_appearance_loaded'])) {
        $settings = $pdo->prepare('SELECT foto_profil, tema, warna, banner, banner_foto, bio FROM `preferensi_user` WHERE NIS = ?');
        $settings->execute([$_SESSION['literaksi_user']['NIS']]);
        $preferences = $settings->fetch() ?: $defaults;
        $_SESSION['literaksi_user']['foto_profil'] = $preferences['foto_profil'];
        $_SESSION['literaksi_user']['tema'] = $preferences['tema'];
        $_SESSION['literaksi_user']['warna'] = $preferences['warna'];
        $_SESSION['literaksi_user']['banner'] = $preferences['banner'];
        $_SESSION['literaksi_user']['banner_foto'] = $preferences['banner_foto'];
        $_SESSION['literaksi_user']['bio'] = $preferences['bio'] ?? '';
        $_SESSION['member_appearance_loaded'] = true;
    }

    $photo = $_SESSION['literaksi_user']['foto_profil'] ?? null;
    $banner = $_SESSION['literaksi_user']['banner'] ?? null;
    $banner = is_string($banner) && preg_match('/^#[a-fA-F0-9]{6}$/', $banner)
        ? strtolower($banner) : '#9581cd';
    $_SESSION['literaksi_user']['banner'] = $banner;
    return [
        'foto_profil' => is_string($photo)
            && preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $photo) ? $photo : null,
        'tema' => in_array($_SESSION['literaksi_user']['tema'] ?? '', ['auto', 'light', 'dark', 'oled', 'flashbang'], true)
            ? $_SESSION['literaksi_user']['tema'] : 'auto',
        'warna' => in_array($_SESSION['literaksi_user']['warna'] ?? '', ['violet', 'blue', 'teal', 'rose'], true)
            ? $_SESSION['literaksi_user']['warna'] : 'violet',
        'banner' => $banner,
        'banner_foto' => is_string($_SESSION['literaksi_user']['banner_foto'] ?? null)
            && preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $_SESSION['literaksi_user']['banner_foto'])
                ? $_SESSION['literaksi_user']['banner_foto'] : null,
        'bio' => is_string($_SESSION['literaksi_user']['bio'] ?? null)
            ? mb_substr(trim($_SESSION['literaksi_user']['bio']), 0, 500) : '',
    ];
}

function render_sidebar(string $page, array $subnav = [], ?string $activeSubnav = null): void
{
    global $pdo;

    $isAdmin = is_admin_session();
    $isMember = isset($_SESSION['literaksi_user']['NIS']) && !$isAdmin;
    $appearance = $isMember ? member_appearance($pdo) : null;
    $sectionLabels = [
        'books' => 'Buku',
        'publishers' => 'Penerbit',
        'categories' => 'Kategori',
        'authors' => 'Penulis',
        'users' => 'Pengguna',
        'staff' => 'Petugas',
    ];
?>
    <aside class="sidebar">
        <div>
            <img id="logodash" src="assets/img/logol.svg" alt="Literaksi">
            <nav>
                <?php foreach (nav_menu() as $key => [$ic, $label, $href]): ?>
                    <a href="<?= $href ?>" class="<?= $key === $page ? 'active' : '' ?>">
                        <?= icon($ic) ?> <span><?= $label ?></span>
                    </a>
                    <?php if ($key === $page && $subnav): ?>
                        <div class="sidebar-subnav" aria-label="Submenu manajemen">
                            <?php foreach ($subnav as $section): ?>
                                <a href="<?= $href ?>&section=<?= rawurlencode($section) ?>"
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
        <?php if ($isAdmin || $isMember): ?>
            <form method="post" action="autentikasi.php?action=logout<?= $isAdmin ? '&amp;role=admin' : '' ?>" class="logout">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                <button type="submit" style="display:flex;align-items:center;gap:10px;width:100%;padding:0;border:0;background:transparent;color:inherit;font:inherit;text-align:left;cursor:pointer">
                    <?= icon('power') ?> <span>Keluar Akun</span>
                </button>
            </form>
        <?php endif; ?>
        <?php if ($isMember): ?>
            <div class="sidebar-accent-picker" role="group" aria-label="Warna aksen">
                <?php foreach (['violet' => 'Ungu', 'blue' => 'Biru', 'teal' => 'Teal', 'rose' => 'Rose'] as $color => $label): ?>
                    <button type="button" class="accent-swatch accent-<?= $color ?>"
                        data-save-accent="<?= $color ?>" aria-label="Gunakan warna <?= $label ?>"
                        aria-pressed="<?= $appearance['warna'] === $color ? 'true' : 'false' ?>"
                        data-tooltip="<?= $label ?>"></button>
                <?php endforeach; ?>
            </div>
            <p class="appearance-status sidebar-appearance-status sr-only" aria-live="polite"></p>
        <?php endif; ?>
    </aside>
<?php
}

function render_topbar(array $user, int $badge, string $page): void
{
    global $pdo;

    $isAdmin = is_admin_session();
    $isMember = isset($_SESSION['literaksi_user']['NIS']) && !$isAdmin;
    $isGuest = !$isAdmin && !$isMember;
    $appearance = $isMember ? member_appearance($pdo) : null;
    $storedPhoto = $appearance['foto_profil'] ?? null;
    $catalogUrl = $isGuest ? 'guest.php' : 'user.php';
?>
    <?php if ($isMember): ?>
        <script>
            (() => {
                const root = document.documentElement;
                const preference = <?= json_encode($appearance['tema']) ?>;
                const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');
                const applyTheme = () => {
                    const isDark = preference === 'dark' || preference === 'oled'
                        || (preference === 'auto' && systemTheme.matches);
                    root.dataset.theme = preference === 'flashbang' ? 'flashbang' : (isDark ? 'dark' : 'light');
                    root.dataset.themeMode = preference;
                };
                applyTheme();
                if (preference === 'auto') {
                    systemTheme.addEventListener('change', applyTheme);
                }
            })();
            document.documentElement.dataset.accent = <?= json_encode($appearance['warna']) ?>;
        </script>
    <?php endif; ?>
    <header class="topbar">
        <?php if (!$isAdmin): ?>
            <form class="search-box" method="get" action="<?= htmlspecialchars($catalogUrl) ?>">
                <input type="hidden" name="page" value="katalog">
                <?= icon('search', 18) ?>
                <input type="search" name="q" placeholder="Cari buku..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </form>
        <?php endif; ?>
        <div class="top-right">
            <?php if ($isGuest): ?>
                <nav class="guest-actions" aria-label="Akun">
                    <a class="guest-login" href="autentikasi.php?action=login&amp;return=guest">Masuk</a>
                    <a class="guest-register" href="autentikasi.php?action=register&amp;source=guest">Daftar</a>
                </nav>
            <?php endif; ?>
            <?php if ($isMember): ?>
                <a href="user.php?page=notifikasi" class="bell <?= $page === 'notifikasi' ? 'active' : '' ?>"
                    aria-label="<?= $badge > 0 ? 'Ada notifikasi baru' : 'Notifikasi' ?>" title="Notifikasi">
                    <?= icon('bell', 22) ?>
                    <?php if ($badge > 0): ?><span class="badge" aria-hidden="true"></span><?php endif; ?>
                </a>
            <?php endif; ?>
            <div class="avatar">
                <?php if ($storedPhoto !== null): ?>
                    <img src="fungsi.php?action=profile_photo&v=<?= rawurlencode($storedPhoto) ?>" alt="">
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
                const csrf = <?= json_encode($_SESSION['csrf']) ?>;
                const statusNodes = document.querySelectorAll('.appearance-status');
                const controls = [...document.querySelectorAll('[data-save-accent]')];
                const setStatus = (message, isError = false) => statusNodes.forEach((node) => {
                    node.textContent = message;
                    node.classList.toggle('is-error', isError);
                });

                async function savePreference(field, value) {
                    if (controls.some((control) => control.disabled)) return false;
                    controls.forEach((control) => { control.disabled = true; });
                    setStatus('Menyimpan...');
                    const datasetField = 'accent';
                    const previousValue = document.documentElement.dataset[datasetField];
                    document.documentElement.dataset[datasetField] = value;
                    try {
                        const response = await fetch('fungsi.php?action=save_appearance', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({ csrf, field, value })
                        });
                        const result = await response.json();
                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Pengaturan gagal disimpan.');
                        }
                        setStatus('');
                        return true;
                    } catch (error) {
                        if (previousValue) {
                            document.documentElement.dataset[datasetField] = previousValue;
                        } else {
                            delete document.documentElement.dataset[datasetField];
                        }
                        setStatus(error instanceof Error ? error.message : 'Pengaturan gagal disimpan.', true);
                        return false;
                    } finally {
                        controls.forEach((control) => { control.disabled = false; });
                    }
                }

                controls.forEach((button) => {
                    button.addEventListener('click', async () => {
                        const field = 'accent';
                        const value = button.dataset.saveAccent;
                        if (button.getAttribute('aria-pressed') === 'true'
                            || !await savePreference(field, value)) return;

                        controls.forEach((swatch) => swatch.setAttribute('aria-pressed', String(swatch === button)));
                    });
                });
            })();
        </script>
    <?php endif; ?>
<?php
}

function render_site_footer(): void
{
    $licenseUrl = 'https://creativecommons.org/licenses/by-nc/4.0/';
?>
    <footer class="site-footer">
        <p>
            <a href="https://example.com/">LITERAKSI</a> &copy; 2026 by
            <a href="https://example.com/">Bara, Adi, Arif</a> is licensed under
            <a rel="license" href="<?= $licenseUrl ?>">CC BY-NC 4.0</a>
        </p>
        <div class="site-footer-license-icons" aria-label="Creative Commons, Attribution, NonCommercial">
            <?php foreach (['cc' => 'Creative Commons', 'by' => 'Attribution', 'nc' => 'NonCommercial'] as $icon => $label): ?>
                <a rel="license" href="<?= $licenseUrl ?>" aria-label="Lisensi CC BY-NC 4.0: <?= htmlspecialchars($label) ?>">
                    <img src="https://mirrors.creativecommons.org/presskit/icons/<?= $icon ?>.svg" alt="<?= htmlspecialchars($label) ?>">
                </a>
            <?php endforeach; ?>
        </div>
    </footer>
<?php
}
