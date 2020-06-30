<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Suggestion\Performance;

use Tiki\Suggestion\SuggestionRulesInterface as SuggestionRules;
use TikiLib;

class NumberTrackers implements SuggestionRules
{
	private static $limitTrackers = 100;

	public function parser()
	{
		global $prefs;
		$message = '';
		if ($prefs['feature_trackers'] == 'y') {
			$trackers = TikiLib::lib('trk')->list_trackers();
			$totalTrackers = ! empty($trackers['cant']) ? $trackers['cant'] : 0;
			if ($totalTrackers >= self::$limitTrackers) {
				$message = tra('You are using a lot of trackers: well done! Did you know about the advanced features?');
			}
		}
		return $message;
	}
}
