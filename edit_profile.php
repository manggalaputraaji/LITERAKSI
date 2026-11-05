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
$nis  = $user['NIS'];
$appearance = member_appearance($pdo);

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$error = '';
$form = ['nama' => $user['nama'], 'username' => $user['username'], 'email' => $user['email']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'profile';
    $postedCsrf = $_POST['csrf'] ?? '';

    if (!is_string($postedCsrf) || !hash_equals($_SESSION['csrf'], $postedCsrf)) {
        $error = 'Sesi tidak valid, silakan muat ulang halaman.';
    } elseif ($action === 'photo') {
        $upload = $_FILES['foto_profil'] ?? null;
        if (!is_array($upload) || !isset($upload['error'], $upload['size'], $upload['tmp_name'])
            || !is_int($upload['error']) || !is_int($upload['size']) || !is_string($upload['tmp_name'])) {
            $error = 'Pilih foto profil yang valid.';
        } elseif ($upload['error'] !== UPLOAD_ERR_OK) {
            $error = $upload['error'] === UPLOAD_ERR_INI_SIZE || $upload['error'] === UPLOAD_ERR_FORM_SIZE
                ? 'Ukuran foto maksimal 3 MB.'
                : 'Foto gagal diunggah. Silakan coba lagi.';
        } elseif ($upload['size'] < 1 || $upload['size'] > 3 * 1024 * 1024 || !is_uploaded_file($upload['tmp_name'])) {
            $error = 'Foto harus berukuran maksimal 3 MB.';
        } else {
            $imageInfo = getimagesize($upload['tmp_name']);
            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $fileInfo->file($upload['tmp_name']);
            $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

            if ($imageInfo === false || !isset($extensions[$mime]) || $imageInfo['mime'] !== $mime
                || $imageInfo[0] > 6000 || $imageInfo[1] > 6000
                || $imageInfo[0] * $imageInfo[1] > 20_000_000) {
                $error = 'Gunakan gambar JPG, PNG, atau WebP yang valid (maksimal 6000 × 6000 px).';
            } else {
                $directory = __DIR__ . '/uploads/profile-photos/';
                if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
                    $error = 'Folder penyimpanan foto tidak dapat dibuat.';
                } else {
                    $newPhoto = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                    if (!move_uploaded_file($upload['tmp_name'], $directory . $newPhoto)) {
                        $error = 'Foto gagal disimpan. Periksa izin folder uploads.';
                    } else {
                        $oldPhoto = $appearance['foto_profil'];
                        try {
                            $pdo->prepare('INSERT INTO pengaturan_anggota (NIS, foto_profil) VALUES (?, ?)
                                ON DUPLICATE KEY UPDATE foto_profil = VALUES(foto_profil)')
                                ->execute([$nis, $newPhoto]);
                        } catch (PDOException $e) {
                            if (!unlink($directory . $newPhoto)) {
                                error_log('Unable to clean up failed profile photo upload: ' . $directory . $newPhoto);
                            }
                            throw $e;
                        }
                        if (is_string($oldPhoto) && preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $oldPhoto)) {
                            $oldPhotoPath = $directory . $oldPhoto;
                            if (is_file($oldPhotoPath) && !unlink($oldPhotoPath)) {
                                error_log('Unable to remove replaced profile photo: ' . $oldPhotoPath);
                            }
                        }
                        $_SESSION['literaksi_user']['foto_profil'] = $newPhoto;
                        $_SESSION['member_appearance_loaded'] = true;
                        header('Location: edit_profile.php?photo=updated');
                        exit;
                    }
                }
            }
        }
    } elseif ($action === 'remove_photo') {
        $oldPhoto = $appearance['foto_profil'];
        $pdo->prepare('INSERT INTO pengaturan_anggota (NIS, foto_profil) VALUES (?, NULL)
            ON DUPLICATE KEY UPDATE foto_profil = NULL')->execute([$nis]);
        $appearance['foto_profil'] = null;
        unset($_SESSION['literaksi_user']['foto_profil']);
        $_SESSION['member_appearance_loaded'] = true;
        if (is_string($oldPhoto) && preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $oldPhoto)) {
            $oldPhotoPath = __DIR__ . '/uploads/profile-photos/' . $oldPhoto;
            if (is_file($oldPhotoPath) && !unlink($oldPhotoPath)) {
                error_log('Unable to remove deleted profile photo: ' . $oldPhotoPath);
            }
        }
        header('Location: edit_profile.php?photo=removed');
        exit;
    } else {
        $form['nama']     = trim($_POST['nama'] ?? '');
        $form['username'] = trim($_POST['username'] ?? '');
        $form['email']    = trim($_POST['email'] ?? '');
        $lama   = $_POST['password_lama'] ?? '';
        $baru   = $_POST['password_baru'] ?? '';
        $konf   = $_POST['konfirmasi'] ?? '';

        if ($form['nama'] === '' || $form['username'] === '' || $form['email'] === '') {
            $error = 'Nama, username, dan email wajib diisi.';
        } elseif (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $form['username'])) {
            $error = 'Username 3-50 karakter, hanya huruf, angka, titik, dan underscore.';
        } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } else {
            $cek = $pdo->prepare('SELECT username, email FROM siswa WHERE (username = ? OR email = ?) AND NIS <> ?');
            $cek->execute([$form['username'], $form['email'], $nis]);
            $ada = $cek->fetch();

            $newHash = null;
            if ($ada) {
                $error = strcasecmp($ada['username'], $form['username']) === 0
                    ? 'Username sudah dipakai.' : 'Email sudah terdaftar.';
            } elseif ($baru !== '' || $konf !== '' || $lama !== '') {
                $q = $pdo->prepare('SELECT password FROM siswa WHERE NIS = ?');
                $q->execute([$nis]);
                $hash = $q->fetchColumn();

                if (!$hash || !password_verify($lama, $hash)) {
                    $error = 'Password lama salah.';
                } elseif (strlen($baru) < 6) {
                    $error = 'Password baru minimal 6 karakter.';
                } elseif ($baru !== $konf) {
                    $error = 'Konfirmasi password baru tidak sama.';
                } else {
                    $newHash = password_hash($baru, PASSWORD_DEFAULT);
                }
            }

            if ($error === '') {
                if ($newHash !== null) {
                    $pdo->prepare('UPDATE siswa SET nama = ?, username = ?, email = ?, password = ? WHERE NIS = ?')
                        ->execute([$form['nama'], $form['username'], $form['email'], $newHash, $nis]);
                } else {
                    $pdo->prepare('UPDATE siswa SET nama = ?, username = ?, email = ? WHERE NIS = ?')
                        ->execute([$form['nama'], $form['username'], $form['email'], $nis]);
                }
                $_SESSION['literaksi_user']['nama']     = $form['nama'];
                $_SESSION['literaksi_user']['username'] = $form['username'];
                $_SESSION['literaksi_user']['email']    = $form['email'];
                header('Location: profile.php?updated=1');
                exit;
            }
        }
    }
}

