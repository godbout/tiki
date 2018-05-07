<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Tests\TikiDb;

use Tiki\TikiDb\SanitizeEncoding;

class SanitizeEncodingTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @param $encoding
	 * @param $input
	 * @param $expected
	 *
	 * @dataProvider filterDataProvider
	 */
	public function testFilter($encoding, $input, $expected)
	{
		SanitizeEncoding::setCurrentCharset($encoding);
		$result = SanitizeEncoding::filter($input);
		$this->assertEquals($expected, $result);
	}

	public function filterDataProvider()
	{
		return [
			[SanitizeEncoding::UTF8SUBSET, '', ''],
			[SanitizeEncoding::UTF8FULL, '', ''],
			[SanitizeEncoding::UTF8SUBSET, 'Latin: Vitrum edere possum; mihi non nocet.', 'Latin: Vitrum edere possum; mihi non nocet.'],
			[SanitizeEncoding::UTF8FULL, 'Latin: Vitrum edere possum; mihi non nocet.', 'Latin: Vitrum edere possum; mihi non nocet.'],
			[SanitizeEncoding::UTF8SUBSET, 'Sanskrit: काचं शक्नोम्यत्तुम् । नोपहिनस्ति माम् ॥', 'Sanskrit: काचं शक्नोम्यत्तुम् । नोपहिनस्ति माम् ॥'],
			[SanitizeEncoding::UTF8FULL, 'Sanskrit: काचं शक्नोम्यत्तुम् । नोपहिनस्ति माम् ॥', 'Sanskrit: काचं शक्नोम्यत्तुम् । नोपहिनस्ति माम् ॥'],
			[SanitizeEncoding::UTF8SUBSET, 'Sanskrit: kācaṃ śaknomyattum; nopahinasti mām.', 'Sanskrit: kācaṃ śaknomyattum; nopahinasti mām.'],
			[SanitizeEncoding::UTF8FULL, 'Sanskrit: kācaṃ śaknomyattum; nopahinasti mām.', 'Sanskrit: kācaṃ śaknomyattum; nopahinasti mām.'],
			[SanitizeEncoding::UTF8SUBSET, 'Sample Emoji: 😀 😁 🐶 🐱 🏳️ 🏴', 'Sample Emoji:          ️  '],
			[SanitizeEncoding::UTF8FULL, 'Sample Emoji: 😀 😁 🐶 🐱 🏳️ 🏴', 'Sample Emoji: 😀 😁 🐶 🐱 🏳️ 🏴'],
			[SanitizeEncoding::UTF8SUBSET, 0x01F600, 0x01F600], // Emoji as integer
			[SanitizeEncoding::UTF8FULL, 0x01F600, 0x01F600], // Emoji as integer
			[
				SanitizeEncoding::UTF8SUBSET,
				['Sanskrit: kācaṃ śaknomyattum; nopahinasti mām.', 'Sample Emoji: 😀 😁 🐶 🐱 🏳️ 🏴'],
				['Sanskrit: kācaṃ śaknomyattum; nopahinasti mām.', 'Sample Emoji:          ️  '],
			],
			[
				SanitizeEncoding::UTF8FULL,
				['Sanskrit: kācaṃ śaknomyattum; nopahinasti mām.', 'Sample Emoji: 😀 😁 🐶 🐱 🏳️ 🏴'],
				['Sanskrit: kācaṃ śaknomyattum; nopahinasti mām.', 'Sample Emoji: 😀 😁 🐶 🐱 🏳️ 🏴'],
			],
		];
	}
}
