<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Suggestion;

class Rules
{
	private function getRules()
	{
		$rules = [];
		$dirs = array_filter(glob(__DIR__ . '/*'), 'is_dir');
		foreach ($dirs as $dir) {
			$class = basename($dir);
			$files = array_diff(scandir($dir), ['.', '..', 'index.php']);
			foreach ($files as $file) {
				$rules[] = 'Tiki\\Suggestion\\' . basename($dir) . '\\' . substr(basename($file), 0, -4);
			}
		}
		return $rules;
	}

	public function getAllMessages()
	{
		$suggestionMessages = [];
		$rules = $this->getRules();
		foreach ($rules as $rule) {
			$suggestionParser = call_user_func_array([$rule, 'parser'], []);

			if (! empty($suggestionParser)) {
				$suggestionMessage = is_array($suggestionParser) ? $suggestionParser : [$suggestionParser];
				$suggestionMessages = array_merge($suggestionMessages, $suggestionMessage);
			}
		}

		return $suggestionMessages;
	}
}