$page  = 'profil';
$badge = count(array_filter(get_notifications($pdo, $nis), fn($n) => $n['urgent']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Profil - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body class="dash-body">

    <?php render_sidebar($page); ?>

    <main class="main">
        <?php render_topbar($user, $badge, $page); ?>

        <h3 class="page-title">Edit Profil</h3>

        <form method="post" action="edit_profile.php" class="card form-card" novalidate>
            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
            <input type="hidden" name="action" value="profile">

            <?php if ($error && ($_POST['action'] ?? 'profile') === 'profile'): ?>
                <div class="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <h4>Data Diri</h4>
            <label>Nama Lengkap
                <input type="text" name="nama" value="<?= htmlspecialchars($form['nama']) ?>" required>
            </label>
            <label>Username
                <input type="text" name="username" value="<?= htmlspecialchars($form['username']) ?>" required>
            </label>
            <label>Email
                <input type="email" name="email" value="<?= htmlspecialchars($form['email']) ?>" required>
            </label>
            <label>NIS
                <input type="text" value="<?= htmlspecialchars((string) $nis) ?>" disabled>
            </label>

            <h4>Ganti Password <small>(kosongkan jika tidak ingin mengubah)</small></h4>
            <label>Password Lama
                <input type="password" name="password_lama" autocomplete="current-password">
            </label>
            <label>Password Baru
                <input type="password" name="password_baru" autocomplete="new-password">
            </label>
            <label>Konfirmasi Password Baru
                <input type="password" name="konfirmasi" autocomplete="new-password">
            </label>

            <div class="form-actions">
                <a href="profile.php" class="btn-secondary">Batal</a>
                <button type="submit">Simpan Perubahan</button>
            </div>
        </form>

        <section class="card settings-card">
            <h4>Foto Profil</h4>
            <p class="settings-description">Unggah foto JPG, PNG, atau WebP. Ukuran maksimal 3 MB.</p>
            <?php if ($error && ($_POST['action'] ?? '') === 'photo'): ?>
                <div class="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['photo'])): ?>
                <div class="alert ok"><?= $_GET['photo'] === 'updated' ? 'Foto profil berhasil diperbarui.' : 'Foto profil berhasil dihapus.' ?></div>
            <?php endif; ?>
            <div class="photo-settings">
                <div class="avatar-lg settings-avatar">
                    <?php if (is_string($appearance['foto_profil'])): ?>
                        <img src="profile_photo.php?v=<?= rawurlencode($appearance['foto_profil']) ?>" alt="Foto profil saat ini">
                    <?php else: ?>
                        <?= htmlspecialchars(mb_strtoupper(mb_substr($user['nama'], 0, 1))) ?>
                    <?php endif; ?>
                </div>
                <div class="photo-actions">
                    <form method="post" action="edit_profile.php" enctype="multipart/form-data" class="photo-upload-form">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                        <input type="hidden" name="action" value="photo">
                        <label>Unggah foto baru
                            <input type="file" name="foto_profil" accept="image/jpeg,image/png,image/webp" required>
                        </label>
                        <button class="btn-primary" type="submit">Simpan Foto</button>
                    </form>
                    <?php if (is_string($appearance['foto_profil'])): ?>
                        <form method="post" action="edit_profile.php">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                            <input type="hidden" name="action" value="remove_photo">
                            <button class="btn-secondary" type="submit">Hapus Foto</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </section>

    </main>

</body>
</html>
