CREATE TABLE IF NOT EXISTS `notifikasi_admin` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_petugas` VARCHAR(30) NOT NULL,
    `id_peminjaman` VARCHAR(30) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_notifikasi_admin_peminjaman` (`id_petugas`, `id_peminjaman`),
    KEY `idx_notifikasi_admin_unread` (`id_petugas`, `read_at`, `id`),
    CONSTRAINT `fk_notifikasi_admin_petugas` FOREIGN KEY (`id_petugas`)
        REFERENCES `admin` (`id_petugas`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_notifikasi_admin_peminjaman` FOREIGN KEY (`id_peminjaman`)
        REFERENCES `peminjaman` (`id_peminjaman`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
