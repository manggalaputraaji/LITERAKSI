-- Adminer 6.3.0 MariaDB 11.4.13-MariaDB-log dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP DATABASE IF EXISTS `literaksi`;
CREATE DATABASE `literaksi` /*!40100 DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci */;
USE `literaksi`;

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `id_petugas` varchar(30) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_petugas`),
  UNIQUE KEY `uq_admin_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admin` (`id_petugas`, `nama`, `username`, `password`) VALUES
('ADM0000001',	'Admin',	'admin',	'$2y$10$ufmTNNhyRsIUfnzmEeVOTOs.fByP.PvhB42o0xpItY2NhqA7U0Q6u'),
('SYS',	'Sistem',	NULL,	NULL);

DROP TABLE IF EXISTS `buku`;
CREATE TABLE `buku` (
  `id_buku` varchar(30) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `sinopsis` mediumtext DEFAULT NULL,
  `stok` int(10) unsigned NOT NULL DEFAULT 0,
  `id_kategori` varchar(30) DEFAULT NULL,
  `id_penerbit` varchar(30) DEFAULT NULL,
  `id_penulis` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id_buku`),
  KEY `idx_buku_judul` (`judul`),
  KEY `idx_buku_kategori` (`id_kategori`),
  KEY `idx_buku_penerbit` (`id_penerbit`),
  KEY `idx_buku_penulis` (`id_penulis`),
  CONSTRAINT `fk_buku_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_buku_penerbit` FOREIGN KEY (`id_penerbit`) REFERENCES `penerbit` (`id_penerbit`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_buku_penulis` FOREIGN KEY (`id_penulis`) REFERENCES `penulis` (`id_penulis`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `buku` (`id_buku`, `judul`, `sinopsis`, `stok`, `id_kategori`, `id_penerbit`, `id_penulis`) VALUES
('BKB0000001',	'Laut Bercerita',	'Sebuah keluarga menanti kabar seorang mahasiswa aktivis yang hilang, sementara kenangan mereka membuka sisi personal dari masa penuh gejolak.',	5,	'BKC0000001',	'BKP0000001',	'PNS0000006'),
('BKB0000002',	'Bumi Manusia',	'Minke, seorang pemuda pribumi pada masa kolonial, berhadapan dengan batas kelas, pendidikan, cinta, dan ketidakadilan.',	4,	'BKC0000001',	'BKP0000002',	'PNS0000007'),
('BKB0000003',	'Filosofi Teras (New Cover)',	'Pengantar praktis filosofi Stoik tentang memilah hal yang dapat dikendalikan dan menyikapi emosi dalam kehidupan sehari-hari.',	0,	NULL,	'BKP0000003',	'PNS0000004'),
('BKB0000004',	'Atomic Habits: Perubahan Kecil yang Memberikan Hasil Luar Biasa',	'Panduan membangun kebiasaan melalui langkah kecil, lingkungan yang mendukung, dan rutinitas yang konsisten.',	6,	'BKC0000003',	'BKP0000004',	'PNS0000005'),
('BKB0000005',	'Hujan',	'Setelah bencana besar mengubah dunia, Lail menelusuri kembali kenangan, kehilangan, dan pilihan-pilihan yang membentuk hidupnya.',	4,	'BKC0000001',	'BKP0000004',	'PNS0000009'),
('BKB0000006',	'Laskar Pelangi Edisi ke-50',	'Sekelompok anak di Belitung mengejar pendidikan bersama guru dan teman-teman, meski harus menghadapi banyak keterbatasan.',	8,	'BKC0000001',	'BKP0000005',	'PNS0000002'),
('BKB0000007',	'Gadis Kretek',	'Pencarian seorang anak atas perempuan dari masa lalu ayahnya membuka kisah keluarga dan dunia industri kretek.',	3,	'BKC0000001',	'BKP0000004',	'PNS0000008'),
('BKB0000008',	'Negeri 5 Menara (2025)',	'Alif menjalani kehidupan di pesantren bersama sahabat-sahabatnya dan belajar berpegang pada cita-cita serta ketekunan.',	5,	'BKC0000001',	'BKP0000004',	'PNS0000001'),
('BKB0000009',	'Perahu Kertas',	'Kugy dan Keenan berusaha mengejar cita-cita seni sekaligus memahami hubungan mereka yang tumbuh selama bertahun-tahun.',	4,	'BKC0000001',	'BKP0000005',	'PNS0000003'),
('BKB0000010',	'Pulang (2023)',	'Dimas Suryo dan keluarganya menata kehidupan jauh dari tanah air sambil memelihara ingatan dan rasa keterhubungan.',	2,	'BKC0000001',	'BKP0000001',	'PNS0000006'),
('DMB0000001',	'Senja di Perpustakaan',	NULL,	3,	'DMC0000001',	'DME0000001',	'DWA0000001'),
('DMB0000002',	'Peta Rahasia Nusantara',	NULL,	3,	'DMC0000002',	'DME0000003',	'DWA0000002'),
('DMB0000003',	'Eksperimen Kecil di Rumah',	NULL,	4,	'DMC0000003',	'DME0000002',	'DWA0000003'),
('DMB0000004',	'Jejak Kota Lama',	NULL,	3,	'DMC0000004',	'DME0000001',	'DWA0000004'),
('DMB0000005',	'Kebiasaan Baik Setiap Hari',	NULL,	3,	'DMC0000005',	'DME0000002',	'DWA0000005'),
('DMB0000006',	'Hujan di Atas Atap',	NULL,	2,	'DMC0000001',	'DME0000003',	'DWA0000006'),
('DMB0000007',	'Pulau di Balik Awan',	NULL,	2,	'DMC0000002',	'DME0000001',	'DWA0000007'),
('DMB0000008',	'Mengenal Langit Malam',	NULL,	2,	'DMC0000003',	'DME0000002',	'DWA0000008'),
('DMB0000009',	'Kisah dari Pelabuhan Tua',	NULL,	2,	'DMC0000004',	'DME0000003',	'DWA0000009'),
('DMB0000010',	'Berani Mencoba Hal Baru',	NULL,	1,	'DMC0000005',	'DME0000001',	'DWA0000010');

DROP TABLE IF EXISTS `data_matrix_tokens`;
CREATE TABLE `data_matrix_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_detail` varchar(30) NOT NULL,
  `purpose` enum('ambil','kembali') NOT NULL,
  `token_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_data_matrix_token_hash` (`token_hash`),
  KEY `idx_data_matrix_detail_purpose` (`id_detail`,`purpose`,`used_at`),
  CONSTRAINT `fk_data_matrix_detail` FOREIGN KEY (`id_detail`) REFERENCES `detail_peminjaman` (`id_detail`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `data_matrix_tokens` (`id`, `id_detail`, `purpose`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES
(6,	'D000000012',	'ambil',	'57b82beab01ac5446f557e39a630ef3fc54d13a2b5fdf01cbfa88378bfb4494b',	'2026-11-18 05:58:43',	'2026-11-17 06:01:45',	'2026-11-16 22:58:43'),
(7,	'D000000012',	'kembali',	'e93e05ce86c01c159ebf6c2801cce33844127575417b63b6982c8405ccddf009',	'2026-11-17 06:17:39',	'2026-11-17 06:02:52',	'2026-11-16 23:02:39');

DROP TABLE IF EXISTS `detail_peminjaman`;
CREATE TABLE `detail_peminjaman` (
  `id_detail` varchar(30) NOT NULL,
  `id_peminjaman` varchar(30) NOT NULL,
  `id_buku` varchar(30) NOT NULL,
  `tgl_kembali` date DEFAULT NULL,
  `tgl_pengajuan_kembali` datetime DEFAULT NULL,
  `denda` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id_detail`),
  KEY `idx_detail_peminjaman` (`id_peminjaman`),
  KEY `idx_detail_buku` (`id_buku`),
  KEY `idx_detail_kembali` (`tgl_kembali`),
  KEY `idx_detail_pengajuan` (`tgl_pengajuan_kembali`),
  CONSTRAINT `fk_detail_buku` FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id_buku`) ON UPDATE CASCADE,
  CONSTRAINT `fk_detail_peminjaman` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `detail_peminjaman` (`id_detail`, `id_peminjaman`, `id_buku`, `tgl_kembali`, `tgl_pengajuan_kembali`, `denda`) VALUES
('D000000001',	'P000000001',	'BKB0000007',	'2026-11-09',	NULL,	0.00),
('D000000002',	'P000000002',	'DMB0000010',	'2026-11-10',	NULL,	0.00),
('D000000003',	'P000000003',	'DMB0000010',	'2026-11-17',	NULL,	0.00),
('D000000004',	'P000000004',	'BKB0000004',	NULL,	NULL,	0.00),
('D000000005',	'P000000005',	'BKB0000007',	NULL,	NULL,	0.00),
('D000000006',	'P000000006',	'BKB0000001',	'2026-11-17',	NULL,	0.00),
('D000000007',	'P000000007',	'DMB0000003',	'2026-11-17',	NULL,	0.00),
('D000000008',	'P000000008',	'BKB0000004',	'2026-11-17',	NULL,	0.00),
('D000000009',	'P000000009',	'DMB0000009',	'2026-11-17',	NULL,	0.00),
('D000000010',	'P000000010',	'DMB0000008',	'2026-11-17',	NULL,	0.00),
('D000000011',	'P000000011',	'DMB0000010',	NULL,	NULL,	0.00),
('D000000012',	'P000000012',	'BKB0000004',	'2026-11-17',	NULL,	0.00);

DROP TABLE IF EXISTS `diskusi`;
CREATE TABLE `diskusi` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `jenis_entri` enum('pesan','suka') NOT NULL DEFAULT 'pesan',
  `jenis_pengirim` enum('user','admin') NOT NULL,
  `id_pengirim_user` varchar(30) DEFAULT NULL,
  `id_pengirim_admin` varchar(30) DEFAULT NULL,
  `id_penyuka_user` varchar(30) DEFAULT NULL,
  `id_penyuka_admin` varchar(30) DEFAULT NULL,
  `sender_name` varchar(150) NOT NULL,
  `message` text DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_diskusi_suka_user` (`parent_id`,`id_penyuka_user`),
  UNIQUE KEY `uq_diskusi_suka_admin` (`parent_id`,`id_penyuka_admin`),
  KEY `idx_diskusi_pengirim_user` (`id_pengirim_user`),
  KEY `idx_diskusi_pengirim_admin` (`id_pengirim_admin`),
  KEY `idx_diskusi_penyuka_user` (`id_penyuka_user`),
  KEY `idx_diskusi_penyuka_admin` (`id_penyuka_admin`),
  KEY `idx_diskusi_created` (`created_at`,`id`),
  KEY `idx_diskusi_parent` (`parent_id`),
  CONSTRAINT `fk_diskusi_admin` FOREIGN KEY (`id_pengirim_admin`) REFERENCES `admin` (`id_petugas`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_diskusi_parent` FOREIGN KEY (`parent_id`) REFERENCES `diskusi` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_diskusi_penyuka_admin` FOREIGN KEY (`id_penyuka_admin`) REFERENCES `admin` (`id_petugas`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_diskusi_penyuka_user` FOREIGN KEY (`id_penyuka_user`) REFERENCES `user` (`NIS`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_diskusi_user` FOREIGN KEY (`id_pengirim_user`) REFERENCES `user` (`NIS`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `diskusi` (`id`, `jenis_entri`, `jenis_pengirim`, `id_pengirim_user`, `id_pengirim_admin`, `id_penyuka_user`, `id_penyuka_admin`, `sender_name`, `message`, `parent_id`, `created_at`) VALUES
(1,	'pesan',	'user',	'12345',	NULL,	NULL,	NULL,	'Bara Manggala Aji Putra',	'check',	NULL,	'2026-11-15 02:37:51'),
(2,	'pesan',	'user',	'12345',	NULL,	NULL,	NULL,	'Bara Manggala Aji Putra',	'Oyabanggg',	1,	'2026-11-15 02:37:59'),
(3,	'pesan',	'admin',	NULL,	'ADM0000001',	NULL,	NULL,	'Admin',	'OYABANGG',	NULL,	'2026-11-15 02:46:45'),
(4,	'pesan',	'user',	'1234',	NULL,	NULL,	NULL,	'FAIRUZ GAMING',	'assalamualaikum masyaallah keren sekali anak saya jadi rajin membaca',	NULL,	'2026-11-15 09:34:13'),
(5,	'suka',	'user',	NULL,	NULL,	'12345',	NULL,	'__LITERAKSI_DISLIKE__',	NULL,	4,	'2026-11-16 09:13:56'),
(6,	'suka',	'user',	NULL,	NULL,	'12345',	NULL,	'__LITERAKSI_DISLIKE__',	NULL,	3,	'2026-11-16 09:13:57');

DROP TABLE IF EXISTS `kategori`;
CREATE TABLE `kategori` (
  `id_kategori` varchar(30) NOT NULL,
  `kategori` varchar(100) NOT NULL,
  PRIMARY KEY (`id_kategori`),
  KEY `idx_kategori_name` (`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `kategori` (`id_kategori`, `kategori`) VALUES
('DMC0000002',	'Fantasi'),
('DMC0000001',	'Fiksi'),
('BKC0000002',	'Filsafat'),
('BKC0000001',	'Novel'),
('BKC0000003',	'Pengembangan Diri'),
('DMC0000005',	'Pengembangan Diri'),
('DMC0000003',	'Sains'),
('DMC0000004',	'Sejarah');

DROP TABLE IF EXISTS `laporan_diskusi`;
CREATE TABLE `laporan_diskusi` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint(20) unsigned DEFAULT NULL,
  `sender_name` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `jenis_pelapor` enum('user','admin') NOT NULL,
  `id_pelapor_user` varchar(30) DEFAULT NULL,
  `id_pelapor_admin` varchar(30) DEFAULT NULL,
  `reporter_name` varchar(150) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('menunggu','ditinjau','dihapus') NOT NULL DEFAULT 'menunggu',
  `reported_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_laporan_diskusi_user` (`message_id`,`id_pelapor_user`),
  UNIQUE KEY `uq_laporan_diskusi_admin` (`message_id`,`id_pelapor_admin`),
  KEY `idx_laporan_diskusi_status` (`status`,`reported_at`),
  KEY `fk_laporan_diskusi_user` (`id_pelapor_user`),
  KEY `fk_laporan_diskusi_admin` (`id_pelapor_admin`),
  CONSTRAINT `fk_laporan_diskusi_admin` FOREIGN KEY (`id_pelapor_admin`) REFERENCES `admin` (`id_petugas`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_laporan_diskusi_message` FOREIGN KEY (`message_id`) REFERENCES `diskusi` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_laporan_diskusi_user` FOREIGN KEY (`id_pelapor_user`) REFERENCES `user` (`NIS`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `laporan_diskusi` (`id`, `message_id`, `sender_name`, `message`, `jenis_pelapor`, `id_pelapor_user`, `id_pelapor_admin`, `reporter_name`, `reason`, `status`, `reported_at`) VALUES
(1,	3,	'Admin',	'OYABANGG',	'user',	'12345',	NULL,	'Bara Manggala Aji Putra',	'Spam atau konten menyesatkan',	'ditinjau',	'2026-11-15 02:48:03');

DROP TABLE IF EXISTS `notifikasi_admin`;
CREATE TABLE `notifikasi_admin` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_petugas` varchar(30) NOT NULL,
  `id_peminjaman` varchar(30) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notifikasi_admin_peminjaman` (`id_petugas`,`id_peminjaman`),
  KEY `idx_notifikasi_admin_unread` (`id_petugas`,`read_at`,`id`),
  KEY `fk_notifikasi_admin_peminjaman` (`id_peminjaman`),
  CONSTRAINT `fk_notifikasi_admin_peminjaman` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_notifikasi_admin_petugas` FOREIGN KEY (`id_petugas`) REFERENCES `admin` (`id_petugas`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notifikasi_admin` (`id`, `id_petugas`, `id_peminjaman`, `created_at`, `read_at`) VALUES
(11,	'ADM0000001',	'P000000012',	'2026-11-16 22:58:43',	NULL);

DROP TABLE IF EXISTS `peminjaman`;
CREATE TABLE `peminjaman` (
  `id_peminjaman` varchar(30) NOT NULL,
  `NIS` varchar(30) NOT NULL,
  `id_petugas` varchar(30) NOT NULL DEFAULT 'SYS',
  `tgl_peminjam` date NOT NULL,
  `tgl_jatuh_tempo` date NOT NULL,
  `status` enum('menunggu_pengambilan','dipinjam','kembali','dibatalkan') NOT NULL DEFAULT 'dipinjam',
  `reservation_expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_peminjaman`),
  KEY `idx_peminjaman_nis` (`NIS`),
  KEY `idx_peminjaman_admin` (`id_petugas`),
  KEY `idx_peminjaman_tanggal` (`tgl_peminjam`),
  KEY `idx_peminjaman_tempo` (`tgl_jatuh_tempo`),
  KEY `idx_peminjaman_reservation` (`status`,`reservation_expires_at`),
  CONSTRAINT `fk_peminjaman_admin` FOREIGN KEY (`id_petugas`) REFERENCES `admin` (`id_petugas`) ON UPDATE CASCADE,
  CONSTRAINT `fk_peminjaman_user` FOREIGN KEY (`NIS`) REFERENCES `user` (`NIS`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `peminjaman` (`id_peminjaman`, `NIS`, `id_petugas`, `tgl_peminjam`, `tgl_jatuh_tempo`, `status`, `reservation_expires_at`) VALUES
('P000000001',	'12345',	'SYS',	'2026-11-06',	'2026-11-13',	'kembali',	NULL),
('P000000002',	'12345',	'SYS',	'2026-11-10',	'2026-11-17',	'kembali',	NULL),
('P000000003',	'12345',	'SYS',	'2026-11-10',	'2026-11-17',	'kembali',	NULL),
('P000000004',	'1234',	'SYS',	'2026-11-15',	'2026-11-22',	'dipinjam',	NULL),
('P000000005',	'010023546',	'SYS',	'2026-11-15',	'2026-11-22',	'dipinjam',	NULL),
('P000000006',	'12345',	'SYS',	'2026-11-17',	'2026-11-24',	'kembali',	NULL),
('P000000007',	'12345',	'SYS',	'2026-11-17',	'2026-11-24',	'kembali',	NULL),
('P000000008',	'12345',	'SYS',	'2026-11-17',	'2026-11-24',	'kembali',	NULL),
('P000000009',	'12345',	'SYS',	'2026-11-17',	'2026-11-24',	'kembali',	NULL),
('P000000010',	'12345',	'SYS',	'2026-11-17',	'2026-11-24',	'kembali',	NULL),
('P000000011',	'12345',	'SYS',	'2026-11-17',	'2026-11-24',	'dipinjam',	NULL),
('P000000012',	'12345',	'ADM0000001',	'2026-11-17',	'2026-11-24',	'kembali',	NULL);

DROP TABLE IF EXISTS `penerbit`;
CREATE TABLE `penerbit` (
  `id_penerbit` varchar(30) NOT NULL,
  `nama_penerbit` varchar(100) NOT NULL,
  PRIMARY KEY (`id_penerbit`),
  KEY `idx_penerbit_name` (`nama_penerbit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `penerbit` (`id_penerbit`, `nama_penerbit`) VALUES
('BKP0000005',	'Bentang Pustaka'),
('DME0000003',	'Bintang Literasi'),
('DME0000002',	'Cakrawala Ilmu'),
('BKP0000004',	'Gramedia Pustaka Utama'),
('BKP0000001',	'Kepustakaan Populer Gramedia'),
('BKP0000002',	'Lentera Dipantara'),
('BKP0000003',	'Penerbit Buku Kompas'),
('DME0000001',	'Pustaka Demo');

DROP TABLE IF EXISTS `penulis`;
CREATE TABLE `penulis` (
  `id_penulis` varchar(30) NOT NULL,
  `nama_penulis` varchar(150) NOT NULL,
  PRIMARY KEY (`id_penulis`),
  KEY `idx_penulis_name` (`nama_penulis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `penulis` (`id_penulis`, `nama_penulis`) VALUES
('PNS0000001',	'Ahmad Fuadi'),
('PNS0000002',	'Andrea Hirata'),
('DWA0000001',	'Ayu Pratiwi'),
('PNS0000010',	'Bara Manggala Aji Putra'),
('DWA0000002',	'Bima Santosa'),
('DWA0000003',	'Citra Lestari'),
('DWA0000004',	'Danu Wicaksono'),
('PNS0000003',	'Dee Lestari'),
('DWA0000005',	'Eka Purnama'),
('DWA0000006',	'Fajar Nugraha'),
('DWA0000007',	'Gita Maharani'),
('DWA0000008',	'Hadi Permana'),
('PNS0000004',	'Henry Manampiring'),
('DWA0000009',	'Indah Kirana'),
('PNS0000005',	'James Clear'),
('DWA0000010',	'Joko Prabowo'),
('PNS0000006',	'Leila S. Chudori'),
('PNS0000007',	'Pramoedya Ananta Toer'),
('PNS0000008',	'Ratih Kumala'),
('PNS0000009',	'Tere Liye');

DROP TABLE IF EXISTS `preferensi_user`;
CREATE TABLE `preferensi_user` (
  `NIS` varchar(30) NOT NULL,
  `foto_profil` varchar(64) DEFAULT NULL,
  `tema` varchar(16) NOT NULL DEFAULT 'auto',
  `warna` enum('violet','blue','teal','rose') NOT NULL DEFAULT 'violet',
  `banner` varchar(7) NOT NULL DEFAULT '#9581cd',
  `banner_foto` varchar(64) DEFAULT NULL,
  `bio` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`NIS`),
  CONSTRAINT `fk_preferensi_user` FOREIGN KEY (`NIS`) REFERENCES `user` (`NIS`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `preferensi_user` (`NIS`, `foto_profil`, `tema`, `warna`, `banner`, `banner_foto`, `bio`) VALUES
('010023546',	NULL,	'auto',	'teal',	'#9581cd',	NULL,	NULL),
('12345',	'f5d04b4c2a8c478f3782fba6c7269790.png',	'dark',	'blue',	'#9581cd',	'5cdf5ef2f9fb80f2de2ea70f98d2cf08.png',	'OYABANGG');

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `NIS` varchar(30) NOT NULL,
  `username` varchar(50) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `email` varchar(254) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`NIS`),
  UNIQUE KEY `uq_user_username` (`username`),
  UNIQUE KEY `uq_user_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `user` (`NIS`, `username`, `nama`, `email`, `password`, `kelas`) VALUES
('010023546',	'Rafid',	'Rafid Aditama',	'rafidaditama406@gmail.com',	'$2y$12$mPLuLNozo9KSWRZrqA4WVeunL8RxcvXSh51LP3y/RZ8IX7EAO3n92',	NULL),
('1234',	'fairuzaki',	'FAIRUZ GAMING',	'fairuzaki@gmail.com',	'$2y$12$MYCcbaW5i.t4XwF7YLIdFewXL.nezGh4ziScwqehQ5HwtpxRDrvDe',	NULL),
('12345',	'manggieez',	'Bara Manggala Aji Putra',	'baramanggala@literaksi.gov',	'$2y$10$GTVve8EMSTCVauukn6CIWeb7.kRw0Vh75DOyB9Ha3F3cXbo7I4Ylq',	'XI RPL A');

-- 2026-11-16 23:05:50 UTC