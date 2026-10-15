<?php
session_start();
require 'config.php';

$error = '';
$old = ['nama' => '', 'username' => '', 'email' => '', 'nomor' => ''];
$password = '';
$konfirmasi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['nama']     = trim($_POST['nama'] ?? '');
    $old['username'] = trim($_POST['username'] ?? '');
    $old['email']    = trim($_POST['email'] ?? '');
    $old['nomor']    = trim($_POST['nomor'] ?? '');
    $password        = $_POST['password'] ?? '';
    $konfirmasi      = $_POST['konfirmasi'] ?? '';

    if (in_array('', [$old['nama'], $old['username'], $old['email'], $old['nomor'], $password, $konfirmasi], true)) {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $old['username'])) {
        $error = 'Username 3-50 karakter, hanya huruf, angka, titik, dan underscore.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (!ctype_digit($old['nomor'])) {
        $error = 'Nomor Anggota / NISN harus berupa angka.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        $cek = $pdo->prepare('SELECT username, email, NIS FROM siswa WHERE username = ? OR email = ? OR NIS = ?');
        $cek->execute([$old['username'], $old['email'], $old['nomor']]);
        $ada = $cek->fetch();

        if ($ada) {
            if (strcasecmp($ada['username'], $old['username']) === 0) {
                $error = 'Username sudah dipakai.';
            } elseif (strcasecmp($ada['email'], $old['email']) === 0) {
                $error = 'Email sudah terdaftar.';
            } else {
                $error = 'Nomor Anggota / NISN sudah terdaftar.';
            }
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO siswa (NIS, username, nama, email, password) VALUES (?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $old['nomor'], $old['username'], $old['nama'], $old['email'],
                password_hash($password, PASSWORD_DEFAULT),
            ]);
            header('Location: login.php?registered=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-wrap">

        <section class="login-left">
            <img id="logologreg" src="assets/img/logod.png" alt="Literaksi">

            <div class="join">
                <h2>Bergabung Sekarang!</h2>
                <p>Jadilah bagian dari komunitas membaca<br>yang baik.</p>
            </div>
        </section>

        <section class="login-right">
            <div class="login-card">
                <h1>Daftar Akun</h1>
                <p class="sub">Buat akun untuk meminjam buku di perpustakaan kami.</p>

                <?php if ($error): ?>
                    <div class="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" action="register.php" novalidate>
                    <input type="text" name="nama" placeholder="Nama Lengkap"
                           value="<?= htmlspecialchars($old['nama']) ?>" required>
                    <input type="text" name="username" placeholder="Buat Username"
                           value="<?= htmlspecialchars($old['username']) ?>" required>
                    <input type="email" name="email" placeholder="Email"
                           value="<?= htmlspecialchars($old['email']) ?>" required>
                    <input type="text" name="nomor" placeholder="Nomor Anggota / NISN"
                           value="<?= htmlspecialchars($old['nomor']) ?>" required>
                    <div class="field">
                        <input type="password" id="password" name="password" placeholder="Password"
                               value="<?= htmlspecialchars($password) ?>" required>
                        <div class="strength" id="strength"><span id="strengthBar"></span></div>
                        <small class="strength-label" id="strengthLabel"></small>
                    </div>
                    <div class="field">
                        <input type="password" id="konfirmasi" name="konfirmasi" placeholder="Konfirmasi Password"
                               value="<?= htmlspecialchars($konfirmasi) ?>" required>
                        <span class="match-icon" id="matchIcon"></span>
                    </div>
                    <button type="submit">Sign Up</button>
                </form>

                <p class="register">Sudah punya akun ? <a href="login.php">Login</a></p>
            </div>
        </section>

    </div>

    <script>
        const pw = document.getElementById('password');
        const cf = document.getElementById('konfirmasi');
        const bar = document.getElementById('strengthBar');
        const box = document.getElementById('strength');
        const label = document.getElementById('strengthLabel');
        const icon = document.getElementById('matchIcon');

        function score(v) {
            let s = 0;
            if (v.length >= 6) s++;
            if (v.length >= 10) s++;
            if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
            if (/\d/.test(v)) s++;
            if (/[^A-Za-z0-9]/.test(v)) s++;
            return s;
        }

        function updateStrength() {
            const v = pw.value;
            if (!v) {
                box.style.visibility = 'hidden';
                label.textContent = '';
                return;
            }
            const s = score(v);
            let width = '33%', color = '#ff6b6b', text = 'Lemah';
            if (s >= 4) { width = '100%'; color = '#3ddc84'; text = 'Kuat'; }
            else if (s === 3) { width = '66%'; color = '#ffb020'; text = 'Sedang'; }
            box.style.visibility = 'visible';
            bar.style.width = width;
            bar.style.background = color;
            label.textContent = 'Kekuatan password: ' + text;
            label.style.color = color;
        }

        function updateMatch() {
            if (!cf.value) {
                icon.textContent = '';
                icon.className = 'match-icon';
                return;
            }
            const ok = cf.value === pw.value;
            icon.textContent = ok ? '✔' : '✖';
            icon.className = 'match-icon ' + (ok ? 'ok' : 'bad');
        }

        pw.addEventListener('input', function () { updateStrength(); updateMatch(); });
        cf.addEventListener('input', updateMatch);
        updateStrength();
        updateMatch();
    </script>
</body>
</html>
