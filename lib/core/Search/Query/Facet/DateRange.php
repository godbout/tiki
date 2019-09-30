<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Query_Facet_DateRange extends Search_Query_Facet_Abstract implements Search_Query_Facet_Interface
{
	private $ranges = [];
	private $format;
	private $keyed = false;

	static function fromField($field)
	{
		return new self($field);
	}

	/**
	 * @return string
	 */
	function getType()
	{
		return 'date_range';
	}

	/**
	 * @return array
	 */
	function getRanges()
	{
		return $this->ranges;
	}

	/**
	 * Use ES date math format as per https://www.elastic.co/guide/en/elasticsearch/reference/5.6/common-options.html#date-math
	 * e.g.
	 *     now+1d/d (tomorrow, starting at midnight)
	 *     now-10M/M (now minus 10 months, rounded down to the start of the month)
	 *     or just a plain date :2018/11/28
	 *
	 * @param string $to
	 * @param string $from
	 * @param string $key
	 *
	 * @return Search_Query_Facet_Interface
	 */
	function addRange($to, $from, $key = '')
	{
		$range = [
			'to' => $to,
			'from' => $from,
		];

		if ($key) {
			$range['key'] = $key;
			$this->keyed = true;
		}

		$this->ranges[] = array_filter($range);

		return $this;
	}

	public function clearRanges() {
		$this->ranges = [];
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
