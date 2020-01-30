<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Class TikiFilter
 *
 * Just offers a get method to obtain an instance of a Zend\Filter\FilterInterface implementation, either stock (Zend) or custom.
 * The objects are "filters" in an extended sense. Data is not necessarily just filtered, but can be otherwise altered.
 * For example, special characters can be escaped.
 *
 * FIXME: The filter() method may perform lossy data alteration quietly, which complicates debugging. See https://github.com/zendframework/zend-filter/issues/63
 */
class TikiFilter
{
	/**
	 * Provides an object implementing Zend\Filter\FilterInterface based on the input
	 *
	 * @param \Zend\Filter\FilterInterface|string $filter		A filter shortcut name, or the filter itself.
	 * @return \Zend\Filter\FilterInterface 					The filter object requested
	 *
	 * @link https://dev.tiki.org/Filtering+Best+Practices
	 * @link https://zendframework.github.io/zend-filter/
	 */
	public static function get($filter)
	{
		if ($filter instanceof \Zend\Filter\FilterInterface) {
			return $filter;
		}

		/**
		 * Filters are listed in order from most strict to least. To select the most optimal filter,
		 * choose the first filter on the list that satisfies your requirements.
		 *
		 * Filters are organized by return type.
		 * Each filter has been tested with a string and can be seen under "Test Return" The string is:
		 * " :/g.,:|4h&#Δ δ_🍘コン onclick<b><script> "
		 */
		switch ($filter) {
			/** Integer return types **/
			case 'int':
				// Test Return 0
				// Transforms a scalar phrase into an integer. eg. '-4 is less than 0' returns -4, while '' returns 0
				return new Zend\Filter\ToInt;

			/** Boolean return types **/
			case 'bool':
				// Test Return (true)
				// False upon:	false, 0, '0', 0.0, '', array(), null, 'false', 'no', 'n' and php casting equivalent to false.
				// True upon:	Everything else returns true. Case insensitive evaluation.
				return new Zend\Filter\Boolean([
					'type'			=> Zend\Filter\Boolean::TYPE_ALL,
					'translations'	=> ['n' => false, 'N' => false]
				]);

			/** Special Filters (may return mixed types or blank sting upon error) **/
			case 'isodate':
				// Test Return (null)
				// may return null
				return new TikiFilter_IsoDate;
			case 'isodatetime':
				// Test Return (null)
				// may return null
				return new TikiFilter_IsoDate('Y-m-d H:i:s');
			case 'iso8601':
				// Test Return (null)
				// may return null
				return new TikiFilter_IsoDate('Y-m-d\TH:i:s');
			case 'attribute_type':
				// Test Return (false)
				// may return false
				return new TikiFilter_AttributeType;
			case 'lang':
				// Test Return ""
				// may return a blank string
				// Allows values for languages (such as 'en') available on the site
				return new TikiFilter_Lang;
			case 'imgsize':
				// may return a blank string
				// Allows digits optionally followed by a space and/or certain size units
				return new TikiFilter_PregFilter(
					'/^(\p{N}+)\p{Zs}?(%|cm|em|ex|in|mm|pc|pt|px|vh|vw|vmin)?$/u',
					'$1$2'
				);
			case 'relativeurl':
				// Test Return ""
				// may return blank string on error
				// If formatted as a absolute url, will return the relative portion, also applies striptags
				return new TikiFilter_RelativeURL;

			/** Digit Filters (no Alpha or HTML) String Return Type **/
			case 'digits':
				// Test Return "4"
				// Removes everything except digits eg. ' 12345 to 67890' returns '1234567890', while '-5' returns '5'
				return new Zend\Filter\Digits;
			case 'digitscolons':
				// Test Return "::4��"
				// Removes everything except digits and colons, e.g., for colon-separated ID numbers.
				// Only characters matched, not patterns - eg 'x75::xx44:' will return '75::44:'
				return new Zend\Filter\PregReplace('/[^\p{N}:]*/', '');
			case 'digitscommas':
				// Test Return ",4��"
				// Removes everything except digits and commas, e.g., for comma-separated ID numbers.
				// Only characters matched, not patterns - eg 'x75,,xx44,' will return '75,,44,'
				return new Zend\Filter\PregReplace('/[^\p{N},]*/', '');
			case 'digitspipes':
				// Test Return "|4��"
				// Removes everything except digits and pipes, e.g., for pipe-separated ID numbers.
				// Only characters matched, not patterns - eg 'x75||xx44|' will return '75||44|'
				return new Zend\Filter\PregReplace('/[^\p{N}\|]*/', '');

			/** Alpha Filters (no Digits or HTML) String Return Type **/
			case 'alpha':
				// Test Return "ghΔδコンonclickbscript"
				// Removes all but alphabetic characters. Unicode support.
				return new TikiFilter_Alpha;
			case 'alphaspace':
				// Test Return " ghΔ δコン onclickbscript "
				// Removes all but alphabetic characters and spaces
				return new TikiFilter_Alpha(true);

				/** Digits & Alpha (no HTML) String Return Type **/
			case 'word':
				// Test Return: "g4h_onclickbscript"
				// Strips everything but digit and alpha and underscore characters. Unicode support.
				return new Zend\Filter\PregReplace('/\W+/', '');
			case 'wordspace':
				// Test Return " g4hΔ δ_コン onclickbscript "
				// Words and spaces only (no trimming)
				return new Zend\Filter\PregReplace('/[^\p{L}\p{M}\p{N}_\p{Zs}]*/u', '');
			case 'alnum':
				// Test Return "g4hΔδコンonclickbscript"
				// Only alphabetic characters and digits. All other characters are suppressed. Unicode support.
				return new TikiFilter_Alnum;
			case 'alnumdash':
				// Test Return "g4h��_���onclickbscript"
				// Removes everything except alphabetic characters, digits, dashes and underscores. Could be used for
				// class names, sortmode values, etc.
				return new Zend\Filter\PregReplace('/[^\p{L}\p{N}\p{Pc}\p{Pd}]*/', '');
			case 'alnumspace':
				// Test Return " g4hΔ δコン onclickbscript "
				// Only alphabetic characters, digits and spaces. All other characters are suppressed. Unicode support
				return new TikiFilter_Alnum(true);
			case 'username':
			case 'groupname':
			case 'pagename':
			case 'topicname':
			case 'themename':
			case 'email':
			case 'url':
			case 'text':
			case 'date':
			case 'time':
			case 'datetime':
			case 'striptags':
				// Test Return " :/g.,:|4h&#Δ δ_🍘コン onclick "
				// Strips XML and HTML tags
				return new Zend\Filter\StripTags;

			/** HTML Permitted, String Return Type **/
			case 'purifier':
				// Test Return " :/g.,:|4hΔ δ_🍘コン onclick<b></b>"
				// Strips non-valid HTML and potentially malicious HTML
				return new TikiFilter_HtmlPurifier('temp/cache');
			case 'xss':
				// Test Return " :/g.,:|4h&#Δ δ_🍘コン on<x>click<b><sc<x>ript> "
				// Leave everything except for potentially malicious HTML
				return new TikiFilter_PreventXss;

			/** Potentially unsafe filters (XSS permitted) **/
			case 'wikicontent':
				// Test Return " :/g.,:|4h&#Δ δ_🍘コン on<x>click<b><sc<x>ript> "
				// Will not filter anything inside a wiki plugin, so "{DIV()}<script>{DIV}" will return identically.
				// When not inside a wiki plugin will apply XSS filtering.
				return new TikiFilter_WikiContent;
			case 'none':
				// Test Return " :/g.,:|4h&#Δ δ_🍘コン onclick<b><script> "
				// Dummy filter to keep value unchanged
				return new TikiFilter_None;
			case 'rawhtml_unsafe':
				// Test Return " :/g.,:|4h&#Δ δ_🍘コン onclick<b><script> "
				// Exotic filter which will remove the '<x>', for values previously "neutered" by the PreventXss filter
				return new TikiFilter_RawUnsafe;

			default:
				trigger_error('Filter not found: ' . $filter, E_USER_WARNING);
				return new TikiFilter_PreventXss;
		}
	}
}
