<?php
// Mengambil notifikasi petugas per halaman; perubahan status dan penghapusan ditangani adminlogic.php.
$adminPageTab = 'admin_notifications';
require __DIR__ . '/adminlogic.php';

$notificationFlash = $_SESSION['admin_notification_flash'] ?? null;
unset($_SESSION['admin_notification_flash']);
if (is_array($notificationFlash) && isset($notificationFlash[0], $notificationFlash[1])) {
    $flash = (string) $notificationFlash[0];
    $flashType = $notificationFlash[1] === 'ok' ? 'ok' : 'bad';
}

$adminId = (string) $user['id_petugas'];
$unreadQuery = $pdo->prepare('SELECT COUNT(*) FROM notifikasi_admin WHERE id_petugas = ? AND read_at IS NULL');
$unreadQuery->execute([$adminId]);
$unreadCount = (int) $unreadQuery->fetchColumn();

$notificationCountQuery = $pdo->prepare('SELECT COUNT(*) FROM notifikasi_admin WHERE id_petugas = ?');
$notificationCountQuery->execute([$adminId]);
$notificationCount = (int) $notificationCountQuery->fetchColumn();
$readCount = max(0, $notificationCount - $unreadCount);
$perPage = 30;
$requestedPage = $_GET['p'] ?? '1';
$currentPage = is_string($requestedPage) && ctype_digit($requestedPage)
    ? max(1, (int) $requestedPage) : 1;
$pageCount = max(1, (int) ceil($notificationCount / $perPage));
$currentPage = min($currentPage, $pageCount);
$offset = ($currentPage - 1) * $perPage;

$notificationsQuery = $pdo->prepare('SELECT n.id, n.id_peminjaman, n.created_at, n.read_at, p.status AS loan_status,
        p.NIS,
        COALESCE(u.nama, \'Pengguna dihapus\') AS member_name,
        COALESCE(b.judul, \'Buku tidak ditemukan\') AS book_title
    FROM notifikasi_admin n
    JOIN peminjaman p ON p.id_peminjaman = n.id_peminjaman
    JOIN detail_peminjaman d ON d.id_peminjaman = p.id_peminjaman
    LEFT JOIN `user` u ON u.NIS = p.NIS
    LEFT JOIN buku b ON b.id_buku = d.id_buku
    WHERE n.id_petugas = ?
    ORDER BY n.id DESC
    LIMIT ? OFFSET ?');
$notificationsQuery->bindValue(1, $adminId, PDO::PARAM_STR);
$notificationsQuery->bindValue(2, $perPage, PDO::PARAM_INT);
$notificationsQuery->bindValue(3, $offset, PDO::PARAM_INT);
$notificationsQuery->execute();
$notifications = $notificationsQuery->fetchAll(PDO::FETCH_ASSOC);
?>
<?php admin_page_header('admin_notifications', 'Notifikasi Admin'); ?>

<section class="admin-notification-page" aria-labelledby="admin-notification-heading">
    <div class="admin-panel admin-notification-panel">
        <div class="table-head">
            <div>
                <h2 id="admin-notification-heading">Peminjaman terbaru</h2>
                <p><?= $unreadCount ?> belum dibaca dari <?= $notificationCount ?> notifikasi</p>
            </div>
            <div class="admin-notification-toolbar">
                <form method="post" action="assets/logic/notifikasiadmin.php">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="mark_all_admin_notifications_read">
                    <input type="hidden" name="notification_page" value="<?= $currentPage ?>">
                    <button class="admin-action" type="submit"<?= $unreadCount === 0 ? ' disabled' : '' ?>>
                        Tandai semua dibaca
                    </button>
                </form>
                <?php if ($readCount > 0): ?>
                    <form method="post" action="assets/logic/notifikasiadmin.php"
                        onsubmit="return confirm('Hapus semua notifikasi yang sudah dibaca? Tindakan ini permanen.');">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                        <input type="hidden" name="action" value="delete_read_admin_notifications">
                        <input type="hidden" name="notification_page" value="<?= $currentPage ?>">
                        <button class="admin-action danger" type="submit">Hapus yang dibaca</button>
                    </form>
                <?php endif; ?>
                <a class="btn-secondary" href="admin.php?page=peminjaman">Lihat peminjaman</a>
            </div>
        </div>
        <div class="admin-notification-list">
            <?php if (!$notifications): ?>
                <p class="admin-empty">Belum ada notifikasi peminjaman.</p>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <?php
                    $isUnread = $notification['read_at'] === null;
                    $createdAt = new DateTimeImmutable((string) $notification['created_at']);
                    ?>
                    <article class="admin-notification-item<?= $isUnread ? ' is-unread' : ' is-read' ?>">
                        <div class="admin-notification-copy">
                            <div class="admin-notification-title">
                                <strong>Peminjaman #<?= htmlspecialchars($notification['id_peminjaman']) ?></strong>
                                <?php if ($isUnread): ?><span class="admin-notification-unread">Belum dibaca</span><?php endif; ?>
                            </div>
                            <p><strong><?= htmlspecialchars($notification['member_name']) ?></strong>
                                (NIS <?= htmlspecialchars($notification['NIS']) ?>)
                                <?= $notification['loan_status'] === 'menunggu_pengambilan' ? 'mengajukan peminjaman' : 'meminjam' ?>
                                <strong><?= htmlspecialchars($notification['book_title']) ?></strong>.</p>
                            <time datetime="<?= htmlspecialchars($createdAt->format(DateTimeInterface::ATOM)) ?>">
                                <?= htmlspecialchars($createdAt->format('d/m/Y H:i')) ?>
                            </time>
                        </div>
                        <?php if ($isUnread): ?>
                            <form class="admin-notification-read-form" method="post" action="assets/logic/notifikasiadmin.php">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                <input type="hidden" name="action" value="mark_admin_notification_read">
                                <input type="hidden" name="notification_id" value="<?= htmlspecialchars((string) $notification['id']) ?>">
                                <input type="hidden" name="notification_page" value="<?= $currentPage ?>">
                                <button class="admin-notification-card-hit-area" type="submit"
                                    aria-label="Tandai peminjaman #<?= htmlspecialchars($notification['id_peminjaman']) ?> sudah dibaca"></button>
                            </form>
                        <?php else: ?>
                            <form class="admin-notification-delete-form" method="post" action="assets/logic/notifikasiadmin.php"
                                onsubmit="return confirm('Hapus notifikasi yang sudah dibaca ini?');">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                <input type="hidden" name="action" value="delete_admin_notification">
                                <input type="hidden" name="notification_id" value="<?= htmlspecialchars((string) $notification['id']) ?>">
                                <input type="hidden" name="notification_page" value="<?= $currentPage ?>">
                                <button class="admin-notification-delete-icon" type="submit"
                                    aria-label="Hapus notifikasi peminjaman #<?= htmlspecialchars($notification['id_peminjaman']) ?>">
                                    <?= icon('close', 16) ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if ($pageCount > 1): ?>
            <nav class="admin-notification-pagination" aria-label="Halaman notifikasi">
                <?php if ($currentPage > 1): ?>
                    <a class="btn-secondary" href="assets/logic/notifikasiadmin.php?p=<?= $currentPage - 1 ?>">Sebelumnya</a>
                <?php endif; ?>
                <span>Halaman <?= $currentPage ?> dari <?= $pageCount ?></span>
                <?php if ($currentPage < $pageCount): ?>
                    <a class="btn-secondary" href="assets/logic/notifikasiadmin.php?p=<?= $currentPage + 1 ?>">Berikutnya</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </div>
</section>

<?php admin_page_footer(); ?>
