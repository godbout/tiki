<?php

// $Id: /cvsroot/tikiwiki/tiki/tiki-export_wiki_pages.php,v 1.11 2007-03-06 19:29:48 sylvieg Exp $

// Copyright (c) 2002-2007, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// Initialization
require_once ('tiki-setup.php');

include_once ("lib/ziplib.php");
include_once ('lib/wiki/exportlib.php');

$access->check_permission(array('tiki_p_admin_wiki','tiki_p_export_wiki'));

if (!isset($_REQUEST["page"])) {
	$exportlib->MakeWikiZip();
	$dump = 'dump';
	if (!empty($tikidomain)) $dump .= "/$tikidomain";
	header ("location: $dump/export.tar");
} else {
	if (isset($_REQUEST["all"]))
		$all = 0;
	else
		$all = 1;

	$data = $exportlib->export_wiki_page($_REQUEST["page"], $all);
	$page = $_REQUEST["page"];
	header ("Content-type: application/unknown");
	header ("Content-Disposition: inline; filename=$page");
	echo $data;
}
