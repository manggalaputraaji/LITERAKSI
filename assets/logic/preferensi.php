<?php
// Memvalidasi perubahan profil anggota sebelum menyimpan preferensi dan berkas unggahan.
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/session.php';
start_app_session();

if (!isset($_SESSION['literaksi_user']) || !is_array($_SESSION['literaksi_user']) || !isset($_SESSION['literaksi_user']['NIS'])) {
    unset($_SESSION['literaksi_user'], $_SESSION['user']);
    header('Location: ' . app_base_url() . 'autentikasi.php?action=login');
    exit;
}

require_once APP_ROOT . '/config.php';
require_once __DIR__ . '/layout.php';

$user = $_SESSION['literaksi_user'];
$memberId  = $user['NIS'];
$appearance = member_appearance($pdo);
$appearance['banner'] = is_string($appearance['banner'] ?? null)
    && preg_match('/^#[a-fA-F0-9]{6}$/', $appearance['banner'])
        ? strtolower($appearance['banner']) : '#9581cd';
$error = '';
$form = ['nama' => $user['nama'], 'username' => $user['username'], 'email' => (string) ($user['email'] ?? ''), 'bio' => $appearance['bio']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = $_POST['csrf'] ?? '';

    if (!is_string($postedCsrf) || !hash_equals($_SESSION['csrf'], $postedCsrf)) {
        $error = 'Sesi tidak valid, silakan muat ulang halaman.';
    } else {
        foreach (['nama', 'username', 'email'] as $field) {
            $form[$field] = trim(is_string($_POST[$field] ?? null) ? $_POST[$field] : '');
        }
        $form['bio'] = trim(is_string($_POST['bio'] ?? null) ? $_POST['bio'] : '');
        $banner = $_POST['banner'] ?? '';
        $bannerMode = $_POST['banner_mode'] ?? 'color';
        $tema = $_POST['tema'] ?? 'light';
        $lama = $_POST['password_lama'] ?? '';
        $baru = $_POST['password_baru'] ?? '';
        $konf = $_POST['konfirmasi'] ?? '';
        $removePhoto = ($_POST['hapus_foto'] ?? '') === '1';
        $upload = $_FILES['foto_profil'] ?? null;
        $bannerUpload = $_FILES['banner_foto'] ?? null;
        $newPhoto = null;
        $newBannerPhoto = null;
        $removeCurrentPhoto = false;
        $removeCurrentBannerPhoto = false;
        $directory = APP_ROOT . '/uploads/profile-photos/';
        $bannerDirectory = APP_ROOT . '/uploads/profile-banners/';
        $oldPhoto = $appearance['foto_profil'];
        $oldBannerPhoto = $appearance['banner_foto'];
        $uploadedPath = null;
        $uploadedBannerPath = null;

        if ($form['nama'] === '' || $form['username'] === '' || $form['email'] === '') {
            $error = 'Nama, username, dan email wajib diisi.';
        } elseif (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $form['username'])) {
            $error = 'Username 3-50 karakter, hanya huruf, angka, titik, dan underscore.';
        } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif (mb_strlen($form['bio']) > 500) {
            $error = 'Bio maksimal 500 karakter.';
        } elseif (!is_string($banner) || !preg_match('/^#[a-fA-F0-9]{6}$/', $banner)) {
            $error = 'Pilihan banner tidak valid.';
        } elseif (!is_string($bannerMode) || !in_array($bannerMode, ['color', 'photo'], true)) {
            $error = 'Pilih warna atau foto banner yang valid.';
        } elseif (!is_string($tema) || !in_array($tema, ['auto', 'light', 'dark', 'oled', 'flashbang'], true)) {
            $error = 'Pilihan tema tidak valid.';
        } elseif (!is_string($lama) || !is_string($baru) || !is_string($konf)) {
            $error = 'Data password tidak valid.';
        } elseif ($removePhoto && is_array($upload) && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $error = 'Pilih unggah foto baru atau hapus foto saat ini, bukan keduanya.';
        } else {
            $cek = $pdo->prepare('SELECT username, email FROM `user` WHERE (username = ? OR email = ?) AND NIS <> ?');
            $cek->execute([$form['username'], $form['email'], $memberId]);
            $ada = $cek->fetch();

            $newHash = null;
            if ($ada) {
                $error = strcasecmp($ada['username'], $form['username']) === 0
                    ? 'Username sudah dipakai.' : 'Email sudah terdaftar.';
            } elseif ($baru !== '' || $konf !== '' || $lama !== '') {
                $q = $pdo->prepare('SELECT password FROM `user` WHERE NIS = ?');
                $q->execute([$memberId]);
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

            if ($error === '' && (!is_array($upload) || !isset($upload['error'], $upload['size'], $upload['tmp_name'])
                || !is_int($upload['error']) || !is_int($upload['size']) || !is_string($upload['tmp_name']))) {
                $error = 'Data foto profil tidak valid.';
            } elseif ($error === '' && $upload['error'] !== UPLOAD_ERR_NO_FILE && $upload['error'] !== UPLOAD_ERR_OK) {
                $error = $upload['error'] === UPLOAD_ERR_INI_SIZE || $upload['error'] === UPLOAD_ERR_FORM_SIZE
                    ? 'Ukuran foto maksimal 3 MB.'
                    : 'Foto gagal diunggah. Silakan coba lagi.';
            } elseif ($error === '' && $upload['error'] === UPLOAD_ERR_OK) {
                if ($upload['size'] < 1 || $upload['size'] > 3 * 1024 * 1024 || !is_uploaded_file($upload['tmp_name'])) {
                    $error = 'Foto harus berukuran maksimal 3 MB.';
                } else {
                    $imageInfo = getimagesize($upload['tmp_name']);
                    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
                    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

                    if ($imageInfo === false || !isset($extensions[$mime]) || $imageInfo['mime'] !== $mime
                        || $imageInfo[0] > 6000 || $imageInfo[1] > 6000
                        || $imageInfo[0] * $imageInfo[1] > 20_000_000) {
                        $error = 'Gunakan gambar JPG, PNG, atau WebP yang valid (maksimal 6000 × 6000 px).';
                    } else {
                        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
                            $error = 'Folder penyimpanan foto tidak dapat dibuat.';
                        } else {
                            $newPhoto = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                            $uploadedPath = $directory . $newPhoto;
                            if (!move_uploaded_file($upload['tmp_name'], $uploadedPath)) {
                                $error = 'Foto gagal disimpan. Periksa izin folder uploads.';
                                $uploadedPath = null;
                                $newPhoto = null;
                            }
                        }
                    }
                }
            }

            if ($error === '' && $bannerMode === 'photo') {
                if (!is_array($bannerUpload) || !isset($bannerUpload['error'], $bannerUpload['size'], $bannerUpload['tmp_name'])
                    || !is_int($bannerUpload['error']) || !is_int($bannerUpload['size']) || !is_string($bannerUpload['tmp_name'])) {
                    $error = 'Data foto banner tidak valid.';
                } elseif ($bannerUpload['error'] !== UPLOAD_ERR_NO_FILE && $bannerUpload['error'] !== UPLOAD_ERR_OK) {
                    $error = $bannerUpload['error'] === UPLOAD_ERR_INI_SIZE || $bannerUpload['error'] === UPLOAD_ERR_FORM_SIZE
                        ? 'Ukuran foto banner maksimal 3 MB.'
                        : 'Foto banner gagal diunggah. Silakan coba lagi.';
                } elseif ($bannerUpload['error'] === UPLOAD_ERR_NO_FILE && $oldBannerPhoto === null) {
                    $error = 'Pilih foto untuk banner.';
                } elseif ($bannerUpload['error'] === UPLOAD_ERR_OK) {
                    if ($bannerUpload['size'] < 1 || $bannerUpload['size'] > 3 * 1024 * 1024
                        || !is_uploaded_file($bannerUpload['tmp_name'])) {
                        $error = 'Foto banner harus berukuran maksimal 3 MB.';
                    } else {
                        $imageInfo = getimagesize($bannerUpload['tmp_name']);
                        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($bannerUpload['tmp_name']);
                        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

                        if ($imageInfo === false || !isset($extensions[$mime]) || $imageInfo['mime'] !== $mime
                            || $imageInfo[0] > 6000 || $imageInfo[1] > 6000
                            || $imageInfo[0] * $imageInfo[1] > 20_000_000) {
                            $error = 'Gunakan gambar JPG, PNG, atau WebP yang valid (maksimal 6000 × 6000 px).';
                        } elseif (!is_dir($bannerDirectory)
                            && !mkdir($bannerDirectory, 0750, true) && !is_dir($bannerDirectory)) {
                            $error = 'Folder penyimpanan banner tidak dapat dibuat.';
                        } else {
                            $newBannerPhoto = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                            $uploadedBannerPath = $bannerDirectory . $newBannerPhoto;
                            if (!move_uploaded_file($bannerUpload['tmp_name'], $uploadedBannerPath)) {
                                $error = 'Foto banner gagal disimpan. Periksa izin folder uploads.';
                                $uploadedBannerPath = null;
                                $newBannerPhoto = null;
                            }
                        }
                    }
                }
            }

            if ($error === '') {
                $removeCurrentPhoto = $removePhoto;
                $removeCurrentBannerPhoto = $bannerMode === 'color';
                try {
                    $pdo->beginTransaction();
                    if ($newHash !== null) {
                        $pdo->prepare('UPDATE `user` SET nama = ?, username = ?, email = ?, password = ? WHERE NIS = ?')
                            ->execute([$form['nama'], $form['username'], $form['email'], $newHash, $memberId]);
                    } else {
                        $pdo->prepare('UPDATE `user` SET nama = ?, username = ?, email = ? WHERE NIS = ?')
                            ->execute([$form['nama'], $form['username'], $form['email'], $memberId]);
                    }

                    $photoValue = $newPhoto ?? ($removeCurrentPhoto ? null : $oldPhoto);
                    $bannerPhotoValue = $newBannerPhoto ?? ($removeCurrentBannerPhoto ? null : $oldBannerPhoto);
                    $pdo->prepare('INSERT INTO `preferensi_user` (NIS, tema, banner, foto_profil, banner_foto, bio) VALUES (?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE tema = VALUES(tema), banner = VALUES(banner), foto_profil = VALUES(foto_profil),
                        banner_foto = VALUES(banner_foto), bio = VALUES(bio)')
                        ->execute([$memberId, $tema, $banner, $photoValue, $bannerPhotoValue, $form['bio']]);
                    $pdo->commit();
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    if (is_string($uploadedPath) && is_file($uploadedPath) && !unlink($uploadedPath)) {
                        error_log('Unable to clean up failed profile photo upload: ' . $uploadedPath);
                    }
                    if (is_string($uploadedBannerPath) && is_file($uploadedBannerPath) && !unlink($uploadedBannerPath)) {
                        error_log('Unable to clean up failed profile banner upload: ' . $uploadedBannerPath);
                    }
                    throw $e;
                }

                $_SESSION['literaksi_user']['nama']     = $form['nama'];
                $_SESSION['literaksi_user']['username'] = $form['username'];
                $_SESSION['literaksi_user']['email']    = $form['email'];
                $_SESSION['literaksi_user']['tema'] = $tema;
                $_SESSION['literaksi_user']['banner'] = strtolower($banner);
                $_SESSION['literaksi_user']['banner_foto'] = $bannerPhotoValue;
                $_SESSION['literaksi_user']['bio'] = $form['bio'];
                if ($newPhoto !== null) {
                    $_SESSION['literaksi_user']['foto_profil'] = $newPhoto;
                } elseif ($removeCurrentPhoto) {
                    unset($_SESSION['literaksi_user']['foto_profil']);
                }
                $_SESSION['member_appearance_loaded'] = true;

                if (($newPhoto !== null || $removeCurrentPhoto) && is_string($oldPhoto)
                    && preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $oldPhoto)) {
                    $oldPhotoPath = $directory . $oldPhoto;
                    if (is_file($oldPhotoPath) && !unlink($oldPhotoPath)) {
                        error_log('Unable to remove replaced profile photo: ' . $oldPhotoPath);
                    }
                }
                if (($newBannerPhoto !== null || $removeCurrentBannerPhoto) && is_string($oldBannerPhoto)
                    && preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $oldBannerPhoto)) {
                    $oldBannerPath = $bannerDirectory . $oldBannerPhoto;
                    if (is_file($oldBannerPath) && !unlink($oldBannerPath)) {
                        error_log('Unable to remove replaced profile banner: ' . $oldBannerPath);
                    }
                }
                header('Location: ' . app_base_url() . 'assets/logic/preferensi.php?updated=1');
                exit;
            }

            if ($error !== '') {
                foreach ([$uploadedPath, $uploadedBannerPath] as $orphanedUpload) {
                    if (is_string($orphanedUpload) && is_file($orphanedUpload) && !unlink($orphanedUpload)) {
                        error_log('Unable to clean up rejected appearance upload: ' . $orphanedUpload);
                    }
                }
            }
        }
    }
}

