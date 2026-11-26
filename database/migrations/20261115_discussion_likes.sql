CREATE TABLE IF NOT EXISTS `diskusi_suka` (
    `message_id` BIGINT UNSIGNED NOT NULL,
    `liker_type` ENUM('member', 'staff') NOT NULL,
    `liker_id` VARCHAR(30) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`message_id`, `liker_type`, `liker_id`),
    KEY `idx_diskusi_suka_liker` (`liker_type`, `liker_id`),
    CONSTRAINT `fk_diskusi_suka_message` FOREIGN KEY (`message_id`)
        REFERENCES `diskusi` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
