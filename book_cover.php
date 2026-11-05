<?php

$id = $_GET['id'] ?? '';
if (!is_string($id) || $id === '') {
    http_response_code(404);
    exit;
}

$directory = __DIR__ . '/uploads/book-covers/';
$filename = hash('sha256', $id);
$imageTypes = [
    'jpg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'avif' => 'image/avif',
    'bmp' => 'image/bmp',
    'ico' => 'image/vnd.microsoft.icon',
];

foreach ($imageTypes as $extension => $contentType) {
    $path = $directory . $filename . '.' . $extension;
    if (is_file($path)) {
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: inline; filename="book-cover.' . $extension . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-cache');
        readfile($path);
        exit;
    }
}

http_response_code(404);
