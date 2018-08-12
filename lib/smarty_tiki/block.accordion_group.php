<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 *
 * \brief smarty_block_tabs : add tabs to a template
 *
 * params: name (optional but unique per page if set)
 * params: toggle=y on n default
 *
 * usage:
 * \code
 *	{accordion}
 * 		{accordion_group title="{tr}Title 1{/tr}"}tab content{/accordion_group}
 * 		{accordion_group title="{tr}Title 2{/tr}"}tab content{/accordion_group}
 *	{/accordion}
 * \endcode
 */

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

function smarty_block_accordion_group($params, $content, $smarty, $repeat)
{
	if ($repeat) {
		return;
	}

	global $accordion_current_group, $accordion_position;

	if (empty($accordion_current_group)) {
		$accordion_current_group = 'a' . uniqid();
		$accordion_position = 0;
	}

	$smarty->loadPlugin('smarty_modifier_escape');
	$title = smarty_modifier_escape($params['title']);
	$id = $accordion_current_group . '-' . ++$accordion_position;

	$first = ($accordion_position == 1) ? 'show' : '';
	$expanded = ($accordion_position == 1) ? 'true' : 'false';

	return <<<CONTENT
<div class="card card-accordian">
	<div class="card-header">
		<h4 class="card-title">
			<a class="accordion-toggle" data-toggle="collapse" href="#$id" aria-expanded="$expanded" aria-controls="$id">
				$title
			</a>
		</h4>
	</div>
	<div id="$id" class="collapse $first" data-parent="#$accordion_current_group"" aria-labelledby="$id">
		<div class="card-body">
			$content
		</div>
	</div>
</div>
CONTENT;
}
