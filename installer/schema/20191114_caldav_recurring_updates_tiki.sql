ALTER TABLE `tiki_calendar_recurrence` ADD `uid` VARCHAR(200);
ALTER TABLE `tiki_calendar_recurrence` ADD `uri` VARCHAR(200);
ALTER TABLE `tiki_calendar_items` ADD `recurrenceStart` INT(14) DEFAULT NULL AFTER `changed`;
UPDATE `tiki_calendar_items` SET `recurrenceStart` = `start` WHERE `changed` = 1;
