<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Query_Facet_DateHistogram extends Search_Query_Facet_Abstract implements Search_Query_Facet_Interface
{
	private $interval;
	private $format;


	static function fromField($field)
	{
		return new self($field);
	}

	/**
	 * @return string
	 */
	function getType()
	{
		return 'date_histogram';
	}

	/**
	 * @return string
	 */
	function getInterval()
	{
		return $this->interval;
	}

	/**
	 * @param $interval
	 *
	 * @return Search_Query_Facet_Interface
	 */
	function setInterval($interval)
	{
		$this->interval = $interval;
		return $this;
	}
	/**
	 * @return mixed
	 */
	public function getFormat()
	{
		return $this->format;
	}

	/**
	 * @param mixed $format
	 * date format as per https://www.joda.org/joda-time/apidocs/org/joda/time/format/DateTimeFormat.html
	 *
	 * @return Search_Query_Facet_Interface
	 */
	public function setFormat($format)
	{
		$this->format = $format;
		return $this;
	}
}
