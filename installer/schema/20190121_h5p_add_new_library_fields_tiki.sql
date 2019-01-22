ALTER TABLE `tiki_h5p_libraries` ADD `has_icon` INT  UNSIGNED  NOT NULL  DEFAULT '0';
ALTER TABLE `tiki_h5p_libraries` ADD `metadata_settings` TEXT NULL;
ALTER TABLE `tiki_h5p_libraries` ADD `add_to` TEXT DEFAULT NULL;

ALTER TABLE `tiki_h5p_contents` CHANGE `author` `authors` MEDIUMTEXT NULL;
ALTER TABLE `tiki_h5p_contents` CHANGE `license` `license` VARCHAR(32) NULL DEFAULT NULL;

ALTER TABLE `tiki_h5p_contents` ADD `source` VARCHAR(2083) NULL;
ALTER TABLE `tiki_h5p_contents` ADD `year_from` INT UNSIGNED NULL;
ALTER TABLE `tiki_h5p_contents` ADD `year_to` INT UNSIGNED NULL;
ALTER TABLE `tiki_h5p_contents` ADD `license_version` VARCHAR(10) NULL;
ALTER TABLE `tiki_h5p_contents` ADD `license_extras` LONGTEXT NULL;
ALTER TABLE `tiki_h5p_contents` ADD `author_comments` LONGTEXT NULL;
ALTER TABLE `tiki_h5p_contents` ADD `changes` MEDIUMTEXT NULL;

