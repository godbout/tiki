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

if (! empty($_REQUEST['submit'])) {
  try {
    $controller = new Services_RemoteController($_REQUEST['url'], 'export');
    $response = $controller->sync_content(['user' => $_REQUEST['user'], 'password' => $_REQUEST['password']]);
    if (! empty($response['error'])) {
      throw new Exception($response['error']);
    }
    $remote_content = $response['data'];

    $export_controller = new Services_Export_Controller();
    $local_content = $export_controller->dumpContent();

    require_once('lib/diff/difflib.php');
    $diff = diff2($local_content, $remote_content, 'sidediff-full');
    if (empty($diff)) {
      $diff = '<tr><td colspan="4">The diff is empty.</td></tr>';
    }
    $smarty->assign('diff', $diff);

  } catch (Exception $e) {
    Feedback::error($e->getMessage(), '');
  }
}

// Display the template
$smarty->assign('mid', 'tiki-admin_sync.tpl');
$smarty->display("tiki.tpl");
