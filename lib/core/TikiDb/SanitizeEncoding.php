<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\TikiDb;

class SanitizeEncoding
{
	/**
	 * string Replacement char when checking for invalid chars
	 */
	const INVALID_CHAR_REPLACEMENT = ' '; // invalid chars will be replaced with space

	/**
	 * Filter chars from the input (string or array) based on the expected charset in the database
	 *
	 * @param string|array $values
	 * @param array $utf8FieldList
	 * @param string $field
	 * @return string|array
	 */
	public static function filterMysqlUtf8($values, $utf8FieldList = [], $field = null)
	{
		// shortcut to avoid extra processing, if not needed
		if (empty($utf8FieldList)) {
			return $values;
		}

		if (is_array($values)) {
			foreach ($values as $key => $value) {
				if (isset($utf8FieldList[$key])) {
					$values[$key] = self::filterAllowedCharsInMysqlCharsetUtf8($value);
				}
			}
			return $values;
		} else {
			if (isset($utf8FieldList[$field])) {
				$values = self::filterAllowedCharsInMysqlCharsetUtf8($values);
			}
		}

		return $values;
	}

	/**
	 * Filter chars based on the encoding expected by the db
	 *
	 * @param string $value
	 * @return string
	 */
	protected static function filterAllowedCharsInMysqlCharsetUtf8($value)
	{
		// TODO: Something more fancy like replace emoji with asccii smiles when possible

		// Regular expression from https://www.w3.org/International/questions/qa-forms-utf-8.en
		$value = preg_replace(
			'%(?:
			\xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
			| [\xF1-\xF3][\x80-\xBF]{3}        # planes 4-15
			| \xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
			)%xs',
			self::INVALID_CHAR_REPLACEMENT,
			$value
		);

		return $value;
	}
}
