<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$section = 'accounting';
require_once('tiki-setup.php');

// Feature available?
if ($prefs['feature_accounting'] != 'y') {
	$smarty->assign('msg', tra("This feature is disabled") . ": feature_accounting");
	$smarty->display("error.tpl");
	die;
}

if (! isset($_REQUEST['bookId'])) {
	$smarty->assign('msg', tra("Missing book id"));
	$smarty->display("error.tpl");
	die;
}
$bookId = $_REQUEST['bookId'];
$smarty->assign('bookId', $bookId);

$accountinglib = TikiLib::lib('accounting');
$book = $accountinglib->getBook($bookId);
$smarty->assign('book', $book);

$globalperms = Perms::get();
$objectperms = Perms::get([ 'type' => 'accounting book', 'object' => $bookId ]);

if (! isset($_REQUEST['action'])) {
	$_REQUEST['action'] = '';
}

if ($_REQUEST['action'] != 'new' and ! isset($_REQUEST['accountId'])) {
	$smarty->assign('msg', tra("Missing account id"));
	$smarty->display("error.tpl");
	die;
}

$smarty->assign('action', $_REQUEST['action']);
if ($_REQUEST['action'] == '' or $_REQUEST['action'] == 'view') {
	if (! ($globalperms->acct_view or $objectperms->acct_view or
		  $globalperms->acct_book or $objectperms->acct_book)) {
		$smarty->assign('msg', tra("You do not have the rights to view this account"));
		$smarty->display("error.tpl");
		die;
	}
} else {
	if (! ($globalperms->acct_manage_accounts or $objectperms->acct_manage_accounts)) {
		$smarty->assign('msg', tra("You do not have the rights to manage accounts"));
		$smarty->display("error.tpl");
		die;
	}
}
$accountId = $_REQUEST['accountId'];
$smarty->assign('accountId', $accountId);

$journal = $accountinglib->getJournal($bookId, $accountId);
$smarty->assign('journal', $journal);

if (! empty($_REQUEST['action'])) {
	$notes = !empty($_POST['accountNotes']) ? $_POST['accountNotes'] : '';
	switch ($_REQUEST['action']) {
		case 'edit':
			$template = "tiki-accounting_account_form.tpl";
			if (isset($_POST['accountName']) && $access->checkCsrf())
			{
				if (! isset($_POST['newAccountId'])) {
					$_POST['newAccountId'] = $accountId;
				}
				$result = $accountinglib->updateAccount(
					$bookId,
					$accountId,
					$_POST['newAccountId'],
					$_POST['accountName'],
					$_POST['accountNotes'],
					$_POST['accountBudget'],
					$_POST['accountLocked'],
					0 /*$_REQUEST['accountTax'] */
				);
				if ($result !== true) {
					Feedback::error(['mes' => $result]);
				} else {
					$smarty->assign('action', 'view');
					$template = "tiki-accounting_account_view.tpl";
					Feedback::success(tr('%0 account in book %1 successfully modified', $_POST['accountName'],
						$bookId));
				}
			}
			$account = $accountinglib->getAccount($bookId, $accountId, true);
			$smarty->assign('account', $account);
			break;
		case 'new':
			$template = "tiki-accounting_account_form.tpl";
			if (isset($_POST['accountName']) && $access->checkCsrf())
			{
				$result = $accountinglib->createAccount(
					$bookId,
					$_POST['newAccountId'],
					$_POST['accountName'],
					$_POST['accountNotes'],
					$_POST['accountBudget'],
					$_POST['accountLocked'],
					0 /*$_REQUEST['accountTax'] */
				);
				if ($result !== true) {
					Feedback::error(['mes' => $result]);
				} else {
					$smarty->assign('action', 'view');
					$template = "tiki-accounting_account_view.tpl";
					Feedback::success(tr('%0 account successfully created for book %1', $_POST['accountName'],
						$bookId));
				}
				$account = [
					'accountBookId' => $bookId,
					'accountId' => $_POST['newAccountId'],
					'accountName' => $_POST['accountName'],
					'accountNotes' => $_POST['accountNotes'],
					'accountBudget' => $_POST['accountBudget'],
					'accountLocked' => $_POST['accountLocked'],
					'accountTax' => $_POST['accountTax'],
					'changeable' => true
				];
			} else {
				$account = ['changeable' => true];
			}
			$smarty->assign('account', $account);
			break;
		case 'lock':
			$account = $accountinglib->getAccount($bookId, $accountId, true);
			if ($access->checkCsrf()) {
				$locked = $accountinglib->changeAccountLock($bookId, $accountId);
				if ($locked) {
					Feedback::success(tr('Account %0 in book %1 successfully locked', $account['accountName'], $bookId)
					);
				} else {
					Feedback::error(tr(
							'Account %0 in book %1 not locked',
							$account['accountName'],
							$bookId)
					);
				}
			}
			$smarty->assign('account', $account);
			$template = "tiki-accounting_account_view.tpl";
			break;
		case 'delete':
			$account = $accountinglib->getAccount($bookId, $accountId, true);
			$smarty->assign('account', $account);
			if ($access->checkCsrfForm(tr(/** @lang text */
				'Delete account %0 from book %1?', $account['accountName'],
				$bookId)))
			{
				$result = $accountinglib->deleteAccount($bookId, $accountId);
			} else {
				$result = false;
			}
			if ($result === true) {
				Feedback::success(tr('%0 account successfully deleted from book %1', $account['accountName'],
					$bookId));
				$template = "tiki-accounting.tpl";
			} else {
				Feedback::error(['mes' => $result]);
				$account = $accountinglib->getAccount($bookId, $accountId, true);
				$smarty->assign('account', $account);
				$template = "tiki-accounting_account_form.tpl";
			}
			break;
	}
} else {
	$account = $accountinglib->getAccount($bookId, $accountId, true);
}
$smarty->assign('account', $account);
if (! $template) {
	$template = "tiki-accounting_account_view.tpl";
}
$smarty->assign('mid', $template);
$smarty->display("tiki.tpl");
