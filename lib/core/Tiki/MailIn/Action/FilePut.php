<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\MailIn\Action;

use Tiki\MailIn\Account;
use Tiki\MailIn\Source\Message;
use TikiLib;

class FilePut implements ActionInterface
{
	private $galleryId;

	function __construct(array $params)
	{
		$this->galleryId = isset($params['galleryId']) ? (int)$params['galleryId'] : 0;
	}

	function getName()
	{
		return tr('Save File');
	}

	function isEnabled()
	{
		global $prefs;

		return $prefs['feature_file_galleries'] == 'y';
	}

	function isAllowed(Account $account, Message $message)
	{
		$user = $message->getAssociatedUser();
		$perms = TikiLib::lib('tiki')->get_user_permission_accessor($user, 'file gallery', $this->galleryId);

		if (! $perms->upload_files) {
			return false;
		}

		return true;
	}

	function execute(Account $account, Message $message)
	{
		global $user;

		$preserve_user = $user;
		$user = $message->getAssociatedUser();

		$logslib = TikiLib::lib('logs');
		$filegallib = TikiLib::lib('filegal');

		$gal_info = $filegallib->get_file_gallery_info($this->galleryId);

		if (! $gal_info) {
			$logslib->add_log('mailin', tr("Gallery not found: %0", $this->galleryId), $message->getAssociatedUser());
			$user = $preserve_user;
			return false;
		}

		$content = $message->getContent();
		$result = $filegallib->upload_single_file($gal_info, $message->getSubject(), strlen($content), 'message/rfc822', $content, $user, null, null, $message->getMessageId());

		$user = $preserve_user;
		return $result;
	}
}
