ALTER TABLE `laporan_diskusi`
    ADD COLUMN `reason` TEXT DEFAULT NULL AFTER `reporter_name`;
