<?php
// Fungsi bersama untuk ikon, menu, notifikasi, sidebar, dan topbar.
require_once __DIR__ . '/bootstrap.php';
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
        'check'  => '<path d="m5 12 4 4L19 6"/>',
        'close'  => '<path d="m18 6-12 12M6 6l12 12"/>',
        'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.5-6 8-6s8 2 8 6"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.6a8 8 0 0 1-1.7 1l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.7-1l-1.7.6-1.4-2.4L7.3 15a8 8 0 0 1 0-2l-1.4-1.1 1.4-2.4 1.7.6a8 8 0 0 1 1.7-1l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.7 1l1.7-.6 1.4 2.4-1.4 1.1a8 8 0 0 1-.1 2z"/>',
        'power'  => '<path d="M12 3v9"/><path d="M6.5 6.5a8 8 0 1 0 11 0"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'printer' => '<path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v7H6zM18 12h.01"/>',
        'alert'  => '<path d="M12 3 2 20h20z"/><path d="M12 10v4M12 17.5v.01"/>',
        'file'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
    ];
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size
        . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
        . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($paths[$name] ?? '') . '</svg>';
}

function render_auth_book_art(): void
{
?>
    <div class="auth-book-art" aria-hidden="true">
        <svg viewBox="0 0 360 220" fill="none" xmlns="http://www.w3.org/2000/svg">
            <ellipse cx="180" cy="197" rx="145" ry="12" fill="#7774A8" fill-opacity=".12"/>
            <path d="M38 182h284l-10 12H48l-10-12Z" fill="#7267B1"/>
            <path d="M50 179h260" stroke="#AAA1E7" stroke-width="3" stroke-linecap="round"/>
            <rect x="57" y="88" width="48" height="91" rx="7" fill="#7565BD"/>
            <path d="M68 89v89M96 96v77" stroke="#B6A9FF" stroke-opacity=".72" stroke-width="3"/>
            <path d="M65 108h31M65 160h31" stroke="#E4DFFF" stroke-opacity=".72" stroke-width="2"/>
            <rect x="111" y="52" width="54" height="127" rx="7" fill="#E8A951"/>
            <path d="M123 54v123M155 60v111" stroke="#FFE0A6" stroke-opacity=".85" stroke-width="3"/>
            <path d="M120 77h34M120 154h34" stroke="#FFF0D0" stroke-opacity=".8" stroke-width="2"/>
            <rect x="171" y="78" width="45" height="101" rx="7" fill="#5F8F98"/>
            <path d="M182 80v97M207 86v83" stroke="#A8D3D3" stroke-opacity=".8" stroke-width="3"/>
            <path d="M180 104h28M180 157h28" stroke="#D8EEEE" stroke-opacity=".72" stroke-width="2"/>
            <rect x="222" y="62" width="54" height="117" rx="7" fill="#C77B91"/>
            <path d="M234 64v113M266 70v101" stroke="#F0B6C5" stroke-opacity=".85" stroke-width="3"/>
            <path d="M231 88h34M231 155h34" stroke="#F9DDE4" stroke-opacity=".78" stroke-width="2"/>
            <rect x="282" y="101" width="28" height="78" rx="6" fill="#8791C7"/>
            <path d="M291 103v74M302 108v64" stroke="#C9D0F4" stroke-opacity=".84" stroke-width="2"/>
            <path d="m75 55 3 8 8 3-8 3-3 8-3-8-8-3 8-3 3-8ZM293 42l2 6 6 2-6 2-2 6-2-6-6-2 6-2 2-6Z" fill="#A99CF5"/>
            <circle cx="113" cy="37" r="3" fill="#E8A951"/>
            <circle cx="264" cy="33" r="2.5" fill="#5F8F98"/>
        </svg>
    </div>
<?php
}

