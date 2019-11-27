CREATE TABLE `tiki_categories_roles_available` (
    `categId` int(12) NOT NULL,
    `categRoleId` int(12) NOT NULL,
    PRIMARY KEY (`categId`,`categRoleId`)
) ENGINE=MyISAM;