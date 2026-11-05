<?php
require_once __DIR__ . '/session.php';
start_app_session();
require 'config.php';

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || !is_string($password) || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT id_petugas, nama, username, password FROM petugas WHERE username = ? LIMIT 1'
            );
            $stmt->execute([$username]);
            $row = $stmt->fetch();
        } catch (PDOException $e) {
            $row = false;
            $error = 'Tabel petugas harus memiliki kolom username dan password.';
        }

        if ($error === '') {
            if (!$row || !password_verify($password, $row['password'])) {
                $error = 'Username atau password salah.';
            } else {
                unset($row['password']);
                session_regenerate_id(true);
                unset($_SESSION['literaksi_user'], $_SESSION['user']);
                unset($_SESSION['member_appearance_loaded']);
                $_SESSION['literaksi_admin'] = $row;
                header('Location: admin_dashboard.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body class="login-body">
    <div class="login-wrap">
        <section class="login-left">
            <img id="logologreg" src="assets/img/logod.png" alt="Literaksi">
            <ul class="features">
                <li>Kelola data pengguna perpustakaan</li>
                <li>Pantau dan perbarui peminjaman</li>
            </ul>
        </section>

        <section class="login-right">
            <div class="login-card">
                <h1>Masuk<br>Administrator</h1>
                <p class="sub">Gunakan akun admin yang telah terdaftar.</p>

                <?php if ($error): ?>
                    <div class="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" action="loginadmin.php" novalidate>
                          <input type="text" name="username" placeholder="Username petugas"
                           value="<?= htmlspecialchars($username) ?>" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit">Login Admin</button>
                </form>

                <p class="register"><a href="login.php">Kembali ke login pengguna</a></p>
            </div>
        </section>
    </div>
</body>
</html>