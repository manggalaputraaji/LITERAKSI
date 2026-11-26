CREATE TABLE IF NOT EXISTS `laporan_diskusi` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `message_id` BIGINT UNSIGNED NOT NULL,
    `sender_name` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `reporter_type` ENUM('member', 'staff') NOT NULL,
    `reporter_id` VARCHAR(30) NOT NULL,
    `reporter_name` VARCHAR(150) NOT NULL,
    `status` ENUM('pending', 'dismissed', 'removed') NOT NULL DEFAULT 'pending',
    `reported_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_laporan_diskusi_reporter` (`message_id`, `reporter_type`, `reporter_id`),
    KEY `idx_laporan_diskusi_status` (`status`, `reported_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
