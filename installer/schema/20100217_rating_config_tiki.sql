CREATE TABLE IF NOT EXISTS `tiki_rating_configs` (
	`ratingConfigId` INT PRIMARY KEY AUTO_INCREMENT,
	`name` VARCHAR(50) NOT NULL,
	`expiry` INT NOT NULL DEFAULT 3600,
	`formula` TEXT NOT NULL,
	`callbacks` TEXT
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `tiki_rating_obtained` (
	`ratingId` INT PRIMARY KEY AUTO_INCREMENT,
	`ratingConfigId` INT NOT NULL,
	`type` VARCHAR(50) NOT NULL,
	`object` INT NOT NULL,
	`expire` INT NOT NULL,
	`value` FLOAT NOT NULL,
	UNIQUE `tiki_obtained_rating_uq` (`type`, `object`, `ratingConfigId`)
) ENGINE=MyISAM;
