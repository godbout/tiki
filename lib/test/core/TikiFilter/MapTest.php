<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @group unit
 *
 */

class TikiFilter_MapTest extends TikiTestCase
{
	function testDirect()
	{
		$this->assertTrue(TikiFilter::get('int') instanceof Zend\Filter\ToInt);
		$this->assertTrue(TikiFilter::get('bool') instanceof Zend\Filter\Boolean);
		$this->assertTrue(TikiFilter::get('isodate') instanceof TikiFilter_IsoDate);
		$this->assertTrue(TikiFilter::get('isodatetime') instanceof TikiFilter_IsoDate);
		$this->assertTrue(TikiFilter::get('iso8601') instanceof TikiFilter_IsoDate);
		$this->assertTrue(TikiFilter::get('attribute_type') instanceof TikiFilter_AttributeType);
		$this->assertTrue(TikiFilter::get('lang') instanceof TikiFilter_Lang);
		$this->assertTrue(TikiFilter::get('relativeurl') instanceof TikiFilter_RelativeURL);
		$this->assertTrue(TikiFilter::get('digits') instanceof Zend\Filter\Digits);
		$this->assertTrue(TikiFilter::get('digitscolons') instanceof Zend\Filter\PregReplace);
		$this->assertTrue(TikiFilter::get('digitscommas') instanceof Zend\Filter\PregReplace);
		$this->assertTrue(TikiFilter::get('digitspipes') instanceof Zend\Filter\PregReplace);
		$this->assertTrue(TikiFilter::get('alpha') instanceof TikiFilter_Alpha);
		$this->assertTrue(TikiFilter::get('alphaspace') instanceof TikiFilter_Alpha);
		$this->assertTrue(TikiFilter::get('word') instanceof Zend\Filter\PregReplace);
		$this->assertTrue(TikiFilter::get('wordspace') instanceof Zend\Filter\PregReplace);
		$this->assertTrue(TikiFilter::get('alnum') instanceof TikiFilter_Alnum);
		$this->assertTrue(TikiFilter::get('alnumdash') instanceof Zend\Filter\PregReplace);
		$this->assertTrue(TikiFilter::get('alnumspace') instanceof TikiFilter_Alnum);
		$this->assertTrue(TikiFilter::get('username') instanceof Zend\Filter\StripTags);
		$this->assertTrue(TikiFilter::get('groupname') instanceof Zend\Filter\StripTags);
		$this->assertTrue(TikiFilter::get('pagename') instanceof Zend\Filter\StripTags);
		$this->assertTrue(TikiFilter::get('topicname') instanceof Zend\Filter\StripTags);
		$this->assertTrue(TikiFilter::get('themename') instanceof Zend\Filter\StripTags);
		$this->assertTrue(TikiFilter::get('email') instanceof Zend\Filter\StripTags);
		$this->assertTrue(TikiFilter::get('url') instanceof Zend\Filter\StripTags);
		$this->assertTrue(TikiFilter::get('text') instanceof Zend\Filter\StripTags);
		$this->assertTrue(TikiFilter::get('date') instanceof Zend\Filter\StripTags);
		$this->assertTrue(TikiFilter::get('time') instanceof Zend\Filter\StripTags);
		$this->assertTrue(TikiFilter::get('datetime') instanceof Zend\Filter\StripTags);
		$this->assertTrue(TikiFilter::get('striptags') instanceof Zend\Filter\StripTags);
		$this->assertTrue(TikiFilter::get('purifier') instanceof TikiFilter_HtmlPurifier);
		$this->assertTrue(TikiFilter::get('html') instanceof TikiFilter_HtmlPurifier);
		$this->assertTrue(TikiFilter::get('xss') instanceof TikiFilter_PreventXss);
		$this->assertTrue(TikiFilter::get('wikicontent') instanceof TikiFilter_WikiContent);
		$this->assertTrue(TikiFilter::get('rawhtml_unsafe') instanceof TikiFilter_RawUnsafe);
		$this->assertTrue(TikiFilter::get('none') instanceof TikiFilter_None);

	}

	function testKnown()
	{
		$this->assertTrue(TikiFilter::get(new Zend\I18n\Filter\Alnum) instanceof Zend\I18n\Filter\Alnum);
	}

	/**
	 * Triggered errors become exceptions...
	 * @expectedException PHPUnit\Framework\Error\Error
	 */
	function testUnknown()
	{
		$this->assertTrue(TikiFilter::get('does_not_exist') instanceof TikiFilter_PreventXss);
	}

	function testComposed()
	{
		$filter = new JitFilter(['foo' => 'test123']);
		$filter->replaceFilter('foo', 'digits');

		$this->assertEquals('123', $filter['foo']);
	}

	function testDefault()
	{
		$filter = new JitFilter(['foo' => 'test123']);
		$filter->setDefaultFilter('digits');

		$this->assertEquals('123', $filter['foo']);
	}

	function testRaw()
	{
		$filter = new TikiFilter_RawUnsafe;
		$this->assertEquals('alert', $filter->filter('alert'));
	}
}
