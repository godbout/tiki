<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Class TikiDb_Pdo_Result
 *
 * Returns result set along with affected rows
 */
class TikiDb_Pdo_Result
{
	/** @var array */
	public $result;
	/** @var int */
	public $numrows;

	/**
	 * TikiDb_Pdo_Result constructor.
	 * @param $result
	 * @param $rowCount
	 */
	function __construct($result, $rowCount)
	{
		$this->result = &$result;
		$this->numrows = is_numeric($rowCount) ? $rowCount : count($this->result);
	}

	/** @return array */
	function fetchRow()
	{
		return is_array($this->result) ? array_shift($this->result) : 0;
	}

	/** @return int */
	function numRows()
	{
		return (int) $this->numrows;
	}
}

class TikiDb_Pdo extends TikiDb
{
	/** @var $db PDO */
	private $db;
	/** @var $rowCount int*/
	private $rowCount;

	/**
	 * TikiDb_Pdo constructor.
	 * @param PDO $db
	 */
	function __construct($db) // {{{
	{
		if (! $db) {
			die("Invalid db object passed to TikiDB constructor");
		}

		$this->db = $db;
		$this->setServerType($db->getAttribute(PDO::ATTR_DRIVER_NAME));
	} // }}}

	function qstr($str) // {{{
	{
		if (is_null($str)) {
			return 'NULL';
		}
		return $this->db->quote($str);
	} // }}}

	private function _query($query, $values = null, $numrows = -1, $offset = -1) // {{{
	{
		global $num_queries;
		$num_queries++;

		$numrows = (int)$numrows;
		$offset = (int)$offset;
		if ($query == null) {
			$query = $this->getQuery();
		}

		$this->convertQueryTablePrefixes($query);

		if ($offset != -1 && $numrows != -1) {
			$query .= " LIMIT $numrows OFFSET $offset";
		} elseif ($numrows != -1) {
			$query .= " LIMIT $numrows";
		}

		$starttime = $this->startTimer();

		$result = false;
		if ($values) {
			if (@ $pq = $this->db->prepare($query)) {
				if (! is_array($values)) {
					$values = [$values];
				}
				$result = $pq->execute($values);
				$this->rowCount = $pq->rowCount();
			}
		} else {
			$result = @ $this->db->query($query);
			$this->rowCount = is_object($result) && get_class($result) === 'PDOStatement' ? $result->rowCount() : 0;
		}

		$this->stopTimer($starttime);

		if ($result === false) {
			if (! $values || ! $pq) { // Query preparation or query failed
				$tmp = $this->db->errorInfo();
			} else { // Prepared query failed to execute
				$tmp = $pq->errorInfo();
				$pq->closeCursor();
			}
			$this->setErrorMessage($tmp[2]);
			return false;
		} else {
			$this->setErrorMessage("");
			if (($values && ! $pq->columnCount()) || (! $values && ! $result->columnCount())) {
				return []; // Return empty result set for statements of manipulation
			} elseif (! $values) {
				return $result->fetchAll(PDO::FETCH_ASSOC);
			} else {
				return $pq->fetchAll(PDO::FETCH_ASSOC);
			}
		}
	} // }}}

	function fetchAll($query = null, $values = null, $numrows = -1, $offset = -1, $reporterrors = parent::ERR_DIRECT) // {{{
	{
		$result = $this->_query($query, $values, $numrows, $offset);
		if (! is_array($result)) {
			$this->handleQueryError($query, $values, $result, $reporterrors);
		}

		return $result;
	} // }}}

	function query($query = null, $values = null, $numrows = -1, $offset = -1, $reporterrors = self::ERR_DIRECT) // {{{
	{
		$result = $this->_query($query, $values, $numrows, $offset);
		if ($result === false) {
			$this->handleQueryError($query, $values, $result, $reporterrors);
		}
		return new TikiDb_Pdo_Result($result, $this->rowCount);
	} // }}}

	function lastInsertId() // {{{
	{
		return $this->db->lastInsertId();
	} // }}}

}