$page  = 'profil';
$badge = count(array_filter(get_notifications($pdo, $memberId), fn($n) => $n['urgent']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <base href="<?= htmlspecialchars(app_base_url()) ?>">
    <link rel="icon" href="assets/img/logo.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preferensi - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(APP_ROOT . '/assets/css/style.css') ?>">
</head>
<body class="dash-body">

    <?php render_sidebar($page); ?>

    <main class="main">
        <?php render_topbar($user, $badge, $page); ?>

        <header class="preferences-page-heading">
            <span class="preferences-eyebrow">PENGATURAN AKUN</span>
            <h1 class="page-title">Preferensi</h1>
        </header>
        <?php if (isset($_GET['updated'])): ?><div class="alert ok">Preferensi berhasil diperbarui.</div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" action="assets/logic/preferensi.php" enctype="multipart/form-data" id="preferences-form">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
            <div class="preferences-layout">
            <section class="card form-card preferences-profile">
                <h2>Data diri</h2>
                <div class="preferences-fields">
                    <label>Nama lengkap
                        <input type="text" name="nama" value="<?= htmlspecialchars($form['nama']) ?>" required>
                    </label>
                    <label>Username
                        <input type="text" name="username" value="<?= htmlspecialchars($form['username']) ?>" required>
                    </label>
                    <label>Email
                        <input type="email" name="email" value="<?= htmlspecialchars($form['email']) ?>" required>
                    </label>
                    <label>NIS
                        <input type="text" value="<?= htmlspecialchars((string) $memberId) ?>" disabled>
                    </label>
                    <label class="preferences-bio-field">Bio
                        <textarea name="bio" rows="3" maxlength="500"><?= htmlspecialchars($form['bio']) ?></textarea>
                    </label>
                </div>

                <h2 class="preferences-password-title">Ganti password</h2>
                <div class="preferences-fields">
                    <label>Password lama
                        <input type="password" name="password_lama" autocomplete="current-password">
                    </label>
                    <label>Password baru
                        <input type="password" name="password_baru" autocomplete="new-password">
                    </label>
                    <label>Konfirmasi password baru
                        <input type="password" name="konfirmasi" autocomplete="new-password">
                    </label>
                </div>
            </section>

            <div class="preferences-options">
                <section class="card settings-card">
                    <div class="settings-heading">
                        <div>
                            <h2>Tampilan</h2>
                        </div>
                    </div>
                    <div class="preferences-theme-control">
                        <label class="preferences-theme-label" for="preferences-theme">Tema</label>
                        <select id="preferences-theme" class="preferences-theme-select" name="tema">
                            <option value="auto" <?= $appearance['tema'] === 'auto' ? 'selected' : '' ?>>Otomatis (ikuti perangkat)</option>
                            <option value="light" <?= $appearance['tema'] === 'light' ? 'selected' : '' ?>>Terang</option>
                            <option value="dark" <?= $appearance['tema'] === 'dark' ? 'selected' : '' ?>>Gelap</option>
                            <option value="oled" <?= $appearance['tema'] === 'oled' ? 'selected' : '' ?>>OLED Black</option>
                            <option value="flashbang" <?= $appearance['tema'] === 'flashbang' ? 'selected' : '' ?>>FLASHBANG !</option>
                        </select>
                    </div>
                </section>

                <section class="card settings-card">
                    <h2>Banner profil</h2>
                    <fieldset class="banner-choice">
                        <legend>Pilih warna atau foto kustom</legend>
                        <label class="banner-option">
                            <input type="radio" name="banner_mode" value="color"
                                <?= $appearance['banner_foto'] === null ? 'checked' : '' ?>>
                            <span>Warna kustom</span>
                            <input type="color" name="banner" value="<?= htmlspecialchars($appearance['banner']) ?>"
                                aria-label="Pilih warna banner profil">
                        </label>
                        <label class="banner-option">
                            <input type="radio" name="banner_mode" value="photo"
                                <?= $appearance['banner_foto'] !== null ? 'checked' : '' ?>>
                            <span>Foto kustom</span>
                            <input type="file" name="banner_foto" accept="image/jpeg,image/png,image/webp">
                        </label>
                        <?php if ($appearance['banner_foto'] !== null): ?>
                            <img class="banner-photo-preview"
                                src="assets/logic/fungsi.php?action=profile_banner&amp;v=<?= rawurlencode($appearance['banner_foto']) ?>"
                                alt="Foto banner saat ini">
                        <?php endif; ?>
                    </fieldset>
                </section>

                <section class="card settings-card">
                    <h2>Foto profil</h2>
                    <div class="photo-settings">
                        <div class="avatar-lg settings-avatar">
                            <?php if (is_string($appearance['foto_profil'])): ?>
                                <img src="assets/logic/fungsi.php?action=profile_photo&v=<?= rawurlencode($appearance['foto_profil']) ?>" alt="Foto profil saat ini">
                            <?php else: ?>
                                <?= htmlspecialchars(mb_strtoupper(mb_substr($user['nama'], 0, 1))) ?>
                            <?php endif; ?>
                        </div>
                        <div class="photo-actions">
                            <div class="photo-upload-form">
                                <label>Unggah foto baru
                                    <input type="file" name="foto_profil" accept="image/jpeg,image/png,image/webp">
                                </label>
                            <?php if (is_string($appearance['foto_profil'])): ?>
                                <label class="photo-remove-option"><input type="checkbox" name="hapus_foto" value="1"> Hapus foto saat ini</label>
                            <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            </div>
            <div class="form-actions preferences-submit">
                <button type="submit">Simpan perubahan</button>
            </div>
        </form>

        <?php render_site_footer(); ?>
    </main>

</body>
</html>
