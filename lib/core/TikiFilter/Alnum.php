<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//namespace Tiki\TikiFilter;

use Laminas\Filter\PregReplace;
use Laminas\Stdlib\StringUtils;
use Laminas\I18n\Filter\AbstractLocale;

class TikiFilter_Alnum extends AbstractLocale
{
	private $pattern;

	/**
	 * Much of the logic for this function is taken directly from the Laminas Alnum filter
	 *
	 * @param string $extraChar	Match an extra preg sequence (like white space, etc)
	 *
	 */
	public function __construct(string $extraChar = '')
	{
		parent::__construct();
		if (! StringUtils::hasPcreUnicodeSupport()) {
			// POSIX named classes are not supported, use alternative a-zA-Z0-9 match
			$this->pattern = '/[^a-zA-Z0-9' . $extraChar . ']/';
		} elseif (in_array(Locale::getPrimaryLanguage($this->getLocale()), ['ja', 'ko', 'zh'], true)) {
			// Use english alphabet
			$this->pattern = '/[^a-zA-Z0-9' . $extraChar . ']/u';
		} else {
			// Use native language alphabet
			$this->pattern = '/[^\p{L}\p{N}' . $extraChar . ']/u';
		}
	}

	/**
	 * @param mixed  $value		The string to be filtered
	 *
	 * @return PregReplace		The filtered value
	 */
	public function filter($value) : string
	{
		return preg_replace($this->pattern, '', $value);
	}
}
