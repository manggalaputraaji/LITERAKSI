<?php
// Membuat satu koneksi PDO untuk query database pada halaman yang memerlukannya.
// Nilai environment dapat mengganti pengaturan lokal tanpa mengubah source code.
$dbHost = getenv('LITERAKSI_DB_HOST') ?: 'localhost';
$dbName = getenv('LITERAKSI_DB_NAME') ?: 'literaksi';
$dbUser = getenv('LITERAKSI_DB_USER') ?: 'root';
$dbPass = getenv('LITERAKSI_DB_PASS');
$dbPass = $dbPass === false ? 'root' : $dbPass;
$dbPort = getenv('LITERAKSI_DB_PORT') ?: '3306';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    error_log('Literaksi database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Koneksi database gagal. Periksa pengaturan koneksi dan pastikan MySQL aktif.');
}
