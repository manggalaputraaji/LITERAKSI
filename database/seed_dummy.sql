-- Repeatable demo data for local development.
-- Demo login: username demo_siswa_1, password Demo123!
START TRANSACTION;

INSERT INTO kategori (id_kategori, kategori) VALUES
    ('DMC0000001', 'Fiksi'),
    ('DMC0000002', 'Fantasi'),
    ('DMC0000003', 'Sains'),
    ('DMC0000004', 'Sejarah'),
    ('DMC0000005', 'Pengembangan Diri')
ON DUPLICATE KEY UPDATE kategori = VALUES(kategori);

INSERT INTO penerbit (id_penerbit, nama_penerbit) VALUES
    ('DME0000001', 'Pustaka Demo'),
    ('DME0000002', 'Cakrawala Ilmu'),
    ('DME0000003', 'Bintang Literasi')
ON DUPLICATE KEY UPDATE nama_penerbit = VALUES(nama_penerbit);

INSERT INTO buku (id_buku, judul, id_kategori, id_penerbit) VALUES
    ('DMB0000001', 'Senja di Perpustakaan', 'DMC0000001', 'DME0000001'),
    ('DMB0000002', 'Peta Rahasia Nusantara', 'DMC0000002', 'DME0000003'),
    ('DMB0000003', 'Eksperimen Kecil di Rumah', 'DMC0000003', 'DME0000002'),
    ('DMB0000004', 'Jejak Kota Lama', 'DMC0000004', 'DME0000001'),
    ('DMB0000005', 'Kebiasaan Baik Setiap Hari', 'DMC0000005', 'DME0000002'),
    ('DMB0000006', 'Hujan di Atas Atap', 'DMC0000001', 'DME0000003'),
    ('DMB0000007', 'Pulau di Balik Awan', 'DMC0000002', 'DME0000001'),
    ('DMB0000008', 'Mengenal Langit Malam', 'DMC0000003', 'DME0000002'),
    ('DMB0000009', 'Kisah dari Pelabuhan Tua', 'DMC0000004', 'DME0000003'),
    ('DMB0000010', 'Berani Mencoba Hal Baru', 'DMC0000005', 'DME0000001')
ON DUPLICATE KEY UPDATE
    judul = VALUES(judul),
    id_kategori = VALUES(id_kategori),
    id_penerbit = VALUES(id_penerbit);

INSERT INTO petugas (id_petugas, nama, username, password) VALUES
    ('DMT0000001', 'Petugas Demo', NULL, NULL)
ON DUPLICATE KEY UPDATE nama = VALUES(nama);

INSERT INTO siswa (NIS, username, password, nama, kelas, email) VALUES
    (990000001, 'demo_siswa_1', '$2y$10$NVnPJmKZ9FAN1exRTSMonOTL2pgpAPgkGcTaGVy805e.kLJkruk8C', 'Alya Demo', 'X-1', 'demo_siswa_1@literaksi.test'),
    (990000002, 'demo_siswa_2', '$2y$10$NVnPJmKZ9FAN1exRTSMonOTL2pgpAPgkGcTaGVy805e.kLJkruk8C', 'Bima Demo', 'X-2', 'demo_siswa_2@literaksi.test'),
    (990000003, 'demo_siswa_3', '$2y$10$NVnPJmKZ9FAN1exRTSMonOTL2pgpAPgkGcTaGVy805e.kLJkruk8C', 'Citra Demo', 'XI-1', 'demo_siswa_3@literaksi.test'),
    (990000004, 'demo_siswa_4', '$2y$10$NVnPJmKZ9FAN1exRTSMonOTL2pgpAPgkGcTaGVy805e.kLJkruk8C', 'Danu Demo', 'XI-2', 'demo_siswa_4@literaksi.test'),
    (990000005, 'demo_siswa_5', '$2y$10$NVnPJmKZ9FAN1exRTSMonOTL2pgpAPgkGcTaGVy805e.kLJkruk8C', 'Eka Demo', 'XII-1', 'demo_siswa_5@literaksi.test'),
    (990000006, 'demo_siswa_6', '$2y$10$NVnPJmKZ9FAN1exRTSMonOTL2pgpAPgkGcTaGVy805e.kLJkruk8C', 'Fajar Demo', 'XII-2', 'demo_siswa_6@literaksi.test'),
    (990000007, 'demo_siswa_7', '$2y$10$NVnPJmKZ9FAN1exRTSMonOTL2pgpAPgkGcTaGVy805e.kLJkruk8C', 'Gita Demo', 'X-3', 'demo_siswa_7@literaksi.test'),
    (990000008, 'demo_siswa_8', '$2y$10$NVnPJmKZ9FAN1exRTSMonOTL2pgpAPgkGcTaGVy805e.kLJkruk8C', 'Hadi Demo', 'XI-3', 'demo_siswa_8@literaksi.test')
