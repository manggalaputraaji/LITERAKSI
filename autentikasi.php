<?php
$action = $_GET['action'] ?? 'login';
$allowedActions = ['login', 'register', 'loginadmin', 'logout'];
$route = is_string($action) && in_array($action, $allowedActions, true) ? $action : '';
$cameFromGuest = ($_GET['return'] ?? '') === 'guest';
$openedRegisterFromGuest = $route === 'register' && ($_GET['source'] ?? '') === 'guest';
require_once __DIR__ . '/session.php';
start_app_session();
require_once __DIR__ . '/layout.php';
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
?>
<?php if ($route === 'login') { ?>
<?php
require 'config.php';

$error = '';
$username = '';
$rememberMe = isset($_COOKIE['literaksi_remember']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = $_POST['csrf'] ?? '';
    if (!is_string($postedCsrf) || !hash_equals($_SESSION['csrf'], $postedCsrf)) {
        $error = 'Sesi tidak valid, silakan muat ulang halaman.';
    }
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember']);

    if ($error === '' && ($username === '' || $password === '')) {
        $error = 'Username / NIS dan password wajib diisi.';
    }

    if ($error === '') {
        $stmt = $pdo->prepare(
            'SELECT NIS, username, nama, email, password FROM `user`
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
            header('Location: user.php?page=dashboard');
            exit;
        }
        $error = 'Username / NIS atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="assets/img/logo.ico" type="image/x-icon">
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
                <?php if ($cameFromGuest): ?>
                    <form class="login-guest-form" method="post" action="autentikasi.php?action=logout&amp;return=guest">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                        <button class="login-guest-link" type="submit">
                            <span aria-hidden="true">&larr;</span> Kembali
                        </button>
                    </form>
                <?php endif; ?>
                <h1>Masuk ke<br>Akun Anda</h1>
                <p class="sub">Selamat datang silahkan login<br>kembali untuk melanjutkan aplikasi</p>

                <?php if (isset($_GET['registered'])): ?>
                    <div class="alert ok">Pendaftaran berhasil, silakan login.</div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post" action="autentikasi.php?action=login<?= $cameFromGuest ? '&amp;return=guest' : '' ?>" novalidate>
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                    <input type="text" name="username" placeholder="Username / NIS"
                           value="<?= htmlspecialchars($username) ?>" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <label class="remember-me">
                        <input type="checkbox" name="remember" value="1" <?= $rememberMe ? 'checked' : '' ?>>
                        <span>Ingat saya</span>
                    </label>
                    <button type="submit">Log in</button>
                </form>

                <p class="register">Belum punya akun? <a href="autentikasi.php?action=register<?= $cameFromGuest ? '&amp;return=guest' : '' ?>">Daftar</a></p>
                <a class="admin-login-link" href="autentikasi.php?action=loginadmin">
                    <span class="admin-login-icon" aria-hidden="true">▣</span>
                    <span><strong>Login sebagai petugas</strong><small>Masuk ke portal admin perpustakaan</small></span>
                    <span class="admin-login-arrow" aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </section>

    </div>
<?php render_site_footer(); ?>
</body>
</html>
<?php } elseif ($route === 'register') { ?>
<?php
require 'config.php';

$error = '';
$form = ['nama' => '', 'username' => '', 'email' => '', 'nomor' => ''];
$password = '';
$confirmPassword = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = $_POST['csrf'] ?? '';
    if (!is_string($postedCsrf) || !hash_equals($_SESSION['csrf'], $postedCsrf)) {
        $error = 'Sesi tidak valid, silakan muat ulang halaman.';
    }
    $form['nama']     = trim($_POST['nama'] ?? '');
    $form['username'] = trim($_POST['username'] ?? '');
    $form['email']    = trim($_POST['email'] ?? '');
    $form['nomor']    = trim($_POST['nomor'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword      = $_POST['konfirmasi'] ?? '';

    if ($error === '') {
        if (in_array('', [$form['nama'], $form['username'], $form['email'], $form['nomor'], $password, $confirmPassword], true)) {
            $error = 'Semua kolom wajib diisi.';
        } elseif (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $form['username'])) {
            $error = 'Username 3-50 karakter, hanya huruf, angka, titik, dan underscore.';
        } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif (!ctype_digit($form['nomor'])) {
            $error = 'Nomor Anggota / NISN harus berupa angka.';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Konfirmasi password tidak sama.';
        } else {
            $existingAccountQuery = $pdo->prepare('SELECT username, email, NIS FROM `user` WHERE username = ? OR email = ? OR NIS = ?');
            $existingAccountQuery->execute([$form['username'], $form['email'], $form['nomor']]);
            $existingAccount = $existingAccountQuery->fetch();

            if ($existingAccount) {
                if (strcasecmp($existingAccount['username'], $form['username']) === 0) {
                    $error = 'Username sudah dipakai.';
                } elseif (strcasecmp($existingAccount['email'], $form['email']) === 0) {
                    $error = 'Email sudah terdaftar.';
                } else {
                    $error = 'Nomor Anggota / NISN sudah terdaftar.';
                }
            } else {
                $insertUser = $pdo->prepare(
                    'INSERT INTO `user` (NIS, username, nama, email, password) VALUES (?, ?, ?, ?, ?)'
                );
                $insertUser->execute([
                    $form['nomor'], $form['username'], $form['nama'], $form['email'],
                    password_hash($password, PASSWORD_DEFAULT),
                ]);
                header('Location: autentikasi.php?action=login&registered=1' . ($cameFromGuest || $openedRegisterFromGuest ? '&return=guest' : ''));
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="assets/img/logo.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar - Literaksi</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
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
                <?php if ($openedRegisterFromGuest): ?>
                    <form class="login-guest-form" method="post" action="autentikasi.php?action=logout&amp;return=guest">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                        <button class="login-guest-link" type="submit">
                            <span aria-hidden="true">&larr;</span> Kembali
                        </button>
                    </form>
                <?php endif; ?>
                <h1>Daftar Akun</h1>
                <p class="sub">Buat akun untuk meminjam buku di perpustakaan kami.</p>

                <?php if ($error): ?>
                    <div class="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post" action="autentikasi.php?action=register<?= $cameFromGuest ? '&amp;return=guest' : '' ?><?= $openedRegisterFromGuest ? '&amp;source=guest' : '' ?>" novalidate>
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                    <input type="text" name="nama" placeholder="Nama Lengkap"
                           value="<?= htmlspecialchars($form['nama']) ?>" required>
                    <input type="text" name="username" placeholder="Buat Username"
                           value="<?= htmlspecialchars($form['username']) ?>" required>
                    <input type="email" name="email" placeholder="Email"
                           value="<?= htmlspecialchars($form['email']) ?>" required>
                    <input type="text" name="nomor" placeholder="Nomor Anggota / NISN"
                           value="<?= htmlspecialchars($form['nomor']) ?>" required>
                    <div class="field">
                        <input type="password" id="password" name="password" placeholder="Password" required>
                        <div class="strength" id="strength"><span id="strengthBar"></span></div>
                        <small class="strength-label" id="strengthLabel"></small>
                    </div>
                    <div class="field">
                        <input type="password" id="konfirmasi" name="konfirmasi" placeholder="Konfirmasi Password" required>
                        <span class="match-icon" id="matchIcon"></span>
                    </div>
                    <button type="submit">Sign Up</button>
                </form>

                <p class="register">Sudah punya akun ? <a href="autentikasi.php?action=login<?= $cameFromGuest || $openedRegisterFromGuest ? '&amp;return=guest' : '' ?>">Login</a></p>
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
<?php render_site_footer(); ?>
</body>
</html>
<?php } elseif ($route === 'loginadmin') { ?>
<?php
require 'config.php';

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = $_POST['csrf'] ?? '';
    if (!is_string($postedCsrf) || !hash_equals($_SESSION['csrf'], $postedCsrf)) {
        $error = 'Sesi tidak valid, silakan muat ulang halaman.';
    }
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($error === '' && ($username === '' || !is_string($password) || $password === '')) {
        $error = 'Username dan password wajib diisi.';
    }

    if ($error === '') {
        try {
            $stmt = $pdo->prepare(
                'SELECT id_petugas, nama, username, password FROM `admin` WHERE username = ? LIMIT 1'
            );
            $stmt->execute([$username]);
            $row = $stmt->fetch();
        } catch (PDOException $e) {
            $row = false;
            $error = 'Tabel admin harus memiliki kolom username dan password.';
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
                header('Location: admin.php?page=dashboard');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="assets/img/logo.ico" type="image/x-icon">
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

                <form method="post" action="autentikasi.php?action=loginadmin" novalidate>
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                          <input type="text" name="username" placeholder="Username petugas"
                           value="<?= htmlspecialchars($username) ?>" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit">Login Admin</button>
                </form>

                <p class="register"><a href="autentikasi.php?action=login">Kembali ke login pengguna</a></p>
            </div>
        </section>
    </div>
<?php render_site_footer(); ?>
</body>
</html>
<?php } elseif ($route === 'logout') { ?>
<?php
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Metode permintaan tidak didukung.');
}
$postedCsrf = $_POST['csrf'] ?? '';
if (!is_string($postedCsrf) || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $postedCsrf)) {
    http_response_code(400);
    exit('Sesi tidak valid. Muat ulang halaman dan coba lagi.');
}
$_SESSION = [];
session_destroy();
$isAdminLogout = session_name() === admin_session_name();
if (!$isAdminLogout) {
    set_remember_cookie(false);
}
setcookie(session_name(), '', [
    'expires' => time() - 42000,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
$destination = ($_GET['return'] ?? '') === 'guest'
    ? 'guest.php?page=katalog'
    : ($isAdminLogout ? 'autentikasi.php?action=loginadmin' : 'autentikasi.php?action=login');
header('Location: ' . $destination);
exit;
} else { ?>
<!DOCTYPE html><html lang="id"><meta charset="UTF-8"><title>Halaman tidak ditemukan</title><body><p>Halaman tidak ditemukan.</p></body></html>
<?php } ?>
