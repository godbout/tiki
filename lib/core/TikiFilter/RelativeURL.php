<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
use Zend\Uri\Exception\InvalidUriPartException;

/**
 * Class TikiFilter_RelativeURL
 *
 * Filters for valid relative URL's, and strips any tags.
 */
class TikiFilter_RelativeURL implements Zend\Filter\FilterInterface
{
	/**
	 *
	 * @param string $input		Absolute or relative URL.
	 * @return string			Absolute URL components stripped out, or a blank string if errors were encountered parsing.
	 */


	public function filter($input) : string
	{

		$filter = new Zend\Filter\StripTags();
		$url = $filter->filter($input);

		try {
			$url = Zend\Uri\UriFactory::factory($url);
		} catch (InvalidUriPartException $e) {
			// if the url is invalid, return a blank string.
			return '';
		}
		$url->normalize();

		$query = $url->getQuery();
		$fragment = $url->getFragment();
		$url = preg_replace('/^\/\/+/', '', $url->getPath());

		if ($query) {
			$url .= '?' . $query;
		}
		if ($fragment) {
			$url .= '#' . $fragment;
		}

		return $url;
	}
}
