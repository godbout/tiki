<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once('tiki-setup.php');
$access->check_permission('tiki_p_admin');

TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/tiki-admin_oauthserver.js');
$oauthserverlib = TikiLib::lib('oauthserver');

$smarty->assign('client_list', array_merge(
	$oauthserverlib->getClientRepository()->list()
));
$smarty->assign('client_empty', new ClientEntity());

$smarty->assign('client_modify_url', TikiLib::lib('service')->getUrl([
	'action' => 'client_modify',
	'controller' => 'oauthserver',
]));

$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('mid', 'tiki-admin_oauthserver.tpl');
$smarty->loadPlugin('smarty_modifier_ternary');
$smarty->display("tiki.tpl");
