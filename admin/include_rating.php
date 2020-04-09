<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
	header('location: index.php');
	exit;
}

$ratingconfiglib = TikiLib::lib('ratingconfig');
$ratinglib = TikiLib::lib('rating');
$access = TikiLib::lib('access');

//don't see an input "test" in the forms at include_rating.tpl
if (isset($_REQUEST['test']) && $access->is_machine_request()) {
	$message = $ratinglib->test_formula($_REQUEST['test'], [ 'type', 'object-id' ]);

		$access->output_serialized(
			[
				'valid' => empty($message),
				'message' => $message,
			]
		);
		exit;
}

//*** begin state-changing actions
if (isset($_POST['create']) && ! empty($jitPost->name->text()) && $access->checkCsrf()) {
	$id = $ratingconfiglib->create_configuration($jitPost->name->text());
	Feedback::success(tr('New rating configuration %0 created', '<em>' . $jitPost->name->text() . '</em>'));
}

if (isset($_POST['edit']) && $access->checkCsrf()) {
	$ratingconfiglib->update_configuration(
		$jitPost->config->digits(),
		$jitPost->name->text(),
		$jitPost->expiry->digits(),
		$jitPost->formula->xss()
	);
	Feedback::success(tr('Rating configuration updated for %0', '<em>' . $jitPost->name->text() . '</em>'));
}
//*** end state-changing actions

$configurations = $ratingconfiglib->get_configurations();

$smarty->assign('configurations', $configurations);
