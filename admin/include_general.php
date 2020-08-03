<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

require_once('tiki-setup.php');
$tikilib->set_preference('display_timezone', $tikilib->get_preference('server_timezone'));

if (! empty($_POST['testMail']) && $access->checkCsrf()) {
    include_once('lib/webmail/tikimaillib.php');
    $mail = new TikiMail();
    $mail->setSubject(tra('Tiki Email Test'));
    $mail->setText(tra('Tiki Test email from:') . ' ' . $_SERVER['SERVER_NAME']);
    if (! $mail->send([$_REQUEST['testMail']])) {
        $msg = tra('Unable to send mail');
        if ($tiki_p_admin == 'y') {
            $mailerrors = print_r($mail->errors, true);
            $msg .= '<br>' . $mailerrors;
        }
        Feedback::warning($msg);
    } else {
        add_feedback('testMail', tra('Test mail sent to') . ' ' . $_REQUEST['testMail'], 3);
    }
}

$engine_type = getCurrentEngine();
$smarty->assign('db_engine_type', $engine_type);
$smarty->assign('now', $tikilib->now);
