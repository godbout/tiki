<?php

namespace Tiki\MailIn\Provider;

use Tiki\MailIn\Action;

class TrackerProvider implements ProviderInterface
{

	function isEnabled()
	{
		global $prefs;
		return $prefs['feature_trackers'] == 'y';
	}

	function getType()
	{
		return 'tracker';
	}

	function getLabel()
	{
		return tr('Store mail in Tracker');
	}

	function getActionFactory(array $acc)
	{
		return new Action\DirectFactory('Tiki\MailIn\Action\Tracker', [
			'attachments' => $acc['attachments'],
			'trackerId' => $acc['trackerId'],
		]);
	}
}