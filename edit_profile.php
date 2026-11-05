<?php
session_start();

if (!isset($_SESSION['literaksi_user']) || !is_array($_SESSION['literaksi_user']) || !isset($_SESSION['literaksi_user']['NIS'])) {
    unset($_SESSION['literaksi_user'], $_SESSION['user']);
    header('Location: login.php');
    exit;
}

require 'config.php';
require 'layout.php';

$user = $_SESSION['literaksi_user'];
$nis  = $user['NIS'];

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$error = '';
$form = ['nama' => $user['nama'], 'username' => $user['username'], 'email' => $user['email']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['nama']     = trim($_POST['nama'] ?? '');
    $form['username'] = trim($_POST['username'] ?? '');
    $form['email']    = trim($_POST['email'] ?? '');
    $lama   = $_POST['password_lama'] ?? '';
    $baru   = $_POST['password_baru'] ?? '';
    $konf   = $_POST['konfirmasi'] ?? '';

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = 'Sesi tidak valid, silakan muat ulang halaman.';
    } elseif ($form['nama'] === '' || $form['username'] === '' || $form['email'] === '') {
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

$page  = 'profil';
$badge = count(array_filter(get_notifications($pdo, $nis), fn($n) => $n['urgent']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Profil - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dash-body">

    <?php render_sidebar($page); ?>

    <main class="main">
        <?php render_topbar($user, $badge, $page); ?>

        <h3 class="page-title">Edit Profil</h3>

        <form method="post" action="edit_profile.php" class="card form-card" novalidate>
            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">

            <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>

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
    </main>

</body>
</html>
