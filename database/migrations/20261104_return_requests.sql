ALTER TABLE detail_peminjaman
    ADD COLUMN tgl_pengajuan_kembali DATETIME NULL DEFAULT NULL AFTER tgl_kembali;
