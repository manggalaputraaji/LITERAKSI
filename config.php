<?php
$dbHost = 'sql107.infinityfree.com';
$dbName = 'if0_43387843_literaksi';
$dbUser = 'if0_43387843';
$dbPass = 'l5F3NVhD8iI1KTo';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Koneksi database gagal: ' . htmlspecialchars($e->getMessage()));
}

try {
    $pdo->query('SELECT 1 FROM pengaturan_anggota LIMIT 0');
} catch (PDOException $e) {
    if ($e->getCode() !== '42S02') {
        throw $e;
    }

    try {
        $pdo->exec('CREATE TABLE `pengaturan_anggota` (
            `NIS` VARCHAR(30) NOT NULL,
            `foto_profil` VARCHAR(64) DEFAULT NULL,
            `tema` ENUM(\'light\', \'dark\') NOT NULL DEFAULT \'light\',
            `warna` ENUM(\'violet\', \'blue\', \'teal\', \'rose\') NOT NULL DEFAULT \'violet\',
            PRIMARY KEY (`NIS`),
            CONSTRAINT `fk_pengaturan_anggota_siswa`
                FOREIGN KEY (`NIS`) REFERENCES `siswa` (`NIS`)
                ON UPDATE CASCADE ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    } catch (PDOException $createError) {
        error_log('Unable to initialize member appearance settings: ' . $createError->getMessage());
        die('Pengaturan foto dan tema belum tersedia. Jalankan database/migrations/20261105_member_appearance.sql '
            . 'atau berikan izin CREATE TABLE kepada pengguna database.');
    }
}

function book_cover_url(string $id): ?string
{
    $directory = __DIR__ . '/uploads/book-covers/';
    $filename = hash('sha256', $id);
    foreach (['jpg', 'png', 'gif', 'webp', 'avif', 'bmp', 'ico'] as $extension) {
        $path = $directory . $filename . '.' . $extension;
        if (is_file($path)) {
            return 'book_cover.php?id=' . rawurlencode($id);
        }
    }

    return null;
}
