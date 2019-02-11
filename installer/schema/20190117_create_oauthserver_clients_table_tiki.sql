CREATE TABLE IF NOT EXISTS `tiki_oauthserver_clients` (
    `identifier` INT(14) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(128) NOT NULL DEFAULT '',
    `client_id` VARCHAR(128) UNIQUE NOT NULL DEFAULT '',
    `client_secret` VARCHAR(255) NOT NULL DEFAULT '',
    `redirect_uri` VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (`identifier`)
) ENGINE=MyISAM AUTO_INCREMENT=1;
