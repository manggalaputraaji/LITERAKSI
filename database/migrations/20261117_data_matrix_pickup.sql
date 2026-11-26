ALTER TABLE `peminjaman`
    MODIFY `status` ENUM('menunggu_pengambilan', 'dipinjam', 'kembali', 'dibatalkan') NOT NULL DEFAULT 'dipinjam',
    ADD COLUMN `reservation_expires_at` DATETIME DEFAULT NULL AFTER `status`,
    ADD KEY `idx_peminjaman_reservation` (`status`, `reservation_expires_at`);

CREATE TABLE IF NOT EXISTS `data_matrix_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_detail` VARCHAR(30) NOT NULL,
    `purpose` ENUM('ambil', 'kembali') NOT NULL,
    `token_hash` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_data_matrix_token_hash` (`token_hash`),
    KEY `idx_data_matrix_detail_purpose` (`id_detail`, `purpose`, `used_at`),
    CONSTRAINT `fk_data_matrix_detail` FOREIGN KEY (`id_detail`)
        REFERENCES `detail_peminjaman` (`id_detail`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