function nav_menu(): array
{
    if (is_admin_session()) {
        return [
            'admin_dashboard' => ['home', 'Dashboard Admin', 'admin.php?page=dashboard'],
            'admin_peminjaman' => ['loans', 'Peminjaman', 'admin.php?page=peminjaman'],
            'admin_notifications' => ['bell', 'Notifikasi', 'assets/logic/notifikasiadmin.php'],
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

// Membuat notifikasi dari peminjaman anggota yang belum dikembalikan.
function get_notifications(PDO $pdo, $nis): array
{
    $notificationQuery = $pdo->prepare('SELECT p.id_peminjaman, p.tgl_jatuh_tempo,
            DATEDIFF(p.tgl_jatuh_tempo, CURDATE()) AS sisa, COUNT(*) AS jumlah
        FROM detail_peminjaman d
        JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
        WHERE p.NIS = ? AND p.status = \'dipinjam\' AND d.tgl_kembali IS NULL
        GROUP BY p.id_peminjaman, p.tgl_jatuh_tempo
        ORDER BY sisa ASC');
    $notificationQuery->execute([$nis]);

    $notifications = [];
    foreach ($notificationQuery->fetchAll() as $loan) {
        $daysRemaining = (int) $loan['sisa'];
        $bookDescription = (int) $loan['jumlah'] . ' buku';
        $loanCode = '#' . $loan['id_peminjaman'];

        if ($daysRemaining < 0) {
            $notifications[] = [
                'type' => 'danger',
                'icon' => 'alert',
                'urgent' => true,
                'url' => 'user.php?page=peminjaman',
                'title' => 'Peminjaman ' . $loanCode . ' terlambat ' . abs($daysRemaining) . ' hari',
                'text'  => $bookDescription . ' belum dikembalikan. Jatuh tempo: ' . $loan['tgl_jatuh_tempo'] . '.'
            ];
        } elseif ($daysRemaining <= 3) {
            $dueDescription = $daysRemaining === 0 ? 'hari ini' : 'dalam ' . $daysRemaining . ' hari';
            $notifications[] = [
                'type' => 'warn',
                'icon' => 'clock',
                'urgent' => true,
                'url' => 'user.php?page=peminjaman',
                'title' => 'Peminjaman ' . $loanCode . ' jatuh tempo ' . $dueDescription,
                'text'  => $bookDescription . ' perlu dikembalikan sebelum ' . $loan['tgl_jatuh_tempo'] . '.'
            ];
        } else {
            $notifications[] = [
                'type' => 'info',
                'icon' => 'book',
                'urgent' => false,
                'url' => 'user.php?page=peminjaman',
                'title' => 'Peminjaman ' . $loanCode . ' sedang berjalan',
                'text'  => $bookDescription . ' dipinjam. Sisa waktu ' . $daysRemaining . ' hari (sampai ' . $loan['tgl_jatuh_tempo'] . ').'
            ];
        }
    }
    return $notifications;
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
    $adminUnreadCount = 0;
    if ($isAdmin) {
        $unread = $pdo->prepare('SELECT COUNT(*) FROM notifikasi_admin WHERE id_petugas = ? AND read_at IS NULL');
        $unread->execute([$_SESSION['literaksi_admin']['id_petugas']]);
        $adminUnreadCount = (int) $unread->fetchColumn();
    }
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
                        <?php if ($isAdmin && $key === 'admin_notifications'): ?>
                            <span class="admin-notification-nav-count" data-admin-notification-badge
                                aria-label="<?= $adminUnreadCount ?> notifikasi belum dibaca"
                                <?= $adminUnreadCount === 0 ? 'hidden' : '' ?>><?= $adminUnreadCount ?></span>
                        <?php endif; ?>
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
                    <img src="assets/logic/fungsi.php?action=profile_photo&v=<?= rawurlencode($storedPhoto) ?>" alt="">
                <?php endif; ?>
            </div>
            <div class="who">
                <strong><?= htmlspecialchars($user['nama'] ?? 'Tamu') ?></strong>
            </div>
        </div>
        <?php if ($isAdmin): ?>
            <div class="admin-live-datetime" aria-label="Tanggal dan waktu saat ini">
                <?= icon('clock', 18) ?>
                <span id="admin-current-date"><?= htmlspecialchars((new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('d/m/Y')) ?></span>
                <time id="admin-current-time" datetime="<?= htmlspecialchars((new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format(DateTimeInterface::ATOM)) ?>"><?= htmlspecialchars((new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('H:i:s')) ?> WIB</time>
            </div>
        <?php endif; ?>
    </header>
    <?php if ($isAdmin): ?>
        <script>
            (() => {
                const dateNode = document.getElementById('admin-current-date');
                const timeNode = document.getElementById('admin-current-time');
                if (!dateNode || !timeNode) return;
                const dateFormat = new Intl.DateTimeFormat('id-ID', {
                    timeZone: 'Asia/Jakarta', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
                });
                const timeFormat = new Intl.DateTimeFormat('id-ID', {
                    timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
                });
                const updateClock = () => {
                    const now = new Date();
                    dateNode.textContent = dateFormat.format(now);
                    timeNode.textContent = `${timeFormat.format(now)} WIB`;
                    timeNode.dateTime = now.toISOString();
                };
                updateClock();
                window.setInterval(updateClock, 1000);
            })();
        </script>
    <?php endif; ?>
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
                        const response = await fetch('assets/logic/fungsi.php?action=save_appearance', {
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

function render_admin_live_notifications(): void
{
    if (!is_admin_session()) {
        return;
    }
    ?>
    <div id="admin-live-notifications" class="admin-live-notifications" aria-live="polite" aria-relevant="additions"></div>
    <p id="admin-live-notification-status" class="sr-only" aria-live="polite">
        Notifikasi langsung aktif. Suara tersedia setelah interaksi pertama.
    </p>
    <script>
        (() => {
            const noticeRegion = document.getElementById('admin-live-notifications');
            const statusNode = document.getElementById('admin-live-notification-status');
            const badge = document.querySelector('[data-admin-notification-badge]');
            const endpoint = 'assets/logic/fungsi.php?action=admin_loan_notifications&role=admin';
            const notificationAudio = new Audio('assets/audio/notif.wav');
            notificationAudio.preload = 'auto';
            notificationAudio.volume = 0.7;
            let cursor = 0;
            let initialized = false;
            let isPolling = false;
            let soundUnlocked = false;

            function setStatus(message) {
                if (statusNode) statusNode.textContent = message;
            }

            function updateUnreadCount(count) {
                if (!badge || !Number.isInteger(count) || count < 0) return;
                badge.textContent = count > 99 ? '99+' : String(count);
                badge.hidden = count === 0;
                badge.setAttribute('aria-label', `${count} notifikasi belum dibaca`);
            }

            async function unlockSound() {
                if (soundUnlocked) return;
                const wasMuted = notificationAudio.muted;
                notificationAudio.muted = true;
                try {
                    await notificationAudio.play();
                    notificationAudio.pause();
                    notificationAudio.currentTime = 0;
                    notificationAudio.muted = wasMuted;
                    soundUnlocked = true;
                    setStatus('Suara notifikasi aktif.');
                    document.removeEventListener('pointerdown', unlockSound);
                    document.removeEventListener('keydown', unlockSound);
                } catch (error) {
                    console.error('Unable to enable admin notification sound.', error);
                    notificationAudio.muted = wasMuted;
                    setStatus('Suara belum dapat diaktifkan. Notifikasi visual tetap aktif.');
                }
            }

            document.addEventListener('pointerdown', unlockSound, { passive: true });
            document.addEventListener('keydown', unlockSound);

            async function playSound() {
                if (!soundUnlocked) return;
                try {
                    notificationAudio.currentTime = 0;
                    await notificationAudio.play();
                } catch (error) {
                    console.error('Unable to play admin notification sound.', error);
                    setStatus('Suara notifikasi gagal diputar. Peringatan visual tetap aktif.');
                }
            }

            function showNotification(notification) {
                if (!noticeRegion) return;
                const notice = document.createElement('article');
                notice.className = 'admin-live-notification';
                notice.setAttribute('role', 'status');

                const heading = document.createElement('strong');
                heading.textContent = 'Permintaan peminjaman baru';
                const message = document.createElement('p');
                message.textContent = notification.loan_status === 'menunggu_pengambilan'
                    ? `${notification.member_name} mengajukan peminjaman "${notification.book_title}".`
                    : `${notification.member_name} meminjam "${notification.book_title}".`;
                const link = document.createElement('a');
                link.href = 'assets/logic/notifikasiadmin.php';
                link.textContent = 'Lihat notifikasi';

                notice.append(heading, message, link);
                noticeRegion.prepend(notice);
                window.setTimeout(() => notice.remove(), 10000);
                void playSound();
            }

            async function fetchNotifications(mode) {
                const query = new URLSearchParams({ mode, after: String(cursor) });
                const response = await fetch(`${endpoint}&${query.toString()}`, {
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { Accept: 'application/json' }
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Notifikasi admin gagal dimuat.');
                }
                return result;
            }

            async function poll() {
                if (isPolling) return;
                isPolling = true;
                try {
                    const result = await fetchNotifications(initialized ? 'poll' : 'init');
                    cursor = Number.isInteger(result.cursor) ? result.cursor : cursor;
                    updateUnreadCount(result.unread_count);
                    if (initialized) {
                        result.notifications.forEach(showNotification);
                    } else {
                        initialized = true;
                    }
                    setStatus('Pemantauan notifikasi admin aktif.');
                } catch (error) {
                    console.error('Admin notification polling failed.', error);
                    setStatus('Notifikasi langsung gagal dimuat. Sistem akan mencoba lagi.');
                } finally {
                    isPolling = false;
                }
            }

            poll();
            window.setInterval(poll, 5000);
        })();
    </script>
    <?php
}

function render_site_footer(): void
{
    $licenseUrl = 'https://creativecommons.org/licenses/by-nc/4.0/';
    render_admin_live_notifications();
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
