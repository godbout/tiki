DELETE FROM `tiki_menu_options` where 
`menuId` = 42 and `type` = 'o' and `name` = 'New Invoice' and `url` = 'tiki-edit_invoice.php' and `position` = 791
and  `section` = 'feature_invoice' and `perm` = 'tiki_p_admin' and `groupname` = '' and `userlevel` = 0;

DELETE FROM `tiki_menu_options` where 
`menuId` = 42 and `type` = 'r' and `name` = 'Invoice' and `url` = 'tiki-list_invoices.php' and `position` = 790
and  `section` = 'feature_invoice' and `perm` = 'tiki_p_admin' and `groupname` = '' and `userlevel` = 0;