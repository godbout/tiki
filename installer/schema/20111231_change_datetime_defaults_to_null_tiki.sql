/* Added in 2018 for tiki 19 - need to change the defaults for datetimes from 0000-00-00 to null for mysql > 5.6 */
ALTER TABLE `tiki_user_reports` CHANGE `last_report` `last_report` DATETIME  NULL, CHANGE `time_to_send` `time_to_send` DATETIME  NULL;
ALTER TABLE `tiki_user_reports_cache` CHANGE `time` `time` DATETIME  NULL;
ALTER TABLE `tiki_payment_requests` CHANGE `due_date` `due_date` DATETIME  NULL;
