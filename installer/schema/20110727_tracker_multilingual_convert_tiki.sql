DROP PROCEDURE IF EXISTS 20110727_tracker_multilingual_convert_tiki;

DELIMITER //
CREATE PROCEDURE 20110727_tracker_multilingual_convert_tiki()
BEGIN

-- Ignore "Can't DROP '%s'; check that column/key exists" error and continue
DECLARE CONTINUE HANDLER FOR 1091
	BEGIN
-- in case we have nothing to drop do nothing
	END;
-- otherwise do something
ALTER TABLE `tiki_tracker_item_fields` DROP PRIMARY KEY;
ALTER TABLE `tiki_tracker_item_fields` DROP KEY `lang`;
ALTER TABLE `tiki_tracker_item_fields` DROP COLUMN `lang`;
ALTER TABLE `tiki_tracker_item_field_logs` DROP COLUMN `lang`;

END;
//
DELIMITER ;

CALL 20110727_tracker_multilingual_convert_tiki;
DROP PROCEDURE 20110727_tracker_multilingual_convert_tiki;

DELIMITER //
CREATE PROCEDURE 20110727_tracker_multilingual_convert_tiki()
BEGIN

-- Ignore "Multiple primary key defined" error and continue
DECLARE CONTINUE HANDLER FOR 1068
	BEGIN
-- do nothing
	END;
-- else
ALTER TABLE `tiki_tracker_item_fields` ADD PRIMARY KEY (`itemId`, `fieldId`);

END;
//
DELIMITER ;

CALL 20110727_tracker_multilingual_convert_tiki;
DROP PROCEDURE 20110727_tracker_multilingual_convert_tiki;

