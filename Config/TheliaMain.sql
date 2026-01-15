
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- customer_group
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `customer_group`;

CREATE TABLE `customer_group`
(
    `code` VARCHAR(45) NOT NULL,
    `is_default` TINYINT(1) DEFAULT 0 NOT NULL,
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    `position` INTEGER,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB COMMENT='The list of customer groups';

-- ---------------------------------------------------------------------
-- customer_customer_group
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `customer_customer_group`;

CREATE TABLE `customer_customer_group`
(
    `customer_id` INTEGER NOT NULL,
    `customer_group_id` INTEGER NOT NULL,
    PRIMARY KEY (`customer_id`,`customer_group_id`),
    INDEX `fi_customer_group_id` (`customer_group_id`),
    CONSTRAINT `fk_customer_id`
        FOREIGN KEY (`customer_id`)
        REFERENCES `customer` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,
    CONSTRAINT `fk_customer_group_id`
        FOREIGN KEY (`customer_group_id`)
        REFERENCES `customer_group` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='The list of customer groups';

-- ---------------------------------------------------------------------
-- customer_group_i18n
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `customer_group_i18n`;

CREATE TABLE `customer_group_i18n`
(
    `id` INTEGER NOT NULL,
    `locale` VARCHAR(5) DEFAULT 'en_US' NOT NULL,
    `title` VARCHAR(255),
    `description` LONGTEXT,
    PRIMARY KEY (`id`,`locale`),
    CONSTRAINT `customer_group_i18n_fk_6a2a90`
        FOREIGN KEY (`id`)
        REFERENCES `customer_group` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
