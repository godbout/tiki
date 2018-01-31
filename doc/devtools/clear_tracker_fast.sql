# Note, change the trackerId from 1234567 before running - use at own risk, there is of course no undo!
# This doesn't do any of the related deletions for attributes or linked wiki pages etc.
DELETE FROM `tiki_tracker_item_fields` WHERE `fieldId` IN (SELECT `fieldId` FROM `tiki_tracker_fields` WHERE `trackerId` = 1234567);
DELETE FROM `tiki_tracker_items` WHERE `trackerId` = 1234567;
UPDATE `tiki_trackers` SET `items` = 0 WHERE `trackerId` = 1234567;
