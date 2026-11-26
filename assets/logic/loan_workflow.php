<?php

// Membatalkan reservasi pengambilan yang sudah lewat batas waktu.
function expire_pending_loan_requests(PDO $pdo): int
{
    $expire = $pdo->prepare("UPDATE peminjaman
        SET status = 'dibatalkan'
        WHERE status = 'menunggu_pengambilan'
            AND reservation_expires_at IS NOT NULL
            AND reservation_expires_at <= CURRENT_TIMESTAMP");
    $expire->execute();
    return $expire->rowCount();
}

// Token asli hanya diberikan ke pemakai; database menyimpan hash agar token tidak tersimpan terbuka.
function issue_data_matrix_token(PDO $pdo, string $detailId, string $purpose, string $expiresAt): string
{
    if (!in_array($purpose, ['ambil', 'kembali'], true)) {
        throw new InvalidArgumentException('Tujuan kode Data Matrix tidak valid.');
    }

    $token = bin2hex(random_bytes(32));
    $expirePrevious = $pdo->prepare('UPDATE data_matrix_tokens
        SET used_at = CURRENT_TIMESTAMP
        WHERE id_detail = ? AND purpose = ? AND used_at IS NULL');
    $expirePrevious->execute([$detailId, $purpose]);

    $insert = $pdo->prepare('INSERT INTO data_matrix_tokens
        (id_detail, purpose, token_hash, expires_at)
        VALUES (?, ?, ?, ?)');
    $insert->execute([$detailId, $purpose, hash('sha256', $token), $expiresAt]);

    return $token;
}
