<?php
require_once __DIR__ . '/session.php';
start_app_session();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['literaksi_user'], $_SESSION['user']);
}

$error = '';
$username = '';
$rememberMe = isset($_COOKIE['literaksi_remember']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember']);

    if ($username === '' || $password === '') {
        $error = 'Username / NIS dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT NIS, username, nama, email, password FROM siswa
             WHERE username = ? OR CAST(NIS AS CHAR) = ? LIMIT 1'
        );
        $stmt->execute([$username, $username]);
        $row = $stmt->fetch();

        if ($row && password_verify($password, $row['password'])) {
            unset($row['password']);
            set_remember_cookie($rememberMe);
            session_regenerate_id(true);
            unset($_SESSION['literaksi_admin']);
            unset($_SESSION['member_appearance_loaded']);
            $_SESSION['literaksi_user'] = $row;
            header('Location: dashboard.php');
            exit;
        }
        $error = 'Username / NIS atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body class="login-body">
    <div class="login-wrap">

        <section class="login-left">
            <img id="logologreg" src="assets/img/logod.png" alt="Literaksi">

            <ul class="features">
                <li>🔔 Akses ribuan buku berkualitas</li>
                <li>🔖 Kelola peminjaman dengan mudah</li>
                <li>🏠 Baca kapan saja dimana saja</li>
            </ul>
        </section>

        <section class="login-right">
            <div class="login-card">
                <h1>Masuk ke<br>Akun Anda</h1>
                <p class="sub">Selamat datang silahkan login<br>kembali untuk melanjutkan aplikasi</p>

                <?php if (isset($_GET['registered'])): ?>
                    <div class="alert ok">Pendaftaran berhasil, silakan login.</div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" action="login.php" novalidate>
                    <input type="text" name="username" placeholder="Username / NIS"
                           value="<?= htmlspecialchars($username) ?>" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <label class="remember-me">
                        <input type="checkbox" name="remember" value="1" <?= $rememberMe ? 'checked' : '' ?>>
                        <span>Ingat saya</span>
                    </label>
                    <button type="submit">Log in</button>
                </form>

                <p class="register">Belum punya akun? <a href="register.php">Daftar</a></p>
                <a class="admin-login-link" href="loginadmin.php">
                    <span class="admin-login-icon" aria-hidden="true">▣</span>
                    <span><strong>Login sebagai petugas</strong><small>Masuk ke portal admin perpustakaan</small></span>
                    <span class="admin-login-arrow" aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </section>

    </div>
</body>
</html>