ON DUPLICATE KEY UPDATE NIS = NIS;

INSERT INTO peminjaman (id_peminjaman, NIS, id_petugas, tgl_peminjam, tgl_jatuh_tempo, status) VALUES
    ('DMP0000001', 990000001, 'DMT0000001', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'dipinjam'),
    ('DMP0000002', 990000002, 'DMT0000001', DATE_SUB(CURDATE(), INTERVAL 14 DAY), DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'dipinjam'),
    ('DMP0000003', 990000003, 'DMT0000001', DATE_SUB(CURDATE(), INTERVAL 6 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'dipinjam'),
    ('DMP0000004', 990000004, 'DMT0000001', DATE_SUB(CURDATE(), INTERVAL 14 DAY), DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'dikembalikan'),
    ('DMP0000005', 990000005, 'DMT0000001', DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'dipinjam'),
    ('DMP0000006', 990000006, 'DMT0000001', DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'dikembalikan'),
    ('DMP0000007', 990000001, 'DMT0000001', DATE(CONCAT(YEAR(CURDATE()), '-01-03')), DATE(CONCAT(YEAR(CURDATE()), '-01-10')), 'dikembalikan'),
    ('DMP0000008', 990000002, 'DMT0000001', DATE(CONCAT(YEAR(CURDATE()), '-02-03')), DATE(CONCAT(YEAR(CURDATE()), '-02-10')), 'dikembalikan'),
    ('DMP0000009', 990000003, 'DMT0000001', DATE(CONCAT(YEAR(CURDATE()), '-03-03')), DATE(CONCAT(YEAR(CURDATE()), '-03-10')), 'dikembalikan'),
    ('DMP0000010', 990000004, 'DMT0000001', DATE(CONCAT(YEAR(CURDATE()), '-04-03')), DATE(CONCAT(YEAR(CURDATE()), '-04-10')), 'dikembalikan'),
    ('DMP0000011', 990000005, 'DMT0000001', DATE(CONCAT(YEAR(CURDATE()), '-05-03')), DATE(CONCAT(YEAR(CURDATE()), '-05-10')), 'dikembalikan'),
    ('DMP0000012', 990000006, 'DMT0000001', DATE(CONCAT(YEAR(CURDATE()), '-06-03')), DATE(CONCAT(YEAR(CURDATE()), '-06-10')), 'dikembalikan'),
    ('DMP0000013', 990000007, 'DMT0000001', DATE(CONCAT(YEAR(CURDATE()), '-07-03')), DATE(CONCAT(YEAR(CURDATE()), '-07-10')), 'dikembalikan'),
    ('DMP0000014', 990000008, 'DMT0000001', DATE(CONCAT(YEAR(CURDATE()), '-08-03')), DATE(CONCAT(YEAR(CURDATE()), '-08-10')), 'dikembalikan'),
    ('DMP0000015', 990000001, 'DMT0000001', DATE(CONCAT(YEAR(CURDATE()), '-09-03')), DATE(CONCAT(YEAR(CURDATE()), '-09-10')), 'dikembalikan'),
    ('DMP0000016', 990000002, 'DMT0000001', DATE(CONCAT(YEAR(CURDATE()), '-10-03')), DATE(CONCAT(YEAR(CURDATE()), '-10-10')), 'dikembalikan'),
    ('DMP0000017', 990000003, 'DMT0000001', DATE(CONCAT(YEAR(CURDATE()), '-11-03')), DATE(CONCAT(YEAR(CURDATE()), '-11-10')), 'dipinjam'),
    ('DMP0000018', 990000004, 'DMT0000001', DATE(CONCAT(YEAR(CURDATE()), '-12-03')), DATE(CONCAT(YEAR(CURDATE()), '-12-10')), 'dipinjam')
ON DUPLICATE KEY UPDATE
    NIS = VALUES(NIS),
    id_petugas = VALUES(id_petugas),
    tgl_peminjam = VALUES(tgl_peminjam),
    tgl_jatuh_tempo = VALUES(tgl_jatuh_tempo),
    status = VALUES(status);

INSERT INTO peminjaman (id_peminjaman, NIS, id_petugas, tgl_peminjam, tgl_jatuh_tempo, status)
SELECT CONCAT('DMP', LPAD(1000 + months.month_number * 10 + slots.slot_number, 7, '0')),
    990000001 + MOD(months.month_number + slots.slot_number - 2, 8),
    'DMT0000001',
    DATE(CONCAT(YEAR(CURDATE()), '-', LPAD(months.month_number, 2, '0'), '-', LPAD(2 + slots.slot_number * 2, 2, '0'))),
    DATE_ADD(DATE(CONCAT(YEAR(CURDATE()), '-', LPAD(months.month_number, 2, '0'), '-', LPAD(2 + slots.slot_number * 2, 2, '0'))), INTERVAL 7 DAY),
    CASE
        WHEN months.month_number < MONTH(CURDATE()) AND MOD(slots.slot_number, 3) <> 0 THEN 'dikembalikan'
        ELSE 'dipinjam'
    END
FROM (
    SELECT 1 AS month_number, 3 AS loan_count UNION ALL
    SELECT 2, 5 UNION ALL
    SELECT 3, 2 UNION ALL
    SELECT 4, 6 UNION ALL
    SELECT 5, 4 UNION ALL
    SELECT 6, 7 UNION ALL
    SELECT 7, 3 UNION ALL
    SELECT 8, 5 UNION ALL
    SELECT 9, 2 UNION ALL
    SELECT 10, 6 UNION ALL
    SELECT 11, 4 UNION ALL
    SELECT 12, 5
) AS months
CROSS JOIN (
    SELECT 1 AS slot_number UNION ALL
    SELECT 2 UNION ALL
    SELECT 3 UNION ALL
    SELECT 4 UNION ALL
    SELECT 5 UNION ALL
    SELECT 6 UNION ALL
    SELECT 7
) AS slots
WHERE slots.slot_number <= months.loan_count
ON DUPLICATE KEY UPDATE
    NIS = VALUES(NIS),
    id_petugas = VALUES(id_petugas),
    tgl_peminjam = VALUES(tgl_peminjam),
    tgl_jatuh_tempo = VALUES(tgl_jatuh_tempo),
    status = VALUES(status);

INSERT INTO detail_peminjaman
    (id_detail, id_peminjaman, id_buku, tgl_kembali, tgl_pengajuan_kembali, denda) VALUES
    ('DMD0000001', 'DMP0000001', 'DMB0000001', NULL, NULL, 0.00),
    ('DMD0000002', 'DMP0000002', 'DMB0000003', NULL, NULL, 0.00),
    ('DMD0000003', 'DMP0000003', 'DMB0000002', NULL, NOW(), 0.00),
    ('DMD0000004', 'DMP0000004', 'DMB0000004', DATE_SUB(CURDATE(), INTERVAL 4 DAY), NULL, 0.00),
    ('DMD0000005', 'DMP0000005', 'DMB0000005', NULL, NULL, 0.00),
    ('DMD0000006', 'DMP0000006', 'DMB0000006', DATE_SUB(CURDATE(), INTERVAL 4 DAY), NULL, 0.00),
    ('DMD0000007', 'DMP0000007', 'DMB0000007', DATE(CONCAT(YEAR(CURDATE()), '-01-07')), NULL, 0.00),
    ('DMD0000008', 'DMP0000008', 'DMB0000008', DATE(CONCAT(YEAR(CURDATE()), '-02-07')), NULL, 0.00),
    ('DMD0000009', 'DMP0000009', 'DMB0000009', DATE(CONCAT(YEAR(CURDATE()), '-03-07')), NULL, 0.00),
    ('DMD0000010', 'DMP0000010', 'DMB0000010', DATE(CONCAT(YEAR(CURDATE()), '-04-07')), NULL, 0.00),
    ('DMD0000011', 'DMP0000011', 'DMB0000001', DATE(CONCAT(YEAR(CURDATE()), '-05-07')), NULL, 0.00),
    ('DMD0000012', 'DMP0000012', 'DMB0000002', DATE(CONCAT(YEAR(CURDATE()), '-06-07')), NULL, 0.00),
    ('DMD0000013', 'DMP0000013', 'DMB0000003', DATE(CONCAT(YEAR(CURDATE()), '-07-07')), NULL, 0.00),
    ('DMD0000014', 'DMP0000014', 'DMB0000004', DATE(CONCAT(YEAR(CURDATE()), '-08-07')), NULL, 0.00),
    ('DMD0000015', 'DMP0000015', 'DMB0000005', DATE(CONCAT(YEAR(CURDATE()), '-09-07')), NULL, 0.00),
    ('DMD0000016', 'DMP0000016', 'DMB0000006', DATE(CONCAT(YEAR(CURDATE()), '-10-07')), NULL, 0.00),
    ('DMD0000017', 'DMP0000017', 'DMB0000007', NULL, NULL, 0.00),
    ('DMD0000018', 'DMP0000018', 'DMB0000008', NULL, NULL, 0.00)
ON DUPLICATE KEY UPDATE
    id_peminjaman = VALUES(id_peminjaman),
    id_buku = VALUES(id_buku),
    tgl_kembali = VALUES(tgl_kembali),
    tgl_pengajuan_kembali = VALUES(tgl_pengajuan_kembali),
    denda = VALUES(denda);

INSERT INTO detail_peminjaman
    (id_detail, id_peminjaman, id_buku, tgl_kembali, tgl_pengajuan_kembali, denda)
SELECT CONCAT('DMD', LPAD(1000 + months.month_number * 10 + slots.slot_number, 7, '0')),
    CONCAT('DMP', LPAD(1000 + months.month_number * 10 + slots.slot_number, 7, '0')),
    CONCAT('DMB', LPAD(1 + MOD(months.month_number + slots.slot_number - 2, 10), 7, '0')),
    CASE
        WHEN months.month_number < MONTH(CURDATE()) AND MOD(slots.slot_number, 3) <> 0
            THEN DATE_ADD(DATE(CONCAT(YEAR(CURDATE()), '-', LPAD(months.month_number, 2, '0'), '-', LPAD(2 + slots.slot_number * 2, 2, '0'))), INTERVAL 7 DAY)
        ELSE NULL
    END,
    NULL,
    0.00
FROM (
    SELECT 1 AS month_number, 3 AS loan_count UNION ALL
    SELECT 2, 5 UNION ALL
    SELECT 3, 2 UNION ALL
    SELECT 4, 6 UNION ALL
    SELECT 5, 4 UNION ALL
    SELECT 6, 7 UNION ALL
    SELECT 7, 3 UNION ALL
    SELECT 8, 5 UNION ALL
    SELECT 9, 2 UNION ALL
    SELECT 10, 6 UNION ALL
    SELECT 11, 4 UNION ALL
    SELECT 12, 5
) AS months
CROSS JOIN (
    SELECT 1 AS slot_number UNION ALL
    SELECT 2 UNION ALL
    SELECT 3 UNION ALL
    SELECT 4 UNION ALL
    SELECT 5 UNION ALL
    SELECT 6 UNION ALL
    SELECT 7
) AS slots
WHERE slots.slot_number <= months.loan_count
ON DUPLICATE KEY UPDATE
    id_peminjaman = VALUES(id_peminjaman),
    id_buku = VALUES(id_buku),
    tgl_kembali = VALUES(tgl_kembali),
    tgl_pengajuan_kembali = VALUES(tgl_pengajuan_kembali),
    denda = VALUES(denda);

INSERT INTO postingan (NIS, isi)
SELECT '990000001', 'Saya baru menemukan buku yang menarik untuk dibaca minggu ini.'
WHERE NOT EXISTS (
    SELECT 1 FROM postingan
    WHERE NIS = '990000001'
      AND isi = 'Saya baru menemukan buku yang menarik untuk dibaca minggu ini.'
);

INSERT INTO postingan (NIS, isi)
SELECT '990000002', 'Membaca sedikit demi sedikit tetap membuat hari terasa lebih bermakna.'
WHERE NOT EXISTS (
    SELECT 1 FROM postingan
    WHERE NIS = '990000002'
      AND isi = 'Membaca sedikit demi sedikit tetap membuat hari terasa lebih bermakna.'
);

INSERT INTO postingan (NIS, isi)
SELECT '990000003', 'Rekomendasi buku sains untuk teman-teman yang suka bereksperimen.'
WHERE NOT EXISTS (
    SELECT 1 FROM postingan
    WHERE NIS = '990000003'
      AND isi = 'Rekomendasi buku sains untuk teman-teman yang suka bereksperimen.'
);

COMMIT;
