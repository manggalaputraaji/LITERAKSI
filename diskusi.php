<?php
// Memproses pesan, balasan, suka, dan laporan diskusi untuk anggota maupun petugas.
require_once __DIR__ . '/assets/logic/session.php';
start_app_session();

$isAdmin = is_admin_session();
$isMember = !$isAdmin && isset($_SESSION['literaksi_user']['NIS']);
if (!$isAdmin && !$isMember) {
    header('Location: autentikasi.php?action=login');
    exit;
}

require __DIR__ . '/config.php';
require_once __DIR__ . '/assets/logic/layout.php';

$senderType = $isAdmin ? 'admin' : 'user';
$senderId = (string) ($isAdmin
    ? $_SESSION['literaksi_admin']['id_petugas']
    : $_SESSION['literaksi_user']['NIS']);
$senderUserId = $isMember ? $senderId : null;
$senderAdminId = $isAdmin ? $senderId : null;
$senderName = (string) ($isAdmin
    ? $_SESSION['literaksi_admin']['nama']
    : $_SESSION['literaksi_user']['nama']);
$discussionUrl = $isAdmin ? 'diskusi.php?role=admin' : 'diskusi.php';
$sort = $_GET['sort'] ?? 'newest';
if (!is_string($sort) || !in_array($sort, ['newest', 'top'], true)) {
    $sort = 'newest';
}
$discussionActionUrl = $discussionUrl . (strpos($discussionUrl, '?') === false ? '?' : '&')
    . 'sort=' . rawurlencode($sort);
