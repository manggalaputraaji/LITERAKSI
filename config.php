<?php
$dbHost = 'localhost';
$dbName = 'literaksi';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Koneksi database gagal: ' . htmlspecialchars($e->getMessage()));
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
