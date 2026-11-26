<?php
// Mengembalikan URL sampul yang tersedia; nama hash tetap dicari untuk mendukung berkas lama.
require_once __DIR__ . '/bootstrap.php';

function book_cover_url(string $id): ?string
{
    $directory = APP_ROOT . '/uploads/book-covers/';

    if (!preg_match('/\A[A-Za-z0-9_-]+\z/', $id)) {
        return null;
    }

    $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'ico'];
    foreach ([$id, hash('sha256', $id)] as $filename) {
        foreach ($extensions as $extension) {
            if (is_file($directory . $filename . '.' . $extension)) {
                return 'assets/logic/fungsi.php?action=book_cover&id=' . rawurlencode($id);
            }
        }
    }

    return null;
}
