<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/***
 * 
 * @var \TikiAccessLib  $access
 * 
 * @var \AccountingLib  $accountinglib
 * 
 * 
 * @var \Smarty_Tiki    $smarty
 * 
 * Define the current section
 * @var string $section
 */ 

$section = 'accounting';
require_once('tiki-setup.php');

// Feature available?
if ($prefs['feature_accounting'] != 'y') {
	$smarty->assign('msg', tra("This feature is disabled") . ": feature_accounting");
	$smarty->display("error.tpl");
	die;
}
if (! isset($_REQUEST['action'])) {
	$_REQUEST['action'] = '';
}

$globalperms = Perms::get();
$accountinglib = TikiLib::lib('accounting');

if ($_REQUEST['book_start_Year']) {
	$bookStartDate = new DateTime();
	$bookStartDate->setDate(
		$_REQUEST['book_start_Year'],
		$_REQUEST['book_start_Month'],
		$_REQUEST['book_start_Day']
	);
}
if ($_REQUEST['book_end_Year']) {
	$bookEndDate = new DateTime();
	$bookEndDate->setDate(
		$_REQUEST['book_end_Year'],
		$_REQUEST['book_end_Month'],
		$_REQUEST['book_end_Day']
	);
}
if (! empty($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'create':
			if (! $globalperms->acct_create_book) {
				Feedback::error(tra('You do not have permission to create a book'));
			} elseif($access->checkCsrf()) {
				$bookId = $accountinglib->createBook(
					$_POST['bookName'],
					'n',
					date_format($bookStartDate, 'Y-m-d H:i:s'),
					date_format($bookEndDate, 'Y-m-d H:i:s'),
					$_POST['bookCurrency'],
					$_POST['bookCurrencyPos'],
					$_POST['bookDecimals'],
					$_POST['bookDecPoint'],
					$_POST['bookThousand'],
					$_POST['exportSeparator'],
					$_POST['exportEOL'],
					$_POST['exportQuote'],
					$_POST['bookAutoTax']
				);
				if (! is_numeric($bookId)) {
					$errors[] = tra($bookId);
					Feedback::error(implode("\n", $errors));
					$smarty->assign('bookName', $_POST['bookName']);
					$smarty->assign('bookStartDate', $bookStartDate);
					$smarty->assign('bookEndDate', $bookEndDate);
					$smarty->assign('bookCurrency', $_POST['bookCurrency']);
					$smarty->assign('bookCurrencyPos', $_POST['bookCurrencyPos']);
					$smarty->assign('bookDecimals', $_POST['bookDecimals']);
					$smarty->assign('bookDecPoint', $_POST['bookDecPoint']);
					$smarty->assign('bookThousand', $_POST['bookThousand']);
					$smarty->assign('exportSeparator', $_POST['exportSeparator']);
					$smarty->assign('exportEOL', $_POST['exportEOL']);
					$smarty->assign('exportQuote', $_POST['exportQuote']);
					$smarty->assign('bookAutoTax', $_POST['bookAutoTax']);
				} else {
					Feedback::success(tr('Book %0 successfully created', $_POST['bookName']));
				}
			}
			break;
		case 'close':
			if (! $globalperms->acct_create_book) {
				Feedback::error(tra('You do not have permission to close this book'));
			} elseif ($access->checkCsrfForm(tra('Close book (this action cannot be undone)?'))) {
				$res = $accountinglib->closeBook($_POST['bookId']);
				if ($res) {
					Feedback::success(tra('Book successfully closed'));
				} else {
					Feedback::error(tra('The attempt to close the book was unsuccessful'));
				}
			}
			break;
		case 'view':
			break;
		default://list
	}
}
$books = $accountinglib->listBooks();
$filtered = Perms::filter(
	[ 'type' => 'accounting book'],
	'object',
	$books,
	[ 'object' => 'bookName' ],
	'acct_view'
);
$smarty->assign('books', $books);
$smarty->assign('canCreate', $globalperms->acct_create_book);
$smarty->assign('mid', 'tiki-accounting_books.tpl');
$smarty->display("tiki.tpl");
