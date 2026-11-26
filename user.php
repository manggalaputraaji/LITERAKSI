<?php
// Menangani halaman anggota; guest.php menggunakan bagian katalog dan detail buku yang sama.
require_once __DIR__ . '/assets/logic/book_cover.php';
require_once __DIR__ . '/assets/logic/session.php';
start_app_session();

$page = $_GET['page'] ?? 'dashboard';
$allowedPages = ['dashboard', 'katalog', 'detail_buku', 'peminjaman', 'notifikasi', 'profile', 'edit_profile'];
$routeIsAllowed = is_string($page) && in_array($page, $allowedPages, true);
$route = $routeIsAllowed ? $page : '';
$isGuestEntry = $isGuestEntry ?? false;

if ($route === 'edit_profile') {
    header('Location: assets/logic/preferensi.php');
    exit;
}

if (
    !$isGuestEntry && in_array($route, ['katalog', 'detail_buku'], true)
    && !isset($_SESSION['literaksi_user']['NIS']) && !is_admin_session()
) {
    $guestUrl = 'guest.php?page=' . rawurlencode($route);
    if (isset($_GET['id']) && is_string($_GET['id'])) {
        $guestUrl .= '&id=' . rawurlencode($_GET['id']);
    }
    header('Location: ' . $guestUrl);
    exit;
}

