-- LITERAKSI database schema for MySQL / MariaDB.
-- Import this file in phpMyAdmin or run: mysql -u root -p < database/literaksi.sql

CREATE DATABASE IF NOT EXISTS `literaksi`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE `literaksi`;

CREATE TABLE IF NOT EXISTS `kategori` (
    `id_kategori` VARCHAR(10) NOT NULL,
    `kategori` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `penerbit` (
    `id_penerbit` VARCHAR(10) NOT NULL,
    `nama_penerbit` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id_penerbit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `buku` (
    `id_buku` VARCHAR(10) NOT NULL,
    `judul` VARCHAR(200) NOT NULL,
    `id_kategori` VARCHAR(10) DEFAULT NULL,
    `id_penerbit` VARCHAR(10) DEFAULT NULL,
    PRIMARY KEY (`id_buku`),
    KEY `idx_buku_kategori` (`id_kategori`),
    KEY `idx_buku_penerbit` (`id_penerbit`),
    CONSTRAINT `fk_buku_kategori`
        FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_buku_penerbit`
        FOREIGN KEY (`id_penerbit`) REFERENCES `penerbit` (`id_penerbit`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `petugas` (
    `id_petugas` VARCHAR(10) NOT NULL,
    `nama` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) DEFAULT NULL,
    `password` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id_petugas`),
    UNIQUE KEY `uq_petugas_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `siswa` (
    `NIS` VARCHAR(30) NOT NULL,
    `username` VARCHAR(50) NOT NULL,
    `nama` VARCHAR(100) NOT NULL,
    `email` VARCHAR(254) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `kelas` VARCHAR(50) DEFAULT NULL,
    PRIMARY KEY (`NIS`),
    UNIQUE KEY `uq_siswa_username` (`username`),
    UNIQUE KEY `uq_siswa_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `peminjaman` (
    `id_peminjaman` VARCHAR(10) NOT NULL,
    `NIS` VARCHAR(30) NOT NULL,
    `id_petugas` VARCHAR(10) NOT NULL,
    `tgl_peminjam` DATE NOT NULL,
    `tgl_jatuh_tempo` DATE NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'dipinjam',
    PRIMARY KEY (`id_peminjaman`),
    KEY `idx_peminjaman_nis` (`NIS`),
    KEY `idx_peminjaman_petugas` (`id_petugas`),
    KEY `idx_peminjaman_tgl_peminjam` (`tgl_peminjam`),
    KEY `idx_peminjaman_tgl_jatuh_tempo` (`tgl_jatuh_tempo`),
    CONSTRAINT `fk_peminjaman_siswa`
        FOREIGN KEY (`NIS`) REFERENCES `siswa` (`NIS`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_peminjaman_petugas`
        FOREIGN KEY (`id_petugas`) REFERENCES `petugas` (`id_petugas`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `detail_peminjaman` (
    `id_detail` VARCHAR(10) NOT NULL,
    `id_peminjaman` VARCHAR(10) NOT NULL,
    `id_buku` VARCHAR(10) NOT NULL,
    `tgl_kembali` DATE DEFAULT NULL,
    `tgl_pengajuan_kembali` DATETIME DEFAULT NULL,
    `denda` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`id_detail`),
    KEY `idx_detail_peminjaman` (`id_peminjaman`),
    KEY `idx_detail_buku` (`id_buku`),
    KEY `idx_detail_tgl_kembali` (`tgl_kembali`),
    KEY `idx_detail_tgl_pengajuan_kembali` (`tgl_pengajuan_kembali`),
    CONSTRAINT `fk_detail_peminjaman`
        FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_detail_buku`
        FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id_buku`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `postingan` (
    `id_post` INT NOT NULL AUTO_INCREMENT,
    `NIS` VARCHAR(30) NOT NULL,
    `isi` TEXT NOT NULL,
    `dibuat` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_post`),
    KEY `idx_postingan_nis` (`NIS`),
    CONSTRAINT `fk_postingan_siswa`
        FOREIGN KEY (`NIS`) REFERENCES `siswa` (`NIS`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- New loans created by members use this reserved system staff ID.
INSERT IGNORE INTO `petugas` (`id_petugas`, `nama`, `username`, `password`)
VALUES ('SYS', 'Sistem', NULL, NULL);
