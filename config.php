<?php
$dbHost = getenv('LITERAKSI_DB_HOST') ?: 'localhost';
$dbName = getenv('LITERAKSI_DB_NAME') ?: 'literaksi';
$dbUser = getenv('LITERAKSI_DB_USER') ?: 'root';
$dbPass = getenv('LITERAKSI_DB_PASS');
$dbPass = $dbPass === false ? 'root' : $dbPass;
$dbPortValue = getenv('LITERAKSI_DB_PORT');
$dbPort = is_string($dbPortValue) && ctype_digit($dbPortValue) ? (int) $dbPortValue : 3306;

try {
    $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error_log('Literaksi database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Koneksi database gagal. Periksa pengaturan koneksi dan pastikan MySQL aktif.');
}

try {
    $pdo->query('SELECT 1 FROM `preferensi_user` LIMIT 0');
} catch (PDOException $e) {
    if ($e->getCode() !== '42S02') {
        throw $e;
    }

    try {
        $pdo->exec('CREATE TABLE `preferensi_user` (
            `NIS` VARCHAR(30) NOT NULL,
            `foto_profil` VARCHAR(64) DEFAULT NULL,
            `tema` VARCHAR(16) NOT NULL DEFAULT \'auto\',
            `warna` ENUM(\'violet\', \'blue\', \'teal\', \'rose\') NOT NULL DEFAULT \'violet\',
            `banner` VARCHAR(7) NOT NULL DEFAULT \'#9581cd\',
            `banner_foto` VARCHAR(64) DEFAULT NULL,
            `bio` VARCHAR(500) DEFAULT NULL,
            PRIMARY KEY (`NIS`),
            CONSTRAINT `fk_preferensi_user` FOREIGN KEY (`NIS`)
                REFERENCES `user` (`NIS`) ON UPDATE CASCADE ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    } catch (PDOException $createError) {
        error_log('Unable to initialize member appearance settings: ' . $createError->getMessage());
        die('Pengaturan foto dan tema belum tersedia. Impor database/literaksi.sql melalui phpMyAdmin '
            . 'atau berikan izin CREATE TABLE kepada pengguna database.');
    }
}

try {
    $themeColumn = $pdo->query("SHOW COLUMNS FROM `preferensi_user` LIKE 'tema'")->fetch();
    if (is_array($themeColumn) && str_starts_with(strtolower($themeColumn['Type']), 'enum(')) {
        $pdo->exec("ALTER TABLE `preferensi_user` MODIFY COLUMN tema VARCHAR(16) NOT NULL DEFAULT 'auto'");
    } elseif (is_array($themeColumn)
        && preg_match('/^varchar\\((\\d+)\\)/i', $themeColumn['Type'], $match)
        && (int) $match[1] < 16) {
        $pdo->exec("ALTER TABLE `preferensi_user` MODIFY COLUMN tema VARCHAR(16) NOT NULL DEFAULT 'auto'");
    }
} catch (PDOException $e) {
    error_log('Unable to migrate appearance themes: ' . $e->getMessage());
    throw $e;
}

try {
    $pdo->query('SELECT banner FROM `preferensi_user` LIMIT 0');
} catch (PDOException $e) {
    if ($e->getCode() !== '42S22') { throw $e; }
    $pdo->exec("ALTER TABLE `preferensi_user` ADD COLUMN banner VARCHAR(7) NOT NULL DEFAULT '#9581cd'");
}

try {
    $bannerColumn = $pdo->query("SHOW COLUMNS FROM `preferensi_user` LIKE 'banner'")->fetch();
    if (is_array($bannerColumn) && str_starts_with(strtolower($bannerColumn['Type']), 'enum(')) {
        $pdo->exec("ALTER TABLE `preferensi_user` MODIFY COLUMN banner VARCHAR(7) NOT NULL DEFAULT '#9581cd'");
        $pdo->exec("UPDATE `preferensi_user` SET banner = CASE banner
            WHEN 'violet' THEN '#9581cd'
            WHEN 'blue' THEN '#6d9ed0'
            WHEN 'teal' THEN '#5eaa97'
            WHEN 'rose' THEN '#ce7196'
            ELSE '#9581cd' END");
    }
} catch (PDOException $e) {
    error_log('Unable to migrate profile banner colors: ' . $e->getMessage());
    throw $e;
}

try {
    $pdo->query('SELECT banner_foto FROM `preferensi_user` LIMIT 0');
} catch (PDOException $e) {
    if ($e->getCode() !== '42S22') { throw $e; }
    $pdo->exec('ALTER TABLE `preferensi_user` ADD COLUMN banner_foto VARCHAR(64) DEFAULT NULL');
}

try {
    $pdo->query('SELECT bio FROM `preferensi_user` LIMIT 0');
} catch (PDOException $e) {
    if ($e->getCode() !== '42S22') { throw $e; }
    $pdo->exec('ALTER TABLE `preferensi_user` ADD COLUMN bio VARCHAR(500) DEFAULT NULL');
}

function book_cover_url(string $id): ?string
{
    $directory = __DIR__ . '/uploads/book-covers/';
    if (preg_match('/\A[A-Za-z0-9_-]+\z/', $id)) {
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'ico'];
        $filenames = [$id, hash('sha256', $id)];
        foreach ($filenames as $filename) {
            foreach ($extensions as $extension) {
                if (is_file($directory . $filename . '.' . $extension)) {
                    return 'fungsi.php?action=book_cover&id=' . rawurlencode($id);
                }
            }
        }
    }

    return null;
}
