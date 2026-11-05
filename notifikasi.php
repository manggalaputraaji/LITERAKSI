<?php
require_once __DIR__ . '/session.php';
start_app_session();

if (!isset($_SESSION['literaksi_user']) || !is_array($_SESSION['literaksi_user']) || !isset($_SESSION['literaksi_user']['NIS'])) {
    unset($_SESSION['literaksi_user'], $_SESSION['user']);
    header('Location: login.php');
    exit;
}

require 'config.php';
require 'layout.php';

$user = $_SESSION['literaksi_user'];
$notifs = get_notifications($pdo, $user['NIS']);
$badge = count(array_filter($notifs, fn($n) => $n['urgent']));
$books = $pdo->query('SELECT b.id_buku, b.judul, k.kategori, p.nama_penerbit
    FROM buku b
    LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
    LEFT JOIN penerbit p ON p.id_penerbit = b.id_penerbit
    ORDER BY b.id_buku DESC
    LIMIT 5')->fetchAll();
$palette = ['#5b8fc9', '#7a1620', '#2d4a6e', '#3b9a8f', '#6c5ce7', '#b5651d'];
$page = 'notifikasi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
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
                    <button class="notification-content" type="button" aria-label="Tandai sudah dibaca">
                        <strong><?= htmlspecialchars($notification['title']) ?></strong>
                        <span><?= htmlspecialchars($notification['text']) ?></span>
                    </button>
                    <button class="notification-dismiss" type="button" aria-label="Tutup notifikasi">&times;</button>
                </article>
            <?php endforeach; ?>
            <p class="notification-empty" <?= $notifs ? 'hidden' : '' ?>>Belum ada notifikasi.</p>
            <p class="notification-filter-empty" hidden>Tidak ada notifikasi pada filter ini.</p>
        </div>

        <section class="whats-new">
            <h2>What's New</h2>
            <p class="whats-new-subtitle">Koleksi terbaru dari perpustakaan kami.</p>

            <div class="new-books">
                <?php foreach ($books as $book):
                    $coverUrl = book_cover_url((string) $book['id_buku']);
                    $bookId = (string) $book['id_buku'];
                ?>
                    <article class="new-book" data-book-id="<?= htmlspecialchars($bookId) ?>">
                        <a class="new-book-cover" href="detail_buku.php?id=<?= rawurlencode($bookId) ?>"
                           style="background: <?= $palette[crc32($book['judul']) % count($palette)] ?>">
                            <?php if ($coverUrl !== null): ?>
                                <img src="<?= htmlspecialchars($coverUrl) ?>" alt="Sampul buku: <?= htmlspecialchars($book['judul']) ?>">
                            <?php else: ?>
                                <span><?= htmlspecialchars($book['judul']) ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="new-book-info">
                            <strong><?= htmlspecialchars($book['judul']) ?></strong>
                            <span><?= htmlspecialchars($book['nama_penerbit'] ?? 'Penerbit tidak diketahui') ?></span>
                            <p><?= htmlspecialchars($book['kategori'] ?? 'Umum') ?></p>
                            <small>Koleksi LITERAKSI</small>
                        </div>
                        <div class="new-book-actions">
                            <a class="new-book-open" href="detail_buku.php?id=<?= rawurlencode($bookId) ?>"
                               aria-label="Lihat <?= htmlspecialchars($book['judul']) ?>" title="Lihat detail">+</a>
                            <button class="new-book-favorite" type="button" aria-label="Tambah ke favorit"
                                    aria-pressed="false" title="Tambah ke favorit">&#9734;</button>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (!$books): ?>
                    <p class="new-books-empty">Belum ada buku baru di katalog.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <script>
        (() => {
            const page = document.querySelector('.notifications-page');
            const userId = page.dataset.user;
            const notificationKey = `literaksi-notifications-${userId}`;
            const favoriteKey = `literaksi-favorites-${userId}`;
            const notifications = [...document.querySelectorAll('.notification-item')];
            const filters = [...document.querySelectorAll('.notification-filters button')];
            const empty = document.querySelector('.notification-empty');
            const filteredEmpty = document.querySelector('.notification-filter-empty');
            let notificationState = {};
            let favorites = [];

            try {
                notificationState = JSON.parse(localStorage.getItem(notificationKey) || '{}');
                favorites = JSON.parse(localStorage.getItem(favoriteKey) || '[]');
                if (!notificationState || Array.isArray(notificationState) || typeof notificationState !== 'object') {
                    notificationState = {};
                }
                if (!Array.isArray(favorites)) {
                    favorites = [];
                }
            } catch (error) {
                notificationState = {};
                favorites = [];
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

            document.querySelectorAll('.new-book-favorite').forEach((button) => {
                const book = button.closest('.new-book');
                const bookId = book.dataset.bookId;

                function renderFavorite() {
                    const isFavorite = favorites.includes(bookId);
                    button.textContent = isFavorite ? '\u2605' : '\u2606';
                    button.setAttribute('aria-pressed', String(isFavorite));
                    button.setAttribute('aria-label', isFavorite ? 'Hapus dari favorit' : 'Tambah ke favorit');
                    button.title = isFavorite ? 'Hapus dari favorit' : 'Tambah ke favorit';
                }

                button.addEventListener('click', () => {
                    favorites = favorites.includes(bookId)
                        ? favorites.filter((favorite) => favorite !== bookId)
                        : [...favorites, bookId];
                    localStorage.setItem(favoriteKey, JSON.stringify(favorites));
                    renderFavorite();
                });
                renderFavorite();
            });

            renderNotifications();
        })();
    </script>

</body>
</html>