?>
<?php if ($route === 'dashboard') { ?>
<?php
// Ringkas statistik anggota dan pilih beberapa buku untuk rekomendasi.
if (!isset($_SESSION['literaksi_user']) || !is_array($_SESSION['literaksi_user']) || !isset($_SESSION['literaksi_user']['NIS'])) {
    unset($_SESSION['literaksi_user'], $_SESSION['user']);
    header('Location: autentikasi.php?action=login');
    exit;
}

require 'config.php';
require_once __DIR__ . '/assets/logic/layout.php';
require_once __DIR__ . '/assets/logic/loan_workflow.php';
expire_pending_loan_requests($pdo);

$user = $_SESSION['literaksi_user'];
$memberId = $user['NIS'];

$q = $pdo->prepare('SELECT COUNT(*) FROM detail_peminjaman d
    JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
    WHERE p.NIS = ? AND p.status = \'dipinjam\' AND d.tgl_kembali IS NULL');
$q->execute([$memberId]);
$activeLoans = (int) $q->fetchColumn();

$q = $pdo->prepare('SELECT COUNT(*) FROM detail_peminjaman d
    JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
    WHERE p.NIS = ? AND p.status IN (\'dipinjam\', \'kembali\')');
$q->execute([$memberId]);
$total = (int) $q->fetchColumn();

$q = $pdo->prepare('SELECT MIN(DATEDIFF(p.tgl_jatuh_tempo, CURDATE()))
    FROM detail_peminjaman d
    JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
    WHERE p.NIS = ? AND p.status = \'dipinjam\' AND d.tgl_kembali IS NULL');
$q->execute([$memberId]);
$daysUntilDue = $q->fetchColumn();

$stats = [
    ['icon' => 'assets/img/lended.svg', 'angka' => $activeLoans, 'label' => 'Buku dipinjam'],
    ['icon' => 'assets/img/deadline.svg', 'angka' => $daysUntilDue === null ? '-' : $daysUntilDue, 'label' => 'Hari Sebelum Jatuh Tempo'],
    ['icon' => 'assets/img/total.svg', 'angka' => $total, 'label' => 'Total Peminjaman'],
];

$books = $pdo->query('SELECT b.id_buku, b.judul, k.kategori
    FROM buku b LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
    ORDER BY RAND() LIMIT 5')->fetchAll();

$palette = ['#315f8a', '#7a1620', '#2d4a6e', '#1c6d63', '#5744bd', '#9d5518'];

$page = 'home';
$badge = count(array_filter(get_notifications($pdo, $memberId), fn($n) => $n['urgent']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="assets/img/logo.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body class="dash-body">

    <?php render_sidebar($page); ?>

    <main class="main">
        <?php render_topbar($user, $badge, $page); ?>

        <section class="banner">
            <h2>Halo, <?= htmlspecialchars($user['nama']) ?>!</h2>
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
            <?php foreach ($books as $book): ?>
                <?php $coverUrl = book_cover_url((string) $book['id_buku']); ?>
                <a class="book-card" href="user.php?page=detail_buku&id=<?= rawurlencode((string) $book['id_buku']) ?>"
                   style="text-decoration:none;color:inherit;display:block;">
                    <div class="cover<?= $coverUrl !== null ? ' has-image' : '' ?>" style="background: <?= $palette[crc32($book['judul']) % count($palette)] ?>">
                        <?php if ($coverUrl !== null): ?>
                            <img class="cover-image" src="<?= htmlspecialchars($coverUrl) ?>" alt="Sampul buku: <?= htmlspecialchars($book['judul']) ?>">
                        <?php else: ?>
                            <span><?= htmlspecialchars($book['judul']) ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="book-title"><?= htmlspecialchars($book['judul']) ?></p>
                    <p class="book-price"><?= htmlspecialchars($book['kategori'] ?? '-') ?></p>
                </a>
            <?php endforeach; ?>
        </div>
        <?php render_site_footer(); ?>
    </main>

</body>
</html>
<?php } elseif ($route === 'katalog') { ?>
<?php
// Validasi filter katalog sebelum menggunakannya untuk menyusun query.
if (isset($_SESSION['literaksi_user']) && (!is_array($_SESSION['literaksi_user']) || !isset($_SESSION['literaksi_user']['NIS']))) {
    unset($_SESSION['literaksi_user'], $_SESSION['user']);
}

require 'config.php';
require_once __DIR__ . '/assets/logic/layout.php';
require_once __DIR__ . '/assets/logic/loan_workflow.php';
expire_pending_loan_requests($pdo);

$isMember = isset($_SESSION['literaksi_user']['NIS']);
$isAdmin = is_admin_session();
$canFilter = $isMember || $isAdmin;
$user = $_SESSION['literaksi_user'] ?? ($isAdmin ? $_SESSION['literaksi_admin'] : ['nama' => 'Tamu']);
$memberId  = $isMember ? $user['NIS'] : null;

$searchInput = $_GET['q'] ?? '';
$search = is_string($searchInput) ? trim($searchInput) : '';
$filterValues = static function (string $key): array {
    $values = $_GET[$key] ?? [];
    if (!is_array($values)) {
        $values = [$values];
    }
    return array_values(array_unique(array_filter(
        $values,
        static fn($value): bool => is_string($value) && $value !== ''
    )));
};
$selectedCategories = $canFilter ? $filterValues('kategori') : [];
$selectedPublishers = $canFilter ? $filterValues('penerbit') : [];
$selectedAuthors = $canFilter ? $filterValues('penulis') : [];
$availableOnly = $canFilter && ($_GET['tersedia'] ?? '') === '1';
$catalogUrl = $isGuestEntry ? 'guest.php' : 'user.php';
$detailUrl = $isGuestEntry ? 'guest.php?page=detail_buku' : 'user.php?page=detail_buku';

$categories = $canFilter ? $pdo->query('SELECT k.id_kategori AS value, k.kategori AS label, COUNT(b.id_buku) AS jumlah
    FROM kategori k LEFT JOIN buku b ON b.id_kategori = k.id_kategori
    GROUP BY k.id_kategori, k.kategori ORDER BY k.kategori')->fetchAll() : [];
$publishers = $canFilter ? $pdo->query('SELECT p.id_penerbit AS value, p.nama_penerbit AS label, COUNT(b.id_buku) AS jumlah
    FROM penerbit p LEFT JOIN buku b ON b.id_penerbit = p.id_penerbit
    GROUP BY p.id_penerbit, p.nama_penerbit ORDER BY p.nama_penerbit')->fetchAll() : [];
$authors = $canFilter ? $pdo->query('SELECT pn.id_penulis AS value, pn.nama_penulis AS label, COUNT(b.id_buku) AS jumlah
    FROM penulis pn LEFT JOIN buku b ON b.id_penulis = pn.id_penulis
    GROUP BY pn.id_penulis, pn.nama_penulis ORDER BY pn.nama_penulis')->fetchAll() : [];

$allowedFilters = [
    'kategori' => array_column($categories, 'value'),
    'penerbit' => array_column($publishers, 'value'),
    'penulis' => array_column($authors, 'value'),
];
$selectedCategories = array_values(array_intersect($selectedCategories, $allowedFilters['kategori']));
$selectedPublishers = array_values(array_intersect($selectedPublishers, $allowedFilters['penerbit']));
$selectedAuthors = array_values(array_intersect($selectedAuthors, $allowedFilters['penulis']));

$sql = 'SELECT b.id_buku, b.judul, pn.nama_penulis AS penulis, k.kategori, b.stok,
            p.nama_penerbit,
            GREATEST(CAST(b.stok AS SIGNED) - COALESCE(inventory.dipinjam, 0), 0) AS stok_tersedia
        FROM buku b
        LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
        LEFT JOIN penerbit p ON p.id_penerbit = b.id_penerbit
        LEFT JOIN penulis pn ON pn.id_penulis = b.id_penulis
        LEFT JOIN (
            SELECT d.id_buku, COUNT(*) AS dipinjam
            FROM detail_peminjaman d
            JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
            WHERE d.tgl_kembali IS NULL AND p.status IN (\'menunggu_pengambilan\', \'dipinjam\')
            GROUP BY d.id_buku
        ) inventory ON inventory.id_buku = b.id_buku
        WHERE 1=1';
$params = [];
$facetConditions = [
    'kategori' => ['b.id_kategori', $selectedCategories],
    'penerbit' => ['b.id_penerbit', $selectedPublishers],
    'penulis' => ['b.id_penulis', $selectedAuthors],
];
foreach ($facetConditions as [$column, $values]) {
    if ($values) {
        $sql .= ' AND ' . $column . ' IN (' . implode(', ', array_fill(0, count($values), '?')) . ')';
        array_push($params, ...$values);
    }
}
if ($search !== '') {
    $sql .= ' AND (b.judul LIKE ? OR pn.nama_penulis LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($availableOnly) {
    $sql .= ' AND b.stok > COALESCE(inventory.dipinjam, 0)';
}
$sql .= ' ORDER BY b.judul LIMIT 200';
$q = $pdo->prepare($sql);
$q->execute($params);
$books = $q->fetchAll();

$palette = ['#315f8a', '#7a1620', '#2d4a6e', '#1c6d63', '#5744bd', '#9d5518'];

function build_catalog_url(
    string $search,
    array $categories,
    array $publishers,
    array $authors,
    bool $availableOnly,
    ?string $removeFacet = null,
    ?string $removeValue = null,
    bool $clearFilters = false,
    bool $guest = false
): string
{
    $filters = [
        'kategori' => $categories,
        'penerbit' => $publishers,
        'penulis' => $authors,
    ];
    if ($clearFilters) {
        $filters = array_fill_keys(array_keys($filters), []);
        $availableOnly = false;
    } elseif ($removeFacet !== null && isset($filters[$removeFacet])) {
        $filters[$removeFacet] = array_values(array_filter(
            $filters[$removeFacet],
            static fn(string $value): bool => $value !== $removeValue
        ));
    } elseif ($removeFacet === 'tersedia') {
        $availableOnly = false;
    }

    $qs = array_filter([
        'q' => $search !== '' ? $search : null,
        'kategori' => $filters['kategori'] ?: null,
        'penerbit' => $filters['penerbit'] ?: null,
        'penulis' => $filters['penulis'] ?: null,
        'tersedia' => $availableOnly ? '1' : null,
    ]);
    $url = $guest ? 'guest.php?page=katalog' : 'user.php?page=katalog';
    return $url . ($qs ? '&' . http_build_query($qs) : '');
}

$page  = 'katalog';
$badge = $isMember
    ? count(array_filter(get_notifications($pdo, $memberId), fn($n) => $n['urgent']))
    : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="assets/img/logo.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Katalog Buku - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body class="dash-body">

    <?php render_sidebar($page); ?>

    <main class="main">
        <?php render_topbar($user, $badge, $page); ?>

        <div class="catalog-layout<?= $canFilter ? ' has-filters' : '' ?>">
            <?php if ($canFilter): ?>
            <aside class="catalog-filter-panel">
                <div class="catalog-filter-heading">
                    <div>
                        <h2>Filter katalog</h2>
                    </div>
                    <?php if ($selectedCategories || $selectedPublishers || $selectedAuthors || $availableOnly): ?>
                        <a class="catalog-clear-filters" href="<?= htmlspecialchars(build_catalog_url($search, $selectedCategories, $selectedPublishers, $selectedAuthors, $availableOnly, clearFilters: true, guest: $isGuestEntry)) ?>">Hapus semua filter</a>
                    <?php endif; ?>
                </div>
                <form method="get" action="<?= htmlspecialchars($catalogUrl) ?>" class="catalog-filter-form">
                    <input type="hidden" name="page" value="katalog">
                    <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
                    <label class="catalog-available-filter">
                        <input type="checkbox" name="tersedia" value="1" <?= $availableOnly ? 'checked' : '' ?>>
                        <span>Hanya yang tersedia</span>
                    </label>
                    <?php foreach ([
                        'kategori' => ['title' => 'Kategori', 'options' => $categories, 'selected' => $selectedCategories],
                        'penerbit' => ['title' => 'Penerbit', 'options' => $publishers, 'selected' => $selectedPublishers],
                        'penulis' => ['title' => 'Penulis', 'options' => $authors, 'selected' => $selectedAuthors],
                    ] as $facetKey => $facet): ?>
                        <details class="catalog-facet">
                            <summary><?= htmlspecialchars($facet['title']) ?><span><?= count($facet['options']) ?></span></summary>
                            <div class="catalog-facet-options">
                                <?php if (count($facet['options']) > 8): ?>
                                    <label class="catalog-facet-search">
                                        <span class="sr-only">Cari <?= htmlspecialchars(strtolower($facet['title'])) ?></span>
                                        <input type="search" placeholder="Cari <?= htmlspecialchars(strtolower($facet['title'])) ?>..." data-facet-search>
                                    </label>
                                <?php endif; ?>
                                <?php foreach ($facet['options'] as $option): ?>
                                    <label class="catalog-facet-option" data-facet-option>
                                        <input type="checkbox" name="<?= $facetKey ?>[]" value="<?= htmlspecialchars($option['value']) ?>"
                                            <?= in_array($option['value'], $facet['selected'], true) ? 'checked' : '' ?>
                                            >
                                        <span><?= htmlspecialchars($option['label']) ?></span>
                                        <small><?= number_format((int) $option['jumlah']) ?></small>
                                    </label>
                                <?php endforeach; ?>
                                <?php if (!$facet['options']): ?><p class="catalog-facet-empty">Belum ada pilihan.</p><?php endif; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                    <button class="btn-primary catalog-filter-submit" type="submit">Terapkan filter</button>
                </form>
            </aside>
            <?php endif; ?>

            <section class="catalog-results" aria-labelledby="catalog-results-title">
                <div class="catalog-results-heading">
                    <div>
                        <h2 id="catalog-results-title">Koleksi buku</h2>
                        <p><?= count($books) ?> buku ditemukan<?= $search !== '' ? ' untuk "' . htmlspecialchars($search) . '"' : '' ?></p>
                    </div>
                </div>

                <?php if ($selectedCategories || $selectedPublishers || $selectedAuthors || $availableOnly): ?>
                    <div class="catalog-active-filters" aria-label="Filter aktif">
                        <span class="catalog-active-label">Filter aktif:</span>
                        <?php foreach ([
                            'kategori' => ['values' => $selectedCategories, 'options' => $categories],
                            'penerbit' => ['values' => $selectedPublishers, 'options' => $publishers],
                            'penulis' => ['values' => $selectedAuthors, 'options' => $authors],
                        ] as $facetKey => $activeFacet): ?>
                            <?php foreach ($activeFacet['values'] as $activeValue): ?>
                                <?php
                                $optionIndex = array_search($activeValue, array_column($activeFacet['options'], 'value'), true);
                                $activeLabel = $optionIndex === false ? $activeValue : $activeFacet['options'][$optionIndex]['label'];
                                ?>
                                <a class="catalog-active-chip" href="<?= htmlspecialchars(build_catalog_url(
                                    $search,
                                    $selectedCategories,
                                    $selectedPublishers,
                                    $selectedAuthors,
                                    $availableOnly,
                                    $facetKey,
                                    $activeValue,
                                    guest: $isGuestEntry
                                )) ?>">
                                    <?= htmlspecialchars($activeLabel) ?><span aria-hidden="true">&times;</span>
                                    <span class="sr-only">Hapus filter <?= htmlspecialchars($activeLabel) ?></span>
                                </a>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        <?php if ($availableOnly): ?>
                            <a class="catalog-active-chip" href="<?= htmlspecialchars(build_catalog_url(
                                $search, $selectedCategories, $selectedPublishers, $selectedAuthors, $availableOnly, 'tersedia', guest: $isGuestEntry
                            )) ?>">Tersedia<span aria-hidden="true">&times;</span><span class="sr-only">Hapus filter ketersediaan</span></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="books">
                    <?php if (!$books): ?>
                        <p class="catalog-empty">Tidak ada buku yang cocok.</p>
                    <?php endif; ?>
                    <?php foreach ($books as $book): ?>
                        <?php $coverUrl = book_cover_url((string) $book['id_buku']); ?>
                        <a class="book-card" href="<?= htmlspecialchars($detailUrl) ?>&id=<?= rawurlencode((string) $book['id_buku']) ?>"
                           style="text-decoration:none;color:inherit;">
                            <div class="cover<?= $coverUrl !== null ? ' has-image' : '' ?>" style="background: <?= $palette[crc32($book['judul']) % count($palette)] ?>">
                                <?php if ($coverUrl !== null): ?>
                                    <img class="cover-image" src="<?= htmlspecialchars($coverUrl) ?>" alt="Sampul buku: <?= htmlspecialchars($book['judul']) ?>">
                                <?php else: ?>
                                    <span><?= htmlspecialchars($book['judul']) ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="book-title"><?= htmlspecialchars($book['judul']) ?></p>
                            <p class="book-price"><?= htmlspecialchars($book['penulis'] ?: 'Penulis belum diisi') ?></p>
                            <p class="book-meta"><?= htmlspecialchars($book['kategori'] ?? 'Umum') ?> · <?= htmlspecialchars($book['nama_penerbit'] ?? 'Penerbit tidak diketahui') ?></p>
                            <?php $catalogAvailable = max(0, (int) $book['stok_tersedia']); $catalogTotal = max(0, (int) $book['stok']); ?>
                            <div class="book-stock-availability <?= $catalogAvailable > 0 ? 'available' : 'unavailable' ?>" aria-label="<?= $catalogAvailable ?> dari <?= $catalogTotal ?> buku tersedia">
                                <div class="book-stock-count">
                                    <strong><?= number_format($catalogAvailable) ?></strong>
                                    <span><?= $catalogAvailable > 0 ? 'buku tersedia' : 'stok habis' ?></span>
                                </div>
                                <div class="stock-meter" aria-hidden="true"><span style="width: <?= $catalogTotal > 0 ? min(100, (int) round($catalogAvailable / $catalogTotal * 100)) : 0 ?>%"></span></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
        <?php render_site_footer(); ?>
    </main>

    <script>
        document.querySelectorAll('[data-facet-search]').forEach((input) => {
            input.addEventListener('input', () => {
                const query = input.value.trim().toLocaleLowerCase();
                input.closest('.catalog-facet-options')
                    .querySelectorAll('[data-facet-option]')
                    .forEach((option) => {
                        option.hidden = !option.textContent.toLocaleLowerCase().includes(query);
                    });
            });
        });
    </script>
</body>
</html>
<?php } elseif ($route === 'detail_buku') { ?>
<?php
// Ambil detail buku yang dipilih, lalu tampilkan aksi sesuai jenis sesi pengunjung.
if (isset($_SESSION['literaksi_user']) && (!is_array($_SESSION['literaksi_user']) || !isset($_SESSION['literaksi_user']['NIS']))) {
    unset($_SESSION['literaksi_user'], $_SESSION['user']);
}

require 'config.php';
require_once __DIR__ . '/assets/logic/layout.php';
require_once __DIR__ . '/assets/logic/loan_workflow.php';
expire_pending_loan_requests($pdo);

$isMember = isset($_SESSION['literaksi_user']['NIS']);
$isAdmin = is_admin_session();
$user = $_SESSION['literaksi_user'] ?? ($isAdmin ? $_SESSION['literaksi_admin'] : ['nama' => 'Tamu']);
$memberId  = $isMember ? $user['NIS'] : null;

$bookId = trim($_GET['id'] ?? '');
$catalogUrl = $isGuestEntry ? 'guest.php?page=katalog' : 'user.php?page=katalog';
if ($bookId === '') {
    header('Location: ' . $catalogUrl);
    exit;
}

/* ---------- Ambil data buku ---------- */
$q = $pdo->prepare('SELECT b.id_buku, b.judul, pn.nama_penulis AS penulis, b.sinopsis, b.stok,
        k.kategori, p.nama_penerbit
    FROM buku b
    LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
    LEFT JOIN penerbit p ON p.id_penerbit = b.id_penerbit
    LEFT JOIN penulis pn ON pn.id_penulis = b.id_penulis
    WHERE b.id_buku = ?');
$q->execute([$bookId]);
$book = $q->fetch();

if (!$book) {
    header('Location: ' . $catalogUrl);
    exit;
}

$activeLoansQuery = $pdo->prepare('SELECT COUNT(*) FROM detail_peminjaman d
    JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
    WHERE d.id_buku = ? AND d.tgl_kembali IS NULL
        AND p.status IN (\'menunggu_pengambilan\', \'dipinjam\')');
$activeLoansQuery->execute([$bookId]);
$activeLoans = (int) $activeLoansQuery->fetchColumn();
$availableCopies = max(0, (int) $book['stok'] - $activeLoans);
$alreadyBorrowed = false;
if ($isMember) {
    $memberLoanQuery = $pdo->prepare('SELECT COUNT(*) FROM detail_peminjaman d
        JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
        WHERE p.NIS = ? AND d.id_buku = ? AND d.tgl_kembali IS NULL
            AND p.status IN (\'menunggu_pengambilan\', \'dipinjam\')');
    $memberLoanQuery->execute([$memberId, $bookId]);
    $alreadyBorrowed = (int) $memberLoanQuery->fetchColumn() > 0;
}

if (empty($_SESSION['loan_csrf'])) {
    $_SESSION['loan_csrf'] = bin2hex(random_bytes(32));
}

/* ---------- Helper: generate next varchar(10) ID ---------- */
function next_id(PDO $pdo, string $table, string $col, string $prefix): string
{
    $last = $pdo->query("SELECT $col FROM $table ORDER BY $col DESC LIMIT 1")->fetchColumn();
    if ($last && preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $last, $m)) {
        $n = (int) $m[1] + 1;
    } else {
        $n = 1;
    }
    return $prefix . str_pad((string) $n, 9, '0', STR_PAD_LEFT); // 1 + 9 = 10 chars
}

/* ---------- Proses Pinjam ---------- */
$flash     = '';
$flashType = 'ok';

if ($isMember && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pinjam'])) {
    try {
        $csrfToken = $_POST['csrf'] ?? '';
        if (!is_string($csrfToken) || !hash_equals($_SESSION['loan_csrf'], $csrfToken)) {
            throw new RuntimeException('Sesi tidak valid. Muat ulang halaman dan coba lagi.');
        }
        $pdo->beginTransaction();
        $bookLock = $pdo->prepare('SELECT stok FROM buku WHERE id_buku = ? FOR UPDATE');
        $bookLock->execute([$bookId]);
        $lockedStock = $bookLock->fetchColumn();
        if ($lockedStock === false) {
            throw new RuntimeException('Buku tidak ditemukan.');
        }

        $memberLoanQuery->execute([$memberId, $bookId]);
        $alreadyBorrowed = (int) $memberLoanQuery->fetchColumn() > 0;
        $activeLoansQuery->execute([$bookId]);
        $activeLoans = (int) $activeLoansQuery->fetchColumn();
        $availableCopies = max(0, (int) $lockedStock - $activeLoans);

        if ($alreadyBorrowed) {
            $pdo->rollBack();
            $flash = 'Kamu masih meminjam buku ini dan belum mengembalikannya.';
            $flashType = 'bad';
        } elseif ($availableCopies < 1) {
            $pdo->rollBack();
            $flash = 'Maaf, semua buku ini sedang dipinjam.';
            $flashType = 'bad';
        } else {
            $id_peminjaman = next_id($pdo, 'peminjaman', 'id_peminjaman', 'P');
            $id_detail = next_id($pdo, 'detail_peminjaman', 'id_detail', 'D');

            $pdo->prepare('INSERT INTO peminjaman
                (id_peminjaman, NIS, id_petugas, tgl_peminjam, tgl_jatuh_tempo, status, reservation_expires_at)
                VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), ?, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 24 HOUR))')
                ->execute([$id_peminjaman, $memberId, 'SYS', 'menunggu_pengambilan']);

            $pdo->prepare('INSERT INTO detail_peminjaman
                (id_detail, id_peminjaman, id_buku, tgl_kembali, denda)
                VALUES (?, ?, ?, NULL, 0.00)')
                ->execute([$id_detail, $id_peminjaman, $bookId]);

            $reservationQuery = $pdo->prepare('SELECT reservation_expires_at FROM peminjaman WHERE id_peminjaman = ?');
            $reservationQuery->execute([$id_peminjaman]);
            $reservationExpiresAt = (string) $reservationQuery->fetchColumn();
            $matrixToken = issue_data_matrix_token($pdo, $id_detail, 'ambil', $reservationExpiresAt);

            $adminIds = $pdo->query("SELECT id_petugas FROM `admin` WHERE id_petugas <> 'SYS'")
                ->fetchAll(PDO::FETCH_COLUMN);
            $notificationInsert = $pdo->prepare('INSERT INTO notifikasi_admin (id_petugas, id_peminjaman) VALUES (?, ?)');
            foreach ($adminIds as $adminId) {
                $notificationInsert->execute([$adminId, $id_peminjaman]);
            }

            $pdo->commit();
            $_SESSION['loan_result'] = [
                'success' => true,
                'message' => 'Permintaan peminjaman berhasil dikirim. Peminjaman aktif setelah petugas memindai kode Data Matrix.',
                'book_title' => (string) $book['judul'],
                'book_id' => (string) $bookId,
                'loan_id' => (string) $id_peminjaman,
                'matrix_token' => $matrixToken,
                'matrix_expires_at' => $reservationExpiresAt,
            ];
            header('Location: assets/logic/status_peminjaman.php');
            exit;
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Book loan request failed: ' . $e->getMessage());
        $flash = $e instanceof RuntimeException ? $e->getMessage() : 'Gagal memproses peminjaman. Silakan coba lagi.';
        $flashType = 'bad';
    }

    if ($flashType === 'bad') {
        $_SESSION['loan_result'] = [
            'success' => false,
            'message' => $flash,
            'book_title' => (string) $book['judul'],
            'book_id' => (string) $bookId,
            'loan_id' => '',
        ];
        header('Location: assets/logic/status_peminjaman.php');
        exit;
    }
}

/* ---------- Warna cover ---------- */
$palette    = ['#315f8a', '#7a1620', '#2d4a6e', '#1c6d63', '#5744bd', '#9d5518'];
$coverColor = $palette[crc32($book['judul']) % count($palette)];
$coverUrl   = book_cover_url((string) $book['id_buku']);

$page  = 'katalog';
$badge = $isMember
    ? count(array_filter(get_notifications($pdo, $memberId), fn($n) => $n['urgent']))
    : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="assets/img/logo.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($book['judul']) ?> - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body class="dash-body">

    <?php render_sidebar($page); ?>

    <main class="main">
        <?php render_topbar($user, $badge, $page); ?>

        <a class="back-link" href="<?= htmlspecialchars($catalogUrl) ?>">
            <span aria-hidden="true">&larr;</span><span>Kembali</span>
        </a>

        <?php if ($flash !== ''): ?>
            <div class="alert <?= $flashType === 'ok' ? 'ok' : '' ?>"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <div class="detail-grid">
            <div class="detail-cover<?= $coverUrl !== null ? ' has-image' : '' ?>" style="background: <?= $coverColor ?>">
                <?php if ($coverUrl !== null): ?>
                    <img class="detail-cover-image" src="<?= htmlspecialchars($coverUrl) ?>" alt="Sampul buku: <?= htmlspecialchars($book['judul']) ?>">
                <?php else: ?>
                    <span><?= htmlspecialchars($book['judul']) ?></span>
                <?php endif; ?>
            </div>

            <div class="detail-body">
                <span class="status info"><?= htmlspecialchars($book['kategori'] ?? 'Umum') ?></span>
                <h1><?= htmlspecialchars($book['judul']) ?></h1>
                <p class="detail-author">
                    Penulis: <strong><?= htmlspecialchars($book['penulis'] ?: 'Belum diketahui') ?></strong>
                </p>
                <div class="detail-stock-row">
                    <div class="detail-stock-summary <?= $availableCopies > 0 ? 'available' : 'unavailable' ?>">
                        <div class="detail-stock-copy">
                            <strong><?= number_format($availableCopies) ?></strong>
                            <span><?= $availableCopies > 0 ? 'buku tersedia' : 'stok habis' ?></span>
                        </div>
                        <div class="stock-meter" role="meter" aria-label="Stok buku tersedia" aria-valuemin="0" aria-valuemax="<?= max(1, (int) $book['stok']) ?>" aria-valuenow="<?= $availableCopies ?>">
                            <span style="width: <?= (int) $book['stok'] > 0 ? min(100, (int) round($availableCopies / (int) $book['stok'] * 100)) : 0 ?>%"></span>
                        </div>
                    </div>
                </div>

                <h2 class="detail-meta-heading">Informasi buku</h2>
                <div class="detail-meta">
                    <div>
                        <dt>ID Buku</dt>
                        <dd><?= htmlspecialchars($book['id_buku']) ?></dd>
                    </div>
                    <div>
                        <dt>Kategori</dt>
                        <dd><?= htmlspecialchars($book['kategori'] ?? '-') ?></dd>
                    </div>
                    <div>
                        <dt>Penerbit</dt>
                        <dd><?= htmlspecialchars($book['nama_penerbit'] ?? '-') ?></dd>
                    </div>
                    <div>
                        <dt>Total koleksi</dt>
                        <dd><?= number_format((int) $book['stok']) ?> buku</dd>
                    </div>
                    <div>
                        <dt>Masa Pinjam</dt>
                        <dd>7 hari</dd>
                    </div>
                </div>

                <section class="detail-about">
                    <h3 class="section-title">Tentang Buku</h3>
                    <p class="detail-synopsis">
                        <?= nl2br(htmlspecialchars($book['sinopsis'] ?: 'Sinopsis buku ini belum tersedia.')) ?>
                    </p>
                </section>

                <?php if ($isMember): ?>
                    <?php if (!$alreadyBorrowed && $availableCopies > 0): ?>
                        <form class="book-action-panel" method="post">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['loan_csrf']) ?>">
                            <div>
                                <h3>Ingin membaca buku ini?</h3>
                            </div>
                            <button type="submit" name="pinjam" value="1" class="btn-primary">
                                Pinjam Buku
                            </button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="book-action-panel">
                        <div>
                            <h3>Ingin meminjam buku ini?</h3>
                        </div>
                        <a class="btn-primary" href="autentikasi.php?action=login&amp;return=guest">Masuk untuk meminjam</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php render_site_footer(); ?>
    </main>

</body>
</html>
<?php } elseif ($route === 'peminjaman') { ?>
<?php
// Tampilkan daftar peminjaman anggota beserta status dan tindakan yang tersedia.
if (!isset($_SESSION['literaksi_user']) || !is_array($_SESSION['literaksi_user']) || !isset($_SESSION['literaksi_user']['NIS'])) {
    unset($_SESSION['literaksi_user'], $_SESSION['user']);
    header('Location: autentikasi.php?action=login');
    exit;
}

require 'config.php';
require_once __DIR__ . '/assets/logic/layout.php';
require_once __DIR__ . '/assets/logic/loan_workflow.php';
expire_pending_loan_requests($pdo);

$user = $_SESSION['literaksi_user'];
$memberId  = $user['NIS'];

if (empty($_SESSION['return_csrf'])) {
    $_SESSION['return_csrf'] = bin2hex(random_bytes(32));
}
if (empty($_SESSION['matrix_csrf'])) {
    $_SESSION['matrix_csrf'] = bin2hex(random_bytes(32));
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
                AND p.status = \'dipinjam\'
                AND d.tgl_kembali IS NULL AND d.tgl_pengajuan_kembali IS NULL');
        $request->execute([$detailId, $memberId]);

        if ($request->rowCount() === 0) {
            $returnError = 'Peminjaman tidak ditemukan atau pengajuan pengembalian sudah dikirim.';
        } else {
            $_SESSION['return_notice'] = 'Permintaan pengembalian dikirim. Serahkan buku ke petugas untuk konfirmasi.';
            header('Location: user.php?page=peminjaman');
            exit;
        }
    }
}

$loanFilter = $_GET['status'] ?? 'semua';
if (!in_array($loanFilter, ['semua', 'pengambilan', 'dipinjam', 'kembali', 'dibatalkan'], true)) {
    $loanFilter = 'semua';
}

// Find out whether detail_peminjaman links to buku through id_buku.
$dCols = array_map('strtolower', $pdo->query('SHOW COLUMNS FROM detail_peminjaman')->fetchAll(PDO::FETCH_COLUMN));
$bCols = array_map('strtolower', $pdo->query('SHOW COLUMNS FROM buku')->fetchAll(PDO::FETCH_COLUMN));
$hasBuku = in_array('id_buku', $dCols, true) && in_array('id_buku', $bCols, true);

$select = 'p.id_peminjaman, p.status, p.reservation_expires_at, p.tgl_jatuh_tempo, d.tgl_kembali,
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
if ($loanFilter === 'pengambilan') {
    $sql .= ' AND p.status = \'menunggu_pengambilan\'';
} elseif ($loanFilter === 'dipinjam') {
    $sql .= ' AND p.status = \'dipinjam\' AND d.tgl_kembali IS NULL';
} elseif ($loanFilter === 'kembali') {
    $sql .= ' AND d.tgl_kembali IS NOT NULL';
} elseif ($loanFilter === 'dibatalkan') {
    $sql .= ' AND p.status = \'dibatalkan\'';
}
$sql .= ' ORDER BY (p.status = \'menunggu_pengambilan\') DESC, (d.tgl_kembali IS NULL) DESC, p.tgl_jatuh_tempo ASC';

$q = $pdo->prepare($sql);
$q->execute([$memberId]);
$loans = $q->fetchAll();

$palette = ['#315f8a', '#7a1620', '#2d4a6e', '#1c6d63', '#5744bd', '#9d5518'];

function get_loan_status(array $loan): array
{
    if ($loan['status'] === 'menunggu_pengambilan') {
        return ['warn', 'Menunggu pengambilan'];
    }
    if ($loan['status'] === 'dibatalkan') {
        return ['danger', 'Permintaan kedaluwarsa'];
    }
    if ($loan['tgl_kembali'] !== null) {
        return ['ok', 'Dikembalikan ' . $loan['tgl_kembali']];
    }
    if ($loan['tgl_pengajuan_kembali'] !== null) {
        return ['warn', 'Menunggu konfirmasi pengembalian'];
    }
    $daysUntilDue = (int) $loan['sisa'];
    if ($daysUntilDue < 0)   return ['danger', 'Terlambat ' . abs($daysUntilDue) . ' hari'];
    if ($daysUntilDue === 0) return ['warn', 'Jatuh tempo hari ini'];
    if ($daysUntilDue <= 3)  return ['warn', 'Sisa ' . $daysUntilDue . ' hari'];
    return ['info', 'Sisa ' . $daysUntilDue . ' hari'];
}

$tabs = ['semua' => 'Semua', 'pengambilan' => 'Menunggu pengambilan', 'dipinjam' => 'Sedang dipinjam', 'kembali' => 'Sudah dikembalikan', 'dibatalkan' => 'Kedaluwarsa'];

$page  = 'peminjaman';
$badge = count(array_filter(get_notifications($pdo, $memberId), fn($n) => $n['urgent']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="assets/img/logo.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Peminjaman Saya - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body class="dash-body">

    <?php render_sidebar($page); ?>

    <main class="main">
        <?php render_topbar($user, $badge, $page); ?>

        <h3 class="page-title">Peminjaman Saya</h3>

        <div class="chips loan-tabs">
            <?php foreach ($tabs as $key => $label): ?>
                <a class="chip <?= $loanFilter === $key ? 'active' : '' ?>"
                   href="user.php?page=peminjaman<?= $key === 'semua' ? '' : '&status=' . $key ?>"><?= $label ?></a>
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

        <div class="books loan-books">
            <?php if (!$loans): ?>
                <p>Belum ada peminjaman<?= $loanFilter !== 'semua' ? ' pada kategori ini' : '' ?>.</p>
            <?php endif; ?>
            <?php foreach ($loans as $loan):
                $judul = $hasBuku && !empty($loan['judul']) ? $loan['judul'] : 'Peminjaman #' . $loan['id_peminjaman'];
                $coverUrl = !empty($loan['id_buku']) ? book_cover_url((string) $loan['id_buku']) : null;
                [$cls, $text] = get_loan_status($loan);
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
                    <div class="loan-card-info">
                        <?php if ($hasBuku && !empty($loan['kategori'])): ?>
                            <p class="book-meta"><?= htmlspecialchars($loan['kategori']) ?></p>
                        <?php endif; ?>
                        <span class="status <?= $cls ?>"><?= htmlspecialchars($text) ?></span>
                        <?php if ($loan['status'] === 'menunggu_pengambilan' && $loan['reservation_expires_at'] !== null): ?>
                            <p class="loan-due-date"><span>Batas pengambilan</span><strong><?= htmlspecialchars($loan['reservation_expires_at']) ?></strong></p>
                        <?php elseif ($loan['status'] === 'dipinjam'): ?>
                        <p class="loan-due-date">
                            <span>Jatuh tempo</span>
                            <strong><?= htmlspecialchars((string) $loan['tgl_jatuh_tempo']) ?></strong>
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php if ($loan['status'] === 'menunggu_pengambilan'): ?>
                        <form method="post" action="assets/logic/data_matrix_peminjaman.php">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['matrix_csrf']) ?>">
                            <input type="hidden" name="id_detail" value="<?= htmlspecialchars((string) $loan['id_detail']) ?>">
                            <button class="admin-action" type="submit">Buka kode pengambilan</button>
                        </form>
                    <?php elseif ($loan['status'] === 'dipinjam'): ?>
                        <form method="post" action="assets/logic/data_matrix_peminjaman.php">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['matrix_csrf']) ?>">
                            <input type="hidden" name="id_detail" value="<?= htmlspecialchars((string) $loan['id_detail']) ?>">
                            <button class="admin-action" type="submit">Buka kode pengembalian</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($loan['status'] === 'dipinjam' && $loan['tgl_kembali'] === null && $loan['tgl_pengajuan_kembali'] === null): ?>
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['return_csrf']) ?>">
                            <input type="hidden" name="action" value="request_return">
                            <input type="hidden" name="id_detail" value="<?= htmlspecialchars($loan['id_detail']) ?>">
                            <button type="submit" class="admin-action">Ajukan pengembalian</button>
                        </form>
                    <?php elseif ($loan['status'] === 'dipinjam' && $loan['tgl_pengajuan_kembali'] !== null && $loan['tgl_kembali'] === null): ?>
                        <p class="book-meta">Pengajuan kembali: <?= htmlspecialchars($loan['tgl_pengajuan_kembali']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php render_site_footer(); ?>
    </main>

</body>
</html>
<?php } elseif ($route === 'notifikasi') { ?>
<?php
// Susun notifikasi jatuh tempo dan ambil beberapa buku terbaru sebagai rekomendasi.
if (!isset($_SESSION['literaksi_user']) || !is_array($_SESSION['literaksi_user']) || !isset($_SESSION['literaksi_user']['NIS'])) {
    unset($_SESSION['literaksi_user'], $_SESSION['user']);
    header('Location: autentikasi.php?action=login');
    exit;
}

require 'config.php';
require_once __DIR__ . '/assets/logic/layout.php';

$user = $_SESSION['literaksi_user'];
$notifs = get_notifications($pdo, $user['NIS']);
$badge = count(array_filter($notifs, fn($n) => $n['urgent']));
$books = $pdo->query('SELECT b.id_buku, b.judul, pn.nama_penulis, k.kategori, p.nama_penerbit
    FROM buku b
    LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
    LEFT JOIN penerbit p ON p.id_penerbit = b.id_penerbit
    LEFT JOIN penulis pn ON pn.id_penulis = b.id_penulis
    ORDER BY b.id_buku DESC
    LIMIT 5')->fetchAll();
$palette = ['#315f8a', '#7a1620', '#2d4a6e', '#1c6d63', '#5744bd', '#9d5518'];
$page = 'notifikasi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="assets/img/logo.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notifikasi Saya - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body class="dash-body">

    <?php render_sidebar($page); ?>

    <main class="main notifications-page" data-user="<?= htmlspecialchars((string) $user['NIS']) ?>">
        <?php render_topbar($user, $badge, $page); ?>

        <h1 class="notification-heading">Notifikasi Saya</h1>

        <nav class="notification-filters" aria-label="Filter notifikasi">
            <button type="button" class="active" data-filter="all">Semua</button>
            <button type="button" data-filter="unread">Belum dibaca</button>
            <button type="button" data-filter="read">Sudah dibaca</button>
        </nav>

        <div class="notification-list" aria-live="polite">
            <?php foreach ($notifs as $notification):
                $notificationId = hash('sha256', $notification['title'] . '|' . $notification['text']);
                $kind = $notification['type'] === 'danger' ? 'error'
                    : ($notification['type'] === 'warn' ? 'warning' : 'success');
                $icon = $kind === 'success' ? 'check' : ($kind === 'error' ? 'close' : 'alert');
            ?>
                <article class="notification-item <?= $kind ?>" data-notification-id="<?= $notificationId ?>" data-state="unread">
                    <span class="notification-icon"><?= icon($icon, 16) ?></span>
                    <a class="notification-content" href="<?= htmlspecialchars($notification['url'] ?? 'user.php?page=notifikasi') ?>"
                       aria-label="Buka detail: <?= htmlspecialchars($notification['title']) ?>">
                        <strong><?= htmlspecialchars($notification['title']) ?></strong>
                        <span><?= htmlspecialchars($notification['text']) ?></span>
                    </a>
                    <button class="notification-dismiss" type="button" aria-label="Tutup notifikasi">&times;</button>
                </article>
            <?php endforeach; ?>
            <p class="notification-empty" <?= $notifs ? 'hidden' : '' ?>>Belum ada notifikasi.</p>
            <p class="notification-filter-empty" hidden>Tidak ada notifikasi pada filter ini.</p>
        </div>

        <section class="whats-new">
            <h2>What's New</h2>

            <div class="new-books">
                <?php foreach ($books as $book):
                    $coverUrl = book_cover_url((string) $book['id_buku']);
                    $bookId = (string) $book['id_buku'];
                ?>
                    <a class="new-book" href="user.php?page=detail_buku&amp;id=<?= rawurlencode($bookId) ?>"
                       style="text-decoration:none;color:inherit;">
                        <div class="new-book-cover" style="background: <?= $palette[crc32($book['judul']) % count($palette)] ?>">
                            <?php if ($coverUrl !== null): ?>
                                <img src="<?= htmlspecialchars($coverUrl) ?>" alt="Sampul buku: <?= htmlspecialchars($book['judul']) ?>">
                            <?php else: ?>
                                <span><?= htmlspecialchars($book['judul']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="new-book-info">
                            <strong><?= htmlspecialchars($book['judul']) ?></strong>
                            <span><?= htmlspecialchars($book['nama_penulis'] ?? 'Penulis belum diisi') ?></span>
                            <span><?= htmlspecialchars($book['nama_penerbit'] ?? 'Penerbit tidak diketahui') ?></span>
                            <p><?= htmlspecialchars($book['kategori'] ?? 'Umum') ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
                <?php if (!$books): ?>
                    <p class="new-books-empty">Belum ada buku baru di katalog.</p>
                <?php endif; ?>
            </div>
        </section>
        <?php render_site_footer(); ?>
    </main>
    <script>
        (() => {
            const page = document.querySelector('.notifications-page');
            const userId = page.dataset.user;
            const notificationKey = `literaksi-notifications-${userId}`;
            const notifications = [...document.querySelectorAll('.notification-item')];
            const filters = [...document.querySelectorAll('.notification-filters button')];
            const empty = document.querySelector('.notification-empty');
            const filteredEmpty = document.querySelector('.notification-filter-empty');
            let notificationState = {};

            try {
                notificationState = JSON.parse(localStorage.getItem(notificationKey) || '{}');
                if (!notificationState || Array.isArray(notificationState) || typeof notificationState !== 'object') {
                    notificationState = {};
                }
            } catch (error) {
                notificationState = {};
            }

            let activeFilter = 'all';

            function renderNotifications() {
                let visibleCount = 0;
                let matchingCount = 0;
                notifications.forEach((item) => {
                    const state = notificationState[item.dataset.notificationId];
                    item.dataset.state = state === 'read' ? 'read' : state === 'dismissed' ? 'dismissed' : 'unread';
                    const matches = item.dataset.state !== 'dismissed'
                        && (activeFilter === 'all' || item.dataset.state === activeFilter);
                    item.hidden = !matches;
                    if (item.dataset.state !== 'dismissed') matchingCount++;
                    if (matches) visibleCount++;
                });
                empty.hidden = matchingCount !== 0;
                filteredEmpty.hidden = matchingCount === 0 || visibleCount !== 0;
            }

            function saveNotifications() {
                localStorage.setItem(notificationKey, JSON.stringify(notificationState));
                renderNotifications();
            }

            filters.forEach((button) => {
                button.addEventListener('click', () => {
                    activeFilter = button.dataset.filter;
                    filters.forEach((filter) => filter.classList.toggle('active', filter === button));
                    renderNotifications();
                });
            });

            notifications.forEach((item) => {
                item.querySelector('.notification-content').addEventListener('click', () => {
                    notificationState[item.dataset.notificationId] = 'read';
                    saveNotifications();
                });
                item.querySelector('.notification-dismiss').addEventListener('click', () => {
                    notificationState[item.dataset.notificationId] = 'dismissed';
                    saveNotifications();
                });
            });

            renderNotifications();
        })();
    </script>

</body>
</html>
<?php } elseif ($route === 'profile') { ?>
<?php
// Tampilkan profil publik dan batasi data pribadi sesuai hak akses pemirsa.
$isAdminViewer = is_admin_session();
$viewerUser = $isAdminViewer ? $_SESSION['literaksi_admin'] : ($_SESSION['literaksi_user'] ?? null);
if (!$isAdminViewer && (!is_array($viewerUser) || !isset($viewerUser['NIS']))) {
    header('Location: autentikasi.php?action=login');
    exit;
}

require 'config.php';
require_once __DIR__ . '/assets/logic/layout.php';

$requestedMemberId = $_GET['nis'] ?? ($viewerUser['NIS'] ?? '');
if (!is_string($requestedMemberId) || $requestedMemberId === '' || strlen($requestedMemberId) > 30) {
    http_response_code(404);
    exit('Profil tidak ditemukan.');
}
$profileQuery = $pdo->prepare('SELECT s.NIS, s.nama, s.username, s.email,
        settings.foto_profil, settings.banner, settings.banner_foto, settings.bio
    FROM `user` s LEFT JOIN `preferensi_user` settings ON settings.NIS = s.NIS
    WHERE s.NIS = ? LIMIT 1');
$profileQuery->execute([$requestedMemberId]);
$user = $profileQuery->fetch();
if (!$user) {
    http_response_code(404);
    exit('Profil tidak ditemukan.');
}
$memberId = (string) $user['NIS'];
$isOwnProfile = !$isAdminViewer && (string) $viewerUser['NIS'] === $memberId;
$profilePhoto = is_string($user['foto_profil'] ?? null)
    && preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $user['foto_profil']) ? $user['foto_profil'] : null;
$profileBannerPhoto = is_string($user['banner_foto'] ?? null)
    && preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $user['banner_foto']) ? $user['banner_foto'] : null;
$profileBannerColor = is_string($user['banner'] ?? null) && preg_match('/^#[a-fA-F0-9]{6}$/', $user['banner'])
    ? strtolower($user['banner']) : '#9581cd';
$profileBio = is_string($user['bio'] ?? null) ? trim($user['bio']) : '';

$q = $pdo->prepare('SELECT COUNT(*) FROM detail_peminjaman d
    JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
    WHERE p.NIS = ? AND p.status = \'dipinjam\' AND d.tgl_kembali IS NULL');
$q->execute([$memberId]);
$activeLoans = (int) $q->fetchColumn();

$q = $pdo->prepare('SELECT COUNT(*) FROM detail_peminjaman d
    JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
    WHERE p.NIS = ? AND p.status IN (\'dipinjam\', \'kembali\')');
$q->execute([$memberId]);
$total = (int) $q->fetchColumn();
$booksQuery = $pdo->prepare('SELECT b.id_buku, b.judul, pn.nama_penulis, p.tgl_peminjam
    FROM detail_peminjaman d
    JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
    JOIN buku b ON b.id_buku = d.id_buku
    LEFT JOIN penulis pn ON pn.id_penulis = b.id_penulis
    WHERE p.NIS = ? AND p.status = \'dipinjam\' AND d.tgl_kembali IS NULL
    ORDER BY p.tgl_peminjam DESC, b.judul ASC');
$booksQuery->execute([$memberId]);
$borrowedBooks = $booksQuery->fetchAll();

$page  = 'profil';
$badge = !$isAdminViewer && $isOwnProfile
    ? count(array_filter(get_notifications($pdo, (string) $viewerUser['NIS']), fn($n) => $n['urgent']))
    : 0;
$initial = mb_strtoupper(mb_substr($user['nama'], 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="assets/img/logo.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body class="dash-body">

    <?php render_sidebar($page); ?>

    <main class="main">
        <?php render_topbar($viewerUser, $badge, $page); ?>

        <section class="profile-head">
            <div class="profile-cover<?= $profileBannerPhoto !== null ? ' has-banner-photo' : '' ?>"
                style="--profile-banner-color: <?= htmlspecialchars($profileBannerColor, ENT_QUOTES) ?>">
                <?php if (!$isOwnProfile): ?><a class="profile-back-discussion" href="diskusi.php<?= $isAdminViewer ? '?role=admin' : '' ?>">&larr; Kembali</a><?php endif; ?>
                <?php if ($profileBannerPhoto !== null): ?>
                    <img class="profile-cover-image"
                        src="assets/logic/fungsi.php?action=profile_banner&amp;nis=<?= rawurlencode($memberId) ?>&amp;v=<?= rawurlencode($profileBannerPhoto) ?><?= $isAdminViewer ? '&amp;role=admin' : '' ?>"
                        alt="">
                <?php endif; ?>
            </div>
            <div class="avatar-lg">
                <?php if ($profilePhoto !== null): ?>
                    <img src="assets/logic/fungsi.php?action=profile_photo&amp;nis=<?= rawurlencode($memberId) ?>&amp;v=<?= rawurlencode($profilePhoto) ?><?= $isAdminViewer ? '&amp;role=admin' : '' ?>" alt="Foto profil <?= htmlspecialchars($user['nama']) ?>">
                <?php else: ?>
                    <?= htmlspecialchars($initial) ?>
                <?php endif; ?>
            </div>
            <h2><?= htmlspecialchars($user['nama']) ?></h2>
            <small>Anggota Perpustakaan &middot; @<?= htmlspecialchars($user['username']) ?></small>
            <p class="profile-bio"><?= $profileBio !== '' ? nl2br(htmlspecialchars($profileBio)) : ($isOwnProfile ? 'Bio belum ditambahkan.' : 'Pengguna ini belum menambahkan bio.') ?></p>
            <?php if ($isOwnProfile): ?><a class="btn-edit" href="assets/logic/preferensi.php">Preferensi</a><?php endif; ?>
        </section>

        <?php if ($isOwnProfile && isset($_GET['updated'])): ?>
            <div class="alert ok" style="margin-top:16px">Profil berhasil diperbarui.</div>
        <?php endif; ?>

        <div class="profile-overview">
            <div class="card">
                <h4>Tentang</h4>
                <dl class="info">
                    <dt>Nama</dt>     <dd><?= htmlspecialchars($user['nama']) ?></dd>
                    <dt>Username</dt> <dd>@<?= htmlspecialchars($user['username']) ?></dd>
                    <?php if ($isOwnProfile): ?>
                        <dt>Email</dt><dd><?= htmlspecialchars((string) ($user['email'] ?? '')) ?></dd>
                        <dt>NIS</dt><dd><?= htmlspecialchars($memberId) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
            <div class="card">
                <h4>Aktivitas Membaca</h4>
                <div class="mini-stats">
                    <div><strong><?= $activeLoans ?></strong><span>Sedang dipinjam</span></div>
                    <div><strong><?= $total ?></strong><span>Total pinjam</span></div>
                </div>
            </div>
            <section class="card profile-books">
                <h4>Buku yang sedang dipinjam</h4>
                <?php if (!$borrowedBooks): ?>
                    <p class="profile-muted">Tidak ada buku yang sedang dipinjam.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($borrowedBooks as $book): ?>
                            <li><strong><?= htmlspecialchars($book['judul']) ?></strong>
                                <?php if (!empty($book['nama_penulis'])): ?><small><?= htmlspecialchars($book['nama_penulis']) ?></small><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>
        <?php render_site_footer(); ?>
    </main>

</body>
</html>
<?php } else { ?>
<!DOCTYPE html><html lang="id"><meta charset="UTF-8"><title>Halaman tidak ditemukan</title><body><p>Halaman tidak ditemukan.</p></body></html>
<?php } ?>
