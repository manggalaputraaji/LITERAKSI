ALTER TABLE `pengaturan_anggota`
    MODIFY COLUMN `banner` VARCHAR(7) NOT NULL DEFAULT '#9581cd';

UPDATE `pengaturan_anggota`
SET `banner` = CASE `banner`
    WHEN 'violet' THEN '#9581cd'
    WHEN 'blue' THEN '#6d9ed0'
    WHEN 'teal' THEN '#5eaa97'
    WHEN 'rose' THEN '#ce7196'
    ELSE `banner`
END;