$profileRoleParam = $isAdmin ? '&amp;role=admin' : '';
$error = '';
$message = '';
$notice = ($_GET['reported'] ?? '') === '1' ? 'Laporan berhasil dikirim ke admin.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    if (!is_string($csrf) || !hash_equals($_SESSION['csrf'], $csrf)) {
        $error = 'Sesi tidak valid. Muat ulang halaman dan coba lagi.';
    } elseif (($_POST['action'] ?? '') === 'send') {
        $message = is_string($_POST['message'] ?? null) ? trim($_POST['message']) : '';
        $parentId = $_POST['parent_id'] ?? '';
        if ($message === '') {
            $error = 'Pesan tidak boleh kosong.';
        } elseif (mb_strlen($message) > 2000) {
            $error = 'Pesan maksimal 2.000 karakter.';
        } elseif ($parentId !== '' && filter_var($parentId, FILTER_VALIDATE_INT) === false) {
            $error = 'Topik balasan tidak valid.';
        } else {
            if ($parentId === '') {
                $query = $pdo->prepare(
                    'INSERT INTO diskusi (jenis_entri, jenis_pengirim, id_pengirim_user, id_pengirim_admin, sender_name, message)
                     VALUES (\'pesan\', ?, ?, ?, ?, ?)'
                );
                $query->execute([$senderType, $senderUserId, $senderAdminId, $senderName, $message]);
            } else {
                $query = $pdo->prepare(
                    'INSERT INTO diskusi (jenis_entri, jenis_pengirim, id_pengirim_user, id_pengirim_admin, sender_name, message, parent_id)
                     SELECT \'pesan\', ?, ?, ?, ?, ?, id FROM diskusi WHERE id = ? AND jenis_entri = \'pesan\' AND parent_id IS NULL'
                );
                $query->execute([$senderType, $senderUserId, $senderAdminId, $senderName, $message, (int) $parentId]);
                if ($query->rowCount() === 0) {
                    $error = 'Topik balasan tidak ditemukan.';
                }
            }
            if ($error === '') {
                $focusId = (int) $pdo->lastInsertId();
                header('Location: ' . $discussionActionUrl . '#message-' . $focusId);
                exit;
            }
        }
    } elseif (($_POST['action'] ?? '') === 'report') {
        $messageId = filter_var($_POST['message_id'] ?? null, FILTER_VALIDATE_INT);
        $reportOptions = [
            'spam' => 'Spam atau konten menyesatkan',
            'harassment' => 'Perundungan atau pelecehan',
            'hate' => 'Ujaran kebencian',
            'inappropriate' => 'Konten tidak pantas',
            'privacy' => 'Membagikan informasi pribadi',
            'violence' => 'Ancaman atau kekerasan',
            'other' => 'Lainnya',
        ];
        $selectedReasons = $_POST['reasons'] ?? [];
        $customReason = is_string($_POST['custom_reason'] ?? null) ? trim($_POST['custom_reason']) : '';
        if (!is_array($selectedReasons) || !$selectedReasons) {
            $error = 'Pilih setidaknya satu alasan laporan.';
        } elseif (array_filter($selectedReasons, static fn($reason): bool => !is_string($reason) || !isset($reportOptions[$reason]))) {
            $error = 'Pilihan alasan laporan tidak valid.';
        } elseif ($messageId === false || $messageId === null || $messageId < 1) {
            $error = 'Pesan tidak valid.';
        } elseif (in_array('other', $selectedReasons, true) && ($customReason === '' || mb_strlen($customReason) > 500)) {
            $error = 'Jelaskan alasan lainnya (maksimal 500 karakter).';
        } else {
            $selectedReasons = array_values(array_unique($selectedReasons));
            $reasonText = implode(', ', array_map(static fn(string $reason): string => $reportOptions[$reason], $selectedReasons));
            if (in_array('other', $selectedReasons, true)) {
                $reasonText .= ': ' . $customReason;
            }
            $report = $pdo->prepare('INSERT IGNORE INTO laporan_diskusi
                (message_id, sender_name, message, jenis_pelapor, id_pelapor_user, id_pelapor_admin, reporter_name, reason)
                SELECT d.id, d.sender_name, d.message, ?, ?, ?, ?, ? FROM diskusi d
                WHERE d.id = ? AND d.jenis_entri = \'pesan\' AND NOT (d.jenis_pengirim = ? AND
                    ((d.id_pengirim_user = ? AND ? = \'user\') OR (d.id_pengirim_admin = ? AND ? = \'admin\')))');
            $report->execute([$senderType, $senderUserId, $senderAdminId, $senderName, $reasonText,
                $messageId, $senderType, $senderId, $senderType, $senderId, $senderType]);
            if ($report->rowCount() > 0) {
                header('Location: ' . $discussionActionUrl . '&reported=1');
                exit;
            }
            $error = 'Pesan sudah dilaporkan, tidak ditemukan, atau merupakan pesanmu sendiri.';
        }
    } elseif (($_POST['action'] ?? '') === 'like') {
        $messageId = filter_var($_POST['message_id'] ?? null, FILTER_VALIDATE_INT);
        if ($messageId === false || $messageId === null || $messageId < 1) {
            $error = 'Pesan tidak valid.';
        } else {
            $messageExists = $pdo->prepare("SELECT 1 FROM diskusi WHERE id = ? AND jenis_entri = 'pesan'");
            $messageExists->execute([$messageId]);
            if (!$messageExists->fetchColumn()) {
                $error = 'Pesan tidak ditemukan.';
            } else {
                $actorLike = "jenis_entri = 'suka' AND parent_id = ? AND ((jenis_pengirim = 'user' AND id_penyuka_user = ?) OR (jenis_pengirim = 'admin' AND id_penyuka_admin = ?))";
                $liked = $pdo->prepare('SELECT 1 FROM diskusi WHERE ' . $actorLike);
                $liked->execute([$messageId, $senderUserId, $senderAdminId]);
                if ($liked->fetchColumn()) {
                    $pdo->prepare('DELETE FROM diskusi WHERE ' . $actorLike)
                        ->execute([$messageId, $senderUserId, $senderAdminId]);
                } else {
                    $pdo->prepare("INSERT IGNORE INTO diskusi (jenis_entri, jenis_pengirim, id_penyuka_user, id_penyuka_admin, sender_name, parent_id)
                        VALUES ('suka', ?, ?, ?, ?, ?)")
                        ->execute([$senderType, $senderUserId, $senderAdminId, $senderName, $messageId]);
                }
                header('Location: ' . $discussionActionUrl . '#message-' . $messageId);
                exit;
            }
        }
    } elseif (($_POST['action'] ?? '') === 'dismiss_report' && $isAdmin) {
        $reportId = filter_var($_POST['report_id'] ?? null, FILTER_VALIDATE_INT);
        if ($reportId === false || $reportId === null || $reportId < 1) {
            $error = 'Laporan tidak valid.';
        } else {
            $dismiss = $pdo->prepare("UPDATE laporan_diskusi SET status = 'ditinjau' WHERE id = ? AND status = 'menunggu'");
            $dismiss->execute([$reportId]);
            if ($dismiss->rowCount() > 0) {
                header('Location: ' . $discussionActionUrl);
                exit;
            }
            $error = 'Laporan sudah ditangani atau tidak ditemukan.';
        }
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $messageId = filter_var($_POST['message_id'] ?? null, FILTER_VALIDATE_INT);
        if ($messageId === false || $messageId === null || $messageId < 1) {
            $error = 'Pesan tidak valid.';
        } else {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE laporan_diskusi SET status = 'dihapus' WHERE message_id = ? AND status = 'menunggu'")
                ->execute([$messageId]);
            $pdo->prepare("DELETE FROM diskusi WHERE jenis_entri = 'suka' AND parent_id = ?")->execute([$messageId]);
            $query = $isAdmin
                ? $pdo->prepare("DELETE FROM diskusi WHERE id = ? AND jenis_entri = 'pesan' AND (
                    (jenis_pengirim = 'admin' AND id_pengirim_admin = ?)
                    OR EXISTS (SELECT 1 FROM laporan_diskusi WHERE message_id = diskusi.id AND status IN ('menunggu', 'dihapus'))
                )")
                : $pdo->prepare("DELETE FROM diskusi WHERE id = ? AND jenis_entri = 'pesan' AND jenis_pengirim = 'user' AND id_pengirim_user = ?");
            $query->execute($isAdmin ? [$messageId, $senderId] : [$messageId, $senderId]);
            if ($query->rowCount() === 0) {
                $pdo->rollBack();
                $error = $isAdmin
                    ? 'Admin hanya dapat menghapus pesan sendiri atau pesan yang dilaporkan.'
                    : 'Pesan tidak ditemukan atau kamu tidak berhak menghapusnya.';
            } else {
                $pdo->commit();
                header('Location: ' . $discussionActionUrl);
                exit;
            }
        }
    } else {
        $error = 'Aksi tidak dikenali.';
    }
}

$rootSelection = $sort === 'top'
    ? "SELECT root.id FROM diskusi root
        LEFT JOIN diskusi likes ON likes.parent_id = root.id AND likes.jenis_entri = 'suka'
        WHERE root.jenis_entri = 'pesan' AND root.parent_id IS NULL
        GROUP BY root.id ORDER BY COUNT(likes.id) DESC, root.id DESC LIMIT 100"
    : "SELECT id FROM diskusi WHERE jenis_entri = 'pesan' AND parent_id IS NULL ORDER BY created_at DESC, id DESC LIMIT 100";
$messages = $pdo->query(
    "SELECT d.id, d.parent_id, d.jenis_pengirim,
            COALESCE(d.id_pengirim_user, d.id_pengirim_admin) AS id_pengirim,
            d.sender_name, d.message, d.created_at,
            parent.sender_name AS parent_sender_name, profile_settings.foto_profil AS sender_photo
     FROM diskusi d LEFT JOIN diskusi parent ON parent.id = d.parent_id AND parent.jenis_entri = 'pesan'
     LEFT JOIN `preferensi_user` profile_settings
        ON d.jenis_pengirim = 'user' AND profile_settings.NIS = d.id_pengirim_user
     WHERE d.jenis_entri = 'pesan' AND (
     d.id IN (
         SELECT recent.id FROM ($rootSelection) AS recent
     ) OR d.parent_id IN (
         SELECT recent.id FROM ($rootSelection) AS recent
     ))
     ORDER BY COALESCE(d.parent_id, d.id), d.id"
)->fetchAll();
$threads = [];
foreach ($messages as $discussionMessage) {
    if ($discussionMessage['parent_id'] === null) {
        $threads[(int) $discussionMessage['id']] = ['message' => $discussionMessage, 'replies' => []];
    }
}
$likesQuery = $pdo->prepare("SELECT parent_id AS message_id, COUNT(*) AS total,
        SUM((jenis_pengirim = 'user' AND id_penyuka_user = ?) OR (jenis_pengirim = 'admin' AND id_penyuka_admin = ?)) AS liked_by_me
    FROM diskusi WHERE jenis_entri = 'suka' GROUP BY parent_id");
$likesQuery->execute([$senderUserId, $senderAdminId]);
$messageLikes = [];
foreach ($likesQuery->fetchAll() as $likeRow) {
    $messageLikes[(int) $likeRow['message_id']] = [
        'total' => (int) $likeRow['total'],
        'liked' => (int) $likeRow['liked_by_me'] > 0,
    ];
}
foreach ($messages as $discussionMessage) {
    if ($discussionMessage['parent_id'] !== null && isset($threads[(int) $discussionMessage['parent_id']])) {
        $threads[(int) $discussionMessage['parent_id']]['replies'][] = $discussionMessage;
    }
}
$threads = array_values($threads);
usort($threads, static function (array $first, array $second) use ($sort, $messageLikes): int {
    if ($sort === 'top') {
        $firstLikes = $messageLikes[(int) $first['message']['id']]['total'] ?? 0;
        $secondLikes = $messageLikes[(int) $second['message']['id']]['total'] ?? 0;
        if ($firstLikes !== $secondLikes) {
            return $secondLikes <=> $firstLikes;
        }
    }

    $dateOrder = strcmp(
        (string) $second['message']['created_at'],
        (string) $first['message']['created_at']
    );
    return $dateOrder !== 0
        ? $dateOrder
        : ((int) $second['message']['id'] <=> (int) $first['message']['id']);
});
$discussionCount = (int) $pdo->query("SELECT COUNT(*) FROM diskusi WHERE jenis_entri = 'pesan'")->fetchColumn();

$page = 'diskusi';
$user = $isAdmin ? $_SESSION['literaksi_admin'] : $_SESSION['literaksi_user'];
$badge = $isMember ? count(array_filter(
    get_notifications($pdo, $senderId),
    static fn(array $notification): bool => $notification['urgent']
)) : 0;
$pendingReports = $isAdmin
    ? $pdo->query("SELECT * FROM laporan_diskusi WHERE status = 'menunggu' ORDER BY reported_at DESC")->fetchAll()
    : [];
$reportedMessageIds = array_fill_keys(array_map('intval', array_column($pendingReports, 'message_id')), true);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="assets/img/logo.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diskusi - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body class="dash-body">
    <?php render_sidebar($page); ?>
    <main class="main">
        <?php render_topbar($user, $badge, $page); ?>
        <h1 class="page-title">Diskusi</h1>
        <p class="discussion-page-description">
            Bagikan kabar perpustakaan, rekomendasi bacaan, dan tanggapan. Jaga diskusi tetap sopan, relevan, dan bermanfaat.
        </p>

        <?php if ($error !== ''): ?>
            <div class="alert bad"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($notice !== ''): ?><div class="alert ok"><?= htmlspecialchars($notice) ?></div><?php endif; ?>

        <form class="card discussion-composer" method="post" action="<?= htmlspecialchars($discussionActionUrl) ?>">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
            <input type="hidden" name="action" value="send">
            <label class="sr-only" for="discussion-message">Pesan</label>
            <textarea id="discussion-message" name="message" rows="3" maxlength="2000"
                placeholder="Bagikan pengumuman, rekomendasi buku, atau pertanyaan..."
                required><?= htmlspecialchars($message) ?></textarea>
            <div class="discussion-composer-footer">
                <button type="submit">Kirim pesan</button>
            </div>
        </form>

        <div class="discussion-toolbar">
            <strong><?= $discussionCount ?> komentar</strong>
            <form method="get" action="diskusi.php" class="discussion-sort">
                <?php if ($isAdmin): ?><input type="hidden" name="role" value="admin"><?php endif; ?>
                <label for="discussion-sort">Urutkan:</label>
                <select id="discussion-sort" name="sort">
                    <option value="top" <?= $sort === 'top' ? 'selected' : '' ?>>Komentar teratas</option>
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Terbaru</option>
                </select>
                <button type="submit">Terapkan</button>
            </form>
        </div>

        <section class="discussion-list" aria-label="Pesan diskusi">
            <?php if (!$threads): ?>
                <div class="notif-empty"><p>Belum ada pesan.</p></div>
            <?php endif; ?>
            <?php foreach ($threads as $thread): ?>
                <?php $discussionMessage = $thread['message']; ?>
                <div class="discussion-thread">
                    <?php
                    $isOwner = $discussionMessage['jenis_pengirim'] === $senderType
                        && (string) $discussionMessage['id_pengirim'] === $senderId;
                    $initial = mb_strtoupper(mb_substr($discussionMessage['sender_name'], 0, 1));
                    $senderPhoto = is_string($discussionMessage['sender_photo'] ?? null)
                        && preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $discussionMessage['sender_photo'])
                            ? $discussionMessage['sender_photo'] : null;
                    $isSelfMember = $isMember
                        && $discussionMessage['jenis_pengirim'] === 'user'
                        && (string) $discussionMessage['id_pengirim'] === $senderId;
                    $canOpenMemberProfile = $discussionMessage['jenis_pengirim'] === 'user'
                        && $discussionMessage['id_pengirim'] !== null && !$isSelfMember;
                    ?>
                    <article class="card discussion-message" id="message-<?= (int) $discussionMessage['id'] ?>">
                        <div class="discussion-message-header">
                            <?php if ($canOpenMemberProfile): ?>
                                <a class="discussion-profile-link" href="user.php?page=profile&amp;nis=<?= rawurlencode($discussionMessage['id_pengirim']) ?><?= $profileRoleParam ?>"
                                    title="Lihat profil <?= htmlspecialchars($discussionMessage['sender_name']) ?>">
                            <?php endif; ?>
                            <span class="avatar-sm" aria-hidden="true">
                                <?php if ($senderPhoto !== null): ?>
                                    <img src="assets/logic/fungsi.php?action=profile_photo&amp;nis=<?= rawurlencode($discussionMessage['id_pengirim']) ?>&amp;v=<?= rawurlencode($senderPhoto) ?><?= $isAdmin ? '&amp;role=admin' : '' ?>" alt="">
                                <?php else: ?><?= htmlspecialchars($initial) ?><?php endif; ?>
                            </span>
                            <?php if ($canOpenMemberProfile): ?></a><?php endif; ?>
                            <div class="discussion-author">
                                <strong><?php if ($canOpenMemberProfile): ?><a class="discussion-author-link" href="user.php?page=profile&amp;nis=<?= rawurlencode($discussionMessage['id_pengirim']) ?><?= $profileRoleParam ?>"><?php endif; ?><?= htmlspecialchars($discussionMessage['sender_name']) ?><?php if ($canOpenMemberProfile): ?></a><?php endif; ?></strong>
                                <small>
                                    <?= $discussionMessage['jenis_pengirim'] === 'admin' ? 'Petugas' : 'Anggota' ?>
                                    &middot; <?= date('d M Y, H:i', strtotime($discussionMessage['created_at'])) ?>
                                </small>
                            </div>
                            <?php if ($isOwner || ($isAdmin && isset($reportedMessageIds[(int) $discussionMessage['id']]))): ?>
                                <form method="post" action="<?= htmlspecialchars($discussionActionUrl) ?>" class="discussion-delete"
                                    onsubmit="return confirm('Hapus pesan ini?');">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="message_id" value="<?= (int) $discussionMessage['id'] ?>">
                                    <button class="discussion-icon-button is-destructive" type="submit" aria-label="Hapus pesan" title="Hapus pesan">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m4 4v6m6-6v6"/></svg>
                                    </button>
                                </form>
                            <?php elseif (!$isAdmin): ?>
                                <button type="button" class="discussion-report discussion-icon-button js-open-report" data-message-id="<?= (int) $discussionMessage['id'] ?>" aria-label="Laporkan pesan" title="Laporkan pesan">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 21V5m0 0c5-4 9 4 14 0v10c-5 4-9-4-14 0"/></svg>
                                </button>
                            <?php endif; ?>
                        </div>
                        <p><?= nl2br(htmlspecialchars($discussionMessage['message'])) ?></p>
                        <?php $like = $messageLikes[(int) $discussionMessage['id']] ?? ['total' => 0, 'liked' => false]; ?>
                        <form method="post" action="<?= htmlspecialchars($discussionActionUrl) ?>#message-<?= (int) $discussionMessage['id'] ?>" class="discussion-like-form">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                            <input type="hidden" name="action" value="like">
                            <input type="hidden" name="message_id" value="<?= (int) $discussionMessage['id'] ?>">
                            <button class="discussion-like<?= $like['liked'] ? ' is-liked' : '' ?>" type="submit"
                                aria-label="<?= $like['liked'] ? 'Batalkan suka' : 'Sukai pesan' ?>" aria-pressed="<?= $like['liked'] ? 'true' : 'false' ?>">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/></svg>
                                <span><?= $like['total'] ?></span>
                            </button>
                        </form>
                        <details class="discussion-reply">
                            <summary>Balas</summary>
                            <form method="post" action="<?= htmlspecialchars($discussionActionUrl) ?>">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                                <input type="hidden" name="action" value="send">
                                <input type="hidden" name="parent_id" value="<?= (int) $discussionMessage['id'] ?>">
                                <label class="sr-only" for="reply-<?= (int) $discussionMessage['id'] ?>">Balasan</label>
                                <textarea id="reply-<?= (int) $discussionMessage['id'] ?>" name="message" rows="2"
                                    maxlength="2000" required></textarea>
                                <button type="submit">Kirim balasan</button>
                            </form>
                        </details>
                    </article>
                    <?php if ($thread['replies']): ?>
                    <details class="discussion-replies" id="replies-<?= (int) $thread['message']['id'] ?>">
                        <summary>
                            <span class="discussion-replies-open">Lihat <?= count($thread['replies']) ?> balasan</span>
                            <span class="discussion-replies-close">Sembunyikan balasan</span>
                        </summary>
                        <div class="discussion-replies-list">
                    <?php foreach ($thread['replies'] as $discussionMessage): ?>
                        <?php
                        $isOwner = $discussionMessage['jenis_pengirim'] === $senderType
                            && (string) $discussionMessage['id_pengirim'] === $senderId;
                        $initial = mb_strtoupper(mb_substr($discussionMessage['sender_name'], 0, 1));
                        $senderPhoto = is_string($discussionMessage['sender_photo'] ?? null)
                            && preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $discussionMessage['sender_photo'])
                                ? $discussionMessage['sender_photo'] : null;
                        $isSelfMember = $isMember
                            && $discussionMessage['jenis_pengirim'] === 'user'
                            && (string) $discussionMessage['id_pengirim'] === $senderId;
                        $canOpenMemberProfile = $discussionMessage['jenis_pengirim'] === 'user'
                            && $discussionMessage['id_pengirim'] !== null && !$isSelfMember;
                        ?>
                        <article class="card discussion-message is-reply" id="message-<?= (int) $discussionMessage['id'] ?>">
                            <div class="discussion-message-header">
                                <?php if ($canOpenMemberProfile): ?>
                                    <a class="discussion-profile-link" href="user.php?page=profile&amp;nis=<?= rawurlencode($discussionMessage['id_pengirim']) ?><?= $profileRoleParam ?>"
                                        title="Lihat profil <?= htmlspecialchars($discussionMessage['sender_name']) ?>">
                                <?php endif; ?>
                                <span class="avatar-sm" aria-hidden="true">
                                    <?php if ($senderPhoto !== null): ?>
                                        <img src="assets/logic/fungsi.php?action=profile_photo&amp;nis=<?= rawurlencode($discussionMessage['id_pengirim']) ?>&amp;v=<?= rawurlencode($senderPhoto) ?><?= $isAdmin ? '&amp;role=admin' : '' ?>" alt="">
                                    <?php else: ?><?= htmlspecialchars($initial) ?><?php endif; ?>
                                </span>
                                <?php if ($canOpenMemberProfile): ?></a><?php endif; ?>
                                <div class="discussion-author">
                                    <strong><?php if ($canOpenMemberProfile): ?><a class="discussion-author-link" href="user.php?page=profile&amp;nis=<?= rawurlencode($discussionMessage['id_pengirim']) ?><?= $profileRoleParam ?>"><?php endif; ?><?= htmlspecialchars($discussionMessage['sender_name']) ?><?php if ($canOpenMemberProfile): ?></a><?php endif; ?></strong>
                                    <small>
                                        <?= $discussionMessage['jenis_pengirim'] === 'admin' ? 'Petugas' : 'Anggota' ?>
                                        &middot; <?= date('d M Y, H:i', strtotime($discussionMessage['created_at'])) ?>
                                    </small>
                                </div>
                                <?php if ($isOwner || ($isAdmin && isset($reportedMessageIds[(int) $discussionMessage['id']]))): ?>
                                    <form method="post" action="<?= htmlspecialchars($discussionActionUrl) ?>" class="discussion-delete"
                                        onsubmit="return confirm('Hapus pesan ini?');">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="message_id" value="<?= (int) $discussionMessage['id'] ?>">
                                        <button class="discussion-icon-button is-destructive" type="submit" aria-label="Hapus pesan" title="Hapus pesan">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m4 4v6m6-6v6"/></svg>
                                        </button>
                                    </form>
                                <?php elseif (!$isAdmin): ?>
                                    <button type="button" class="discussion-report discussion-icon-button js-open-report" data-message-id="<?= (int) $discussionMessage['id'] ?>" aria-label="Laporkan pesan" title="Laporkan pesan">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 21V5m0 0c5-4 9 4 14 0v10c-5 4-9-4-14 0"/></svg>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <small class="discussion-reply-context">
                                Balasan untuk <?= htmlspecialchars($discussionMessage['parent_sender_name'] ?? 'pesan yang dihapus') ?>
                            </small>
                            <p><?= nl2br(htmlspecialchars($discussionMessage['message'])) ?></p>
                            <?php $like = $messageLikes[(int) $discussionMessage['id']] ?? ['total' => 0, 'liked' => false]; ?>
                            <form method="post" action="<?= htmlspecialchars($discussionActionUrl) ?>#message-<?= (int) $discussionMessage['id'] ?>" class="discussion-like-form">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                                <input type="hidden" name="action" value="like">
                                <input type="hidden" name="message_id" value="<?= (int) $discussionMessage['id'] ?>">
                                <button class="discussion-like<?= $like['liked'] ? ' is-liked' : '' ?>" type="submit"
                                    aria-label="<?= $like['liked'] ? 'Batalkan suka' : 'Sukai pesan' ?>" aria-pressed="<?= $like['liked'] ? 'true' : 'false' ?>">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/></svg>
                                    <span><?= $like['total'] ?></span>
                                </button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                        </div>
                    </details>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>
        <dialog class="discussion-dialog" id="report-dialog" aria-labelledby="report-dialog-title">
            <form method="post" action="<?= htmlspecialchars($discussionActionUrl) ?>" class="discussion-dialog-content" id="report-form">
                <button type="button" class="discussion-dialog-close" data-close-dialog aria-label="Tutup">&times;</button>
                <h2 id="report-dialog-title">Laporkan pesan</h2>
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                <input type="hidden" name="action" value="report">
                <input type="hidden" name="message_id" id="report-message-id" value="">
                <fieldset class="report-reasons">
                    <legend>Alasan laporan</legend>
                    <?php foreach ([
                        'spam' => 'Spam atau konten menyesatkan',
                        'harassment' => 'Perundungan atau pelecehan',
                        'hate' => 'Ujaran kebencian',
                        'inappropriate' => 'Konten tidak pantas',
                        'privacy' => 'Membagikan informasi pribadi',
                        'violence' => 'Ancaman atau kekerasan',
                        'other' => 'Lainnya (tidak ada di atas)',
                    ] as $reasonValue => $reasonLabel): ?>
                        <label class="report-reason-option">
                            <input type="checkbox" name="reasons[]" value="<?= htmlspecialchars($reasonValue) ?>"
                                <?= $reasonValue === 'other' ? 'data-custom-reason-toggle' : '' ?>>
                            <span><?= htmlspecialchars($reasonLabel) ?></span>
                        </label>
                    <?php endforeach; ?>
                    <label class="report-custom-reason" id="report-custom-reason-wrap" hidden>
                        <span>Jelaskan alasan lainnya</span>
                        <textarea name="custom_reason" id="report-custom-reason" rows="3" maxlength="500" disabled></textarea>
                    </label>
                </fieldset>
                <div class="discussion-dialog-actions"><button type="button" data-close-dialog>Batal</button><button type="submit">Kirim laporan</button></div>
            </form>
        </dialog>
        <?php if ($isAdmin): ?>
            <button type="button" class="admin-reports-toggle" id="open-admin-reports" aria-expanded="false">Laporan <span><?= count($pendingReports) ?></span></button>
            <aside class="admin-reports-popout" id="admin-reports-popout" aria-label="Pesan yang dilaporkan" hidden>
                <div class="admin-reports-popout-header"><h2>Pesan Terlapor</h2><button type="button" id="close-admin-reports" aria-label="Tutup">&times;</button></div>
                <div class="admin-reports-popout-list">
                    <?php if (!$pendingReports): ?><p class="admin-reports-empty">Belum ada laporan masuk.</p><?php endif; ?>
                    <?php foreach ($pendingReports as $report): ?>
                        <article class="reported-item">
                            <strong><?= htmlspecialchars($report['sender_name']) ?></strong>
                            <p><?= nl2br(htmlspecialchars($report['message'])) ?></p>
                            <p class="reported-reason"><strong>Alasan:</strong> <?= nl2br(htmlspecialchars($report['reason'] ?? '')) ?></p>
                            <small>Dilaporkan oleh <?= htmlspecialchars($report['reporter_name']) ?> · <?= date('d M Y, H:i', strtotime($report['reported_at'])) ?></small>
                            <div class="discussion-dialog-actions">
                                <form method="post" action="<?= htmlspecialchars($discussionActionUrl) ?>" onsubmit="return confirm('Hapus pesan ini dari diskusi?');">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="message_id" value="<?= (int) $report['message_id'] ?>">
                                    <button class="reported-delete-button" type="submit" aria-label="Hapus pesan" title="Hapus pesan">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m4 4v6m6-6v6"/></svg>
                                    </button>
                                </form>
                                <form method="post" action="<?= htmlspecialchars($discussionActionUrl) ?>">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                                    <input type="hidden" name="action" value="dismiss_report">
                                    <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
                                    <button class="reported-dismiss-button" type="submit">Tandai ditinjau</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </aside>
        <?php endif; ?>
        <?php render_site_footer(); ?>
    </main>
    <script>
        if (/^#message-\d+$/.test(window.location.hash)) {
            const targetMessage = document.querySelector(window.location.hash);
            const replyGroup = targetMessage?.closest('.discussion-replies-list')?.closest('.discussion-replies');
            if (replyGroup) {
                replyGroup.open = true;
                requestAnimationFrame(() => targetMessage.scrollIntoView({ block: 'center' }));
            }
        }
        const reportDialog = document.getElementById('report-dialog');
        document.querySelectorAll('.js-open-report').forEach((button) => button.addEventListener('click', () => {
            document.getElementById('report-form').reset();
            document.getElementById('report-message-id').value = button.dataset.messageId;
            customReasonWrap.hidden = true;
            customReasonInput.disabled = true;
            customReasonInput.required = false;
            reportDialog.showModal();
        }));
        const customReasonToggle = document.querySelector('[data-custom-reason-toggle]');
        const customReasonWrap = document.getElementById('report-custom-reason-wrap');
        const customReasonInput = document.getElementById('report-custom-reason');
        customReasonToggle?.addEventListener('change', () => {
            customReasonWrap.hidden = !customReasonToggle.checked;
            customReasonInput.disabled = !customReasonToggle.checked;
            customReasonInput.required = customReasonToggle.checked;
        });
        const adminReportsPanel = document.getElementById('admin-reports-popout');
        const adminReportsToggle = document.getElementById('open-admin-reports');
        adminReportsToggle?.addEventListener('click', () => {
            adminReportsPanel.hidden = !adminReportsPanel.hidden;
            adminReportsToggle.setAttribute('aria-expanded', String(!adminReportsPanel.hidden));
        });
        document.getElementById('close-admin-reports')?.addEventListener('click', () => {
            adminReportsPanel.hidden = true;
            adminReportsToggle.setAttribute('aria-expanded', 'false');
        });
        document.querySelectorAll('[data-close-dialog]').forEach((button) => button.addEventListener('click', () => button.closest('dialog').close()));
    </script>
</body>
</html>
