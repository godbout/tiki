CREATE TABLE `tiki_address_books` (
    `addressBookId` INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `user` VARCHAR(200),
    `name` VARCHAR(255),
    `uri` VARBINARY(200),
    `description` TEXT,
    UNIQUE(`user`(141), `uri`)
) ENGINE=MyISAM;

CREATE TABLE `tiki_address_cards` (
    `addressCardId` INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `addressBookId` INT UNSIGNED NOT NULL,
    `carddata` MEDIUMBLOB,
    `uri` VARBINARY(200),
    `lastmodified` INT(11) UNSIGNED,
    `etag` VARBINARY(32),
    `size` INT(11) UNSIGNED NOT NULL,
    INDEX(`addressBookId`, `uri`)
) ENGINE=MyISAM;
