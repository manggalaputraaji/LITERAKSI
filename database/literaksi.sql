-- Reset the tables in the database selected in phpMyAdmin, then restore the
-- records from the supplied database export. This keeps shared-host database names intact.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `postingan`, `diskusi_suka`, `laporan_diskusi`, `akun_oauth`,
    `data_matrix_tokens`, `notifikasi_admin`, `detail_peminjaman`, `peminjaman`, `diskusi`, `preferensi_user`,
    `pengaturan_anggota`, `buku`, `kategori`, `penerbit`, `penulis`,
    `user`, `admin`, `siswa`, `petugas`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS `user` (
    `NIS` VARCHAR(30) NOT NULL,
    `username` VARCHAR(50) NOT NULL,
    `nama` VARCHAR(150) NOT NULL,
    `email` VARCHAR(254) DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `kelas` VARCHAR(50) DEFAULT NULL,
    PRIMARY KEY (`NIS`),
    UNIQUE KEY `uq_user_username` (`username`),
    UNIQUE KEY `uq_user_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin` (
    `id_petugas` VARCHAR(30) NOT NULL,
    `nama` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) DEFAULT NULL,
    `password` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id_petugas`),
    UNIQUE KEY `uq_admin_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kategori` (
    `id_kategori` VARCHAR(30) NOT NULL,
    `kategori` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id_kategori`),
    KEY `idx_kategori_name` (`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `penerbit` (
    `id_penerbit` VARCHAR(30) NOT NULL,
    `nama_penerbit` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id_penerbit`),
    KEY `idx_penerbit_name` (`nama_penerbit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `penulis` (
    `id_penulis` VARCHAR(30) NOT NULL,
    `nama_penulis` VARCHAR(150) NOT NULL,
    PRIMARY KEY (`id_penulis`),
    KEY `idx_penulis_name` (`nama_penulis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `buku` (
    `id_buku` VARCHAR(30) NOT NULL,
    `judul` VARCHAR(200) NOT NULL,
    `sinopsis` MEDIUMTEXT DEFAULT NULL,
    `stok` INT UNSIGNED NOT NULL DEFAULT 0,
    `id_kategori` VARCHAR(30) DEFAULT NULL,
    `id_penerbit` VARCHAR(30) DEFAULT NULL,
    `id_penulis` VARCHAR(30) DEFAULT NULL,
    PRIMARY KEY (`id_buku`),
    KEY `idx_buku_judul` (`judul`),
    KEY `idx_buku_kategori` (`id_kategori`),
    KEY `idx_buku_penerbit` (`id_penerbit`),
    KEY `idx_buku_penulis` (`id_penulis`),
    CONSTRAINT `fk_buku_kategori` FOREIGN KEY (`id_kategori`)
        REFERENCES `kategori` (`id_kategori`) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_buku_penerbit` FOREIGN KEY (`id_penerbit`)
        REFERENCES `penerbit` (`id_penerbit`) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_buku_penulis` FOREIGN KEY (`id_penulis`)
        REFERENCES `penulis` (`id_penulis`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `peminjaman` (
    `id_peminjaman` VARCHAR(30) NOT NULL,
    `NIS` VARCHAR(30) NOT NULL,
    `id_petugas` VARCHAR(30) NOT NULL DEFAULT 'SYS',
    `tgl_peminjam` DATE NOT NULL,
    `tgl_jatuh_tempo` DATE NOT NULL,
    `status` ENUM('menunggu_pengambilan', 'dipinjam', 'kembali', 'dibatalkan') NOT NULL DEFAULT 'dipinjam',
    `reservation_expires_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id_peminjaman`),
    KEY `idx_peminjaman_nis` (`NIS`),
    KEY `idx_peminjaman_admin` (`id_petugas`),
    KEY `idx_peminjaman_tanggal` (`tgl_peminjam`),
    KEY `idx_peminjaman_tempo` (`tgl_jatuh_tempo`),
    KEY `idx_peminjaman_reservation` (`status`, `reservation_expires_at`),
    CONSTRAINT `fk_peminjaman_user` FOREIGN KEY (`NIS`)
        REFERENCES `user` (`NIS`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_peminjaman_admin` FOREIGN KEY (`id_petugas`)
        REFERENCES `admin` (`id_petugas`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `detail_peminjaman` (
    `id_detail` VARCHAR(30) NOT NULL,
    `id_peminjaman` VARCHAR(30) NOT NULL,
    `id_buku` VARCHAR(30) NOT NULL,
    `tgl_kembali` DATE DEFAULT NULL,
    `tgl_pengajuan_kembali` DATETIME DEFAULT NULL,
    `denda` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`id_detail`),
    KEY `idx_detail_peminjaman` (`id_peminjaman`),
    KEY `idx_detail_buku` (`id_buku`),
    KEY `idx_detail_kembali` (`tgl_kembali`),
    KEY `idx_detail_pengajuan` (`tgl_pengajuan_kembali`),
    CONSTRAINT `fk_detail_peminjaman` FOREIGN KEY (`id_peminjaman`)
        REFERENCES `peminjaman` (`id_peminjaman`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_detail_buku` FOREIGN KEY (`id_buku`)
        REFERENCES `buku` (`id_buku`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `preferensi_user` (
    `NIS` VARCHAR(30) NOT NULL,
    `foto_profil` VARCHAR(64) DEFAULT NULL,
    `tema` VARCHAR(16) NOT NULL DEFAULT 'auto',
    `warna` ENUM('violet', 'blue', 'teal', 'rose') NOT NULL DEFAULT 'violet',
    `banner` VARCHAR(7) NOT NULL DEFAULT '#9581cd',
    `banner_foto` VARCHAR(64) DEFAULT NULL,
    `bio` VARCHAR(500) DEFAULT NULL,
    PRIMARY KEY (`NIS`),
    CONSTRAINT `fk_preferensi_user` FOREIGN KEY (`NIS`)
        REFERENCES `user` (`NIS`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `diskusi` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `jenis_entri` ENUM('pesan', 'suka') NOT NULL DEFAULT 'pesan',
    `jenis_pengirim` ENUM('user', 'admin') NOT NULL,
    `id_pengirim_user` VARCHAR(30) DEFAULT NULL,
    `id_pengirim_admin` VARCHAR(30) DEFAULT NULL,
    `id_penyuka_user` VARCHAR(30) DEFAULT NULL,
    `id_penyuka_admin` VARCHAR(30) DEFAULT NULL,
    `sender_name` VARCHAR(150) NOT NULL,
    `message` TEXT DEFAULT NULL,
    `parent_id` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_diskusi_suka_user` (`parent_id`, `id_penyuka_user`),
    UNIQUE KEY `uq_diskusi_suka_admin` (`parent_id`, `id_penyuka_admin`),
    KEY `idx_diskusi_pengirim_user` (`id_pengirim_user`),
    KEY `idx_diskusi_pengirim_admin` (`id_pengirim_admin`),
    KEY `idx_diskusi_penyuka_user` (`id_penyuka_user`),
    KEY `idx_diskusi_penyuka_admin` (`id_penyuka_admin`),
    KEY `idx_diskusi_created` (`created_at`, `id`),
    KEY `idx_diskusi_parent` (`parent_id`),
    CONSTRAINT `fk_diskusi_parent` FOREIGN KEY (`parent_id`)
        REFERENCES `diskusi` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_diskusi_user` FOREIGN KEY (`id_pengirim_user`)
        REFERENCES `user` (`NIS`) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_diskusi_admin` FOREIGN KEY (`id_pengirim_admin`)
        REFERENCES `admin` (`id_petugas`) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_diskusi_penyuka_user` FOREIGN KEY (`id_penyuka_user`)
        REFERENCES `user` (`NIS`) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_diskusi_penyuka_admin` FOREIGN KEY (`id_penyuka_admin`)
        REFERENCES `admin` (`id_petugas`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `laporan_diskusi` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `message_id` BIGINT UNSIGNED DEFAULT NULL,
    `sender_name` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `jenis_pelapor` ENUM('user', 'admin') NOT NULL,
    `id_pelapor_user` VARCHAR(30) DEFAULT NULL,
    `id_pelapor_admin` VARCHAR(30) DEFAULT NULL,
    `reporter_name` VARCHAR(150) NOT NULL,
    `reason` TEXT DEFAULT NULL,
    `status` ENUM('menunggu', 'ditinjau', 'dihapus') NOT NULL DEFAULT 'menunggu',
    `reported_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_laporan_diskusi_user` (`message_id`, `id_pelapor_user`),
    UNIQUE KEY `uq_laporan_diskusi_admin` (`message_id`, `id_pelapor_admin`),
    KEY `idx_laporan_diskusi_status` (`status`, `reported_at`),
    CONSTRAINT `fk_laporan_diskusi_message` FOREIGN KEY (`message_id`)
        REFERENCES `diskusi` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_laporan_diskusi_user` FOREIGN KEY (`id_pelapor_user`)
        REFERENCES `user` (`NIS`) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_laporan_diskusi_admin` FOREIGN KEY (`id_pelapor_admin`)
        REFERENCES `admin` (`id_petugas`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restore the rows present in if0_43387843_literaksi.sql.zip.
INSERT INTO `user` (`NIS`, `username`, `nama`, `email`, `password`, `kelas`) VALUES
    ('12345', 'manggieez', 'Bara Manggala Aji Putra', 'baramanggala@literaksi.gov', '$2y$10$GTVve8EMSTCVauukn6CIWeb7.kRw0Vh75DOyB9Ha3F3cXbo7I4Ylq', NULL);

INSERT INTO `admin` (`id_petugas`, `nama`, `username`, `password`) VALUES
    ('ADM0000001', 'Admin', 'admin', '$2y$10$ufmTNNhyRsIUfnzmEeVOTOs.fByP.PvhB42o0xpItY2NhqA7U0Q6u'),
    ('SYS', 'Sistem', NULL, NULL);

INSERT INTO `kategori` (`id_kategori`, `kategori`) VALUES
    ('BKC0000001', 'Novel'),
    ('BKC0000002', 'Filsafat'),
    ('BKC0000003', 'Pengembangan Diri'),
    ('DMC0000001', 'Fiksi'),
    ('DMC0000002', 'Fantasi'),
    ('DMC0000003', 'Sains'),
    ('DMC0000004', 'Sejarah'),
    ('DMC0000005', 'Pengembangan Diri');

INSERT INTO `penerbit` (`id_penerbit`, `nama_penerbit`) VALUES
    ('BKP0000001', 'Kepustakaan Populer Gramedia'),
    ('BKP0000002', 'Lentera Dipantara'),
    ('BKP0000003', 'Penerbit Buku Kompas'),
    ('BKP0000004', 'Gramedia Pustaka Utama'),
    ('BKP0000005', 'Bentang Pustaka'),
    ('DME0000001', 'Pustaka Demo'),
    ('DME0000002', 'Cakrawala Ilmu'),
    ('DME0000003', 'Bintang Literasi');

INSERT INTO `penulis` (`id_penulis`, `nama_penulis`) VALUES
    ('PNS0000001', 'Ahmad Fuadi'),
    ('PNS0000002', 'Andrea Hirata'),
    ('DWA0000001', 'Ayu Pratiwi'),
    ('DWA0000002', 'Bima Santosa'),
    ('DWA0000003', 'Citra Lestari'),
    ('DWA0000004', 'Danu Wicaksono'),
    ('PNS0000003', 'Dee Lestari'),
    ('DWA0000005', 'Eka Purnama'),
    ('DWA0000006', 'Fajar Nugraha'),
    ('DWA0000007', 'Gita Maharani'),
    ('DWA0000008', 'Hadi Permana'),
    ('PNS0000004', 'Henry Manampiring'),
    ('DWA0000009', 'Indah Kirana'),
    ('PNS0000005', 'James Clear'),
    ('DWA0000010', 'Joko Prabowo'),
    ('PNS0000006', 'Leila S. Chudori'),
    ('PNS0000007', 'Pramoedya Ananta Toer'),
    ('PNS0000008', 'Ratih Kumala'),
    ('PNS0000009', 'Tere Liye');

INSERT INTO `buku` (`id_buku`, `judul`, `sinopsis`, `stok`, `id_kategori`, `id_penerbit`, `id_penulis`) VALUES
    ('BKB0000001', 'Laut Bercerita', 'Sebuah keluarga menanti kabar seorang mahasiswa aktivis yang hilang, sementara kenangan mereka membuka sisi personal dari masa penuh gejolak.', 5, 'BKC0000001', 'BKP0000001', 'PNS0000006'),
    ('BKB0000002', 'Bumi Manusia', 'Minke, seorang pemuda pribumi pada masa kolonial, berhadapan dengan batas kelas, pendidikan, cinta, dan ketidakadilan.', 4, 'BKC0000001', 'BKP0000002', 'PNS0000007'),
    ('BKB0000003', 'Filosofi Teras (New Cover)', 'Pengantar praktis filosofi Stoik tentang memilah hal yang dapat dikendalikan dan menyikapi emosi dalam kehidupan sehari-hari.', 0, 'BKC0000002', 'BKP0000003', 'PNS0000004'),
    ('BKB0000004', 'Atomic Habits: Perubahan Kecil yang Memberikan Hasil Luar Biasa', 'Panduan membangun kebiasaan melalui langkah kecil, lingkungan yang mendukung, dan rutinitas yang konsisten.', 6, 'BKC0000003', 'BKP0000004', 'PNS0000005'),
    ('BKB0000005', 'Hujan', 'Setelah bencana besar mengubah dunia, Lail menelusuri kembali kenangan, kehilangan, dan pilihan-pilihan yang membentuk hidupnya.', 4, 'BKC0000001', 'BKP0000004', 'PNS0000009'),
    ('BKB0000006', 'Laskar Pelangi Edisi ke-50', 'Sekelompok anak di Belitung mengejar pendidikan bersama guru dan teman-teman, meski harus menghadapi banyak keterbatasan.', 8, 'BKC0000001', 'BKP0000005', 'PNS0000002'),
    ('BKB0000007', 'Gadis Kretek', 'Pencarian seorang anak atas perempuan dari masa lalu ayahnya membuka kisah keluarga dan dunia industri kretek.', 3, 'BKC0000001', 'BKP0000004', 'PNS0000008'),
    ('BKB0000008', 'Negeri 5 Menara (2025)', 'Alif menjalani kehidupan di pesantren bersama sahabat-sahabatnya dan belajar berpegang pada cita-cita serta ketekunan.', 5, 'BKC0000001', 'BKP0000004', 'PNS0000001'),
    ('BKB0000009', 'Perahu Kertas', 'Kugy dan Keenan berusaha mengejar cita-cita seni sekaligus memahami hubungan mereka yang tumbuh selama bertahun-tahun.', 4, 'BKC0000001', 'BKP0000005', 'PNS0000003'),
    ('BKB0000010', 'Pulang (2023)', 'Dimas Suryo dan keluarganya menata kehidupan jauh dari tanah air sambil memelihara ingatan dan rasa keterhubungan.', 2, 'BKC0000001', 'BKP0000001', 'PNS0000006'),
    ('DMB0000001', 'Senja di Perpustakaan', NULL, 3, 'DMC0000001', 'DME0000001', 'DWA0000001'),
    ('DMB0000002', 'Peta Rahasia Nusantara', NULL, 3, 'DMC0000002', 'DME0000003', 'DWA0000002'),
    ('DMB0000003', 'Eksperimen Kecil di Rumah', NULL, 4, 'DMC0000003', 'DME0000002', 'DWA0000003'),
    ('DMB0000004', 'Jejak Kota Lama', NULL, 3, 'DMC0000004', 'DME0000001', 'DWA0000004'),
    ('DMB0000005', 'Kebiasaan Baik Setiap Hari', NULL, 3, 'DMC0000005', 'DME0000002', 'DWA0000005'),
    ('DMB0000006', 'Hujan di Atas Atap', NULL, 2, 'DMC0000001', 'DME0000003', 'DWA0000006'),
    ('DMB0000007', 'Pulau di Balik Awan', NULL, 2, 'DMC0000002', 'DME0000001', 'DWA0000007'),
    ('DMB0000008', 'Mengenal Langit Malam', NULL, 2, 'DMC0000003', 'DME0000002', 'DWA0000008'),
    ('DMB0000009', 'Kisah dari Pelabuhan Tua', NULL, 2, 'DMC0000004', 'DME0000003', 'DWA0000009'),
    ('DMB0000010', 'Berani Mencoba Hal Baru', NULL, 1, 'DMC0000005', 'DME0000001', 'DWA0000010');

INSERT INTO `peminjaman` (`id_peminjaman`, `NIS`, `id_petugas`, `tgl_peminjam`, `tgl_jatuh_tempo`, `status`) VALUES
    ('P000000001', '12345', 'SYS', '2026-11-06', '2026-11-13', 'kembali'),
    ('P000000002', '12345', 'SYS', '2026-11-10', '2026-11-17', 'kembali'),
    ('P000000003', '12345', 'SYS', '2026-11-10', '2026-11-17', 'dipinjam');

INSERT INTO `detail_peminjaman` (`id_detail`, `id_peminjaman`, `id_buku`, `tgl_kembali`, `tgl_pengajuan_kembali`, `denda`) VALUES
    ('D000000001', 'P000000001', 'BKB0000007', '2026-11-09', NULL, '0.00'),
    ('D000000002', 'P000000002', 'DMB0000010', '2026-11-10', NULL, '0.00'),
    ('D000000003', 'P000000003', 'DMB0000010', NULL, NULL, '0.00');

INSERT INTO `preferensi_user` (`NIS`, `foto_profil`, `tema`, `warna`, `banner`, `banner_foto`, `bio`) VALUES
    ('12345', NULL, 'dark', 'teal', '#9581cd', NULL, NULL);
