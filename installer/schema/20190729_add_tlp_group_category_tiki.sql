ALTER TABLE tiki_categories ADD column `tplGroupContainerId` int(12) default NULL;
ALTER TABLE tiki_categories ADD column `tplGroupPattern` varchar(200) default NULL;