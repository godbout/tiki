<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Tiki\Sections;

if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
	die('This script may only be included.');
}

if ($prefs['feature_categories'] == 'y' && $prefs['categories_used_in_tpl'] == 'y') {
	$categlib = TikiLib::lib('categ');

	$objectCategoryIds = [];
	$objectCategoryIdsNoJail = [];

	$currentObject = Sections::currentObject();
	if ($currentObject) {
		$objectCategoryIds = $categlib->get_object_categories($currentObject['type'], $currentObject['object']);
		$objectCategoryIdsNoJail = $categlib->get_object_categories(
			$currentObject['type'],
			$currentObject['object'],
			-1,
			false
		);
	}

	$smarty->assign_by_ref('objectCategoryIds', $objectCategoryIds);
	// use in smarty {if isset($objectCategoryIds) and in_array(54, $objectCategoryIds)} My stuff ..{/if}
}
