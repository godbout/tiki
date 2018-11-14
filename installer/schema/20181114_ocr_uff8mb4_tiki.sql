ALTER TABLE `tiki_files` CHANGE `ocr_data` `ocr_data` mediumtext COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_files` CHANGE `ocr_lang` `ocr_lang` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
