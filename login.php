<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['literaksi_user'], $_SESSION['user']);
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

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
            session_regenerate_id(true);
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
    <link rel="stylesheet" href="assets/css/style.css">
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
                    <button type="submit">Log in</button>
                </form>

                <p class="register">Belum punya akun ? <a href="register.php">Daftar</a></p>
            </div>
        </section>

    </div>
</body>
</html>
