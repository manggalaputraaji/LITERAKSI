<?php
require_once __DIR__ . '/session.php';
start_app_session();

if (!isset($_SESSION['literaksi_admin']) || !is_array($_SESSION['literaksi_admin']) || !isset($_SESSION['literaksi_admin']['id_petugas'])) {
    unset($_SESSION['literaksi_admin']);
    header('Location: loginadmin.php');
    exit;
}

if (!isset($adminPageTab) && !isset($_GET['tab'])) {
    header('Location: admin_dashboard.php');
    exit;
}

require 'config.php';
require 'layout.php';

$user = $_SESSION['literaksi_admin'];
$adminPageTab = $adminPageTab ?? null;
$pageMode = $adminPageTab ?? 'single';
$sectionOptions = [
    'manajemen' => ['books', 'publishers', 'categories'],
    'manajemen_user' => ['users', 'staff'],
];

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

$tab = $adminPageTab ?? ($_GET['tab'] ?? 'dashboard');
$activeSection = null;
if (isset($sectionOptions[$pageMode])) {
    $requestedSection = $_SERVER['REQUEST_METHOD'] === 'POST'
        ? ($_POST['tab'] ?? '')
        : ($_GET['section'] ?? $sectionOptions[$pageMode][0]);
    $activeSection = in_array($requestedSection, $sectionOptions[$pageMode], true)
        ? $requestedSection
        : $sectionOptions[$pageMode][0];
    $tab = $activeSection;
} elseif ($adminPageTab === null && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tab'])) {
    $tab = $_POST['tab'];
}
$tabs = ['dashboard', 'loans', 'books', 'publishers', 'categories', 'users', 'staff'];
if ($adminPageTab !== null) {
    $tabs[] = $adminPageTab;
}
if (!in_array($tab, $tabs, true)) {
    $tab = 'dashboard';
}
$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'semua';
if (!in_array($statusFilter, ['semua', 'dipinjam', 'terlambat', 'menunggu', 'kembali'], true)) {
    $statusFilter = 'semua';
}
$flash = '';
$flashType = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf'] ?? '';
    if (!is_string($postedToken) || !hash_equals($_SESSION['admin_csrf'], $postedToken)) {
        http_response_code(400);
        exit('Permintaan tidak valid. Muat ulang halaman dan coba lagi.');
    }

    $action = $_POST['action'] ?? '';
    if (!is_string($action)) {
        $action = '';
    }
    $pendingBookCover = null;
    try {
        if ($action === 'update_user') {
            $nis = trim($_POST['nis'] ?? '');
            $nama = trim($_POST['nama'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $kelas = trim($_POST['kelas'] ?? '');
            if (
                $nis === '' || $nama === '' || !preg_match('/^[A-Za-z0-9_.]{3,50}$/', $username)
                || !filter_var($email, FILTER_VALIDATE_EMAIL)
            ) {
                throw new RuntimeException('Data pengguna tidak valid.');
            }

            $duplicate = $pdo->prepare('SELECT COUNT(*) FROM siswa WHERE (username = ? OR email = ?) AND NIS <> ?');
            $duplicate->execute([$username, $email, $nis]);
            if ((int) $duplicate->fetchColumn() > 0) {
                throw new RuntimeException('Username atau email sudah digunakan akun lain.');
            }
            $pdo->prepare('UPDATE siswa SET nama = ?, username = ?, email = ?, kelas = ? WHERE NIS = ?')
                ->execute([$nama, $username, $email, $kelas !== '' ? $kelas : null, $nis]);
            $flash = 'Data pengguna berhasil diperbarui.';
            $tab = 'users';
        } elseif ($action === 'create_user') {
            $nis = trim($_POST['nis'] ?? '');
            $nama = trim($_POST['nama'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $kelas = trim($_POST['kelas'] ?? '');
            $password = $_POST['password'] ?? '';
            if (
                !ctype_digit($nis) || $nama === '' || !preg_match('/^[A-Za-z0-9_.]{3,50}$/', $username)
                || !filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($password) || strlen($password) < 6
            ) {
                throw new RuntimeException('Lengkapi data dengan benar. Password minimal 6 karakter.');
            }
            $duplicate = $pdo->prepare('SELECT COUNT(*) FROM siswa WHERE NIS = ? OR username = ? OR email = ?');
            $duplicate->execute([$nis, $username, $email]);
            if ((int) $duplicate->fetchColumn() > 0) {
                throw new RuntimeException('NIS, username, atau email sudah digunakan.');
            }
            $pdo->prepare('INSERT INTO siswa (NIS, username, nama, email, password, kelas) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$nis, $username, $nama, $email, password_hash($password, PASSWORD_DEFAULT), $kelas !== '' ? $kelas : null]);
            $flash = 'Pengguna baru berhasil ditambahkan.';
            $tab = 'users';
        } elseif ($action === 'delete_user') {
            $nis = trim($_POST['nis'] ?? '');
            if ($nis === '') {
                throw new RuntimeException('Akun ini tidak dapat dihapus.');
            }

            $pdo->beginTransaction();
            $member = $pdo->prepare('SELECT NIS FROM siswa WHERE NIS = ? FOR UPDATE');
            $member->execute([$nis]);
            if (!$member->fetchColumn()) {
                throw new RuntimeException('Akun tidak ditemukan.');
            }

            $pdo->prepare('DELETE d FROM detail_peminjaman d
                JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
                WHERE p.NIS = ?')->execute([$nis]);
            $pdo->prepare('DELETE FROM peminjaman WHERE NIS = ?')->execute([$nis]);

            $postsTable = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables
                WHERE table_schema = DATABASE() AND table_name = ?');
            $postsTable->execute(['postingan']);
            if ((int) $postsTable->fetchColumn() > 0) {
                $pdo->prepare('DELETE FROM postingan WHERE NIS = ?')->execute([$nis]);
            }

            $deleteMember = $pdo->prepare('DELETE FROM siswa WHERE NIS = ?');
            $deleteMember->execute([$nis]);
            if ($deleteMember->rowCount() === 0) {
                throw new RuntimeException('Akun tidak ditemukan.');
            }
            $pdo->commit();
            $flash = 'Pengguna berhasil dihapus.';
            $tab = 'users';
        } elseif ($action === 'create_book' || $action === 'update_book') {
            $id = trim($_POST['id_buku'] ?? '');
            $judul = trim($_POST['judul'] ?? '');
            $kategori = trim($_POST['id_kategori'] ?? '');
            $penerbit = trim($_POST['id_penerbit'] ?? '');
            if ($judul === '' || strlen($judul) > 200) {
                throw new RuntimeException('Judul buku wajib diisi dan maksimal 200 karakter.');
            }
            if ($kategori !== '') {
                $check = $pdo->prepare('SELECT COUNT(*) FROM kategori WHERE id_kategori = ?');
                $check->execute([$kategori]);
                if ((int) $check->fetchColumn() === 0) {
                    throw new RuntimeException('Kategori yang dipilih tidak ditemukan.');
                }
            }
            if ($penerbit !== '') {
                $check = $pdo->prepare('SELECT COUNT(*) FROM penerbit WHERE id_penerbit = ?');
                $check->execute([$penerbit]);
                if ((int) $check->fetchColumn() === 0) {
                    throw new RuntimeException('Penerbit yang dipilih tidak ditemukan.');
                }
            }
            if ($action === 'create_book') {
                $id = admin_next_id($pdo, 'buku', 'id_buku', 'BK');
                if (isset($_FILES['cover_image']) && is_array($_FILES['cover_image'])) {
                    $pendingBookCover = admin_store_book_cover($_FILES['cover_image'], $id);
                }
                $pdo->prepare('INSERT INTO buku (id_buku, judul, id_kategori, id_penerbit) VALUES (?, ?, ?, ?)')
                    ->execute([$id, $judul, $kategori !== '' ? $kategori : null, $penerbit !== '' ? $penerbit : null]);
                $pendingBookCover = null;
                $flash = 'Buku berhasil ditambahkan.';
            } else {
                if (isset($_FILES['cover_image']) && is_array($_FILES['cover_image'])) {
                    admin_store_book_cover($_FILES['cover_image'], $id);
                }
                $pdo->prepare('UPDATE buku SET judul = ?, id_kategori = ?, id_penerbit = ? WHERE id_buku = ?')
                    ->execute([$judul, $kategori !== '' ? $kategori : null, $penerbit !== '' ? $penerbit : null, $id]);
                $flash = 'Data buku berhasil diperbarui.';
            }
            $tab = 'books';
        } elseif ($action === 'delete_book') {
            $id = trim($_POST['id_buku'] ?? '');
            $check = $pdo->prepare('SELECT COUNT(*) FROM detail_peminjaman WHERE id_buku = ?');
            $check->execute([$id]);
            if ((int) $check->fetchColumn() > 0) {
                throw new RuntimeException('Buku memiliki riwayat peminjaman dan tidak dapat dihapus.');
            }
            $pdo->prepare('DELETE FROM buku WHERE id_buku = ?')->execute([$id]);
            $flash = 'Buku berhasil dihapus.';
            $tab = 'books';
        } elseif (in_array($action, ['create_publisher', 'update_publisher', 'delete_publisher'], true)) {
            $id = trim($_POST['id_penerbit'] ?? '');
            $name = trim($_POST['nama_penerbit'] ?? '');
            if ($action === 'create_publisher' || $action === 'update_publisher') {
                if ($name === '' || strlen($name) > 100) {
                    throw new RuntimeException('Nama penerbit wajib diisi dan maksimal 100 karakter.');
                }
                if ($action === 'create_publisher') {
                    $id = admin_next_id($pdo, 'penerbit', 'id_penerbit', 'PNR');
                    $pdo->prepare('INSERT INTO penerbit (id_penerbit, nama_penerbit) VALUES (?, ?)')->execute([$id, $name]);
                    $flash = 'Penerbit berhasil ditambahkan.';
                } else {
                    $pdo->prepare('UPDATE penerbit SET nama_penerbit = ? WHERE id_penerbit = ?')->execute([$name, $id]);
                    $flash = 'Data penerbit berhasil diperbarui.';
                }
            } else {
                $check = $pdo->prepare('SELECT COUNT(*) FROM buku WHERE id_penerbit = ?');
                $check->execute([$id]);
                if ((int) $check->fetchColumn() > 0) {
                    throw new RuntimeException('Penerbit masih digunakan buku. Ubah penerbit buku terlebih dahulu.');
                }
                $pdo->prepare('DELETE FROM penerbit WHERE id_penerbit = ?')->execute([$id]);
                $flash = 'Penerbit berhasil dihapus.';
            }
            $tab = 'publishers';
        } elseif (in_array($action, ['create_category', 'update_category', 'delete_category'], true)) {
            $id = trim($_POST['id_kategori'] ?? '');
            $name = trim($_POST['kategori'] ?? '');
            if ($action === 'create_category' || $action === 'update_category') {
                if ($name === '' || strlen($name) > 100) {
                    throw new RuntimeException('Nama kategori wajib diisi dan maksimal 100 karakter.');
                }
                if ($action === 'create_category') {
                    $id = admin_next_id($pdo, 'kategori', 'id_kategori', 'KAT');
                    $pdo->prepare('INSERT INTO kategori (id_kategori, kategori) VALUES (?, ?)')->execute([$id, $name]);
                    $flash = 'Kategori berhasil ditambahkan.';
                } else {
                    $pdo->prepare('UPDATE kategori SET kategori = ? WHERE id_kategori = ?')->execute([$name, $id]);
                    $flash = 'Data kategori berhasil diperbarui.';
                }
            } else {
                $check = $pdo->prepare('SELECT COUNT(*) FROM buku WHERE id_kategori = ?');
                $check->execute([$id]);
                if ((int) $check->fetchColumn() > 0) {
                    throw new RuntimeException('Kategori masih digunakan buku. Ubah kategori buku terlebih dahulu.');
                }
                $pdo->prepare('DELETE FROM kategori WHERE id_kategori = ?')->execute([$id]);
                $flash = 'Kategori berhasil dihapus.';
            }
            $tab = 'categories';
        } elseif (in_array($action, ['create_staff', 'update_staff', 'delete_staff'], true)) {
            $id = trim($_POST['id_petugas'] ?? '');
            $nama = trim($_POST['nama'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            if ($action === 'create_staff' || $action === 'update_staff') {
                if ($nama === '' || strlen($nama) > 100 || ($username !== '' && !preg_match('/^[A-Za-z0-9_.]{3,50}$/', $username))) {
                    throw new RuntimeException('Nama atau username petugas tidak valid.');
                }
                if ($action === 'create_staff' && (!is_string($password) || strlen($password) < 6 || $username === '')) {
                    throw new RuntimeException('Username wajib diisi dan password minimal 6 karakter.');
                }
                if ($password !== '' && (!is_string($password) || strlen($password) < 6)) {
                    throw new RuntimeException('Password minimal 6 karakter.');
                }
                $duplicate = $pdo->prepare('SELECT COUNT(*) FROM petugas WHERE username = ? AND id_petugas <> ?');
                $duplicate->execute([$username, $id]);
                if ($username !== '' && (int) $duplicate->fetchColumn() > 0) {
                    throw new RuntimeException('Username petugas sudah digunakan.');
                }
                if ($action === 'create_staff') {
                    $id = admin_next_id($pdo, 'petugas', 'id_petugas', 'PTG');
                    $pdo->prepare('INSERT INTO petugas (id_petugas, nama, username, password) VALUES (?, ?, ?, ?)')
                        ->execute([$id, $nama, $username, password_hash($password, PASSWORD_DEFAULT)]);
                    $flash = 'Petugas berhasil ditambahkan.';
                } elseif ($password !== '') {
                    $pdo->prepare('UPDATE petugas SET nama = ?, username = ?, password = ? WHERE id_petugas = ?')
                        ->execute([$nama, $username !== '' ? $username : null, password_hash($password, PASSWORD_DEFAULT), $id]);
                    $flash = 'Data petugas dan password berhasil diperbarui.';
                } else {
                    $pdo->prepare('UPDATE petugas SET nama = ?, username = ? WHERE id_petugas = ?')
                        ->execute([$nama, $username !== '' ? $username : null, $id]);
                    $flash = 'Data petugas berhasil diperbarui.';
                }
                if ($id === (string) $user['id_petugas']) {
                    $_SESSION['literaksi_admin']['nama'] = $nama;
                    $_SESSION['literaksi_admin']['username'] = $username;
                }
            } else {
                if ($id === (string) $user['id_petugas']) {
                    throw new RuntimeException('Akun petugas yang sedang digunakan tidak dapat dihapus.');
                }
                $check = $pdo->prepare('SELECT COUNT(*) FROM peminjaman WHERE id_petugas = ?');
                $check->execute([$id]);
                if ((int) $check->fetchColumn() > 0) {
                    throw new RuntimeException('Petugas memiliki riwayat peminjaman dan tidak dapat dihapus.');
                }
                $pdo->prepare('DELETE FROM petugas WHERE id_petugas = ?')->execute([$id]);
                $flash = 'Petugas berhasil dihapus.';
            }
            $tab = 'staff';
        } elseif ($action === 'confirm_return') {
            $detailId = trim($_POST['id_detail'] ?? '');
            if ($detailId === '') {
                throw new RuntimeException('Detail peminjaman tidak valid.');
            }
            $pdo->beginTransaction();
            $pendingReturn = $pdo->prepare('SELECT id_peminjaman FROM detail_peminjaman
                WHERE id_detail = ? AND tgl_kembali IS NULL AND tgl_pengajuan_kembali IS NOT NULL
                FOR UPDATE');
            $pendingReturn->execute([$detailId]);
            $loanId = $pendingReturn->fetchColumn();
            if ($loanId === false) {
                throw new RuntimeException('Tidak ada pengajuan pengembalian yang menunggu konfirmasi.');
            }
            $returnBook = $pdo->prepare('UPDATE detail_peminjaman
                SET tgl_kembali = CURDATE(), tgl_pengajuan_kembali = NULL
                WHERE id_detail = ? AND tgl_kembali IS NULL AND tgl_pengajuan_kembali IS NOT NULL');
            $returnBook->execute([$detailId]);
            if ($returnBook->rowCount() === 0) {
                throw new RuntimeException('Pengajuan pengembalian sudah diproses.');
            }
            $remaining = $pdo->prepare('SELECT COUNT(*) FROM detail_peminjaman WHERE id_peminjaman = ? AND tgl_kembali IS NULL');
            $remaining->execute([$loanId]);
            if ((int) $remaining->fetchColumn() === 0) {
                $pdo->prepare('UPDATE peminjaman SET status = ? WHERE id_peminjaman = ?')->execute(['kembali', $loanId]);
            }
            $pdo->commit();
            $flash = 'Pengembalian buku berhasil dikonfirmasi.';
            $tab = 'loans';
        } elseif ($action === 'reject_return') {
            $detailId = trim($_POST['id_detail'] ?? '');
            if ($detailId === '') {
                throw new RuntimeException('Detail peminjaman tidak valid.');
            }
            $rejectReturn = $pdo->prepare('UPDATE detail_peminjaman
                SET tgl_pengajuan_kembali = NULL
                WHERE id_detail = ? AND tgl_kembali IS NULL AND tgl_pengajuan_kembali IS NOT NULL');
            $rejectReturn->execute([$detailId]);
            if ($rejectReturn->rowCount() === 0) {
                throw new RuntimeException('Tidak ada pengajuan pengembalian yang menunggu konfirmasi.');
            }
            $flash = 'Pengajuan ditolak. Peminjaman tetap tercatat sebagai belum dikembalikan.';
            $tab = 'loans';
        } else {
            throw new RuntimeException('Aksi tidak dikenal.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (is_string($pendingBookCover) && is_file($pendingBookCover) && !unlink($pendingBookCover)) {
            error_log('Unable to remove a book cover after a failed book insert.');
        }
        $flash = $e instanceof RuntimeException ? $e->getMessage() : 'Perubahan gagal disimpan.';
        $flashType = 'bad';
        $actionTabs = [
            'update_user' => 'users', 'create_user' => 'users', 'delete_user' => 'users',
            'create_book' => 'books', 'update_book' => 'books', 'delete_book' => 'books',
            'create_publisher' => 'publishers', 'update_publisher' => 'publishers', 'delete_publisher' => 'publishers',
            'create_category' => 'categories', 'update_category' => 'categories', 'delete_category' => 'categories',
            'create_staff' => 'staff', 'update_staff' => 'staff', 'delete_staff' => 'staff',
            'confirm_return' => 'loans', 'reject_return' => 'loans',
        ];
        $tab = $actionTabs[$action] ?? 'loans';
    }
}

function admin_next_id(PDO $pdo, string $table, string $column, string $prefix): string
{
    $allowed = [
        'buku' => 'id_buku',
        'penerbit' => 'id_penerbit',
        'kategori' => 'id_kategori',
        'petugas' => 'id_petugas',
    ];
    if (!isset($allowed[$table]) || $allowed[$table] !== $column) {
        throw new LogicException('Konfigurasi ID tidak valid.');
    }

    $width = 10 - strlen($prefix);
    $pattern = '^' . preg_quote($prefix, '/') . '[0-9]+$';
    $sql = "SELECT MAX(CAST(SUBSTRING($column, " . (strlen($prefix) + 1) . ") AS UNSIGNED))
            FROM $table WHERE $column REGEXP " . $pdo->quote($pattern);
    $next = (int) $pdo->query($sql)->fetchColumn() + 1;
    if (strlen((string) $next) > $width) {
        throw new RuntimeException('Batas ID untuk data baru sudah tercapai.');
    }
    return $prefix . str_pad((string) $next, $width, '0', STR_PAD_LEFT);
}

function admin_store_book_cover(array $upload, string $bookId): ?string
{
    $error = $upload['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Sampul gagal diunggah. Silakan pilih ulang gambar.');
    }

    $temporaryPath = $upload['tmp_name'] ?? '';
    $size = $upload['size'] ?? 0;
    if (
        !is_string($temporaryPath) || !is_uploaded_file($temporaryPath)
        || !is_int($size) || $size < 1 || $size > 5 * 1024 * 1024
    ) {
        throw new RuntimeException('Ukuran sampul harus maksimal 5 MB.');
    }

    $imageInfo = getimagesize($temporaryPath);
    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/avif' => 'avif',
        'image/bmp' => 'bmp',
        'image/x-ms-bmp' => 'bmp',
        'image/vnd.microsoft.icon' => 'ico',
        'image/x-icon' => 'ico',
    ];
    $imageExtension = $imageInfo !== false ? ($extensions[$imageInfo['mime'] ?? ''] ?? null) : null;
    $detectedExtension = $extensions[$mimeType] ?? null;
    if (
        $imageInfo === false || $imageExtension === null || $imageExtension !== $detectedExtension
        || $imageInfo[0] * $imageInfo[1] > 20000000
    ) {
        throw new RuntimeException('Sampul harus berupa gambar JPG, PNG, GIF, WebP, AVIF, BMP, atau ICO yang valid.');
    }

    $directory = __DIR__ . '/uploads/book-covers';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder penyimpanan sampul tidak dapat dibuat.');
    }

    $destination = $directory . '/' . hash('sha256', $bookId) . '.' . $extensions[$mimeType];
    if (!move_uploaded_file($temporaryPath, $destination)) {
        throw new RuntimeException('Sampul tidak dapat disimpan. Periksa izin folder uploads.');
    }

    foreach (['jpg', 'png', 'gif', 'webp', 'avif', 'bmp', 'ico'] as $extension) {
        $previousCover = $directory . '/' . hash('sha256', $bookId) . '.' . $extension;
        if ($previousCover !== $destination && is_file($previousCover) && !unlink($previousCover)) {
            error_log('Unable to remove a replaced book cover: ' . $previousCover);
        }
    }

    return $destination;
}

function admin_page_header(string $page, string $title, array $subnav = [], ?string $activeSection = null): void
{
    global $user, $flash, $flashType;
    ?>
    <!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= htmlspecialchars($title) ?> - Literaksi</title><link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>"></head><body class="dash-body">
    <?php render_sidebar($page, $subnav, $activeSection); ?><main class="main"><?php render_topbar($user, 0, $page); ?><div class="admin-heading"><div><p class="admin-eyebrow">PANEL ADMIN</p><h1 class="page-title"><?= htmlspecialchars($title) ?></h1></div></div><?php if ($flash !== ''): ?><div class="alert <?= $flashType === 'ok' ? 'ok' : 'bad' ?>"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
    <?php
}
function admin_page_footer(): void
{
    ?>
    </main></body></html>
    <?php
}