ALTER TABLE `tiki_webmail_contacts` ADD `uri` VARCHAR(200) NULL DEFAULT NULL;
ALTER TABLE `tiki_webmail_contacts` ADD INDEX `user-uri` (`user`(100), `uri`(91));
