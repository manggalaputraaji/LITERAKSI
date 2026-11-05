CREATE TABLE IF NOT EXISTS `pengaturan_anggota` (
    `NIS` VARCHAR(30) NOT NULL,
    `foto_profil` VARCHAR(64) DEFAULT NULL,
    `tema` ENUM('light', 'dark') NOT NULL DEFAULT 'light',
    `warna` ENUM('violet', 'blue', 'teal', 'rose') NOT NULL DEFAULT 'violet',
    PRIMARY KEY (`NIS`),
    CONSTRAINT `fk_pengaturan_anggota_siswa`
        FOREIGN KEY (`NIS`) REFERENCES `siswa` (`NIS`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
