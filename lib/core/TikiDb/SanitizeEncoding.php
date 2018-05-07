<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\TikiDb;

class SanitizeEncoding
{
	const UTF8SUBSET = 'utf8';  // utf8 in mysql is a subset of utf8
	const UTF8FULL = 'utf8mb4'; // utf8mb4 in mysql is the full range of utf8 chars

	/**
	 * @var null|string shared by all tiki table objects (set on first query / table create for performance)
	 */
	protected static $currentCharset = null;

	/**
	 * @var string Replacement char when checking for invalid chars for the
	 */
	protected static $invalidCharReplacement = ' '; // invalid chars will be replaced with space

	/**
	 * @var string Extra table to add to the query to make sure we can always determine the encoding
	 */
	protected static $referenceTable = 'tiki_pages';

	/**
	 * Detect the character encoding for the sample table
	 * Internally adds a second table that should be on all tiki instances to assure we have results
	 *
	 * @param \TikiDb $db
	 * @param string $sampleTable
	 */
	public static function detectCharset($db, $sampleTable = '')
	{
		if (self::$currentCharset === null) {
			if (empty($sampleTable)) {
				$sampleTable = self::$referenceTable;
			}
			$checkCharsetQuery = 'SELECT DISTINCT(character_set_name) as csn FROM information_schema.`COLUMNS`'
				. ' WHERE table_schema = DATABASE()'
				. ' AND (table_name = ? OR table_name = ?)' // include tiki_pages also
				. ' AND character_set_name IS NOT NULL ORDER BY character_set_name ASC';
			$result = $db->fetchAll($checkCharsetQuery, [$sampleTable, self::$referenceTable]);
			foreach ($result as $row) {
				if (in_array($row['csn'], [self::UTF8SUBSET, self::UTF8FULL])) {
					self::$currentCharset = $row['csn'];
					break;
				}
			}
		}
	}

	/**
	 * Filter chars from the input (string or array) based on the expected charset in the database
	 *
	 * @param string|array $values
	 * @return string|array
	 */
	public static function filter($values)
	{
		// shortcut to avoid extra processing, if not needed
		if (self::$currentCharset !== self::UTF8SUBSET) {
			return $values;
		}

		if (is_array($values)) {
			return array_map('self::filterAllowedCharsByCharset', $values);
		} else {
			return self::filterAllowedCharsByCharset($values);
		}
	}

	/**
	 * Filter chars based on the encoding expected by the db
	 *
	 * @param string $value
	 * @return string
	 */
	protected static function filterAllowedCharsByCharset($value)
	{
		// if anything else that self::UTF8SUBSET, don't change the value.
		if (self::$currentCharset === self::UTF8SUBSET && is_string($value)) {
			// TODO: Something more fancy like replace emoji with asccii smiles when possible
			// Regular expression from https://www.w3.org/International/questions/qa-forms-utf-8.en

			$value = preg_replace(
				'%(?:
				\xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
				| [\xF1-\xF3][\x80-\xBF]{3}        # planes 4-15
				| \xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
				)%xs',
				self::$invalidCharReplacement,
				$value
			);
		}

		return $value;
	}

	/**
	 * Returns the current charset to be used when filtering allowed chars
	 *
	 * @return string
	 */
	public static function getCurrentCharset()
	{
		return self::$currentCharset;
	}

	/**
	 * set the current charset to be used when filtering allowed chars
	 *
	 * @param string $charset
	 */
	public static function setCurrentCharset($charset)
	{
		self::$currentCharset = $charset;
	}
}
